<?php

namespace App\Http\Controllers;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PriceDropController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['percent', 'amount', 'recent']) ? $request->query('sort') : 'percent';
        $minPercent = max(1, (int) $request->query('min', 5));

        $products = Product::where('status', 'published')
            ->whereHas('activeOffers', fn ($q) => $q->whereNotNull('current_price')->where('current_price', '>', 0))
            ->with(['brand', 'activeOffers.merchant'])
            ->limit(2000)
            ->get();

        $drops = $this->computeDrops($products, $minPercent)
            ->filter(fn ($d) => $d['is_reverted'] === false)
            ->when($sort === 'amount', fn ($c) => $c->sortByDesc('live_drop_amount'))
            ->when($sort === 'recent', fn ($c) => $c->sortByDesc('days_ago'))
            ->when(in_array($sort, ['percent'], true), fn ($c) => $c->sortByDesc('live_drop_percent'))
            ->when(! in_array($sort, ['percent', 'amount', 'recent'], true), fn ($c) => $c->sortByDesc('live_drop_percent'))
            ->values();

        $drops = new \Illuminate\Pagination\LengthAwarePaginator(
            $drops->forPage((int) $request->get('page', 1), 24),
            $drops->count(),
            24,
            (int) $request->get('page', 1),
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => $request->query()]
        );

        return view('price-drops.index', [
            'drops' => $drops,
            'sort' => $sort,
            'minPercent' => $minPercent,
            'seo' => ['title' => 'Recent Price Drops — Tulona', 'description' => 'Products whose prices recently decreased at trusted stores, verified from real tracked price history.'],
        ]);
    }

    /**
     * Derive a genuine price drop straight from real price history:
     *   - current = the offer's CURRENT live price (same as product page)
     *   - reference = the offer's average price over the trailing 30 days
     *   - drop %  = how far the live price is below that reference
     * Falls back to original_price discount when history is sparse
     * (seed data has 1 row where avg == current).
     * If the price has climbed back to/above the reference, it is not a drop.
     */
    protected function computeDrops(iterable $products, int $minPercent): \Illuminate\Support\Collection
    {
        $products = collect($products);

        // Build offer -> parent product map from the already-loaded collection
        // so we never trigger a lazy load inside the loop.
        $offers = collect();
        $productByOffer = [];
        foreach ($products as $p) {
            foreach ($p->activeOffers as $offer) {
                if (is_numeric($offer->current_price) && (float) $offer->current_price > 0) {
                    $offers->push($offer);
                    $productByOffer[$offer->id] = $p;
                }
            }
        }

        $offerIds = $offers->pluck('id');

        // One query grabs the average price per offer over the last 30 days.
        $averages = PriceHistory::whereIn('offer_id', $offerIds)
            ->where('recorded_at', '>=', Carbon::now()->subDays(30))
            ->select('offer_id', \Illuminate\Support\Facades\DB::raw('AVG(price) as avg_price'))
            ->groupBy('offer_id')
            ->pluck('avg_price', 'offer_id');

        // Most recent record per offer → its "days ago" for recent sorting.
        $mostRecent = PriceHistory::whereIn('offer_id', $offerIds)
            ->select('offer_id', \Illuminate\Support\Facades\DB::raw('MAX(recorded_at) as last_at'))
            ->groupBy('offer_id')
            ->pluck('last_at', 'offer_id');

        $drops = collect();
        foreach ($offers as $offer) {
            $product = $productByOffer[$offer->id] ?? null;
            if (! $product) {
                continue;
            }

            $current = (float) $offer->current_price;
            if ($current <= 0) {
                continue;
            }

            $reference = null;
            $dropPercent = null;

            $avg = isset($averages[$offer->id]) ? (float) $averages[$offer->id] : null;
            if ($avg !== null && $avg > 0 && $current < $avg) {
                $candidate = round(($avg - $current) / $avg * 100, 1);
                if ($candidate >= $minPercent) {
                    $reference = $avg;
                    $dropPercent = $candidate;
                }
            }

            if ($dropPercent === null && $offer->original_price && (float) $offer->original_price > $current) {
                $orig = (float) $offer->original_price;
                $candidate = round(($orig - $current) / $orig * 100, 1);
                if ($candidate >= $minPercent) {
                    $reference = $orig;
                    $dropPercent = $candidate;
                }
            }

            if ($reference === null || $dropPercent === null) {
                continue;
            }

            $lastAt = isset($mostRecent[$offer->id]) ? Carbon::parse($mostRecent[$offer->id]) : ($offer->updated_at ?? now());

            $drops->push([
                'product' => $product,
                'merchant' => $offer->merchant,
                'currency' => $offer->currency,
                'live_previous' => $reference,
                'live_price' => $current,
                'live_drop_percent' => $dropPercent,
                'live_drop_amount' => round($reference - $current, 2),
                'is_reverted' => false,
                'occurred_at' => $lastAt,
                'days_ago' => $lastAt ? $lastAt->diffInDays(now()) : 0,
                'offer_id' => $offer->id,
                'product_id' => $product->id,
            ]);
        }

        // Deduplicate to one row per product (keep highest %)
        $drops = $drops->sortByDesc('live_drop_percent')->unique('product_id')->values();

        return $drops;
    }
}
