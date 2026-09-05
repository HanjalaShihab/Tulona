<?php

namespace App\Http\Controllers\Admin;

use App\Connectors\Parser\HtmlProductParser;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Services\ProductPublishService;
use App\Services\Scraping\UrlFetcher;
use App\Support\StartechAffiliate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "Scrape and Post" — a single-product workflow. Give a book/product detail
 * page, fetch a draft, edit it, choose a merchant + category/subcategory
 * (typed, optional subcategory) + affiliate link, then Post → the product is
 * published into its category section and appears on the site.
 */
class ScrapePostController extends Controller
{
    public function __construct(
        protected UrlFetcher $fetcher,
        protected HtmlProductParser $parser,
    ) {}

    protected function draftKey(): string
    {
        return 'scrape_post.draft';
    }

    public function index(): View
    {
        return view('admin.scrape-post.index', [
            'categories' => Category::whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')->withCount('children')->get(),
        ]);
    }

    /**
     * Fetch a single product detail page into an editable draft. Scraping only
     * needs the URL — the merchant (and its parsing config) is auto-detected
     * from the URL host so nothing else has to be chosen up front.
     */
    public function scrape(Request $request): RedirectResponse
    {
        $this->authorize('manage-products');

        $data = $request->validate([
            'source_url' => 'required|url|max:2048',
        ]);

        try {
            $raw = $this->fetcher->fetch($data['source_url']);

            $merchantId = $this->detectMerchant($data['source_url']);
            $merchant = $merchantId ? Merchant::find($merchantId) : null;
            $config = $merchant?->configuration ?? [];

            $details = $this->parser->detail($raw, $data['source_url'], $config);

            $price = isset($details['price']) ? $details['price'] : null;
            $original = $details['original_price'] ?? null;

            $images = $details['images'] ?? [];
            if (empty($images) && isset($details['image'])) {
                $images = [$details['image']];
            }
            $images = array_values(array_unique(array_filter(array_map('strval', $images))));

            // Auto-associate a brand when the page exposes one (best-effort, by
            // name/slug) so the review form is pre-populated and the post maps it.
            $brandId = null;
            if (! empty($details['brand_slug'])) {
                $brandName = trim((string) $details['brand_slug']);
                $brand = Brand::whereRaw('LOWER(name) = ?', [mb_strtolower($brandName)])
                    ->orWhere('slug', Str::slug($brandName))
                    ->first();
                $brandId = $brand?->id;
            }

            $draft = [
                'source_url' => $data['source_url'],
                'external_url' => $data['source_url'],
                'merchant_id' => $merchantId,
                'brand_id' => $brandId,
                'affiliate_url' => '',
                'name' => trim((string) ($details['name'] ?? '')),
                'description' => trim((string) ($details['description'] ?? '')),
                'price' => $price,
                'original_price' => $original,
                'currency' => 'BDT',
                'availability' => $details['availability'] ?? 'in_stock',
                'images' => $images,
                'image' => $images[0] ?? null,
                'sku' => $details['sku'] ?? null,
                'model_number' => $details['model_number'] ?? null,
                'gtin' => $details['gtin'] ?? null,
                'rating' => $details['rating'] ?? null,
            ];

            if (empty($draft['name'])) {
                throw new \RuntimeException('Could not find a product name on that page. You can still fetch it by editing the details below if enough data was returned.');
            }

            session([$this->draftKey() => $draft]);

            return redirect()->route('admin.scrape-post.edit')->with('status', 'Product details fetched — review and edit below, then Post.');
        } catch (\Throwable $e) {
            return back()->withErrors(['scrape' => $e->getMessage()]);
        }
    }

    public function edit(): View
    {
        $draft = session($this->draftKey());

        return view('admin.scrape-post.edit', [
            'draft' => $draft,
            'merchants' => Merchant::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            // Every category in the tree (all depths) so anything can be posted into.
            'categories' => Category::cascadeData(),
        ]);
    }

    /** Publish the posted product into its category section with a merchant offer. */
    public function post(Request $request, ProductPublishService $service): RedirectResponse
    {
        $this->authorize('manage-products');

        $draft = session($this->draftKey());
        abort_unless(is_array($draft), 422, 'Nothing to post — scrape a product first.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'merchant_id' => 'required|exists:merchants,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:categories,id',
            'category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'affiliate_url' => 'required|url|max:2048',
            'startech_tracking_code' => 'nullable|string|max:100',
            'current_price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'required|size:3',
            'description' => 'nullable|string|max:5000',
            'image' => 'nullable|url|max:2048',
            'availability' => 'required|in:in_stock,out_of_stock,preorder,unknown',
            'sku' => 'nullable|string|max:100',
            'is_trending' => 'boolean',
            'is_featured' => 'boolean',
            'is_top_selling' => 'boolean',
        ]);
        $code = $data['startech_tracking_code'] ?? null;
        unset($data['startech_tracking_code']);
        $data['affiliate_url'] = StartechAffiliate::maybeAppend($data['affiliate_url'], (int) $data['merchant_id'], null, $draft['external_url'] ?? $data['affiliate_url'], $code);

        $result = $service->publish($data, $draft);

        session()->forget($this->draftKey());

        $offerCount = $result['product']->offers()->count();
        $status = $offerCount > 1
            ? 'Posted — "'.$result['product']->name.'" is sold by '.$offerCount.' stores, all shown side by side in the product page Store Comparison section.'
            : 'Product posted to "'.$result['categoryName'].'" and is live — '.$result['product']->name.' (id #'.$result['product']->id.')';

        return redirect()->route('admin.scrape-post.index')->with('status', $status);
    }

    /** Discard the current draft ("remove") and start again. */
    public function reset(): RedirectResponse
    {
        $this->authorize('manage-products');
        session()->forget($this->draftKey());

        return redirect()->route('admin.scrape-post.index')->with('status', 'Scraped product removed.');
    }

    /** Best-effort merchant detection from the URL host so merchants need not be chosen at scrape time. */
    protected function detectMerchant(string $url): ?int
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return null;
        }

        foreach (Merchant::where('status', 'active')->get(['id', 'slug', 'name', 'website_url', 'base_affiliate_url']) as $merchant) {
            foreach ([$merchant->website_url, $merchant->base_affiliate_url] as $candidate) {
                $candidateHost = $candidate ? strtolower((string) parse_url($candidate, PHP_URL_HOST)) : '';
                if ($candidateHost !== '' && (str_ends_with($host, $candidateHost) || str_ends_with($candidateHost, $host))) {
                    return $merchant->id;
                }
            }
            if (str_contains($host, 'startech') && (str_contains(strtolower($merchant->slug ?? ''), 'startech') || str_contains(strtolower($merchant->name ?? ''), 'star tech'))) {
                return $merchant->id;
            }
        }

        return null;
    }
}
