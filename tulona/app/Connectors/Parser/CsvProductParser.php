<?php

namespace App\Connectors\Parser;

use App\Contracts\Merchant\ProductParser;

/**
 * CSV parser producing raw associative rows keyed by normalized canonical names
 * via config('merchants.mapping.columns'). Header aliases are normalized by
 * lowercasing+sliming so feeds with headers like "Product Name" still match.
 */
class CsvProductParser implements ProductParser
{
    public function parse(string $raw, array $config): iterable
    {
        $columns = $config['columns'] ?? config('merchants.mapping.columns', []);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $raw);
        rewind($handle);

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $line = array_map(static fn ($v) => $v === null ? null : trim((string) $v), $line);

            if ($line === [null] || array_filter($line) === []) {
                continue;
            }

            if ($headers === null) {
                $headers = $this->normalizeHeaders($line);

                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $line[$i] ?? null;
            }

            if (array_filter($assoc) !== []) {
                $rows[] = $assoc;
            }
        }

        fclose($handle);

        // Re-key to canonical canonical names from the mapping, keeping unknown extras.
        $canonical = array_fill_keys($columns, null);
        foreach ($rows as $row) {
            yield array_merge($canonical, array_intersect_key($row, $canonical));
        }
    }

    public function supports(string $method): bool
    {
        return $method === 'csv';
    }

    protected function normalizeHeaders(array $headers): array
    {
        $map = [
            'category' => 'category_slug',
            'brand' => 'brand_slug',
            'merchant' => 'merchant_slug',
            'price_usd' => 'price',
            'original price' => 'original_price',
            'url' => 'external_url',
            'affiliate url' => 'affiliate_url',
        ];

        return collect($headers)
            ->map(fn ($h) => $h === null ? '' : strtolower(trim($h)))
            ->map(fn ($h) => $map[$h] ?? $h)
            ->map(fn ($h) => str_replace(' ', '_', $h))
            ->values()
            ->all();
    }
}
