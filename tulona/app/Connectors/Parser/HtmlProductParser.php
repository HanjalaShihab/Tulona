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

    /** Resolve a category by slug/name, merging with an existing one or creating it. */
    public function supports(string $method): bool
    {
        return in_array($method, ['html', 'url', 'scrape'], true);
    }

    /**
     * Enrich a single product detail page — the result of following one of the
     * listing links. Tries JSON-LD first, then falls back to DOM/OpenGraph tags.
     * Returns an associative array of detail fields; missing fields are left out
     * so the caller can merge them over the listing row.
     */
    public function detail(string $raw, ?string $baseUrl = null, array $config = []): array
    {
        $details = [];

        $ld = $this->extractJsonLd($raw);
        foreach ($ld as $block) {
            $products = $this->collectProductNodes($block);
            foreach ($products as $node) {
                $row = (new JsonProductParser)->cleanRow($node);
                foreach (['name', 'price', 'original_price', 'sku', 'model_number', 'description', 'availability', 'currency', 'brand_slug'] as $k) {
                    if (($row[$k] ?? null) !== null) {
                        $details[$k] = $row[$k];
                    }
                }
            }
            if (! empty($details)) {
                break;
            }
        }

        // DOM fallback: OG tags + spec/label patterns on the detail page.
        $dom = new DOMDocument;
        $domPrice = null;
        $domOriginal = null;
        libxml_use_internal_errors(true);
        if ($dom->loadHTML('<?xml encoding="UTF-8">'.$raw, LIBXML_NOWARNING | LIBXML_NOERROR)) {
            $xpath = new DOMXPath($dom);
            libxml_clear_errors();

            $pickMeta = function (string $prop) use ($xpath): ?string {
                foreach (['//meta[@property="'.$prop.'"]', '//meta[@name="'.$prop.'"]', '//meta[@itemprop="'.$prop.'"]'] as $q) {
                    $nodes = $xpath->query($q);
                    if ($nodes && $nodes->length > 0 && ($n = $nodes->item(0)) instanceof DOMElement) {
                        $val = trim($n->getAttribute('content') ?: $n->getAttribute('value'));
                        if ($val !== '') {
                            return $val;
                        }
                    }
                }

                return null;
            };

            if (empty($details['name'])) {
                $details['name'] = $pickMeta('og:title') ?? $pickMeta('twitter:title');
            }
            if (empty($details['description'])) {
                $details['description'] = $pickMeta('og:description') ?? $pickMeta('twitter:description') ?? $pickMeta('description');
            }

            // Price: OpenGraph / microdata price tags first, then a full DOM scan
            // that distinguishes the discounted price from the <del>-tagged
            // original (see detailPrices below).
            if (empty($details['price'])) {
                $metaPrice = $pickMeta('product:price:amount') ?? $pickMeta('og:price:amount') ?? $pickMeta('price') ?? $pickMeta('sale-price');
                if ($metaPrice !== null && is_numeric(str_replace([',', '৳', 'Tk', 'BDT'], '', $metaPrice))) {
                    $details['price'] = (float) str_replace([',', '৳', 'Tk', 'BDT'], '', $metaPrice);
                }
            }
            if (empty($details['currency'])) {
                $details['currency'] = $pickMeta('product:price:currency') ?? $pickMeta('og:price:currency') ?? $pickMeta('priceCurrency');
            }

            // Images: og:image + twitter:image + <img> + [itemprop=image].
            $images = [];
            foreach (['og:image', 'twitter:image'] as $prop) {
                $v = $pickMeta($prop);
                if ($v) {
                    $images[] = $v;
                }
            }
            foreach (['//div[contains(@class,"product-image")]//img', '//*[@itemprop="image"]//img', '//*[@itemprop="image" and not(img)]', '//a[contains(@class,"gallery")]//img'] as $q) {
                $nodes = $xpath->query($q);
                if (! $nodes) {
                    continue;
                }
                foreach ($nodes as $img) {
                    if (! $img instanceof DOMElement) {
                        continue;
                    }
                    $src = $img->getAttribute('content') ?: $img->getAttribute('src') ?: $img->getAttribute('data-src');
                    if ($src) {
                        $images[] = $src;
                    }
                }
            }
            $images = array_values(array_unique(array_filter($images)));
            $details['images'] = array_map(fn ($u) => $baseUrl ? ($this->resolveUrl($u, $baseUrl) ?: $u) : $u, $images);

            // Availability from common markers.
            $bodyText = strtolower((string) $dom->textContent);
            if (empty($details['availability'])) {
                if (preg_match('~(currently )?(out of stock|oos)~', $bodyText)) {
                    $details['availability'] = 'out_of_stock';
                } elseif (preg_match('~in stock~', $bodyText)) {
                    $details['availability'] = 'in_stock';
                }
            }

            // SKU / model from common label patterns.
            if (empty($details['sku']) || empty($details['model_number'])) {
                foreach ($xpath->query('//*[contains(@class,"sku") or contains(@class,"model") or contains(@class,"mpn") or contains(@class,"product-code") or (self::*[@itemprop="sku"])]') as $el) {
                    if (! $el instanceof DOMElement) {
                        continue;
                    }
                    $t = trim((string) $el->textContent);
                    if ($t === '' || preg_match('/sku|model|product code|mpn/i', $t)) {
                        continue;
                    }
                    if (empty($details['sku'])) {
                        $details['sku'] = $t;
                    } elseif (empty($details['model_number'])) {
                        $details['model_number'] = $t;
                    }
                }
            }

            // The visual price pair on a detail page: discounted price beside a
            // <del> original. Recommendation containers are excluded so the main
            // product's prices don't leak from "related products" blocks.
            [$domPrice, $domOriginal] = $this->detailPrices($xpath);
        }

        // A visible discounted + <del> original pair in the DOM is what the
        // shopper actually sees, so it wins over a JSON-LD reference price.
        // Otherwise keep the structured price and fill the gaps from DOM.
        if ($domPrice !== null && $domOriginal !== null) {
            $details['price'] = $domPrice;
            $details['original_price'] = $domOriginal;
        } else {
            if (($details['price'] ?? null) === null) {
                $details['price'] = $domPrice;
            }
            if (($details['original_price'] ?? null) === null) {
                $details['original_price'] = $domOriginal;
            }
        }

        return $details;
    }

    /**
     * Page-level price scan for a single detail page.
     *
     * A detail page usually shows the discounted price beside the <del>-tagged
     * original. This scans every price-looking element (excluding recommendation /
     * related-product containers whose prices must not leak into the main pair),
     * classifies del/s/old/regular/strike tokens as the ORIGINAL price and
     * everything else as the current price, then returns the shortest (leaf)
     * value of each.
     *
     * @return array{0: float|null, 1: float|null} [current, original]
     */
    protected function detailPrices(DOMXPath $xpath): array
    {
        $skipClasses = ['related', 'recommend', 'recommendation', 'suggestion', 'suggested', 'upsell', 'cross-sell', 'also-like', 'you-may', 'similar', 'trending', 'carousel', 'more-from', 'other-products', 'product-slider', 'best-seller'];

        $conditions = [];
        foreach ($skipClasses as $class) {
            $conditions[] = 'contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")';
        }
        foreach ($xpath->query('//*['.implode(' or ', $conditions).']') as $node) {
            if ($node instanceof DOMElement) {
                $node->setAttribute('data-tulona-skip', '1');
            }
        }

        $current = [];
        $original = [];

        foreach ($xpath->query('//*[not(ancestor-or-self::*[@data-tulona-skip])]') as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }
            $t = trim((string) $el->textContent);
            if ($t === '') {
                continue;
            }
            if (preg_match('/([0-9][0-9,]*)\s*(?:৳|\$|Tk|BDT)/iu', $t, $m) || preg_match('/(?:৳|\$|Tk|BDT)\s*([0-9][0-9,]*)/iu', $t, $m)) {
                $isOriginal = $el->nodeName === 'del' || $el->nodeName === 's'
                    || $this->hasClass($el, ['old', 'original', 'regular', 'compare', 'list', 'msrp', 'strike', 'was']);
                if ($isOriginal) {
                    $original[$m[1]] = strlen($t);
                } else {
                    $current[$m[1]] = strlen($t);
                }
            }
        }

        $pick = function (array $map): ?float {
            if ($map === []) {
                return null;
            }
            // Prefer the shortest text: the leaf price element, not a wrapper
            // that concatenates several prices.
            asort($map);

            return (float) str_replace(',', '', (string) array_key_first($map));
        };

        return [$pick($current), $pick($original)];
    }

    protected function collectProductNodes(array $data): array
    {
        $out = [];
        foreach (['@graph', 'itemListElement', 'mainEntity'] as $key) {
            $nodes = $data[$key] ?? null;
            if (is_array($nodes) && array_is_list($nodes)) {
                foreach ($nodes as $n) {
                    if (is_array($n) && ($n['@type'] ?? null) === 'Product') {
                        $out[] = $n;
                    }
                }
            }
        }
        if (empty($out) && ($data['@type'] ?? null) === 'Product') {
            $out[] = $data;
        }

        return $out;
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

    public function defaultHtmlConfig(): array
    {
        return [
            'product_selector' => '.product, .product-item, .product-card, .card-product, .product-list-item, li.product, li.item, .item.product, .product-box, tr.latest-product, .latest-product, [class*="product-card"], [class*="product-tile"], [class*="product-list"] > [class*="product"]',
            'name' => '.product-name, .p-name, .product-name a, .product-title, .product-title a, .name, .title, h2, h3, h4, a[title], a[aria-label]',
            'price' => '.price, .p-price, .product-price, .amount, .current-price, .offer-price, .sale-price, .now-price, [class*="price"]',
            'original_price' => '.old-price, .original-price, .compare-price, .regular-price, .price-before, del, s, .old, [class*="old-price"], [class*="regular-price"]',
            'link' => 'a',
        ];
    }

    protected function parseDom(string $raw, array $config): iterable
    {
        if (strlen($raw) > 4000000) {
            return;
        }

        $defaults = $this->defaultHtmlConfig();
        $htmlConf = array_merge($defaults, $config['html'] ?? []);
        $htmlConf['_base_url'] = $htmlConf['_base_url'] ?? ($config['_base_url'] ?? null);

        $productSel = $htmlConf['product_selector'] ?? null;
        if (empty($productSel)) {
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

        $rows = [];
        foreach ($nodes as $node) {
            $row = $this->rowFromNode($xpath, $node, $htmlConf);
            if (isset($row['name'])) {
                $rows[] = $row;
            }
        }

        // Generic fallback: only useful once per domain — if selectors caught
        // enough products, don't risk false positives from the heuristics.
        if (count($rows) < 2) {
            $rows = array_merge($rows, iterator_to_array($this->detectUniversal($xpath, $dom, $htmlConf)));
        }

        foreach ($rows as $row) {
            yield $row;
        }
    }

    /**
     * Universal detection that does not rely on store-specific class names.
     *
     * Scans every product-like <a href> on the page (a link pointing to a
     * product detail URL, ideally wrapping an image), then extracts:
     *  - name      → image alt → link title → link text
     *  - link      → absolute product URL
     *  - image     → the first image URL found on/near the link
     *  - price     → the nearest price token in the link's ancestor subtree
     * Rows are de-duplicated by link URL.
     */
    protected function detectUniversal(DOMXPath $xpath, DOMDocument $dom, array $htmlConf): iterable
    {
        $seen = [];
        $rows = [];
        $base = isset($htmlConf['_base_url']) ? $htmlConf['_base_url'] : null;
        $baseOrigin = $base ? (parse_url($base)['scheme'] ?? 'https').'://'.(parse_url($base)['host'] ?? '') : null;

        foreach ($xpath->query('//a[@href]') as $a) {
            if (! $a instanceof DOMElement) {
                continue;
            }
            $href = trim($a->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }
            $abs = $this->resolveUrl($href, $base ?? '');
            if (! $abs || filter_var($abs, FILTER_VALIDATE_URL) === false) {
                continue;
            }
            $host = parse_url($abs)['host'] ?? '';
            if ($baseOrigin && ($baseOrigin !== (parse_url($abs)['scheme'] ?? 'https').'://'.$host)) {
                continue;
            }

            // Skip obvious non-product links (nav/menu/login/cart/tags/authors/pages/pagination).
            // Skip obvious non-product links (root, nav/menu/login/cart/tags/pagination).
            $path = parse_url($abs, PHP_URL_PATH) ?? '';
            if (in_array($path, ['', '/'], true) || preg_match('~/tag/|/category/|/page/|(login|cart|checkout|search|contact|about|account|wishlist)~i', $abs) || isset($seen[$abs])) {
                continue;
            }
            $seen[$abs] = true;

            // Prefer anchors that contain an image (typical product cards), but
            // accept any anchor with a non-navigational href as a candidate.
            $links = [$a];
            $imgs = $xpath->query('.//img', $a);
            $image = null;
            if ($imgs && $imgs->length > 0 && ($first = $imgs->item(0)) instanceof DOMElement) {
                $image = $first->getAttribute('src') ?: $first->getAttribute('data-src');
            }

            $name = $a->getAttribute('title');
            if ($name === '' && $imgs && $imgs->length > 0 && ($first = $imgs->item(0)) instanceof DOMElement) {
                $name = $first->getAttribute('alt') ?: '';
            }
            if ($name === '') {
                $name = trim((string) $a->textContent);
            }
            if (mb_strlen($name) < 3) {
                continue;
            }

            [$price, $original] = $this->nearestPrice($a, $xpath);

            // Out-of-stock cards expose no price.
            $outOfStock = $this->isCardOutOfStock($a, $xpath);
            if ($outOfStock) {
                $price = null;
                $original = null;
            }

            // Only accept when we have a strong product signal (an image or a
            // price) — a bare text link (nav, "about", blog, footer) is too
            // ambiguous to risk staging as a product.
            if ($image === null && $price === null) {
                continue;
            }

            if ($image !== null) {
                $image = $this->resolveUrl($image, $abs) ?: $image;
            }

            $rows[] = [
                'name' => trim($name),
                'price' => $price,
                'original_price' => $original,
                'currency' => 'BDT',
                'affiliate_url' => null,
                'external_url' => $abs,
                'sku' => null,
                'availability' => $outOfStock ? 'out_of_stock' : null,
                'description' => null,
                'image' => $image,
            ];
        }

        yield from $rows;
    }

    /**
     * Find a product's price without leaking a sibling card's price.
     *
     * Walks up from the anchor and collects price-looking text from each
     * single-product scope (an ancestor whose subtree holds exactly one product
     * link). It stops ascending the moment it reaches an ancestor that contains
     * more than one product link — that is the grid/menu boundary holding
     * SIBLING products, whose prices must never be attributed to this product.
     * Out-of-stock cards yield no price.
     *
     * Returns [price, original] as clean floats (null when unavailable).
     */
    protected function nearestPrice(DOMElement $node, DOMXPath $xpath): array
    {
        $price = null;
        $original = null;
        $cur = $node;

        for ($depth = 0; $depth < 5 && $cur; $depth++) {
            // Once the scope spans more than one product link we've reached the
            // sibling container — stop so a neighbouring card's price can't leak.
            if ($depth > 0 && $this->countProductLinks($xpath, $cur) > 1) {
                break;
            }

            // Out-of-stock card → no price to pick up.
            if ($this->isOutOfStockScope($cur)) {
                break;
            }

            [$p, $o] = $this->priceInScope($xpath, $cur);
            if ($p !== null && $price === null) {
                $price = $p;
            }
            if ($o !== null && $original === null) {
                $original = $o;
            }
            if ($price !== null) {
                break;
            }

            $cur = $cur->parentNode;
        }

        return [$price, $original];
    }

    /** Count distinct product-like link URLs within a scope's subtree. */
    protected function countProductLinks(DOMXPath $xpath, DOMElement $scope): int
    {
        $hrefs = [];
        foreach ($xpath->query('.//a[@href]', $scope) as $a) {
            if (! $a instanceof DOMElement) {
                continue;
            }
            $h = $a->getAttribute('href');
            if ($h !== '' && ! str_starts_with($h, '#') && ! str_starts_with($h, 'javascript:')) {
                $hrefs[trim($h)] = true;
            }
        }

        return count($hrefs);
    }

    protected function isOutOfStockScope(DOMElement $scope): bool
    {
        $text = mb_strtolower(trim((string) $scope->textContent));
        if ($text === '') {
            return false;
        }

        // No leading \b on the phrase: DOM textContent concatenates siblings with
        // no separator, so "Product B" + "Out of stock" becomes "product bout of
        // stock" — a word boundary before "out" would never match. Substring
        // matching of the distinctive phrase is safe and robust.
        return (bool) preg_match('/\b(out of stock|sold out|out-of-stock|currently unavailable|not in stock|not available|oos)\b/', $text)
            || (bool) preg_match('/(out of stock|sold out|out-of-stock|currently unavailable|not in stock|not available)/', $text);
    }

    /**
     * Check whether the anchor belongs to an out-of-stock card, walking up the
     * anchor's ancestors but stopping at the sibling-product boundary (the same
     * scope rule as nearestPrice). This catches "out of stock" markers that sit
     * as siblings of the product link inside its card.
     */
    protected function isCardOutOfStock(DOMElement $node, DOMXPath $xpath): bool
    {
        $cur = $node;

        for ($depth = 0; $depth < 5 && $cur; $depth++) {
            // Stop at the sibling-product boundary before inspecting the scope so
            // a neighbouring product's "out of stock" text can't bleed over.
            if ($depth > 0 && $this->countProductLinks($xpath, $cur) > 1) {
                break;
            }
            if ($this->isOutOfStockScope($cur)) {
                return true;
            }
            $cur = $cur->parentNode;
        }

        return false;
    }

    /** Collect the first (most specific) price + original price from a scope. */
    protected function priceInScope(DOMXPath $xpath, DOMElement $scope): array
    {
        $candidates = [];
        $originalCandidates = [];

        foreach ($scope->getElementsByTagName('*') as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }
            $t = trim((string) $el->textContent);
            if ($t === '') {
                continue;
            }
            if (preg_match('/([0-9][0-9,]*)\s*(?:৳|\$|Tk|BDT)/iu', $t, $m) || preg_match('/(?:৳|\$|Tk|BDT)\s*([0-9][0-9,]*)/iu', $t, $m)) {
                if ($el->nodeName === 'del' || $this->hasClass($el, ['old', 'original', 'regular', 'sale'])) {
                    $originalCandidates[$m[1]] = strlen($t);
                } else {
                    $candidates[$m[1]] = strlen($t);
                }
            }
        }

        $pick = function (array $map) {
            if (empty($map)) {
                return null;
            }
            // Prefer the shortest text (a leaf price element, not a wrapper that
            // concatenates several values).
            asort($map);

            return (float) str_replace(',', '', (string) array_key_first($map));
        };

        return [$pick($candidates), $pick($originalCandidates)];
    }

    protected function hasClass(DOMElement $el, array $classes): bool
    {
        $own = preg_split('/\s+/', trim($el->getAttribute('class')));

        return count(array_intersect($own, $classes)) > 0;
    }

    protected function rowFromNode(DOMXPath $xpath, DOMElement $node, array $conf): array
    {
        $nameSel = $conf['name'] ?? null;
        $name = $nameSel ? $this->queryText($xpath, $node, $nameSel) : trim((string) $node->getAttribute('title'));

        $priceRaw = $this->queryText($xpath, $node, $conf['price'] ?? null);
        $originalRaw = $this->queryText($xpath, $node, $conf['original_price'] ?? null);

        // Out-of-stock cards: never assign a price to them (the scraped price
        // is meaningless / may leak from a neighbouring card).
        $outOfStock = $this->isOutOfStockScope($node);

        return [
            'name' => $name,
            'price' => $outOfStock ? null : $this->cleanPrice($priceRaw),
            'original_price' => $outOfStock ? null : $this->cleanPrice($originalRaw),
            'currency' => 'BDT',
            'affiliate_url' => null,
            'external_url' => $this->queryHref($xpath, $node, $conf['link'] ?? null),
            'sku' => $this->queryText($xpath, $node, $conf['sku'] ?? null),
            'availability' => $outOfStock ? 'out_of_stock' : null,
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

        // Prefer the most specific/leaf match: among all elements matched by the
        // selector alternatives, return the shortest text. This avoids grabbing a
        // container whose textContent concatenates nested values (e.g. a price
        // wrapper holding both the current and original price).
        $best = null;
        foreach ($this->queryAll($xpath, $selector, $node) as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }
            $text = trim((string) $el->textContent);
            if ($text === '') {
                continue;
            }
            if ($best === null || strlen($text) < strlen($best)) {
                $best = $text;
            }
        }

        return $best ?? '';
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
