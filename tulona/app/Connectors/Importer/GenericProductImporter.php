<?php

namespace App\Connectors\Importer;

use App\Contracts\Merchant\ProductImporter;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Services\PriceTrackingService;
use App\Services\Scraping\ProductMatcher;
use Illuminate\Support\Str;

/**
 * Generic, schema-driven importer used by the CSV/feed-first connectors.
 *
 * §56 pipeline: normalized row ─→ match canonical product (gtin/model/sku/slug)
 * ─→ upsert merchant offer ─→ sync affiliate offer ─→ record price history.
 * With `preview=true` nothing is written; iteration only simulates matching.
 */
class GenericProductImporter implements ProductImporter
{
    protected array $counts = ['matched' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

    public function __construct(protected ProductMatcher $matcher) {}

    public function import(Merchant $merchant, iterable $rows, array $config, bool $preview = false): array
    {
        $this->counts = ['matched' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            try {
                $product = $preview
                    ? $this->matcher->exact($row)
                    : $this->matchOrCreate($row);

                if ($product === null) {
                    $this->counts['skipped']++;

                    continue;
                }

                $this->counts['matched']++;

                if ($preview) {
                    continue;
                }

                $offer = $this->upsertOffer($merchant, $product, $row);
                $this->counts[$offer->wasRecentlyCreated ? 'created' : 'updated']++;
            } catch (\Throwable) {
                $this->counts['errors']++;
            }
        }

        return $this->counts;
    }

    protected function matchOrCreate(array $row): ?Product
    {
        $product = $this->matcher->exact($row);

        if ($product !== null) {
            if ($product->trashed()) {
                $product->restore();
            }

            return $product;
        }

        $categoryId = $this->categoryId($row['category_slug'] ?? null);
        $brandId = $this->brandId($row['brand_slug'] ?? null);

        if ($categoryId === null || empty($row['name'])) {
            return null;
        }

        return Product::create([
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'name' => $row['name'],
            'slug' => Str::slug($row['name']),
            'short_description' => $row['description'] ?? null,
            'gtin' => $row['gtin'] ?? null,
            'model_number' => $row['model_number'] ?? null,
            'sku' => $row['sku'] ?? null,
            'status' => 'published',
        ]);
    }

    protected function upsertOffer(Merchant $merchant, Product $product, array $row): Offer
    {
        $offer = $product->offers()->updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'external_product_id' => $row['sku'] ?? null,
                'external_url' => $row['external_url'] ?? null,
                'affiliate_url' => $row['affiliate_url'] ?? '',
                'current_price' => $row['price'],
                'original_price' => $row['original_price'],
                'currency' => $row['currency'] ?? 'BDT',
                'availability' => $row['availability'] ?? 'unknown',
                'source' => 'import',
                'status' => 'active',
                'last_synced_at' => now(),
            ]
        );

        if (! ($row['affiliate_url'] ?? null)) {
            $offer->affiliateOffer()->updateOrCreate([], [
                'offer_id' => $offer->id,
                'product_id' => $offer->product_id,
                'merchant_id' => $offer->merchant_id,
                'status' => 'pending',
                'affiliate_url' => null,
            ]);
        } else {
            $offer->affiliateOffer()->updateOrCreate([], [
                'affiliate_url' => $row['affiliate_url'],
                'normal_product_url' => $row['external_url'] ?? null,
                'status' => 'manual',
                'generation_method' => 'manual',
                'generated_at' => now(),
            ]);
        }

        app(PriceTrackingService::class)->recordPrice($offer, $offer->current_price);

        return $offer;
    }

    protected function categoryId(?string $slug): ?int
    {
        $category = $slug === null ? null : Category::where('slug', $slug)->first();

        return $category?->id;
    }

    protected function brandId(?string $slug): ?int
    {
        $brand = $slug === null ? null : Brand::where('slug', $slug)->first();

        return $brand?->id;
    }
}
