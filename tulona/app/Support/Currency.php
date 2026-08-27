<?php

namespace App\Support;

/**
 * Currency presentation (§68): prices are shown in their original currency,
 * never silently converted and passed off as merchant prices.
 */
class Currency
{
    public const SYMBOLS = [
        'BDT' => '৳', 'USD' => '$', 'INR' => '₹', 'EUR' => '€', 'GBP' => '£',
    ];

    public static function format(?float $amount, string $code = 'BDT'): string
    {
        if ($amount === null) {
            return 'Price unavailable';
        }

        $symbol = self::SYMBOLS[strtoupper($code)] ?? $code.' ';
        $formatted = number_format($amount, 0);

        return str_starts_with($symbol, '§') ? $formatted : $symbol.$formatted;
    }

    public static function freshness(Offer|\App\Models\Offer $offer, int $thresholdHours): ?string
    {
        if (! $offer->last_synced_at) {
            return null;
        }

        $mins = $offer->last_synced_at->diffInMinutes(now());

        return match (true) {
            $mins < 60 => "Updated {$mins} min ago",
            $mins < 1440 => 'Updated '.floor($mins / 60).' hr ago',
            default => 'Updated '.floor($mins / 1440).' days ago',
        };
    }
}
