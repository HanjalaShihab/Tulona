<?php

namespace App\Http\Controllers;

use App\Models\PriceDropEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceDropController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['percent', 'amount', 'recent']) ? $request->query('sort') : 'percent';

        $drops = PriceDropEvent::query()
            ->with(['product.brand', 'offer.merchant', 'product.activeOffers'])
            // Only the most recent drop per product → avoid duplicate cards
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('price_drop_events')->groupBy('product_id');
            })
            ->when($sort === 'percent', fn ($q) => $q->orderByDesc('drop_percent'))
            ->when($sort === 'amount', fn ($q) => $q->orderByDesc('drop_amount'))
            ->when($sort === 'recent', fn ($q) => $q->latest('occurred_at'))
            ->paginate(24)
            ->withQueryString();

        return view('price-drops.index', [
            'drops' => $drops,
            'sort' => $sort,
            'seo' => ['title' => 'Recent Price Drops — Tulona', 'description' => 'Products whose prices recently decreased at trusted stores, ranked by verified price history data.'],
        ]);
    }
}
