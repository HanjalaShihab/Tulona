<?php

namespace App\Services\Scraping;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * §32 / §56 PRODUCT MATCHING against canonical products.
 * Priority: exact GTIN → model number → SKU → canonical slug. A name-only
 * match may return a "potential" product for admin review instead of auto-linking.
 */
class ProductMatcher
{
    public function exact(array $row): ?Product
    {
        $q = Product::query();

        if (! empty($row['gtin'])) {
            $q->where('gtin', $row['gtin']);
        } elseif (! empty($row['model_number'])) {
            $q->where('model_number', $row['model_number']);
        } elseif (! empty($row['sku'])) {
            $q->where('sku', $row['sku']);
        } elseif (! empty($row['name'])) {
            $q->where('slug', Str::slug($row['name']));
        } else {
            return null;
        }

        return $q->first();
    }

    /** Name-based loose match candidates (canonical slug prefix contained). */
    public function potential(array $row): ?Product
    {
        if (empty($row['name'])) {
            return null;
        }

        $name = Str::slug($row['name']);
        if ($name === '') {
            return null;
        }

        return Product::where('slug', 'like', "{$name}%")
            ->orWhere('slug', 'like', "%{$name}%")
            ->first();
    }

    /** Result descriptor used by the staging preview (§16). */
    public function matchTypeFor(array $row, ?Product $product): string
    {
        if ($product === null) {
            return 'new';
        }

        return $this->exact($row)?->id === $product->id ? 'matched' : 'potential';
    }
}
