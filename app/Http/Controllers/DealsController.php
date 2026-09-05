<?php

namespace App\Http\Controllers;

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
            'seo' => ['title' => "Today's Best Deals & Discounts — Tulona", 'description' => 'Real, verified discounts and price drops across trusted stores. No fake urgency — just data.'],
        ]);
    }

    public static function dealQuery(): Builder
    {
        return Product::query()
            ->where('status', 'published')
            ->whereHas('offers', fn (Builder $q) => $q->where('status', 'active')->whereNotNull('current_price')->whereColumn('original_price', '>', 'current_price'))
            ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->withCount(['offers as offers_count' => fn (Builder $q) => $q->where('status', 'active')])
            ->selectRaw('products.*, (SELECT MIN(current_price) FROM offers WHERE offers.product_id = products.id AND status = "active" AND current_price IS NOT NULL AND original_price > current_price) as best_price, (SELECT MAX(current_price*1.0/NULLIF(original_price,0)) FROM offers WHERE offers.product_id = products.id AND status = "active" AND current_price IS NOT NULL AND original_price > current_price) as best_ratio, (SELECT MAX(original_price) FROM offers WHERE offers.product_id = products.id AND status = "active" AND current_price IS NOT NULL AND original_price > current_price) as max_original')
            ->orderBy('best_ratio', 'asc');
    }
}
