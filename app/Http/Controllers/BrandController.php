<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::withCount(['products as product_count' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('name')
            ->get();

        return view('brands.index', [
            'brands' => $brands,
            'seo' => [
                'title' => 'All Brands — Tulona',
                'description' => 'Browse all brands on Tulona — compare prices across trusted stores.',
            ],
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $query = Product::where('brand_id', $brand->id)
            ->published()
            ->with(['category', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->withCount('activeOffers');

        $this->applyFilters($request, $query);
        $this->applySorting($request, $query);

        $products = $query->paginate(24)->withQueryString();

        $categoryIds = Product::where('brand_id', $brand->id)->published()->pluck('category_id')->unique()->filter()->values();
        $categories = $categoryIds->isNotEmpty() ? Category::whereIn('id', $categoryIds)->where('is_active', true)->orderBy('name')->get() : collect();
        $merchants = Merchant::where('status', 'active')->orderBy('name')->limit(10)->get();

        return view('brands.show', [
            'brand' => $brand,
            'products' => $products,
            'categories' => $categories,
            'merchants' => $merchants,
            'sort' => $request->query('sort', 'relevance'),
            'seo' => [
                'title' => $brand->seo_title ?: "{$brand->name} Products — Prices & Deals",
                'description' => $brand->seo_description ?: "Browse {$brand->name} products, compare store prices and find current deals.",
            ],
        ]);
    }

    protected function applyFilters(Request $request, Builder $query): void
    {
        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($c) => $c->whereIn('slug', (array) $category));
        }
        if ($merchant = $request->query('merchant')) {
            $query->whereHas('offers', fn ($o) => $o->where('status', 'active')->whereHas('merchant', fn ($m) => $m->where('slug', $merchant)));
        }
        if ($min = $request->query('min_price')) {
            $query->whereHas('offers', fn ($o) => $o->where('status', 'active')->where('current_price', '>=', (float) $min));
        }
        if ($max = $request->query('max_price')) {
            $query->whereHas('offers', fn ($o) => $o->where('status', 'active')->where('current_price', '<=', (float) $max));
        }
        if ($request->boolean('in_stock')) {
            $query->whereHas('offers', fn ($o) => $o->where('status', 'active')->where('availability', 'in_stock'));
        }
        if ($searchIn = trim((string) $request->query('sq'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$searchIn}%")->orWhere('short_description', 'like', "%{$searchIn}%"));
        }
    }

    public const SORTS = ['relevance', 'price_asc', 'price_desc', 'discount', 'popular', 'rating', 'newest'];

    protected function applySorting(Request $request, Builder $query): void
    {
        match ($request->query('sort')) {
            'price_asc' => $query->selectRaw('products.*, (SELECT MIN(current_price) FROM offers WHERE offers.product_id = products.id AND offers.status = "active") AS best_price')
                ->orderByRaw('best_price IS NULL, best_price ASC'),
            'price_desc' => $query->selectRaw('products.*, (SELECT MIN(current_price) FROM offers WHERE offers.product_id = products.id AND offers.status = "active") AS best_price')
                ->orderByDesc('best_price'),
            'discount' => $query->selectRaw('products.*, (SELECT MAX((original_price - current_price) / NULLIF(original_price,0) * 100) FROM offers WHERE offers.product_id = products.id AND offers.status = "active" AND original_price > current_price) AS max_disc')
                ->orderByRaw('max_disc IS NULL, max_disc DESC'),
            'rating' => $query->orderByRaw('rating IS NULL, rating DESC'),
            'newest' => $query->latest(),
            'popular' => $query->orderByDesc('popularity_score'),
            default => $query->orderByDesc('is_featured')->orderByDesc('popularity_score'),
        };
    }
}
