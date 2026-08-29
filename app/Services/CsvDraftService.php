<?php

namespace App\Services;

use App\Models\Merchant;

/**
 * Turns an uploaded products CSV into editable ProductDraft rows. Unlike the
 * bulk ImportService (which validates & auto-posts in a background job), this
 * flow keeps every row as a draft so the admin can review/edit/post each one.
 *
 * Expected header aliases are normalized (see the mapping in config/merchants)
 * so a feed like Rokomari's affiliate export still maps cleanly. Unknown or
 * missing values are left blank for the admin to fill on the review screen.
 */
class CsvDraftService
{
    public const COLUMNS = [
        'name', 'category_slug', 'brand_slug', 'merchant_slug', 'price',
        'original_price', 'currency', 'affiliate_url', 'external_url',
        'availability', 'gtin', 'model_number', 'sku', 'description',
    ];

    /** @return array<int, array<string, mixed>> normalized row payloads */
    public function parse(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open the uploaded CSV file.');
        }

        $rows = [];
        $headers = null;

        try {
            while (($line = fgetcsv($handle)) !== false) {
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

                if (! empty($assoc['name']) || array_filter($assoc) !== []) {
                    $rows[] = $assoc;
                }
            }
        } finally {
            fclose($handle);
        }

        $canonical = array_fill_keys(self::COLUMNS, null);

        return array_map(
            static fn ($row) => array_merge($canonical, array_intersect_key($row, $canonical)),
            $rows
        );
    }

    /** Normalize a row into an editable draft payload (merchant resolved by slug). */
    public function toDraftPayload(array $row): array
    {
        $currency = strtoupper((string) ($row['currency'] ?? '')) ?: 'BDT';
        $availability = $this->normalizeAvailability($row['availability'] ?? null);

        return [
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'category_slug' => (string) ($row['category_slug'] ?? ''),
            'category_id' => null,
            'subcategory' => null,
            'merchant_id' => $this->resolveMerchantId($row['merchant_slug'] ?? null, $row['external_url'] ?? null),
            'affiliate_url' => (string) ($row['affiliate_url'] ?? ''),
            'external_url' => (string) ($row['external_url'] ?? ''),
            'current_price' => $this->cleanNumber($row['price'] ?? null),
            'original_price' => $this->cleanNumber($row['original_price'] ?? null),
            'currency' => $currency,
            'availability' => $availability,
            'gtin' => (string) ($row['gtin'] ?? ''),
            'model_number' => (string) ($row['model_number'] ?? ''),
            'sku' => (string) ($row['sku'] ?? ''),
            'image' => (string) ($row['image'] ?? ''),
            'is_trending' => false,
            'is_featured' => false,
            'is_top_selling' => false,
        ];
    }

    protected function resolveMerchantId(?string $slug, ?string $url): ?int
    {
        if (! empty($slug)) {
            $merchant = Merchant::where('slug', $slug)->first();
            if ($merchant !== null) {
                return $merchant->id;
            }
        }

        if (! empty($url)) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            foreach (Merchant::where('status', 'active')->get(['id', 'website_url', 'base_affiliate_url']) as $m) {
                foreach ([$m->website_url, $m->base_affiliate_url] as $candidate) {
                    $candidateHost = $candidate ? strtolower((string) parse_url($candidate, PHP_URL_HOST)) : '';
                    if ($candidateHost !== '' && (str_ends_with($host, $candidateHost) || str_ends_with($candidateHost, $host))) {
                        return $m->id;
                    }
                }
            }
        }

        return null;
    }

    protected function cleanNumber(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace([',', '৳', 'TK', ' '], '', (string) $value);

        return is_numeric($value) ? $value : null;
    }

    protected function normalizeAvailability(?string $value): string
    {
        $clean = strtolower(str_replace([' ', '-'], '_', trim((string) $value)));

        return match ($clean) {
            'in_stock', 'in-stock', 'stock', '1', 'true', 'available' => 'in_stock',
            'out_of_stock', 'out-of-stock', '0', 'false', 'sold_out' => 'out_of_stock',
            'preorder', 'pre-order' => 'preorder',
            default => 'unknown',
        };
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

        return array_map(
            fn ($h) => $map[$h] ?? $h,
            array_map(
                fn ($h) => str_replace(' ', '_', strtolower(trim((string) $h))),
                $headers
            )
        );
    }
}
