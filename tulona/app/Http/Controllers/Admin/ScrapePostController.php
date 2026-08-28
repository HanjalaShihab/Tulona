<?php

namespace App\Http\Controllers\Admin;

use App\Connectors\Parser\HtmlProductParser;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\PriceTrackingService;
use App\Services\ProductMatchService;
use App\Services\Scraping\UrlFetcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            $draft = [
                'source_url' => $data['source_url'],
                'external_url' => $data['source_url'],
                'merchant_id' => $merchantId,
                'affiliate_url' => '',
                'name' => trim((string) ($details['name'] ?? '')),
                'description' => trim((string) ($details['description'] ?? '')),
                'price' => $price,
                'original_price' => $original,
                'currency' => 'BDT',
                'availability' => $details['availability'] ?? 'unknown',
                'images' => $images,
                'image' => $images[0] ?? null,
                'sku' => $details['sku'] ?? null,
                'model_number' => $details['model_number'] ?? null,
                'gtin' => $details['gtin'] ?? null,
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
            // "Fixed" categories = the active top-level categories shown under
            // "Popular Categories" on the landing page, each with its subcategories.
            'categories' => Category::whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')->with('children')->get(),
        ]);
    }

    /** Publish the posted product into its category section with a merchant offer. */
    public function post(Request $request): RedirectResponse
    {
        $this->authorize('manage-products');

        $draft = session($this->draftKey());
        abort_unless(is_array($draft), 422, 'Nothing to post — scrape a product first.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'merchant_id' => 'required|exists:merchants,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'category' => 'nullable|string|max:255|required_without:category_id',
            'subcategory' => 'nullable|string|max:255',
            'affiliate_url' => 'required|url|max:2048',
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
        ], [
            'category.required_without' => 'Choose a landing-page category or type a new one below.',
        ]);

        $categoryName = null;
        $product = DB::transaction(function () use ($data, $draft, &$categoryName) {
            $category = $this->resolvePostedCategory(
                $data['category_id'] ?? null,
                $data['category'] ?? null,
                $data['subcategory'] ?? null
            );
            $categoryName = $category->name;

            $description = ($data['description'] ?? null) ?: null;

            // Candidate (never saved) used to detect whether this is the same
            // real-world product already listed from another merchant — see
            // ProductMatchService. When it matches, the new merchant's offer is
            // attached to the existing product so the Compare Stores section
            // shows every store side by side instead of creating a duplicate.
            $candidate = new Product([
                'name' => $data['name'],
                'category_id' => $category->id,
                'sku' => ($data['sku'] ?? null) ?: ($draft['sku'] ?? null),
                'model_number' => $draft['model_number'] ?? null,
                'gtin' => $draft['gtin'] ?? null,
            ]);

            $match = app(ProductMatchService::class)->find($candidate);
            $merged = $match !== null;

            if ($merged) {
                $product = $match;

                $fill = app(ProductMatchService::class)->missingIdentifiers($product, $candidate);
                if (blank($product->short_description) && $description !== null) {
                    $fill['short_description'] = Str::limit($description, 500);
                }
                if ($fill) {
                    $product->update($fill);
                }

                // Only ever promote on a merge (another store for an existing
                // product) — a new merchant offer must not clear homepage flags.
                $promote = array_filter([
                    'is_trending' => (bool) ($data['is_trending'] ?? false),
                    'is_featured' => (bool) ($data['is_featured'] ?? false),
                    'is_top_selling' => (bool) ($data['is_top_selling'] ?? false),
                ]);
                if ($promote) {
                    $product->update($promote);
                }
            } else {
                $product = Product::withTrashed()->firstOrNew(['slug' => Str::slug($data['name'])]);

                $product->fill([
                    'category_id' => $category->id,
                    'name' => $data['name'],
                    'sku' => ($data['sku'] ?? null) ?: null,
                    'short_description' => $description !== null ? Str::limit($description, 500) : null,
                    'description' => $description,
                    'product_type' => 'physical',
                    'status' => 'published',
                    'is_trending' => (bool) ($data['is_trending'] ?? false),
                    'is_featured' => (bool) ($data['is_featured'] ?? false),
                    'is_top_selling' => (bool) ($data['is_top_selling'] ?? false),
                ])->save();

                // A later post of the same name should be relinked + republished even
                // if it was trashed or previously posted to a different category.
                if ($product->trashed()) {
                    $product->restore();
                }
            }

            $num = fn ($k) => isset($data[$k]) && $data[$k] !== '' && $data[$k] !== null ? (float) $data[$k] : null;

            $offer = Offer::updateOrCreate(
                ['product_id' => $product->id, 'merchant_id' => $data['merchant_id']],
                [
                    'external_url' => $draft['external_url'] ?? null,
                    'affiliate_url' => $data['affiliate_url'] ?? '',
                    'current_price' => $num('current_price'),
                    'original_price' => $num('original_price'),
                    'currency' => $data['currency'],
                    'availability' => $data['availability'],
                    'source' => 'manual',
                    'status' => 'active',
                    'last_synced_at' => now(),
                ]
            );

            $affiliateUrl = $data['affiliate_url'] ?? null;
            $image = $data['image'] ?? null;

            $offer->affiliateOffer()->updateOrCreate([], [
                'offer_id' => $offer->id,
                'product_id' => $offer->product_id,
                'merchant_id' => $offer->merchant_id,
                'normal_product_url' => $draft['external_url'] ?? null,
                'affiliate_url' => $affiliateUrl,
                'status' => $affiliateUrl ? 'manual' : 'pending',
                'generation_method' => $affiliateUrl ? 'manual' : null,
                'generated_at' => $affiliateUrl ? now() : null,
            ]);

            // Replace the product image entirely when a new one is provided so a
            // re-post never silently keeps a stale image. When this post was
            // matched to an existing product (a new merchant), keep the existing
            // main image unless the product has none yet.
            if (! empty($image)) {
                if ($merged) {
                    if ($product->images()->doesntExist()) {
                        $product->images()->create(['path' => $image, 'is_main' => true, 'sort_order' => 1]);
                    }
                } else {
                    ProductImage::where('product_id', $product->id)->delete();
                    $product->images()->create(['path' => $image, 'is_main' => true, 'sort_order' => 1]);
                }
            }

            if ($offer->current_price !== null) {
                app(PriceTrackingService::class)->recordPrice($offer, $offer->current_price);
            }

            AuditLog::record($merged ? 'product.merged' : 'product.posted', $product, [
                'merchant_id' => $data['merchant_id'],
                'category_id' => $category->id,
            ]);

            return $product;
        });

        session()->forget($this->draftKey());

        $offerCount = $product->offers()->count();
        $status = $offerCount > 1
            ? 'Posted — "'.$product->name.'" is sold by '.$offerCount.' stores, all shown side by side in the product page Compare Stores section.'
            : 'Product posted to "'.$categoryName.'" and is live — '.$product->name.' (id #'.$product->id.')';

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

        foreach (Merchant::where('status', 'active')->get(['id', 'website_url', 'base_affiliate_url']) as $merchant) {
            foreach ([$merchant->website_url, $merchant->base_affiliate_url] as $candidate) {
                $candidateHost = $candidate ? strtolower((string) parse_url($candidate, PHP_URL_HOST)) : '';
                if ($candidateHost !== '' && (str_ends_with($host, $candidateHost) || str_ends_with($candidateHost, $host))) {
                    return $merchant->id;
                }
            }
        }

        return null;
    }

    /** Resolve/create a category (and optional subcategory) by typed name. */
    protected function resolveCategory(string $categoryName, ?string $subcategoryName): Category
    {
        $parent = $this->findOrCreateCategory($categoryName, null);

        if (! empty($subcategoryName)) {
            return $this->findOrCreateCategory($subcategoryName, $parent->id);
        }

        return $parent;
    }

    /** Resolve the category from the posted form: a fixed landing-page category id, or a typed new name. */
    protected function resolvePostedCategory(?int $categoryId, ?string $categoryName, ?string $subcategoryName): Category
    {
        if ($categoryId !== null) {
            $parent = Category::findOrFail($categoryId);

            return ! empty($subcategoryName)
                ? $this->findOrCreateCategory($subcategoryName, $parent->id)
                : $parent;
        }

        return $this->resolveCategory((string) $categoryName, $subcategoryName);
    }

    protected function findOrCreateCategory(string $name, ?int $parentId): Category
    {
        $name = trim($name);
        $slug = Str::slug($name);

        $category = Category::where('slug', $slug)
            ->where('parent_id', $parentId)
            ->first();

        if ($category === null) {
            $category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->where('parent_id', $parentId)
                ->first();
        }

        if ($category !== null) {
            return $category;
        }

        // categories.slug is globally unique — if the slug is already taken by
        // another branch of the tree, allocate a unique sibling slug instead of
        // failing the insert.
        $uniqueSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $slug.'-'.(++$counter);
        }

        return Category::create([
            'name' => $name,
            'slug' => $uniqueSlug,
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
