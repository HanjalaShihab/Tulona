<?php

namespace App\Http\Controllers;

use App\Models\Comparison;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealsController extends Controller
{
    /** /deals — genuine discounts only; no fake urgency (§18). */
    public function index(Request $request): View
    {
        $query = $this->dealQuery();

        if ($merchant = $request->query('merchant')) {
            $query->whereHas('offers', fn ($o) => $o->whereHas('merchant', fn ($m) => $m->where('slug', $merchant)));
        }

        return view('deals.index', [
            'products' => $query->paginate(24)->withQueryString(),
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(),
            'activeMerchant' => $request->query('merchant'),
            'comparisons' => $this->comparisons($request->query('merchant')),
            'seo' => ['title' => "Today's Best Deals & Discounts — Tulona", 'description' => 'Real, verified discounts and price drops across trusted stores. No fake urgency — just data.'],
        ]);
    }

    /** Published comparisons, optionally matching the active merchant store. */
    protected function comparisons(?string $merchantSlug)
    {
        $query = Comparison::published()->withCount('products')->latest('updated_at')->limit(4);

        if ($merchantSlug) {
            $query->whereHas('offers.merchant', fn ($m) => $m->where('slug', $merchantSlug));
        }

        return $query->get();
    }

    public static function dealQuery(): Builder
    {
        return Product::query()
            ->where('products.status', 'published')
            ->join('offers', function ($j) {
                $j->on('offers.product_id', '=', 'products.id')
                    ->where('offers.status', 'active')
                    ->whereNotNull('offers.current_price')
                    ->whereColumn('offers.original_price', '>', 'offers.current_price');
            })
            ->groupBy('products.id')
            ->selectRaw('products.*, MIN(offers.current_price) as best_price, MAX(offers.current_price/original_price) as best_ratio, MAX(offers.original_price) as max_original')
            ->orderByRaw('best_ratio ASC')
            ->with(['brand', 'activeOffers'])
            ->selectRaw('(SELECT COUNT(*) FROM offers WHERE offers.product_id = products.id AND offers.status = "active") AS offers_count');
    }
}
