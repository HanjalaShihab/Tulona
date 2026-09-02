<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\PriceDropEvent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $data = [
            'categories' => Category::whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')
                ->withCount(['products as product_count' => fn ($q) => $q->where('status', 'published')])
                ->get(),
            'trending' => Product::published()->where('is_trending', true)->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])->limit(8)->get(),
            'topSelling' => Product::published()->where('is_top_selling', true)->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])->limit(8)->get(),
            'featured' => Product::published()->where('is_featured', true)->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])->limit(4)->get(),
            'newArrivals' => Product::published()->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
                ->withCount('activeOffers')
                ->latest()
                ->limit(8)
                ->get(),
            'deals' => $this->bestDeals(),
            'drops' => PriceDropEvent::with('product.brand')->latest('occurred_at')->limit(6)->get(),
            'guides' => Article::published()->where('type', 'guide')->latest('published_at')->limit(3)->get(),
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(),
        ];

        return view('home', $data + [
            'seo' => ['title' => 'Tulona — Find the right product at the right price', 'description' => 'Compare products, prices, deals and trusted stores in Bangladesh before you buy. Independent, data-driven shopping research.'],
        ]);
    }

    protected function bestDeals()
    {
        $aggregated = DB::table('offers')
            ->select([
                'product_id',
                DB::raw('MIN(current_price) as best_price'),
                DB::raw('MAX(original_price) as max_original'),
                DB::raw('COUNT(DISTINCT id) as offer_count'),
            ])
            ->where('status', 'active')
            ->whereNotNull('current_price')
            ->groupBy('product_id')
            ->havingRaw('MAX(original_price) IS NOT NULL AND MAX(original_price) > MIN(current_price) * 1.05'); // genuine ≥5% discount only

        return Product::query()
            ->where('products.status', 'published')
            ->joinSub($aggregated, 'agg', function ($join) {
                $join->on('agg.product_id', '=', 'products.id');
            })
            ->select('products.*', 'agg.best_price', 'agg.max_original', 'agg.offer_count')
            ->orderByRaw('(agg.max_original - agg.best_price) / agg.max_original DESC')
            ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->limit(8)
            ->get();
    }
}
