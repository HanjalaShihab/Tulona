<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\PriceDropEvent;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\Log;

/**
 * Records every meaningful price change: append-only history + drop events.
 * Never fabricates data; duplicate rows suppressed when price unchanged (§27).
 */
class PriceTrackingService
{
    /** @return bool true when a new history point was recorded */
    public function recordPrice(Offer $offer, ?float $newPrice): bool
    {
        if ($newPrice === null || $newPrice <= 0) {
            return false;
        }

        $last = $offer->priceHistory()->latest('recorded_at')->first();

        if ($last && round((float) $last->price, 2) === round((float) $newPrice, 2)) {
            return false; // no change → no duplicate record
        }

        PriceHistory::create([
            'offer_id' => $offer->id,
            'price' => $newPrice,
            'currency' => $offer->currency,
            'recorded_at' => now(),
        ]);

        if ($last && (float) $last->price > (float) $newPrice) {
            $this->createDropEvent($offer, (float) $last->price, (float) $newPrice);
        }

        return true;
    }

    protected function createDropEvent(Offer $offer, float $previous, float $current): void
    {
        $dropPercent = round(($previous - $current) / $previous * 100, 2);

        PriceDropEvent::create([
            'product_id' => $offer->product_id,
            'offer_id' => $offer->id,
            'previous_price' => $previous,
            'current_price' => $current,
            'drop_amount' => round($previous - $current, 2),
            'drop_percent' => $dropPercent,
            'currency' => $offer->currency,
            'occurred_at' => now(),
        ]);

        Log::info('Price drop recorded', [
            'offer_id' => $offer->id, 'previous' => $previous, 'current' => $current, 'percent' => $dropPercent,
        ]);
    }

    public function summaryFor(Offer $offer): ?array
    {
        $prices = $offer->priceHistory()->orderBy('recorded_at')->pluck('price');

        if ($prices->count() < 2 || is_null($offer->current_price)) {
            return null; // insufficient data → no misleading stats (§14)
        }

        $current = (float) $offer->current_price;

        return [
            'lowest' => (float) $prices->min(),
            'highest' => (float) $prices->max(),
            'average' => round((float) $prices->avg(), 2),
            'points' => $prices->map(fn ($p) => (float) $p)->all(),
        ];
    }
}
