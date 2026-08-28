<?php

namespace App\Http\Controllers;

use App\Models\AttributeDefinition;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function all(Request $request): View
    {
        $query = Product::published()
            ->whereHas('category', fn ($c) => $c->where('is_active', true))
            ->with(['brand', 'category', 'activeOffers'])->withCount('activeOffers');
        $this->applyFilters($request, $query);
        $this->applySorting($request, $query);

        return view('products.index', [
            'products' => $query->paginate(24)->withQueryString(),
            'sort' => $request->query('sort', 'relevance'),
            'seo' => ['title' => 'All Products — Prices, Deals & Comparisons', 'description' => 'Browse every product on Tulona with multi-store price comparison.'],
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $descendantIds = $category->descendantsAndSelf();

        $query = Product::query()
            ->where('status', 'published')
            ->whereIn('category_id', $descendantIds)
            ->with(['brand', 'activeOffers'])
            ->withCount('activeOffers');

        $this->applyFilters($request, $query);
        $this->applySorting($request, $query);

        return view('categories.show', [
            'category' => $category,
            'products' => $query->paginate(24)->withQueryString(),
            'subcategories' => $category->children()->where('is_active', true)->get(),
            'comparisons' => $this->categoryComparisons($descendantIds),
            'brands' => Brand::whereIn('id', fn ($q) => $q->select('brand_id')->from('products')->whereIn('category_id', $descendantIds))->orderBy('name')->get(),
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(),
            'filters' => $this->availableFilters($descendantIds),
            'sort' => $request->query('sort', 'relevance'),
            'seo' => [
                'title' => $category->seo_title ?: "{$category->name} — Prices, Deals & Comparisons",
                'description' => $category->seo_description ?: "Compare {$category->name} prices across trusted stores. Find the best deals and price drops.",
            ],
        ]);
    }

    protected function applyFilters(Request $request, Builder $query): void
    {
        if ($brand = $request->query('brand')) {
            $query->whereHas('brand', fn ($b) => $b->whereIn('slug', (array) $brand));
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
            default => $query->orderByDesc('is_featured')->orderByDesc('popularity_score'),
        };
    }

    /** Category-specific filterable attribute definitions (§11). */
    protected function availableFilters(array $categoryIds)
    {
        return AttributeDefinition::whereIn('category_id', $categoryIds)
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->get();
    }

    /** Published comparisons whose products fall within this category (§37). */
    protected function categoryComparisons(array $categoryIds)
    {
        return Comparison::published()
            ->withCount('products')
            ->whereHas('products', fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->latest('updated_at')
            ->limit(4)
            ->get();
    }
}
