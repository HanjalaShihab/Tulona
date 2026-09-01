<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;

/**
 * Detects when a product already exists in the catalogue so a new merchant's
 * offer can be attached to the SAME product page (Compare Stores) instead of
 * silently creating a duplicate product.
 *
 * Matching is deliberately conservative — signals are evaluated strongest
 * first, and fuzzy name matching is scoped to one top-level category and
 * guarded against numeric variant drift ("12 GB" vs "16 GB") and brand
 * conflicts:
 *   1. GTIN / UPC / EAN exact match (globally unique)
 *   2. Brand + model number exact match
 *   3. Identical normalized name (category-agnostic)
 *   4. Strongly similar normalized name within the same top-level category
 */
class ProductMatchService
{
    public function find(?Product $candidate): ?Product
    {
        if ($candidate === null || trim((string) $candidate->name) === '') {
            return null;
        }

        $q = Product::query()->where('status', 'published');
        if ($candidate->id) {
            $q->whereKeyNot($candidate->id);
        }
        $pool = $q->get(['id', 'name', 'brand_id', 'category_id', 'model_number', 'gtin']);

        if ($pool->isEmpty()) {
            return null;
        }

        // 1) GTIN / UPC / EAN — globally unique identifiers.
        if (filled($gtin = $candidate->gtin)) {
            $hit = $pool->first(fn ($p) => filled($p->gtin) && (string) $p->gtin === (string) $gtin);
            if ($hit !== null) {
                return Product::find($hit->id);
            }
        }

        // 2) Brand + model number.
        if ($candidate->brand_id && filled($model = $candidate->model_number)) {
            $hit = $pool->first(fn ($p) => (int) $p->brand_id === (int) $candidate->brand_id
                && filled($p->model_number) && (string) $p->model_number === (string) $model);
            if ($hit !== null) {
                return Product::find($hit->id);
            }
        }

        $name = $this->normalize((string) $candidate->name);
        if (mb_strlen($name) < 3) {
            return null;
        }

        // 3) Identical normalized name — the strongest name-only signal, so it
        //    is allowed to match across categories (e.g. the same re-post).
        foreach ($pool as $p) {
            if ($this->normalize((string) $p->name) === $name) {
                return Product::find($p->id);
            }
        }

        // 4) Similar name within the same top-level category (+ variant guard).
        $rootId = $this->rootCategoryId($candidate->category_id);
        if ($rootId === null) {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($pool as $p) {
            if ($this->rootCategoryId($p->category_id) !== $rootId) {
                continue;
            }
            if ($this->brandsConflict($candidate, $p)) {
                continue;
            }
            if ($this->hasNumericVariantConflict((string) $candidate->name, (string) $p->name)) {
                continue;
            }

            $score = $this->similarity($name, $this->normalize((string) $p->name));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $p;
            }
        }

        if ($best !== null && $bestScore >= $this->threshold($name)) {
            return Product::find($best->id);
        }

        return null;
    }

    /**
     * Bloom-ready backfill info for an existing product — the caller merges
     * only the identifier fields the existing row is missing.
     */
    public function missingIdentifiers(Product $matched, Product $candidate): array
    {
        $fill = [];
        foreach (['sku', 'model_number', 'gtin'] as $field) {
            if (blank($matched->{$field}) && filled($candidate->{$field})) {
                $fill[$field] = $candidate->{$field};
            }
        }

        return $fill;
    }

    protected function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    protected function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    /** Longer names can tolerate a token or two differing between merchants. */
    protected function threshold(string $normalized): float
    {
        $tokens = preg_split('/\s+/', trim($normalized)) ?: [];

        return count($tokens) <= 2 ? 1.0 : (count($tokens) >= 5 ? 0.72 : 0.78);
    }

    protected function rootCategoryId(?int $categoryId): ?int
    {
        if (! $categoryId) {
            return null;
        }

        $categories = Category::query()->get(['id', 'parent_id'])->keyBy('id');
        $seen = [];
        $current = $categories->get($categoryId);

        while ($current !== null) {
            if (in_array($current->id, $seen, true)) {
                return null;
            }
            $seen[] = $current->id;

            if (! $current->parent_id) {
                return (int) $current->id;
            }

            $current = $categories->get($current->parent_id);
        }

        return (int) $categoryId;
    }

    protected function brandsConflict(Product $candidate, Product $existing): bool
    {
        return (bool) $candidate->brand_id
            && (bool) $existing->brand_id
            && (int) $candidate->brand_id !== (int) $existing->brand_id;
    }

    /** e.g. "12GB"/"16GB", "1TB"/"512GB" — different values of the same unit. */
    protected function hasNumericVariantConflict(string $a, string $b): bool
    {
        $unitsA = $this->variantUnits($a);
        $unitsB = $this->variantUnits($b);

        foreach ($unitsA as $unit => $value) {
            if (isset($unitsB[$unit]) && abs($unitsB[$unit] - $value) > 0.001) {
                return true;
            }
        }

        return false;
    }

    protected function variantUnits(string $value): array
    {
        preg_match_all('/(\d+(?:\.\d+)?)(gb|tb|mb|mp|mah|g|kg|hz|wh|w|inch|mm|cm)\b/i', $value, $matches, PREG_SET_ORDER);

        $units = [];
        foreach ($matches as $match) {
            $units[strtolower($match[2])] = (float) $match[1];
        }

        return $units;
    }
}
