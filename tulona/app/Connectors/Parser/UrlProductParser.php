<?php

namespace App\Connectors\Parser;

use App\Contracts\Merchant\ProductParser;

/**
 * Dispatcher parser for URL scraping (§14, §31). Sniffs the payload format and
 * delegates to the matching concrete parser. Recognises:
 *  - JSON (a raw array, {"products": ...}, or JSON-LD @graph)
 *  - CSV feeds
 *  - HTML product-list pages (via JSON-LD extraction or DOM selectors)
 */
class UrlProductParser implements ProductParser
{
    public function __construct(
        protected JsonProductParser $jsonParser,
        protected CsvProductParser $csvParser,
        protected HtmlProductParser $htmlParser,
    ) {}

    public function parse(string $raw, array $config): iterable
    {
        $format = $this->sniff($raw, $config);

        $parser = match ($format) {
            'html' => $this->htmlParser,
            'csv' => $this->csvParser,
            default => $this->jsonParser,
        };

        yield from $parser->parse($raw, $config);
    }

    /**
     * Delegate pagination discovery to the underlying format parser (HTML pages
     * yield next-page URLs). Non-HTML feeds return no pagination.
     */
    public function paginationUrls(string $raw, array $config, string $baseUrl): array
    {
        $format = $this->sniff($raw, $config);

        if ($format !== 'html') {
            return [];
        }

        return $this->htmlParser->paginationUrls($raw, $config, $baseUrl);
    }

    public function supports(string $method): bool
    {
        return in_array($method, ['url', 'scrape', 'html', 'api', 'json', 'feed'], true);
    }

    protected function sniff(string $raw, array $config): string
    {
        $forced = strtolower($config['parser'] ?? $config['scraper_type'] ?? '');
        if (in_array($forced, ['json', 'csv', 'html'], true)) {
            return $forced;
        }

        $trimmed = ltrim($raw);
        $first = substr($trimmed, 0, 1);

        if (in_array($first, ['{', '['], true)) {
            return 'json';
        }

        if (str_starts_with(strtolower($trimmed), '<!doctype') || str_starts_with(strtolower($trimmed), '<html')) {
            return 'html';
        }

        return 'csv';
    }
}
