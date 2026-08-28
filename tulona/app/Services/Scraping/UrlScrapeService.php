<?php

namespace App\Services\Scraping;

use App\Connectors\Parser\HtmlProductParser;
use App\Models\ImportBatch;
use App\Services\Merchant\ConnectorRegistry;
use Illuminate\Support\Facades\Log;

/**
 * §14 + §16 URL scraping pipeline, always invoked from a queued job (§15).
 * Fetches a merchant commission/product-list URL, parses rows with the
 * merchant's connector, normalizes, matches canonical products, then stages a
 * preview of import_items (new / matched / duplicate / error) for admin review.
 */
class UrlScrapeService
{
    public function __construct(
        protected UrlFetcher $fetcher,
        protected ConnectorRegistry $registry,
        protected ProductMatcher $matcher,
        protected HtmlProductParser $htmlParser,
    ) {}

    public function scrape(ImportBatch $batch): void
    {
        $merchant = $batch->merchant;
        abort_unless($merchant, 422, 'This batch has no merchant connected.');

        $url = $batch->source_url;
        abort_unless($url && filter_var($url, FILTER_VALIDATE_URL), 422, 'Invalid source URL.');

        $batch->update(['status' => 'processing']);

        $connector = $this->registry->get('url'); // URL-aware parser regardless of merchant's csv/api connector
        $config = $merchant->configuration ?? [];
        $parser = $connector->parser();

        // Safety ceiling against runaway/infinite pagination. Defaults to 1 so
        // only the products on the given (first) page are generated — a merchant
        // may opt into more via config html.max_pages.
        $maxPages = max(1, (int) ($config['html']['max_pages'] ?? 1));

        $counts = ['total' => 0, 'matched' => 0, 'new' => 0, 'duplicate' => 0, 'error' => 0, 'pages' => 0, 'details' => 0];

        // Cap on per-product detail-page fetches during a single scrape so a very
        // large first page still completes in one request cycle.
        $maxDetails = max(0, (int) ($config['html']['max_details'] ?? 200));

        $batch->items()->delete(); // re-staged preview replaces any earlier one

        // Walk pagination: fetch a page, stage its rows, then queue the page
        // links discovered in its HTML. A visited set guarantees termination.
        $visited = [];
        $queued = [];
        $queue = [];
        $seenKeys = [];
        $stagedRows = [];
        $errorRows = [];

        foreach ($this->normalizePageUrls([$url]) as $seed) {
            if (! isset($queued[$seed]) && ! isset($visited[$seed])) {
                $queued[$seed] = true;
                $queue[] = $seed;
            }
        }

        while (! empty($queue) && $counts['pages'] < $maxPages) {
            $pageUrl = array_shift($queue);
            unset($queued[$pageUrl]);
            if (isset($visited[$pageUrl])) {
                continue;
            }
            $visited[$pageUrl] = true;
            $counts['pages']++;

            $raw = $this->fetcher->fetch($pageUrl);

            // Make the current page URL available to HTML parsers so they can
            // resolve relative product links/images discovered on the page.
            $pageConfig = array_merge($config, ['_base_url' => $pageUrl]);

            foreach ($parser->parse($raw, $pageConfig) as $rawRow) {
                $counts['total']++;

                try {
                    // Follow each product's own page to fetch full details (images,
                    // description, more accurate price/SKU/availability). Merged over
                    // the listing row so listing data is preferred where present.
                    if (! empty($rawRow['external_url'])
                        && $counts['details'] < $maxDetails
                        && ($config['html']['fetch_details'] ?? true)) {
                        $counts['details']++;
                        try {
                            $detailRow = $this->htmlParser->detail(
                                $this->fetcher->fetch($rawRow['external_url']),
                                $rawRow['external_url'],
                                $config,
                            );
                            // Detail fills gaps but never overrides listing data that
                            // is already present (listing is the more reliable source
                            // for the current price on the category page).
                            $listing = array_filter($rawRow, fn ($v) => $v !== null && $v !== '');
                            $rawRow = array_merge($detailRow, $listing);
                        } catch (\Throwable $e) {
                            Log::debug('Detail fetch skipped', ['url' => $rawRow['external_url'], 'error' => $e->getMessage()]);
                        }
                    }

                    $normalized = $connector->normalizer()->normalize($rawRow, $config);

                    if (empty($normalized['name'])) {
                        throw new \RuntimeException('Row has no product name.');
                    }

                    // De-duplicate rows already staged from an earlier page.
                    $dedupeKey = strtolower(trim((string) ($normalized['sku'] ?? $normalized['model_number'] ?? $normalized['name'])));
                    if ($dedupeKey !== '') {
                        if (isset($seenKeys[$dedupeKey])) {
                            continue;
                        }
                        $seenKeys[$dedupeKey] = true;
                    }

                    // Fall back to the admin-chosen category for the scrape when
                    // the listing row exposes no category (typical for HTML pages).
                    if (empty($normalized['category_slug']) && ! empty($batch->category_slug)) {
                        $normalized['category_slug'] = $batch->category_slug;
                    }

                    // Normalize merchant-provided category to a canonical slug when
                    // the connector defines a mapping (§32).
                    if ($normalized['category_slug'] ?? false) {
                        $canonical = $connector->categoryMapper()->canonicalSlug($normalized['category_slug'], $merchant);
                        $normalized['category_slug'] = $canonical ?? $normalized['category_slug'];
                    }

                    $product = $this->matcher->exact($normalized);
                    $matchType = $this->matcher->matchTypeFor($normalized, $product);

                    // Name-only hits are flagged for review instead of auto-attaching.
                    if ($product === null) {
                        $product = $this->matcher->potential($normalized);
                        $matchType = $product === null ? 'none' : 'name';
                    }

                    $counts[$product === null ? 'new' : ($matchType === 'name' ? 'duplicate' : 'matched')]++;

                    $stagedRows[] = [
                        'import_batch_id' => $batch->id,
                        'source_identifier' => $normalized['sku'] ?? $normalized['model_number'] ?? $matchType,
                        'raw_data' => $rawRow,
                        'normalized_data' => $normalized,
                        'product_id' => $product?->id,
                        'match_type' => $matchType,
                        'status' => $product === null ? 'new' : ($matchType === 'name' ? 'duplicate' : 'matched'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } catch (\Throwable $e) {
                    $counts['error']++;
                    $errorRows[] = [
                        'import_batch_id' => $batch->id,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'normalized_data' => $normalized ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    Log::debug('Scrape row skipped', ['batch' => $batch->id, 'url' => $pageUrl, 'error' => $e->getMessage()]);
                }
            }

            if (method_exists($parser, 'paginationUrls')) {
                foreach ($this->normalizePageUrls($parser->paginationUrls($raw, $config, $pageUrl)) as $nextUrl) {
                    if (! isset($visited[$nextUrl]) && ! isset($queued[$nextUrl])) {
                        $queued[$nextUrl] = true;
                        $queue[] = $nextUrl;
                    }
                }
            }
        }

        // Bulk-insert staged rows (and errors) in chunks — avoids one INSERT per
        // row and keeps the preview table fast for large paginated catalogues.
        $rows = array_merge($stagedRows, $errorRows);
        foreach (array_chunk($rows, 200) as $chunk) {
            $chunk = array_map(function (array $row) {
                $row['raw_data'] = json_encode($row['raw_data'] ?? null);
                $row['normalized_data'] = json_encode($row['normalized_data'] ?? null);

                return $row;
            }, $chunk);
            $batch->items()->insert($chunk);
        }

        $batch->update([
            'status' => 'preview',
            'total_rows' => $counts['total'],
            'created_count' => $counts['new'],
            'updated_count' => $counts['matched'],
            'skipped_count' => $counts['duplicate'],
            'failed_count' => $counts['error'],
        ]);
    }

    /**
     * Collapse page markers that point at the first page (e.g. "?page=1" is the
     * same as the bare listing) so they deduplicate against the seed URL, then
     * de-duplicate the list. Keeps pagination walks from re-fetching page one.
     */
    protected function normalizePageUrls(array $urls): array
    {
        $seen = [];
        $out = [];

        foreach ($urls as $url) {
            $parts = parse_url((string) $url);
            if (! isset($parts['query'])) {
                $normalized = $url;
            } else {
                parse_str($parts['query'], $query);
                foreach (['page', 'p', 'pageNumber', 'pg', 'paged'] as $key) {
                    if (isset($query[$key]) && (string) $query[$key] === '1') {
                        unset($query[$key]);
                    }
                }

                $built = $parts['scheme'].'://'.$parts['host'];
                if (isset($parts['port'])) {
                    $built .= ':'.$parts['port'];
                }
                $built .= ($parts['path'] ?? '/');
                if (! empty($query)) {
                    $built .= '?'.http_build_query($query);
                }
                $normalized = $built;
            }

            if (! isset($seen[$normalized])) {
                $seen[$normalized] = true;
                $out[] = $normalized;
            }
        }

        return $out;
    }
}
