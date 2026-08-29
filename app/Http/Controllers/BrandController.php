<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function show(string $slug): View
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $products = Product::where('brand_id', $brand->id)
            ->published()
            ->with(['category', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->withCount('activeOffers')
            ->orderByDesc('popularity_score')
            ->paginate(24);

        return view('brands.show', [
            'brand' => $brand,
            'products' => $products,
            'seo' => [
                'title' => $brand->seo_title ?: "{$brand->name} Products — Prices & Deals",
                'description' => $brand->seo_description ?: "Browse {$brand->name} products, compare store prices and find current deals.",
            ],
        ]);
    }
}
