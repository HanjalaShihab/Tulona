<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\PriceDropEvent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $data = [
            'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get(),
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
        return Product::query()
            ->where('products.status', 'published')
            ->join('offers', function ($j) {
                $j->on('offers.product_id', '=', 'products.id')
                    ->where('offers.status', 'active')->whereNotNull('offers.current_price');
            })
            ->groupBy('products.id')
            ->selectRaw('products.*, MIN(offers.current_price) as best_price, MAX(offers.original_price) as max_original, COUNT(DISTINCT offers.id) as offer_count')
            ->havingRaw('max_original IS NOT NULL AND max_original > best_price * 1.05') // genuine ≥5% discount only
            ->orderByRaw('(max_original - best_price) / max_original DESC')
            ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->limit(8)
            ->get();
    }
}
