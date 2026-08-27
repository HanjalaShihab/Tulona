<?php

namespace App\Services;

use App\Models\Comparison;
use App\Models\Offer;
use Illuminate\Support\Collection;

/**
 * §35/§36 Comparison engine: given a Comparison (its products + participating
 * merchant offers with overrides), compute per-product the comparison table
 * (price/availability/warranty/shipping per merchant) and the aggregate
 * stats (lowest, highest, difference, discount) plus Best Price vs Best Deal.
 *
 * Never mutates canonical product/offer data — it only reads and layers the
 * admin overrides stored on comparison_offer.
 */
class ComparisonEngineService
{
    public function products(Comparison $comparison): Collection
    {
        return $comparison->products()->with(['brand', 'category', 'attributes.definition'])->get();
    }

    /** All offers (visible, ordered) tied to this comparison via comparison_offer. */
    public function offers(Comparison $comparison): Collection
    {
        return $comparison->offers()
            ->wherePivot('is_hidden', false)
            ->get()
            ->groupBy('product_id');
    }

    /**
     * Per-product comparison table + aggregate stats (§35).
     *
     * @return Collection<int, array{product, columns:Collection, stats:array}>
     */
    public function build(Comparison $comparison): Collection
    {
        $products = $this->products($comparison);

        return $products->map(function ($product) use ($comparison) {
            $columns = $comparison->offers()
                ->where('comparison_offer.product_id', $product->id)
                ->wherePivot('is_hidden', false)
                ->get()
                ->map(fn (Offer $offer) => $this->column($offer));

            return [
                'product' => $product,
                'pick_label' => $product->pivot->pick_label,
                'editorial_notes' => $product->pivot->editorial_notes,
                'columns' => $columns,
                'stats' => $this->stats($columns->where('price', '!=', null)),
            ];
        });
    }

    /** One merchant column for a product (respecting comparison_offer overrides). */
    protected function column(Offer $offer): array
    {
        $p = $offer->pivot;
        $price = $p->override_price ?? $offer->current_price;
        $availability = $p->override_availability ?? $offer->availability;
        $original = $offer->original_price;

        return [
            'offer' => $offer,
            'merchant' => $offer->merchant,
            'merchant_name' => $offer->merchant->name,
            'price' => $price !== null ? (float) $price : null,
            'currency' => $offer->currency ?? 'BDT',
            'affiliate_url' => $offer->resolvedAffiliateUrl(),
            'availability' => $availability,
            'warranty' => $p->override_warranty ?? null,
            'shipping' => $p->override_shipping ?? null,
            'discount_pct' => $this->discountPct($original, $price),
            'is_override' => $p->override_price !== null,
        ];
    }

    protected function stats(Collection $columns): array
    {
        $prices = $columns->pluck('price')->filter()->values();

        if ($prices->isEmpty()) {
            return ['lowest' => null, 'highest' => null, 'difference' => null, 'pct_diff' => null];
        }

        $lowest = $prices->min();
        $highest = $prices->max();
        $difference = $highest - $lowest;

        return [
            'lowest' => $lowest,
            'highest' => $highest,
            'difference' => $difference,
            'pct_diff' => $lowest > 0 ? round(($difference / $lowest) * 100, 1) : null,
            'lowest_merchant' => $columns->firstWhere('price', $lowest)['merchant_name'] ?? null,
        ];
    }

    /**
     * §36 Best Price vs Best Overall Deal. Best Price = lowest-priced visible
     * offer. Best Deal uses admin's stored override if set, else a warranty/
     * shipping/availability-aware heuristic — never just the cheapest.
     */
    public function bestPrice(Comparison $comparison): ?array
    {
        $best = null;
        foreach ($comparison->offers()->wherePivot('is_hidden', false)->get() as $offer) {
            $price = $offer->pivot->override_price ?? $offer->current_price;
            if ($price === null) {
                continue;
            }
            if ($best === null || (float) $price < $best['price']) {
                $best = [
                    'offer' => $offer,
                    'merchant' => $offer->merchant,
                    'product' => $offer->product,
                    'price' => (float) $price,
                ];
            }
        }

        return $best;
    }

    public function bestDeal(Comparison $comparison): ?array
    {
        // §36 "Allow Admin override": a manually flagged is_best_deal offer wins;
        // otherwise fall back to the heuristic. Among several flagged offers the
        // lowest-priced one is returned.
        $flagged = $comparison->offers()
            ->wherePivot('is_hidden', false)
            ->wherePivot('is_best_deal', true)
            ->get()
            ->filter(fn (Offer $offer) => ($offer->pivot->override_price ?? $offer->current_price) !== null)
            ->sortBy(fn (Offer $offer) => (float) ($offer->pivot->override_price ?? $offer->current_price));

        $first = $flagged->first();
        if ($first) {
            return [
                'offer' => $first,
                'merchant' => $first->merchant,
                'product' => $first->product,
                'price' => (float) ($first->pivot->override_price ?? $first->current_price),
            ];
        }

        return $this->heuristicBestDeal($comparison);
    }

    protected function heuristicBestDeal(Comparison $comparison): ?array
    {
        $best = null;
        $bestScore = PHP_FLOAT_MIN;

        foreach ($comparison->offers()->wherePivot('is_hidden', false)->get() as $offer) {
            $p = $offer->pivot;
            $price = $p->override_price ?? $offer->current_price;
            if ($price === null) {
                continue;
            }

            $score = 1000 - (float) $price;
            $score += $p->override_warranty ? 20 : 0;
            $score += $p->override_availability === 'in_stock' || $offer->availability === 'in_stock' ? 15 : 0;
            $score += $p->override_shipping === 'free' ? 10 : 0;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'offer' => $offer,
                    'merchant' => $offer->merchant,
                    'product' => $offer->product,
                    'price' => (float) $price,
                ];
            }
        }

        return $best;
    }

    protected function discountPct(?float $original, ?float $current): ?float
    {
        if ($original === null || $current === null || $original <= 0) {
            return null;
        }

        return round((($original - $current) / $original) * 100, 1);
    }
}
