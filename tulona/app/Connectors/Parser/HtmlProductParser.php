<?php

namespace App\Connectors\Parser;

use App\Contracts\Merchant\ProductParser;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * HTML category/product-list page parser (§14, §31).
 *
 * Strategy, in order of reliability:
 *  1. JSON-LD structured data embedded in <script type="application/ld+json"> —
 *      most shop fronts (incl. many BD merchants) expose Product / ItemList /
 *      OfferCatalog / @graph nodes. Delegated to the JSON parser.
 *  2. DOM heuristics over product cards using optional per-merchant selectors
 *      from the merchant configuration (html.product_selector etc.).
 *
 * Selectors (CSS-like, comma separated) read from $config:
 *  - product          root element per product card
 *  - name, price, original_price, sku, link, image, availability, description
 *  If no selectors are configured, it yields nothing (JSON-LD path only) so the
 *  preview can tell the admin that an HTML selector config is required.
 */
class HtmlProductParser implements ProductParser
{
    public function __construct(protected JsonProductParser $jsonParser) {}

    public function parse(string $raw, array $config): iterable
    {
        $ld = $this->extractJsonLd($raw);

        if (! empty($ld)) {
            yield from $this->jsonParser->parse(json_encode($ld, JSON_UNESCAPED_SLASHES), $config);

            return;
        }

        yield from $this->parseDom($raw, $config);
    }

    /**
     * Discover pagination page URLs from an HTML listing page.
     *
     * Strategy: collect links from a pagination container (configurable via
     * html.pagination_selector, defaulting to common containers), or fall back
     * to any same-origin link whose query carries a page-like param. All URLs
     * are resolved to absolute and de-duplicated; the requesting page itself is
     * excluded so the caller can walk pagination without looping.
     */
    public function paginationUrls(string $raw, array $config, string $baseUrl): array
    {
        $containerSel = $config['html']['pagination_selector'] ?? '.pagination, .pager, .pagination-nav, nav.pagination, .pagination-wrap';

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$raw, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        if (! $loaded) {
            return [];
        }

        $xpath = new DOMXPath($dom);

        $links = [];
        $containers = $this->queryAll($xpath, $containerSel);
        if (! empty($containers)) {
            foreach ($containers as $container) {
                foreach ($xpath->query('.//a[@href]', $container) as $a) {
                    if ($a instanceof DOMElement) {
                        $links[] = $a->getAttribute('href');
                    }
                }
            }
        }

        if (empty($links)) {
            foreach ($xpath->query('//a[@href]') as $a) {
                if ($a instanceof DOMElement) {
                    $href = $a->getAttribute('href');
                    if (preg_match('/[?&](page|pageNumber|pg|paged|page_num)=/i', $href)) {
                        $links[] = $href;
                    }
                }
            }
        }

        $baseParts = parse_url($baseUrl);
        $origin = ($baseParts['scheme'] ?? 'https').'://'.($baseParts['host'] ?? '');

        $pages = [];
        foreach ($links as $href) {
            $absolute = $this->resolveUrl(trim($href), $baseUrl);
            if (! $absolute || $absolute === $baseUrl) {
                continue;
            }
            $parts = parse_url($absolute);
            if (($parts['scheme'] ?? '').'://'.($parts['host'] ?? '') !== $origin) {
                continue;
            }
            $pages[$absolute] = true;
        }

        return array_keys($pages);
    }

    protected function resolveUrl(string $href, string $baseUrl): ?string
    {
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';

        if (str_starts_with($href, '//')) {
            return $scheme.':'.$href;
        }
        if (str_starts_with($href, '/')) {
            return $scheme.'://'.$host.$href;
        }

        $path = preg_replace('#/[^/]*$#', '/', $base['path'] ?? '/');

        return $scheme.'://'.$host.$path.$href;
    }

    public function supports(string $method): bool
    {
        return in_array($method, ['html', 'url', 'scrape'], true);
    }

    protected function extractJsonLd(string $raw): array
    {
        $blocks = [];
        if (preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $raw, $m)) {
            foreach ($m[1] as $json) {
                $decoded = json_decode(trim($json), true);
                if (is_array($decoded)) {
                    $blocks[] = $decoded;
                }
            }
        }

        return $blocks;
    }

    protected function parseDom(string $raw, array $config): iterable
    {
        $productSel = $config['html']['product_selector'] ?? null;
        if (empty($productSel) || (strlen($raw) > 4000000)) {
            return;
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$raw, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        if (! $loaded) {
            return;
        }

        $xpath = new DOMXPath($dom);
        $nodes = $this->queryAll($xpath, $productSel);

        foreach ($nodes as $node) {
            $row = $this->rowFromNode($xpath, $node, $config['html'] ?? []);
            if (isset($row['name'])) {
                yield $row;
            }
        }
    }

    protected function rowFromNode(DOMXPath $xpath, DOMElement $node, array $conf): array
    {
        $nameSel = $conf['name'] ?? null;
        $name = $nameSel ? $this->queryText($xpath, $node, $nameSel) : trim((string) $node->getAttribute('title'));

        $priceRaw = $this->queryText($xpath, $node, $conf['price'] ?? null);
        $originalRaw = $this->queryText($xpath, $node, $conf['original_price'] ?? null);

        return [
            'name' => $name,
            'price' => $this->cleanPrice($priceRaw),
            'original_price' => $this->cleanPrice($originalRaw),
            'currency' => 'BDT',
            'affiliate_url' => null,
            'external_url' => $this->queryHref($xpath, $node, $conf['link'] ?? null),
            'sku' => $this->queryText($xpath, $node, $conf['sku'] ?? null),
            'availability' => null,
            'description' => $this->queryText($xpath, $node, $conf['description'] ?? null),
        ];
    }

    protected function cleanPrice(string $raw): ?float
    {
        $clean = str_replace(['৳', 'Tk', 'BDT', ',', ' ', 'TK'], '', $raw);
        $clean = trim($clean);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function queryText(DOMXPath $xpath, DOMElement $node, ?string $selector): string
    {
        if (! $selector) {
            return '';
        }
        $el = $this->queryAll($xpath, $selector, $node)[0] ?? null;

        return trim((string) ($el instanceof DOMElement ? $el->textContent : ''));
    }

    protected function queryHref(DOMXPath $xpath, DOMElement $node, ?string $selector): ?string
    {
        if (! $selector) {
            return null;
        }
        $el = ($this->queryAll($xpath, $selector, $node)[0] ?? null) ?: $node;

        return $el instanceof DOMElement ? ($el->getAttribute('href') ?: null) : null;
    }

    protected function queryAll(DOMXPath $xpath, string $selector, ?DOMElement $ctx = null): array
    {
        $out = [];
        $root = $ctx ? './/' : '//';

        // Split on commas into independent selector alternatives.
        foreach (array_map('trim', explode(',', $selector)) as $alternative) {
            if ($alternative === '') {
                continue;
            }

            $nodes = $xpath->query($root.$this->compileSelector($alternative), $ctx);
            if (! $nodes) {
                continue;
            }

            foreach ($nodes as $n) {
                if ($n instanceof DOMElement) {
                    $out[] = $n;
                }
            }
        }

        return $out;
    }

    /**
     * Turn a small subset of CSS into an XPath expression. Supports descendant
     * (whitespace) and child (>) combinators, tag names, class tokens (.x), and
     * compound tokens like a.x. Used with a context node via ".//" or globally
     * via "//".
     */
    protected function compileSelector(string $selector): string
    {
        $tokens = preg_split('/\s+/', trim($selector));
        $xpath = '';
        $first = true;

        foreach ($tokens as $token) {
            if ($token === '>') {
                $xpath .= '/';
                $first = false;

                continue;
            }

            if (! $first && ! str_ends_with($xpath, '/')) {
                $xpath .= '//';
            }
            $xpath .= $this->tokenToXPath($token);
            $first = false;
        }

        return $xpath === '' ? '*' : $xpath;
    }

    protected function tokenToXPath(string $token): string
    {
        $tag = '*';
        $classes = [];

        if (preg_match_all('/\.([a-zA-Z0-9_-]+)/', $token, $m)) {
            $classes = $m[1];
        }

        $tagMatch = preg_replace('/\.[a-zA-Z0-9_-]+/', '', $token);
        if ($tagMatch !== '') {
            $tag = $tagMatch;
        }

        $pred = array_map(
            fn (string $class) => 'contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")',
            $classes
        );

        return $tag.($pred ? '['.implode(' and ', $pred).']' : '');
    }
}
