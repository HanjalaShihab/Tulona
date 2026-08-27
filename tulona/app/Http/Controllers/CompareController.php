<?php

namespace App\Http\Controllers;

use App\Models\AttributeDefinition;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Anonymous product comparison (§17): 2–4 products via query string,
 * category-aware attribute table, no login required.
 */
class CompareController extends Controller
{
    public function index(Request $request): View
    {
        $slugs = collect(explode(',', (string) $request->query('products', '')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->take(4)
            ->values();

        $products = Product::whereIn('slug', $slugs)
            ->published()
            ->with(['brand', 'category', 'activeOffers.merchant'])
            ->get()
            ->sortBy(fn ($p) => array_search($p->slug, $slugs->all()))
            ->values();

        $attributes = AttributeDefinition::whereIn('category_id', $products->pluck('category_id')->unique())
            ->orderByDesc('is_filterable')->orderBy('sort_order')->limit(20)->get();

        return view('compare.index', [
            'slugs' => $slugs,
            'products' => $products,
            'attributes' => $attributes,
            'seo' => ['title' => 'Compare Products — Tulona', 'robots' => 'noindex'],
        ]);
    }
}
