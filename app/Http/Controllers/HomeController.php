<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Services\MerchandisingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, MerchandisingService $merch): View
    {
        $categories = Category::whereNull('parent_id')->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        // Fix product_count to include subcategories (descendants) - was counting only direct products
        $categories->each(function ($cat) {
            $ids = $cat->descendantsAndSelf();
            $cat->product_count = Product::where('status', 'published')->whereIn('category_id', $ids)->count();
        });

        // Computed merchandising sections
        $trending = $merch->trending(8);
        $deals = $merch->bestDeals(8);
        $drops = $merch->priceDrops(6);
        $topSelling = $merch->mostClicked(8, 30);
        $newArrivals = $merch->newArrivals(8, 30);
        $campaigns = $merch->activeCampaigns();

        // Featured: admin-controlled is_featured, show 10 latest
        $featured = Product::published()
            ->where('is_featured', true)
            ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->withCount('activeOffers')
            ->latest()
            ->limit(10)
            ->get();

        $guides = Article::published()->where('type', 'guide')->latest('published_at')->limit(3)->get();
        $merchants = Merchant::where('status', 'active')->orderBy('name')->get();

        return view('home', compact(
            'categories', 'trending', 'deals', 'drops', 'topSelling',
            'newArrivals', 'campaigns', 'featured', 'guides', 'merchants',
        ) + [
            'seo' => [
                'title' => 'Tulona — Find the right product at the right price',
                'description' => 'Compare products, prices, deals and trusted stores in Bangladesh before you buy. Independent, data-driven shopping research.',
            ],
        ]);
    }
}
