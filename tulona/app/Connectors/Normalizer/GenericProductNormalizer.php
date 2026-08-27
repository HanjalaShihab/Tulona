<?php

namespace App\Connectors\Normalizer;

use App\Contracts\Merchant\ProductNormalizer;

/**
 * Cleans raw rows into the normalized intermediate shape the importer expects:
 * strips currency symbols, normalizes availability values and empty strings → null.
 */
class GenericProductNormalizer implements ProductNormalizer
{
    public function normalize(array $raw, array $config): array
    {
        $price = $this->toNullableFloat($raw['price'] ?? null);
        $original = $this->toNullableFloat($raw['original_price'] ?? null);

        return [
            'name' => $this->cleanString($raw['name'] ?? null),
            'category_slug' => $this->cleanString($raw['category_slug'] ?? null),
            'brand_slug' => $this->cleanString($raw['brand_slug'] ?? null),
            'merchant_slug' => $this->cleanString($raw['merchant_slug'] ?? null),
            'price' => $price,
            'original_price' => $original !== null && $original > $price ? $original : null,
            'currency' => strtoupper((string) ($raw['currency'] ?? 'BDT')),
            'affiliate_url' => $this->cleanString($raw['affiliate_url'] ?? null),
            'external_url' => $this->cleanString($raw['external_url'] ?? null),
            'availability' => $this->availability($raw['availability'] ?? 'unknown'),
            'gtin' => $this->cleanString($raw['gtin'] ?? null),
            'model_number' => $this->cleanString($raw['model_number'] ?? null),
            'sku' => $this->cleanString($raw['sku'] ?? null),
            'description' => $this->cleanString($raw['description'] ?? null),
        ];
    }

    protected function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = str_replace([',', '৳', '$', '€', '£'], '', (string) $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    protected function cleanString(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? null : $v;
    }

    protected function availability(mixed $value): string
    {
        $maps = [
            'in stock' => 'in_stock', 'in-stock' => 'in_stock', 'instock' => 'in_stock',
            'out of stock' => 'out_of_stock', 'out-of-stock' => 'out_of_stock', 'oos' => 'out_of_stock',
            'pre order' => 'preorder', 'preorder' => 'preorder',
        ];

        $key = strtolower(trim((string) $value));

        return $maps[$key] ?? 'unknown';
    }
}
