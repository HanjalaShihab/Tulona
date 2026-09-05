<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\StartechAffiliate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared publish logic for the single-product workflows ("Scrape and Post" and
 * "Upload CSV → review and post"). Given a validated payload plus an optional
 * draft payload it creates/merges the product, attaches/creates the merchant
 * offer and affiliate link, replaces the image and records the price + audit
 * trail — all in one transaction.
 */
class ProductPublishService
{
    public function __construct(
        protected ProductMatchService $matcher,
        protected PriceTrackingService $pricing,
        protected CategoryDetector $detector,
    ) {}

    /**
     * @param  array  $data  the validated form payload (see ScrapePostController validation)
     * @param  array  $draft  optional scraped/source fields (source_url, external_url, sku, ...)
     * @return array{product: Product, categoryName: string, merged: bool}
     */
    public function publish(array $data, array $draft = []): array
    {
        $categoryName = null;

        $product = DB::transaction(function () use ($data, $draft, &$categoryName) {
            // Auto-detect category from product name if none selected (covers CSV without category)
            $hasCategory = ! empty($data['category_id']) || ! empty($data['subcategory_id']) || ! empty(trim((string) ($data['category'] ?? '')));
            if (! $hasCategory) {
                $detected = $this->detector->detect($data['name'] ?? '', $data['description'] ?? null);
                if ($detected) {
                    $data['category_id'] = $detected->parent_id ? $detected->parent_id : $detected->id;
                    $data['subcategory_id'] = $detected->parent_id ? $detected->id : null;
                    if ($detected->parent_id) {
                        $data['category'] = null;
                        $data['subcategory'] = $detected->name;
                    } else {
                        $data['category'] = $detected->name;
                    }
                } else {
                    // Fallback to Electronics when auto-detect finds no match (prevents empty category creation)
                    $fallback = Category::where('slug', 'electronics')->first() ?? Category::whereNull('parent_id')->where('is_active', true)->first();
                    if ($fallback) {
                        $data['category_id'] = $fallback->id;
                    }
                }
            }
            $category = $this->resolvePostedCategory(
                $data['category_id'] ?? null,
                $data['subcategory_id'] ?? null,
                $data['category'] ?? null,
                $data['subcategory'] ?? null,
            );
            $categoryName = $category->name;

            $description = ($data['description'] ?? null) ?: null;

            $candidate = new Product([
                'name' => $data['name'],
                'category_id' => $category->id,
                'brand_id' => ($data['brand_id'] ?? null) ? (int) $data['brand_id'] : null,
                'sku' => ($data['sku'] ?? null) ?: ($draft['sku'] ?? null),
                'model_number' => $draft['model_number'] ?? null,
                'gtin' => $draft['gtin'] ?? null,
            ]);

            $match = $this->matcher->find($candidate);
            $merged = $match !== null;

            if ($merged) {
                $product = $match;

                $fill = $this->matcher->missingIdentifiers($product, $candidate);
                if (blank($product->brand_id) && $candidate->brand_id) {
                    $fill['brand_id'] = $candidate->brand_id;
                }
                if (blank($product->short_description) && $description !== null) {
                    $fill['short_description'] = Str::limit($description, 500);
                }
                if ($fill) {
                    $product->update($fill);
                }

                $promote = array_filter([
                    'is_trending' => (bool) ($data['is_trending'] ?? false),
                    'is_featured' => (bool) ($data['is_featured'] ?? false),
                    'is_top_selling' => (bool) ($data['is_top_selling'] ?? false),
                ]);
                if ($promote) {
                    $product->update($promote);
                }
            } else {
                $slug = $this->generateUniqueSlug(Str::slug($data['name']), $category->id);

                $product = Product::withTrashed()->firstOrNew(['slug' => $slug]);

                $product->fill([
                    'category_id' => $category->id,
                    'brand_id' => ($data['brand_id'] ?? null) ? (int) $data['brand_id'] : null,
                    'name' => $data['name'],
                    'sku' => ($data['sku'] ?? null) ?: null,
                    'rating' => isset($draft['rating']) && is_numeric($draft['rating']) ? (float) $draft['rating'] : null,
                    'short_description' => $description !== null ? Str::limit($description, 500) : null,
                    'description' => $description,
                    'product_type' => 'physical',
                    'status' => 'published',
                    'is_trending' => (bool) ($data['is_trending'] ?? false),
                    'is_featured' => (bool) ($data['is_featured'] ?? false),
                    'is_top_selling' => (bool) ($data['is_top_selling'] ?? false),
                ])->save();

                if ($product->trashed()) {
                    $product->restore();
                }
            }

            $num = fn ($k) => isset($data[$k]) && $data[$k] !== '' && $data[$k] !== null ? (float) $data[$k] : null;

            // Auto-append StarTech tracking (?tracking=CODE) when merchant is Star Tech
            $affiliateUrlRaw = $data['affiliate_url'] ?? '';
            $startechCode = $data['startech_tracking_code'] ?? null;
            if (! empty($affiliateUrlRaw) || ! empty($draft['external_url'])) {
                $baseForStartech = ! empty($affiliateUrlRaw) ? $affiliateUrlRaw : ($draft['external_url'] ?? '');
                $affiliateUrlRaw = StartechAffiliate::maybeAppend(
                    $baseForStartech ?: $affiliateUrlRaw,
                    (int) $data['merchant_id'],
                    null,
                    $draft['external_url'] ?? $affiliateUrlRaw,
                    $startechCode
                );
            }

            $offer = Offer::updateOrCreate(
                ['product_id' => $product->id, 'merchant_id' => $data['merchant_id']],
                [
                    'external_url' => $draft['external_url'] ?? null,
                    'affiliate_url' => $affiliateUrlRaw ?: ($data['affiliate_url'] ?? ''),
                    'current_price' => $num('current_price'),
                    'original_price' => $num('original_price'),
                    'currency' => $data['currency'],
                    'availability' => $data['availability'],
                    'source' => 'manual',
                    'status' => 'active',
                    'last_synced_at' => now(),
                ]
            );

            $affiliateUrl = $affiliateUrlRaw ?: ($data['affiliate_url'] ?? null);
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
                $this->pricing->recordPrice($offer, $offer->current_price);
            }

            AuditLog::record($merged ? 'product.merged' : 'product.posted', $product, [
                'merchant_id' => $data['merchant_id'],
                'category_id' => $category->id,
            ]);

            return $product;
        });

        return [
            'product' => $product,
            'categoryName' => $categoryName,
            'merged' => false,
        ];
    }

    /** Resolve the category from the posted form: a fixed category id (cascade parent), an optional
     *  subcategory id, or a typed new name. */
    protected function resolvePostedCategory(?int $categoryId, ?int $subcategoryId, ?string $newCategoryName, ?string $subcategoryName): Category
    {
        if ($categoryId !== null) {
            $parent = Category::findOrFail($categoryId);

            if ($subcategoryId !== null) {
                return Category::where('id', $subcategoryId)->where('parent_id', $parent->id)->firstOrFail();
            }

            return ! empty($subcategoryName) ? $this->findOrCreateCategory($subcategoryName, $parent->id) : $parent;
        }

        return $this->resolveCategory((string) $newCategoryName, $subcategoryName);
    }

    protected function resolveCategory(string $categoryName, ?string $subcategoryName): Category
    {
        $parent = $this->findOrCreateCategory($categoryName, null);

        return ! empty($subcategoryName) ? $this->findOrCreateCategory($subcategoryName, $parent->id) : $parent;
    }

    protected function findOrCreateCategory(string $name, ?int $parentId): Category
    {
        $name = trim($name);
        $slug = Str::slug($name);

        $category = Category::where('slug', $slug)->where('parent_id', $parentId)->first();

        if ($category === null) {
            $category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->where('parent_id', $parentId)->first();
        }

        if ($category !== null) {
            return $category;
        }

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

    protected function generateUniqueSlug(string $slug, ?int $parentId): string
    {
        $uniqueSlug = $slug;
        $counter = 1;
        while (Product::where('slug', $uniqueSlug)->where('category_id', $parentId)->exists()) {
            $uniqueSlug = $slug.'-'.(++$counter);
        }

        return $uniqueSlug;
    }
}
