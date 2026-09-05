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
                if (! empty($row['images'])) {
                    $details['images'] = array_values(array_unique(array_filter($row['images'])));
                }
            }
            if (! empty($details)) {
                break;
            }
        }

        // Next.js/SSR structured data: many modern shop fronts embed the full
        // product object in a __NEXT_DATA__ JSON script. This is often the only
        // source of the regular-vs-sale price pair, brand, ratings and the full
        // image gallery. It only FILLS gaps — it never overrides the JSON-LD
        // values above (schema-structured data is treated as more trustworthy).
        $next = $this->extractNextData($raw);
        foreach ([
            'name', 'price', 'original_price', 'currency', 'brand_slug', 'sku',
            'model_number', 'gtin', 'description', 'availability',
        ] as $k) {
            if (($details[$k] ?? null) === null && isset($next[$k]) && $next[$k] !== null) {
                $details[$k] = $next[$k];
            }
        }
        if (empty($details['images']) || ! empty($next['images'])) {
            // Union of structured images (Next.js gallery is typically the most
            // complete), de-duplicated, keeping order. JSON-LD single image stays
            // as a fallback when Next.js carries none.
            $merged = array_values(array_unique(array_merge($next['images'] ?? [], $details['images'] ?? [])));
            if (! empty($merged)) {
                $details['images'] = $merged;
            }
        }
        foreach (['rating', 'rating_count'] as $k) {
            if (isset($next[$k])) {
                $details[$k] = $next[$k];
            }
        }

        // Daraz / Lazada fallback: the product page does not expose the price in
        // JSON-LD, __NEXT_DATA__ or the DOM. The live price only exists inside an
        // escaped, server-embedded JS blob named `pdpTrackingData` (fields such as
        // `pdt_price`, plus an original/list price key when the item is on sale).
        // Fill only the price gaps with it; everything else stays as-is.
        if (($details['price'] ?? null) === null || ($details['original_price'] ?? null) === null) {
            $daraz = $this->extractDarazState($raw);
            if (isset($daraz['price']) && $daraz['price'] !== null) {
                $details['price'] = $daraz['price'];
            }
            if (isset($daraz['original_price']) && $daraz['original_price'] !== null) {
                $details['original_price'] = $daraz['original_price'];
            }
            if (($details['currency'] ?? null) === null && isset($daraz['currency']) && $daraz['currency'] !== null) {
                $details['currency'] = $daraz['currency'];
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
            $images = array_map(fn ($u) => $baseUrl ? ($this->resolveUrl($u, $baseUrl) ?: $u) : $u, $images);
            // The DOM pass is the least reliable gallery source — only use it to
            // ADD images when no structured (JSON-LD / Next.js) gallery was found.
            if (empty($details['images'])) {
                $details['images'] = $images;
            } else {
                $details['images'] = array_values(array_unique(array_merge($details['images'], $images)));
            }

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

    /**
     * Extract structured product data from a Next.js __NEXT_DATA__ JSON script.
     *
     * Modern e-commerce shop fronts (a large share of BD merchants) render via
     * Next.js and embed the full server-rendered product object in
     * <script id="__NEXT_DATA__">. Unlike the raw DOM (which is often just an
     * empty SPA shell) this JSON reliably carries the regular-versus-sale price
     * pair, brand, SKU, description, the full gallery and ratings.
     *
     * Returns a normalized subset keyed like JsonProductParser::clean() plus the
     * extra `images`, `rating` (0-5 rounded) and `rating_count` fields. Empty
     * when no usable product object is found.
     */
    protected function extractNextData(string $raw): array
    {
        if (! preg_match('/<script[^>]+id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/is', $raw, $m)) {
            return [];
        }

        $tree = json_decode(trim($m[1]), true);
        if (! is_array($tree)) {
            return [];
        }

        // Collect every reasonable product-like object (name present plus a
        // price-ish signal and/or sku). Picking the MAIN product from the many
        // (product, similar-products, recently-viewed, wishlist ...) is the hard
        // part — see pickMainProduct below.
        $candidates = [];
        $walk = function (array $branch) use (&$walk, &$candidates): void {
            foreach ($branch as $value) {
                if (! is_array($value)) {
                    continue;
                }
                if ($this->looksLikeProduct($value)) {
                    $candidates[] = $value;
                }
                $walk($value);
            }
        };
        $walk($tree);

        if ($candidates === []) {
            return [];
        }

        return $this->normalizeNextDataProduct($this->pickMainProduct($candidates));
    }

    /** A product candidate has a name plus a price signal or an sku. */
    protected function looksLikeProduct(array $node): bool
    {
        if (empty($node['name']) || ! is_scalar($node['name'])) {
            return false;
        }

        $priceish = ['price', 'sale_price', 'spacial_price', 'special_price', 'regular_price', 'original_price', 'current_price', 'price_off', 'lowest_price'];
        foreach ($priceish as $k) {
            if (isset($node[$k]) && is_scalar($node[$k])) {
                return true;
            }
        }

        return isset($node['sku']) && is_scalar($node['sku']);
    }

    /**
     * Choose the main product from the duplicate candidates embedded in a
     * Next.js state tree (they repeat the product across reducers/fragments and
     * alongside similar/recent/wishlist items).
     *
     * Score each candidate by how strongly it looks like a standalone product
     * unpack (more price fields + an sku + an id beats a thin similar-product
     * card which often lacks both prices). The highest score wins.
     */
    protected function pickMainProduct(array $candidates): array
    {
        $score = function (array $node): int {
            $s = 0;
            $priceFields = 0;
            foreach (['price', 'sale_price', 'spacial_price', 'special_price', 'regular_price', 'original_price', 'current_price'] as $k) {
                if (isset($node[$k]) && is_scalar($node[$k]) && $node[$k] !== '' && (string) $node[$k] !== '0') {
                    $priceFields++;
                }
            }
            $s += min($priceFields, 2) * 3;
            if (isset($node['sku']) && is_scalar($node['sku']) && $node['sku'] !== '') {
                $s += 2;
            }
            if (isset($node['id']) && (is_scalar($node['id']) || is_numeric($node['id'])) && $node['id'] !== '') {
                $s += 1;
            }
            if (isset($node['description']) || isset($node['images'])) {
                $s += 1;
            }
            if (isset($node['regular_price']) && isset($node['sale_price'])) {
                $s += 2; // presence of an explicit pair strongly marks a main product
            }

            return $s;
        };

        usort($candidates, fn ($a, $b) => $score($b) <=> $score($a));

        return $candidates[0];
    }

    /**
     * Map a Next.js product object onto canonical detail fields using flexible
     * key aliases (never site-specific) plus a price-pair resolution.
     */
    protected function normalizeNextDataProduct(array $node): array
    {
        $s = static fn ($v) => is_scalar($v) ? trim((string) $v) : null;

        $name = $s($node['name'] ?? null);
        $sku = $s($node['sku'] ?? null);

        // Explicit price keys, in order of preference per role.
        $price = $s($node['price'] ?? null)
            ?: $s($node['sale_price'] ?? null)
            ?: $s($node['spacial_price'] ?? null)
            ?: $s($node['special_price'] ?? null)
            ?: $s($node['offer_price'] ?? null)
            ?: $s($node['current_price'] ?? null)
            ?: $s($node['now_price'] ?? null)
            ?: $s($node['final_price'] ?? null);
        $original = $s($node['original_price'] ?? null)
            ?: $s($node['regular_price'] ?? null)
            ?: $s($node['list_price'] ?? null)
            ?: $s($node['compare_at_price'] ?? null)
            ?: $s($node['msrp'] ?? null);

        // Value may be a number, a localized string ("৳ 5,950" / "5,950") or a
        // nested {"amount":..,"currency":..} object — normalize and compare.
        $toNum = function ($v) {
            if (is_array($v)) {
                $v = $v['amount'] ?? $v['value'] ?? $v['price'] ?? null;
            }
            if ($v === null) {
                return null;
            }
            $str = is_scalar($v) ? trim((string) $v) : '';
            $clean = str_replace([',', '৳', 'Tk', 'TK', 'BDT', '$', '₹'], '', $str);

            return is_numeric($clean) ? (float) $clean : null;
        };

        $priceN = $toNum($price);
        $originalN = $toNum($original);

        // If only one explicit price exists (or the "sale" price actually looks
        // higher than the "regular" price, which happens when a store stores the
        // pre-discount figure under `price`), resolve the pair so the LOWER
        // value is the current/sale price and the higher is the original.
        if ($priceN !== null && $originalN !== null && $priceN > $originalN) {
            [$priceN, $originalN] = [$originalN, $priceN];
        }

        $image = $this->nextDataImage($node['image'] ?? null) ?: $this->nextDataImage($node['thumbnail'] ?? null);

        $images = [];
        foreach (['images', 'gallery', 'photo', 'pictures', 'image_gallery', 'product_images'] as $gk) {
            foreach ((array) ($node[$gk] ?? []) as $img) {
                if (is_string($img)) {
                    $images[] = $img;
                } elseif (is_array($img)) {
                    foreach (['image', 'src', 'url', 'full', 'large', 'original', 'image_url', 'thumb_url'] as $ik) {
                        if (isset($img[$ik]) && is_string($img[$ik])) {
                            $images[] = $img[$ik];
                            break;
                        }
                    }
                }
            }
            if (! empty($images)) {
                break;
            }
        }
        $images = array_values(array_unique(array_filter($images)));
        if ($image !== null && ! in_array($image, $images, true)) {
            array_unshift($images, $image);
        }
        if (empty($images) && $image !== null) {
            $images = [$image];
        }

        // Ratings: average (0-5 or 0-100) plus a review count.
        $rating = null;
        $ratingValue = $s($node['rating'] ?? null)
            ?: $s($node['rating_value'] ?? null)
            ?: $s($node['rating_summary_value'] ?? null)
            ?: $s($node['rating_summary'] ?? null)
            ?: $s($node['average_rating'] ?? null);

        $ratingCount = $s($node['rating_count'] ?? null)
            ?: $s($node['reviews_count'] ?? null)
            ?: $s($node['review_count'] ?? null)
            ?: $s($node['num_reviews'] ?? null);

        $ratingN = $ratingValue !== null && $ratingValue !== '' && is_numeric($ratingValue) ? (float) $ratingValue : null;
        if ($ratingN !== null && $ratingN > 5) {
            $ratingN = round($ratingN / 20, 1); // normalize a /100 score down to /5
        }

        return array_filter([
            'name' => $name,
            'price' => $priceN,
            'original_price' => $originalN,
            'currency' => $s($node['currency'] ?? null) ?: $s($node['price_currency'] ?? null),
            'brand_slug' => $s($node['brand'] ?? null) ?: $s($node['brand_name'] ?? null),
            'sku' => $sku,
            'model_number' => $s($node['model_number'] ?? null) ?: $s($node['model'] ?? null),
            'gtin' => $s($node['gtin'] ?? null) ?: $s($node['ean'] ?? null) ?: $s($node['barcode'] ?? null),
            'description' => $s($node['description'] ?? null) ?: $s($node['product_details'] ?? null),
            'availability' => $s($node['availability'] ?? null),
            'image' => $image,
            'images' => $images,
            'rating' => $ratingN,
            'rating_count' => $ratingCount !== null && $ratingCount !== '' && is_numeric($ratingCount) ? (int) $ratingCount : null,
        ], static fn ($v) => $v !== null);
    }

    /** Read one image URL/mapped-object out into a string. */
    protected function nextDataImage(mixed $value): ?string
    {
        if (is_string($value)) {
            return trim($value) ?: null;
        }
        if (is_array($value)) {
            foreach (['url', 'src', 'image', 'full', 'large', 'original', 'full_url'] as $k) {
                if (isset($value[$k]) && is_string($value[$k]) && trim($value[$k])) {
                    return trim($value[$k]);
                }
            }
        }

        return null;
    }

    /**
     * Daraz / Lazada detail-page fallback.
     *
     * Marketplaces that render prices only at runtime (client-side hydrate) put
     * the final values in an escaped JS literal, e.g.
     *
     *   pdpTrackingData = "{\"pdt_price\":\"৳ 320\",\"pdt_discount\":\"-20%\",...}";
     *
     * This decodes that literal and pulls out the current and original prices.
     *
     * Daraz's convention matters here: `pdt_price` is the *single* price a
     * non-sale item shows. When the item carries an explicit discount marker
     * (`pdt_discount` like "-20%" / "20%" / "sale") the rendered `pdt_price` is
     * the *list* (original) price, so it must be mapped to `original_price`
     * rather than being presented as the current price. The sale price itself is
     * only delivered by Daraz's signed client API and is not server-rendered, so
     * for discounted items we refuse to fabricate a current price the HTML does
     * not contain.
     *
     * Returns an empty array when the blob is absent or holds no usable price.
     *
     * @return array{price?: float, original_price?: float, currency?: string}
     */
    protected function extractDarazState(string $raw): array
    {
        if (preg_match('~pdpTrackingData\s*=\s*"(.*?)";~s', $raw, $m)) {
            $json = json_decode(str_replace('\\"', '"', $m[1]), true);
            if (is_array($json)) {
                $normalise = static fn ($v): ?float => is_scalar($v)
                    ? (float) preg_replace('/[^0-9.]/', '', str_replace(',', '', (string) $v))
                    : null;

                $first = static fn (array $keys) => (static function () use ($json, $keys) {
                    foreach ($keys as $k) {
                        if (array_key_exists($k, $json) && $json[$k] !== null && $json[$k] !== '') {
                            return $json[$k];
                        }
                    }
                    return null;
                })();

                $rawPrice = $first(['pdt_price', 'price', 'pdt_sale_price']);
                $rawOriginal = $first(['original_price', 'originalPrice', 'cutPrice', 'list_price', 'listPrice', 'pdt_original_price', 'pdt_list_price', 'pdt_price']);
                $discountRaw = $first(['pdt_discount', 'discount', 'discountPercent', 'sale']);

                // Discount marker: a percentage ("-20%", "20%") or an explicit
                // "sale"/"discount" flag. When present, the rendered pdt_price is
                // the list price, NOT a current sale price.
                $isDiscounted = $discountRaw !== null
                    && (bool) preg_match('/\d|sale|discount/i', (string) $discountRaw);

                $currency = null;
                if (isset($json['core']['currencyCode']) && is_scalar($json['core']['currencyCode'])) {
                    $currency = (string) $json['core']['currencyCode'];
                } elseif (isset($json['currency']) && is_scalar($json['currency'])) {
                    $currency = (string) $json['currency'];
                }

                $out = [];

                if ($isDiscounted) {
                    // On sale: only an explicit, separate sale-price token may be
                    // used as the current price. pdt_price becomes the original.
                    $sale = $first(['pdt_sale_price', 'sale_price', 'price', 'offer_price', 'current_price']);
                    if ($sale !== null && $sale !== '' && ! in_array($sale, [$rawOriginal, $rawPrice], true)) {
                        $out['price'] = $normalise($sale);
                    }
                    if ($rawOriginal !== null) {
                        $out['original_price'] = $normalise($rawOriginal);
                    }
                } else {
                    $price = $first(['pdt_sale_price', 'sale_price', 'price', 'offer_price', 'current_price', 'pdt_price']);
                    if ($price !== null) {
                        $out['price'] = $normalise($price);
                    }
                    if ($rawOriginal !== null && $rawOriginal !== $price) {
                        $out['original_price'] = $normalise($rawOriginal);
                    }
                }

                if ($currency !== null) {
                    $out['currency'] = $currency;
                }

                return $out;
            }
        }

        return [];
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

            // Skip obvious non-product links (nav/menu/login/cart/tags/authors/pages/pagination,
            // app-store/social/footer anchors, anything inside footer/nav/header).
            if ($this->isNonProductAnchor($a, $abs) || isset($seen[$abs])) {
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
     * Reject an anchor that surely is not a product card: navigation, footer and
     * social/app-download links show up as "products" in the universal detector
     * when they sit near a stray price token or share a neighbour's image. The
     * public detector must never stage these as products.
     */
    protected function isNonProductAnchor(DOMElement $a, string $abs): bool
    {
        $path = parse_url($abs, PHP_URL_PATH) ?? '';
        if (in_array($path, ['', '/'], true) || preg_match('~/tag/|/category/|/page/|(login|cart|checkout|search|contact|about|account|wishlist|logout|register|profile|newsletter|blog|career|faq|help|support|privacy|terms|cookie)~i', $abs)) {
            return true;
        }

        // External app-store / social hosts are never product cards.
        $host = strtolower((string) parse_url($abs, PHP_URL_HOST));
        if (preg_match('~(play\.google|apps\.apple|appgallery\.huawei|facebook\.com|(twitter|x)\.com|youtube\.com|instagram\.com|linkedin\.com|wa\.me|whatsapp\.com|messenger\.com)~', $host)) {
            return true;
        }

        // Anchors living inside footer/nav/header are navigational, not products.
        $anc = $a->parentNode;
        while ($anc !== null) {
            if ($anc instanceof DOMElement) {
                $tag = strtolower($anc->nodeName);
                $cls = strtolower((string) $anc->getAttribute('class'));
                if ($tag === 'footer' || $tag === 'nav' || $tag === 'header'
                    || preg_match('~\b(footer|foot|bottom-nav|site-nav|main-menu|top-menu|mobile-menu|sidebar)\b~', $cls)) {
                    return true;
                }
            }
            $anc = $anc->parentNode;
        }

        // Discriminating anchor label — hit before the name/price signal is read.
        $label = mb_strtolower(trim((string) $a->textContent)).' '.mb_strtolower((string) $a->getAttribute('title'));
        if (preg_match('~(app download|download app|android app|ios app|play store|app store|huawei appgallery|facebook|youtube|twitter|instagram|linkedin|whatsapp|follow us|share on|login|log in|sign in|sign up|register|my account|wishlist|cart|checkout|newsletter|about us|contact us|career|privacy policy|terms and? conditions|cookies?|become a.? seller|sell on)~i', $label)) {
            return true;
        }

        return false;
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
