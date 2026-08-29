<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Merchant;
use Illuminate\View\View;

class MerchantController extends Controller
{
    /** /merchant/{slug} — informational page; never implies partnership (§48). */
    public function show(string $slug): View
    {
        $merchant = Merchant::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $offers = $merchant->offers()
            ->where('status', 'active')
            ->with(['product.brand', 'product.category', 'product.images', 'product.activeOffers.merchant', 'product.latestDrop'])
            ->latest('updated_at')
            ->paginate(24);

        $categories = Category::whereIn('id',
            $merchant->offers()->join('products', 'products.id', '=', 'offers.product_id')->select('products.category_id')
        )->get();

        return view('merchants.show', [
            'merchant' => $merchant,
            'offers' => $offers,
            'categories' => $categories,
            'productCount' => $merchant->offers()->distinct('product_id')->count('product_id'),
            'seo' => [
                'title' => $merchant->seo_title ?: "Shopping at {$merchant->name} — Prices & Offers on Tulona",
                'description' => $merchant->seo_description ?: "See {$merchant->name} prices across products listed on Tulona and compare before you buy.",
            ],
        ]);
    }
}
