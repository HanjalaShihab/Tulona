<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Merchant;
use App\Models\ProductDraft;
use App\Services\Scraping\UrlScrapeService;

/**
 * "Generate from URL" — scrapes a merchant product-list page (JSON feed, JSON-LD
 * or an HTML category page) and turns every found product into an editable
 * ProductDraft row, exactly like the "Scrape & Post" single-product flow but for
 * a whole listing in one go.
 *
 * Reuses the full UrlScrapeService pipeline (pagination walk + per-product
 * detail-page fetch + normalization + matching) synchronously by staging into a
 * throwaway ImportBatch and immediately converting its rows into drafts — so it
 * works locally without a queue worker. Nothing is published automatically; the
 * admin reviews and edits each draft, then posts them all or one at a time.
 */
class UrlDraftService
{
    public function __construct(
        protected UrlScrapeService $scraper,
    ) {}

    /**
     * @return array{created: int, errors: int, error?: string}
     */
    public function scrapeToDrafts(string $url, ?int $categoryId = null, ?int $merchantId = null): array
    {
        $merchantId = $merchantId ?? $this->resolveMerchantFromUrl($url);

        $batch = ImportBatch::create([
            'filename' => 'url-draft',
            'type' => 'url',
            'source_type' => 'url',
            'source_url' => $url,
            'category_slug' => $this->resolveCategorySlug($categoryId),
            'merchant_id' => $merchantId,
            'status' => 'queued',
            'total_rows' => 0,
            'created_by' => auth()->id(),
        ]);

        try {
            $this->scraper->scrape($batch);

            $created = 0;
            $errors = 0;

            foreach ($batch->items()->whereIn('status', ['new', 'matched', 'duplicate'])->get() as $item) {
                $payload = $this->toDraftPayload($item->normalized_data ?? [], $batch);
                ProductDraft::create([
                    'data' => $payload,
                    'merchant_id' => $payload['merchant_id'] ?? $merchantId,
                    'created_by' => auth()->id(),
                    'status' => 'draft',
                ]);
                $created++;
            }

            // Rows that failed to parse are surfaced as an error count so the admin
            // knows not every product on the page made it into a draft.
            $errors = $batch->items()->where('status', 'error')->count();

            $lastError = null;
            if ($created === 0 && $errors > 0) {
                $lastError = optional($batch->items()->where('status', 'error')->latest('id')->first())->error;
            }

            return ['created' => $created, 'errors' => $errors, 'error' => $lastError];
        } finally {
            // The temp batch was only a staging area for the scraped rows — drafts
            // now own the data, so the batch (and its items) are cleaned up.
            $batch->items()->delete();
            $batch->delete();
        }
    }

    protected function toDraftPayload(array $n, ImportBatch $batch): array
    {
        $images = $n['images'] ?? [];

        return [
            'name' => (string) ($n['name'] ?? ''),
            'description' => (string) ($n['description'] ?? ''),
            'category_slug' => (string) ($n['category_slug'] ?? ($batch->category_slug ?? '')),
            'category_id' => null,
            'subcategory' => null,
            'merchant_id' => $batch->merchant_id ?? $this->resolveMerchantFromUrl($batch->source_url),
            'affiliate_url' => (string) ($n['affiliate_url'] ?? ''),
            'external_url' => (string) ($n['external_url'] ?? ($batch->source_url ?? '')),
            'current_price' => isset($n['price']) && $n['price'] !== null && $n['price'] !== ''
                ? (string) $n['price'] : null,
            'original_price' => isset($n['original_price']) && $n['original_price'] !== null && $n['original_price'] !== ''
                ? (string) $n['original_price'] : null,
            'currency' => strtoupper((string) ($n['currency'] ?? 'BDT')) ?: 'BDT',
            'availability' => $n['availability'] ?? 'unknown',
            'gtin' => (string) ($n['gtin'] ?? ''),
            'model_number' => (string) ($n['model_number'] ?? ''),
            'sku' => (string) ($n['sku'] ?? ''),
            'image' => (string) ($images[0] ?? ''),
            'images' => $images,
            'is_trending' => false,
            'is_featured' => false,
            'is_top_selling' => false,
        ];
    }

    protected function resolveCategorySlug(?int $categoryId): ?string
    {
        if ($categoryId === null) {
            return null;
        }

        return Category::find($categoryId)?->slug;
    }

    protected function resolveMerchantFromUrl(?string $url): ?int
    {
        if (empty($url)) {
            return null;
        }

        $host = $this->bareHost(parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return null;
        }

        foreach (Merchant::where('status', 'active')->get(['id', 'website_url', 'base_affiliate_url']) as $merchant) {
            foreach ([$merchant->website_url, $merchant->base_affiliate_url] as $candidate) {
                $candidateHost = $candidate ? $this->bareHost(parse_url($candidate, PHP_URL_HOST)) : '';
                if ($candidateHost !== '' && (str_ends_with($host, $candidateHost) || str_ends_with($candidateHost, $host))) {
                    return $merchant->id;
                }
            }
        }

        return null;
    }

    /** Lowercase host without a leading "www." so www.startech.com.bd matches startech.com.bd. */
    protected function bareHost(?string $host): string
    {
        $host = strtolower(trim((string) $host));

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
