<?php

namespace App\Services;

use App\Models\Click;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * On-the-fly merchandising scoring engine.
 * Computes trending / popular / best-deals / most-clicked / new-arrivals
 * from real click + view + price data. Results are cached briefly to
 * protect the shared-host database.
 *
 * Cache keys are versioned (CACHE_VERSION) so a re-deploy never reads stale
 * serialized objects from a previous class layout. Every cached value is
 * also type-guarded so a corrupt cached entry is rebuilt, never returned.
 */
class MerchandisingService
{
    protected const CACHE_VERSION = 'v5';

    protected array $trendingWeights = [
        'recent_clicks' => 0.40,
        'recent_views'  => 0.25,
        'ctr'           => 0.15,
        'growth'        => 0.10,
        'saves'         => 0.10,
    ];

    protected array $dealWeights = [
        'discount'      => 0.35,
        'price_drop'    => 0.25,
        'historical'    => 0.15,
        'popularity'    => 0.10,
        'ctr'           => 0.10,
        'freshness'     => 0.05,
    ];

    protected int $minSampleSize = 3;
    protected int $newArrivalDays = 30;

    // ── Public API ───────────────────────────────────────────────────────

    public function trending(int $limit = 8): Collection
    {
        return $this->remember($this->cacheKey('trending.'.$limit), 600, function () use ($limit) {
            try {
                $productIds = $this->publishedProductIds();
                if ($productIds->isEmpty()) {
                    return collect();
                }

                $scores = $this->computeTrendingScores($productIds);

                return Product::query()
                    ->whereIn('id', $productIds)
                    ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
                    ->withCount('activeOffers')
                    ->get()
                    ->each(function (Product $p) use ($scores) {
                        $p->trending_score = $scores[$p->id] ?? 0;
                    })
                    ->sortByDesc('trending_score')
                    ->take($limit)
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('MerchandisingService::trending failed: '.$e->getMessage());
                return collect();
            }
        }, fn ($v) => $v instanceof Collection);
    }

    public function bestDeals(int $limit = 8, float $minDiscountPercent = 15.0): Collection
    {
        return $this->remember($this->cacheKey('deals.'.$limit.'.'.(int)$minDiscountPercent), 600, function () use ($limit, $minDiscountPercent) {
            try {
                $productIds = $this->publishedProductIds();
                if ($productIds->isEmpty()) {
                    return collect();
                }

                $scores = $this->computeDealScores($productIds, $minDiscountPercent);

                return Product::query()
                    ->whereIn('id', $productIds)
                    ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
                    ->withCount('activeOffers')
                    ->get()
                    ->each(function (Product $p) use ($scores) {
                        $p->deal_score = $scores[$p->id] ?? 0;
                    })
                    ->filter(fn (Product $p) => ($p->deal_score ?? 0) > 0)
                    ->sortByDesc('deal_score')
                    ->take($limit)
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('MerchandisingService::bestDeals failed: '.$e->getMessage());
                return collect();
            }
        }, fn ($v) => $v instanceof Collection);
    }

    public function mostClicked(int $limit = 8, int $days = 30): Collection
    {
        return $this->remember($this->cacheKey("most-clicked.{$limit}.{$days}"), 600, function () use ($limit, $days) {
            try {
                $since = Carbon::now()->subDays($days);

                $clickCounts = Click::query()
                    ->where('clicked_at', '>=', $since)
                    ->select('product_id', DB::raw('COUNT(*) as click_total'))
                    ->groupBy('product_id')
                    ->orderByDesc('click_total')
                    ->limit($limit)
                    ->get()
                    ->keyBy('product_id');

                if ($clickCounts->isEmpty()) {
                    return collect();
                }

                return Product::query()
                    ->whereIn('id', $clickCounts->keys())
                    ->where('status', 'published')
                    ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
                    ->withCount('activeOffers')
                    ->get()
                    ->each(function (Product $p) use ($clickCounts) {
                        $p->period_clicks = $clickCounts[$p->id]->click_total;
                    })
                    ->sortByDesc('period_clicks')
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('MerchandisingService::mostClicked failed: '.$e->getMessage());
                return collect();
            }
        }, fn ($v) => $v instanceof Collection);
    }

    public function popular(int $limit = 8): Collection
    {
        return $this->remember($this->cacheKey('popular.'.$limit), 600, function () use ($limit) {
            try {
                $productIds = $this->publishedProductIds();
                if ($productIds->isEmpty()) {
                    return collect();
                }

                $scores = $this->computePopularScores($productIds);

                return Product::query()
                    ->whereIn('id', $productIds)
                    ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
                    ->withCount('activeOffers')
                    ->get()
                    ->each(function (Product $p) use ($scores) {
                        $p->popular_score = $scores[$p->id] ?? 0;
                    })
                    ->sortByDesc('popular_score')
                    ->take($limit)
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('MerchandisingService::popular failed: '.$e->getMessage());
                return collect();
            }
        }, fn ($v) => $v instanceof Collection);
    }

    public function newArrivals(int $limit = 8, ?int $days = null): Collection
    {
        $days = $days ?? $this->newArrivalDays;

        return $this->remember($this->cacheKey("new-arrivals.{$limit}.{$days}"), 600, function () use ($limit, $days) {
            try {
                return Product::published()
                    ->where('created_at', '>=', Carbon::now()->subDays($days))
                    ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
                    ->withCount('activeOffers')
                    ->latest()
                    ->limit($limit)
                    ->get();
            } catch (\Throwable $e) {
                Log::warning('MerchandisingService::newArrivals failed: '.$e->getMessage());
                return collect();
            }
        }, fn ($v) => $v instanceof Collection);
    }

    public function priceDrops(int $limit = 8, int $minPercent = 3): Collection
    {
        return $this->remember($this->cacheKey('price-drops.'.$limit.'.'.$minPercent), 600, function () use ($limit, $minPercent) {
            try {
                return $this->historicalDrops($minPercent)
                    ->filter(fn ($d) => $d['is_reverted'] === false)
                    ->sortByDesc('live_drop_percent')
                    ->take($limit)
                    ->values()
                    ->map(fn ($d) => (object) array_merge($d, [
                        'price_label' => $this->priceDropLabel((object) ['drop_percent' => $d['live_drop_percent']]),
                    ]));
            } catch (\Throwable $e) {
                Log::warning('MerchandisingService::priceDrops failed: '.$e->getMessage());
                return collect();
            }
        }, fn ($v) => $v instanceof Collection);
    }

    /**
     * Shared computation of genuine drops from real price history.
     * Falls back to original_price when price_history is too sparse
     * (e.g. seed data has only 1 history row = avg == current → no drop).
     * Returns a collection of associative drop entries keyed for the view.
     */
    protected function historicalDrops(int $minPercent = 3): Collection
    {
        $products = Product::where('status', 'published')
            ->whereHas('activeOffers', fn ($q) => $q->whereNotNull('current_price')->where('current_price', '>', 0))
            ->with(['brand', 'activeOffers.merchant'])
            ->limit(2000)
            ->get();

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
        if ($offerIds->isEmpty()) {
            return collect();
        }

        $averages = \App\Models\PriceHistory::whereIn('offer_id', $offerIds)
            ->where('recorded_at', '>=', Carbon::now()->subDays(30))
            ->select('offer_id', DB::raw('AVG(price) as avg_price'))
            ->groupBy('offer_id')
            ->pluck('avg_price', 'offer_id');

        $mostRecent = \App\Models\PriceHistory::whereIn('offer_id', $offerIds)
            ->select('offer_id', DB::raw('MAX(recorded_at) as last_at'))
            ->groupBy('offer_id')
            ->pluck('last_at', 'offer_id');

        $byOffer = $offers->keyBy('id');

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

            // 1) Prefer genuine historical average if it shows a drop
            $avg = isset($averages[$offer->id]) ? (float) $averages[$offer->id] : null;
            if ($avg !== null && $avg > 0 && $current < $avg) {
                $candidate = round(($avg - $current) / $avg * 100, 1);
                if ($candidate >= $minPercent) {
                    $reference = $avg;
                    $dropPercent = $candidate;
                }
            }

            // 2) Fallback to original_price discount when history is sparse or avg == current
            //    (seed data: 1 history row where avg == current → would otherwise be empty)
            if ($dropPercent === null && $offer->original_price && (float) $offer->original_price > $current) {
                $orig = (float) $offer->original_price;
                $candidate = round(($orig - $current) / $orig * 100, 1);
                if ($candidate >= $minPercent) {
                    // Use the larger of avg vs orig drop if both exist
                    if ($dropPercent === null || $candidate > $dropPercent) {
                        $reference = $orig;
                        $dropPercent = $candidate;
                    }
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
                // keep offer_id for dedup sorting
                'offer_id' => $offer->id,
                'product_id' => $product->id,
            ]);
        }

        // Deduplicate: keep highest % drop per product (one row per product)
        $drops = $drops->sortByDesc('live_drop_percent')
            ->unique('product_id')
            ->values();

        return $drops;
    }

    public function priceLabelsFor(Product $product): array
    {
        $labels = [];
        $latestDrop = $product->latestDrop;

        if ($latestDrop) {
            if ($latestDrop->drop_percent >= 20) {
                $labels[] = ['text' => 'Big Price Drop', 'type' => 'big-drop'];
            } elseif ($latestDrop->drop_percent >= 10) {
                $labels[] = ['text' => 'Price Drop', 'type' => 'drop'];
            }
        }

        $bestOffer = $product->bestOffer();
        if ($bestOffer && $bestOffer->current_price !== null) {
            $lowest30 = $this->lowestPriceInPeriod($bestOffer, 30);
            if ($lowest30 !== null && (float) $bestOffer->current_price <= $lowest30) {
                $labels[] = ['text' => 'Lowest Price in 30 Days', 'type' => 'lowest'];
            }

            $lowest90 = $this->lowestPriceInPeriod($bestOffer, 90);
            if ($lowest90 !== null && (float) $bestOffer->current_price <= $lowest90) {
                $labels[] = ['text' => 'Lowest Price in 90 Days', 'type' => 'lowest-90'];
            }
        }

        return $labels;
    }

    public function ctrFor(Product $product, int $days = 30): ?float
    {
        try {
            $since = Carbon::now()->subDays($days);

            $clicks = Click::where('product_id', $product->id)
                ->where('clicked_at', '>=', $since)
                ->count();

            $views = DB::table('page_views')
                ->where('path', '/product/'.$product->slug)
                ->where('viewed_at', '>=', $since)
                ->count();

            if ($views < $this->minSampleSize) {
                return null;
            }

            return round($clicks / $views * 100, 2);
        } catch (\Throwable $e) {
            Log::warning('MerchandisingService::ctrFor failed: '.$e->getMessage());
            return null;
        }
    }

    public function activeCampaigns(): Collection
    {
        return $this->remember($this->cacheKey('active-campaigns'), 300, function () {
            try {
                return \App\Models\Campaign::active()
                    ->with(['products' => function ($q) {
                        $q->where('products.status', 'published')
                          ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
                          ->withCount('activeOffers');
                    }])
                    ->orderByDesc('priority')
                    ->get()
                    ->filter(fn ($c) => $c->products->isNotEmpty())
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('MerchandisingService::activeCampaigns failed: '.$e->getMessage());
                return collect();
            }
        }, fn ($v) => $v instanceof Collection);
    }

    // ── Scoring engines ──────────────────────────────────────────────────

    protected function computeTrendingScores(Collection $productIds): array
    {
        $recentClicks = $this->clickCounts($productIds, 7);
        $previousClicks = $this->clickCounts($productIds, 14, 7);
        $totalClicks = $this->clickCounts($productIds, 30);

        $maxClicks = max($recentClicks->max('total') ?? 1, 1);

        $scores = [];
        foreach ($productIds as $pid) {
            $clicks = $recentClicks[$pid]['total'] ?? 0;
            $prevClicks = $previousClicks[$pid]['total'] ?? 0;
            $total = $totalClicks[$pid]['total'] ?? 0;

            $growth = $prevClicks > 0 ? ($clicks - $prevClicks) / $prevClicks : ($clicks > 0 ? 1 : 0);

            $normalizedClicks = $clicks / $maxClicks;
            $normalizedTotal = min($total / max($maxClicks * 4, 1), 1);
            $normalizedGrowth = min(max($growth, 0), 2) / 2;

            $score = ($normalizedClicks * $this->trendingWeights['recent_clicks'])
                + ($normalizedTotal * $this->trendingWeights['recent_views'])
                + ($normalizedClicks * 0.5 * $this->trendingWeights['ctr'])
                + ($normalizedGrowth * $this->trendingWeights['growth']);

            $scores[$pid] = round($score * 100, 2);
        }

        return $scores;
    }

    protected function computeDealScores(Collection $productIds, float $minDiscountPercent = 15.0): array
    {
        $bestOffers = $this->bestOffersForProducts($productIds);
        $popularity = $this->clickCounts($productIds, 30);

        $maxPopularity = max($popularity->max('total') ?? 1, 1);

        $scores = [];
        foreach ($productIds as $pid) {
            $offer = $bestOffers[$pid] ?? null;

            if (! $offer || $offer->current_price === null || ! $offer->original_price || $offer->original_price <= $offer->current_price) {
                $scores[$pid] = 0;
                continue;
            }

            $discount = ($offer->original_price - $offer->current_price) / $offer->original_price;
            // Enforce homepage threshold: more than 15% discount only
            if ($discount * 100 < $minDiscountPercent) {
                $scores[$pid] = 0;
                continue;
            }
            $discountScore = min($discount / 0.5, 1);

            $dropScore = 0;
            $latestDrop = \App\Models\PriceDropEvent::where('offer_id', $offer->id)
                ->where('occurred_at', '>=', Carbon::now()->subDays(30))
                ->latest('occurred_at')
                ->first();
            if ($latestDrop) {
                $dropScore = min($latestDrop->drop_percent / 30, 1);
            }

            $historicalScore = 0;
            $lowest90 = $this->lowestPriceInPeriod($offer, 90);
            if ($lowest90 !== null && $lowest90 > 0) {
                $historicalScore = min(max(1 - ($offer->current_price - $lowest90) / $lowest90, 0), 1);
            }

            $pop = $popularity[$pid]['total'] ?? 0;
            $normalizedPop = $pop / $maxPopularity;

            $freshScore = 0;
            if ($offer->last_synced_at) {
                $hoursStale = $offer->last_synced_at->diffInHours(now());
                $freshScore = max(1 - $hoursStale / 168, 0);
            }

            $score = ($discountScore * $this->dealWeights['discount'])
                + ($dropScore * $this->dealWeights['price_drop'])
                + ($historicalScore * $this->dealWeights['historical'])
                + ($normalizedPop * $this->dealWeights['popularity'])
                + ($freshScore * $this->dealWeights['freshness']);

            $scores[$pid] = round($score * 100, 2);
        }

        return $scores;
    }

    protected function computePopularScores(Collection $productIds): array
    {
        $totalClicks = $this->clickCounts($productIds, 365);
        $recentClicks = $this->clickCounts($productIds, 30);

        $maxTotal = max($totalClicks->max('total') ?? 1, 1);
        $maxRecent = max($recentClicks->max('total') ?? 1, 1);

        $scores = [];
        foreach ($productIds as $pid) {
            $tClicks = $totalClicks[$pid]['total'] ?? 0;
            $rClicks = $recentClicks[$pid]['total'] ?? 0;

            $score = (($tClicks / $maxTotal) * 0.50)
                + (($rClicks / $maxRecent) * 0.50);

            $scores[$pid] = round($score * 100, 2);
        }

        return $scores;
    }

    // ── Data helpers ─────────────────────────────────────────────────────

    protected function cacheKey(string $name): string
    {
        return 'merch.'.self::CACHE_VERSION.'.'.$name;
    }

    /**
     * Cached remember with a type guard. If the store returns garbage
     * (e.g. __PHP_Incomplete_Class from a stale serialized object), the
     * callback is re-run so the caller never receives a broken value.
     */
    protected function remember(string $key, int $ttl, callable $callback, ?callable $validate = null): mixed
    {
        $cached = cache()->get($key);

        $isValid = $cached !== null && ($validate ? $validate($cached) : true);

        if ($isValid) {
            return $cached;
        }

        $value = $callback();

        try {
            cache()->put($key, $value, $ttl);
        } catch (\Throwable $e) {
            Log::warning('MerchandisingService cache put failed: '.$e->getMessage());
        }

        return $value;
    }

    protected function publishedProductIds(): Collection
    {
        return $this->remember($this->cacheKey('published-ids'), 300, function () {
            return Product::where('status', 'published')->pluck('id');
        }, fn ($v) => $v instanceof Collection);
    }

    protected function clickCounts(Collection $productIds, int $days, int $offsetDays = 0): Collection
    {
        $since = Carbon::now()->subDays($days + $offsetDays);
        $until = $offsetDays > 0 ? Carbon::now()->subDays($offsetDays) : now();

        return Click::whereIn('product_id', $productIds)
            ->where('clicked_at', '>=', $since)
            ->where('clicked_at', '<', $until)
            ->select('product_id', DB::raw('COUNT(*) as total'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
    }

    protected function bestOffersForProducts(Collection $productIds): Collection
    {
        return \App\Models\Offer::whereIn('product_id', $productIds)
            ->where('status', 'active')
            ->whereNotNull('current_price')
            ->where('current_price', '>', 0)
            ->orderByRaw("CASE availability WHEN 'in_stock' THEN 0 WHEN 'preorder' THEN 1 WHEN 'unknown' THEN 2 ELSE 3 END")
            ->orderBy('current_price')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');
    }

    protected function lowestPriceInPeriod(\App\Models\Offer $offer, int $days): ?float
    {
        return \App\Models\PriceHistory::where('offer_id', $offer->id)
            ->where('recorded_at', '>=', Carbon::now()->subDays($days))
            ->min('price');
    }

    protected function priceDropLabel($drop): string
    {
        if ($drop->drop_percent >= 20) {
            return 'Big Price Drop';
        }
        if ($drop->drop_percent >= 10) {
            return 'Price Drop';
        }
        return 'Price Changed';
    }
}
