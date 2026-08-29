<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;

/**
 * Anonymous, contextual recommendations (§47): similar / cheaper alternatives /
 * popular picks from the same category — grounded only in real database data.
 */
class RecommendationService
{
    public function alternativesFor(Product $product, int $limit = 4): array
    {
        $categoryIds = $product->category->descendantsAndSelf();

        $base = Product::query()
            ->where('status', 'published')
            ->whereIn('category_id', $categoryIds)
            ->where('id', '!=', $product->id)
            ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->withCount('activeOffers');

        $similar = (clone $base)->orderByDesc('popularity_score')->limit($limit)->get();

        $cheaperIds = $this->bestPrices($similar);
        $myBest = $this->bestPriceFor($product);

        $cheaper = $cheaperIds->filter(fn ($p) => $myBest && $p->best_price < $myBest)->take($limit);

        return [
            'similar' => $similar,
            'cheaper' => $product->activeOffers()->exists() ? $cheaper : collect(),
        ];
    }

    public function trending(int $limit = 8)
    {
        return Product::where('status', 'published')->where('is_trending', true)
            ->orderByDesc('popularity_score')->limit($limit)
            ->get();
    }

    protected function bestPriceFor(Product $product): ?float
    {
        return $product->activeOffers()->whereNotNull('current_price')->min('current_price');
    }

    protected function bestPrices($products)
    {
        return $products->each(function ($p) {
            $p->best_price = $p->activeOffers->where('status', 'active')->min('current_price');
        });
    }
}
