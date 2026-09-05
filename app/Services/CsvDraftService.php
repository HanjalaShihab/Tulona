<?php

namespace App\Services;

use App\Models\Merchant;
use App\Support\StartechAffiliate;

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
        'availability', 'gtin', 'model_number', 'sku', 'description', 'image',
    ];

    /** @return array<int, array<string, mixed>> normalized row payloads */
    public function parse(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open the uploaded CSV file.');
        }

        // Skip a UTF-8 BOM if present. Otherwise fgetcsv does not recognise the
        // first quoted field ("\"Product\"") as enclosed and keeps the quotes.
        $head = fread($handle, 3);
        if ($head === "\xEF\xBB\xBF") {
            // BOM consumed; continue reading after it.
        } else {
            rewind($handle);
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

    /** Normalize a row into an editable draft payload (merchant resolved by slug/host or a default). */
    public function toDraftPayload(array $row, array $defaults = []): array
    {
        $currency = strtoupper((string) ($row['currency'] ?? '')) ?: 'BDT';
        $availability = $this->normalizeAvailability($row['availability'] ?? null);

        $merchantId = $this->resolveMerchantId($row['merchant_slug'] ?? null, $row['external_url'] ?? null)
            ?? ($defaults['merchant_id'] ?? null);

        $externalUrl = (string) ($row['external_url'] ?? '');
        $rawAffiliateUrl = (string) ($row['affiliate_url'] ?? '');

        // StarTech special: affiliate link is just product URL + ?tracking=CODE (ignore base_affiliate_url)
        if (StartechAffiliate::isStartechMerchantId($merchantId, $externalUrl ?: $rawAffiliateUrl)) {
            $productUrl = $rawAffiliateUrl !== '' ? $rawAffiliateUrl : $externalUrl;
            $affiliateUrl = $productUrl !== '' ? StartechAffiliate::buildAffiliateUrl($productUrl) : '';
        } else {
            $merchantAffiliateBase = $merchantId
                ? (string) (Merchant::where('id', $merchantId)->value('base_affiliate_url') ?? '')
                : '';
            $affiliateUrl = $rawAffiliateUrl
                ?: $this->buildAffiliateUrl($merchantAffiliateBase, $externalUrl);
        }

        $categorySlug = (string) ($row['category_slug'] ?? '');
        // Auto-detect category from product name if none provided
        if ($categorySlug === '' && empty($row['category_id'])) {
            $detected = app(CategoryDetector::class)->detect((string) ($row['name'] ?? ''), (string) ($row['description'] ?? ''));
            if ($detected) {
                $categorySlug = $detected->slug;
            }
        }

        return [
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'category_slug' => $categorySlug,
            'category_id' => null,
            'subcategory' => null,
            'brand_slug' => (string) ($row['brand_slug'] ?? ''),
            'merchant_id' => $merchantId,
            'affiliate_url' => $affiliateUrl,
            'external_url' => $externalUrl,
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

    /**
     * Build an affiliate link for a row that carries a product URL but no
     * affiliate column, using the merchant's base affiliate URL. The product URL
     * is substituted into a `{url}` placeholder, or appended when the base has no
     * placeholder. Returns '' when the merchant defines no affiliate base.
     */
    protected function buildAffiliateUrl(string $affiliateBase, string $externalUrl): string
    {
        if (empty($affiliateBase) || empty($externalUrl)) {
            return '';
        }

        return str_contains($affiliateBase, '{url}')
            ? str_replace('{url}', urlencode($externalUrl), $affiliateBase)
            : $affiliateBase;
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
            foreach (Merchant::where('status', 'active')->get(['id', 'slug', 'name', 'website_url', 'base_affiliate_url']) as $m) {
                foreach ([$m->website_url, $m->base_affiliate_url] as $candidate) {
                    $candidateHost = $candidate ? strtolower((string) parse_url($candidate, PHP_URL_HOST)) : '';
                    if ($candidateHost !== '' && (str_ends_with($host, $candidateHost) || str_ends_with($candidateHost, $host))) {
                        return $m->id;
                    }
                }
                // Fallback for StarTech variants: startech.com.bd vs star-tech.com
                if (str_contains($host, 'startech') && (str_contains(strtolower($m->slug ?? ''), 'startech') || str_contains(strtolower($m->name ?? ''), 'star tech'))) {
                    return $m->id;
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
            default => 'in_stock',
        };
    }

    protected function normalizeHeaders(array $headers): array
    {
        $map = [
            // canonical key => aliases (space_dash_and_lower handled below)
            'name' => ['title', 'product_name', 'productname', 'product', 'item', 'item_name', 'itemname', 'product_title', 'itemtitle', 'item'],
            'category_slug' => ['category', 'category_name', 'product_category', 'cat', 'type'],
            'brand_slug' => ['brand', 'brand_name', 'brandname', 'manufacturer', 'maker'],
            'merchant_slug' => ['merchant', 'merchant_name', 'merchantname', 'seller', 'store', 'store_name', 'shop', 'source', 'vendor'],
            'price' => ['current_price', 'sale_price', 'offer_price', 'selling_price', 'sale', 'offer', 'new_price', 'price_usd', 'price_-_current'],
            'original_price' => ['old_price', 'regular_price', 'list_price', 'mrp', 'cut_price', 'was_price', 'price_before', 'price_before_discount', 'original-price', 'before_discount', 'rrp'],
            'currency' => ['price_currency', 'currency_code', 'cur'],
            'affiliate_url' => ['affiliate_url', 'affiliate_link', 'affiliate', 'tracking_url', 'referral_url', 'ref_url', 'click_url', 'deep_link', 'your_link', 'your_url', 'yourlink', 'commision_link', 'commission_link', 'buy_link', 'purchase_link', 'buy_url'],
            'external_url' => ['url', 'product_url', 'link', 'source_url', 'item_url', 'product_link', 'href', 'website'],
            'availability' => ['stock', 'stock_status', 'status'],
            'gtin' => ['ean', 'upc', 'barcode', 'barcode_number', 'isbn'],
            'model_number' => ['model', 'model_no', 'mpn'],
            'sku' => ['product_code', 'productcode', 'item_code', 'itemcode', 'code'],
            'description' => ['short_description', 'desc', 'product_description', 'details', 'summary'],
            'image' => ['image_url', 'imageurl', 'img', 'picture', 'photo', 'product_image', 'image_src', 'thumbnail'],
        ];

        return array_map(
            function ($h) use ($map) {
                // Lowercase, collapse whitespace, and normalise separators
                // (spaces/hyphens/underscores → one underscore).
                $h = (string) $h;
                // Strip the UTF-8 BOM that Excel/affiliate exports often prepend,
                // plus any stray quotes.
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                $h = trim($h, " \t\n\r\0\x0B\"'\xEF\xBB\xBF");
                $h = strtolower($h);
                $h = preg_replace('/[()]/', ' ', $h); // "Price (BDT)" → "price  bdt"
                $h = preg_replace('/[\s\-_]+/', '_', trim($h));
                // Drop common unit/type suffixes so "price_bdt", "price_bd",
                // "price_tk", "price_inr" all resolve to the bare column.
                $h = preg_replace('/_(bdt|tk|usd|inr|gbp|eur|php|pkr|lkr|mmk|rm|rs?)(\d*)$/', '', $h);
                $h = rtrim($h, '_');

                foreach ($map as $key => $aliases) {
                    if ($h === $key || in_array($h, $aliases, true)) {
                        return $key;
                    }
                }

                // Fallback: if the header still literally names a price/url/link
                // column, map it rather than dropping it.
                if (str_contains($h, 'price')) {
                    return str_contains($h, 'old')
                        || str_contains($h, 'original')
                        || str_contains($h, 'regular')
                        || str_contains($h, 'mrp')
                        || str_contains($h, 'list')
                        ? 'original_price' : 'price';
                }
                if (str_contains($h, 'affiliate')) {
                    return 'affiliate_url';
                }
                if (str_contains($h, 'image') || str_contains($h, 'photo') || str_contains($h, 'img')) {
                    return 'image';
                }
                if (str_contains($h, 'url') || str_contains($h, 'link')) {
                    return 'external_url';
                }
                if (str_contains($h, 'brand') || str_contains($h, 'manufacturer')) {
                    return 'brand_slug';
                }
                if (str_contains($h, 'seller') || str_contains($h, 'store') || str_contains($h, 'shop') || str_contains($h, 'merchant') || str_contains($h, 'vendor')) {
                    return 'merchant_slug';
                }
                if (str_contains($h, 'sku') || str_contains($h, 'product_code') || str_contains($h, 'item_code')) {
                    return 'sku';
                }

                return $h;
            },
            $headers
        );
    }
}
