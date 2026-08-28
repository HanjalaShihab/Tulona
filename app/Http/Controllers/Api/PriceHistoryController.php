<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\PriceTrackingService;

class PriceHistoryController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return response()->json($product->activeOffers->map(fn ($o) => [
            'merchant' => $o->merchant->name,
            'currency' => $o->currency,
            'current_price' => $o->current_price,
            'summary' => app(PriceTrackingService::class)->summaryFor($o), // null when insufficient data
            'history' => $o->priceHistory()->orderBy('recorded_at')->get(['price', 'recorded_at']),
        ]));
    }
}
