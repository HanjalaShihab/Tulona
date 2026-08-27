<?php

namespace App\Connectors\Parser;

use App\Contracts\Merchant\ProductParser;

/**
 * Parses a JSON feed/lump of products into raw rows.
 * Supports: a bare JSON array of objects, {"products": [...]}, a JSON-LD
 *
 * @graph / array of Product nodes, or CSV-ish {"data": [...]} payloads.
 * Field aliases (name/title, price/current_price) are collapsed to canonical keys.
 */
class JsonProductParser implements ProductParser
{
    public function parse(string $raw, array $config): iterable
    {
        $json = json_decode($raw, true);
        if (! is_array($json)) {
            return;
        }

        foreach ($this->extractList($json) as $node) {
            if (! is_array($node)) {
                continue;
            }
            $row = $this->clean($node);
            if (isset($row['name'], $row['price']) || isset($row['name'], $row['external_url'])) {
                yield $row;
            }
        }
    }

    public function supports(string $method): bool
    {
        return $method === 'json' || $method === 'api';
    }

    protected function extractList(array $json): array
    {
        if (! array_is_list($json) || $json === []) {
            foreach (['products', 'results', 'data', 'items', '@graph', 'itemListElement'] as $key) {
                if (isset($json[$key]) && is_array($json[$key])) {
                    return array_is_list($json[$key]) && $json[$key] !== [] ? $json[$key] : [$json[$key]];
                }
            }

            return isset($json[0]) && is_array($json[0]) ? [$json] : [];
        }

        // Outer list: either directly product nodes, or a wrapper node that
        // itself holds a nested product list (e.g. a single {"ItemList": ...}
        // or {"products": [...]} hosted inside an array).
        if (isset($json[0], $json[1]) || ! is_array($json[0])) {
            return $json;
        }

        $first = $json[0];
        if (array_is_list($json) && array_is_list($first) && $first !== []) {
            return $first;
        }

        foreach (['products', 'results', 'data', 'items', '@graph', 'itemListElement'] as $key) {
            if (isset($first[$key]) && is_array($first[$key])) {
                return array_is_list($first[$key]) && $first[$key] !== [] ? $first[$key] : [$first[$key]];
            }
        }

        return $json;
    }

    protected function clean(array $node): array
    {
        $pick = static fn (array $n, string ...$keys) => collect($keys)
            ->map(fn ($k) => $n[$k] ?? null)
            ->first(fn ($v) => $v !== null);

        $name = $pick($node, 'name', 'title', 'product_name');
        $offers = is_array($node['offers'] ?? null) ? $node['offers'] : null;
        $offer0 = is_array($offers['offers'] ?? null) ? ($offers['offers'][0] ?? null) : $offers;
        $price = $pick($node, 'price', 'current_price', 'sale_price', 'amount')
            ?? (! $offer0 || is_scalar($offer0) ? null : $pick($offer0, 'price', 'lowPrice', 'highPrice'));
        $currency = $this->scalarOrNull($pick($node, 'currency', 'currency_code'))
            ?? (! $offer0 || is_scalar($offer0) ? null : $this->scalarOrNull($pick($offer0, 'priceCurrency', 'currency')));

        return [
            'name' => is_scalar($name) ? trim((string) $name) : null,
            'category_slug' => $this->scalarOrNull($pick($node, 'category', 'category_name', 'merchant_category')),
            'brand_slug' => $this->scalarOrNull($pick($node, 'brand', 'brand_name')),
            'merchant_slug' => null,
            'price' => is_numeric($price) || is_string($price) ? $price : null,
            'original_price' => $this->scalarOrNull($pick($node, 'original_price', 'regular_price', 'list_price')),
            'currency' => $currency ?? 'BDT',
            'affiliate_url' => $this->urlOrNull($pick($node, 'affiliate_url', 'affiliateUrl', 'tracking_url', 'url', 'url2')),
            'external_url' => $this->urlOrNull($pick($node, 'external_url', 'externalUrl', 'product_url', 'itemUrl', '@id')),
            'availability' => $this->scalarOrNull($pick($node, 'availability', 'stock_status', 'stock'))
                ?? (! $offer0 || is_scalar($offer0) ? null : $this->scalarOrNull($pick($offer0, 'availability'))),
            'gtin' => $this->scalarOrNull($pick($node, 'gtin', 'gtin13', 'ean', 'upc', 'barcode')),
            'model_number' => $this->scalarOrNull($pick($node, 'model_number', 'modelNumber', 'mpn', 'sku_id')),
            'sku' => $this->scalarOrNull($pick($node, 'sku', 'merchant_product_id', 'external_product_id', 'sku', 'id')),
            'description' => $this->scalarOrNull($pick($node, 'description', 'short_description')),
        ];
    }

    protected function scalarOrNull(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }

        return is_scalar($v) ? trim((string) $v) ?: null : null;
    }

    protected function urlOrNull(mixed $v): ?string
    {
        $s = $this->scalarOrNull($v);

        return $s !== null && filter_var($s, FILTER_VALIDATE_URL) ? $s : null;
    }
}
