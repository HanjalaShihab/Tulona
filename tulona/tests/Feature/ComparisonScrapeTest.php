<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\ImportBatch;
use App\Models\ImportItem;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Services\Scraping\UrlScrapeService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/** Comparison scraping with common-product detection (§31–§33). */
class ComparisonScrapeTest extends TestCase
{
    use RefreshDatabase;

    private function actingContent(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'content@tulona.test')->firstOrFail());
    }

    private function merchant(string $name): Merchant
    {
        return Merchant::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
            'affiliate_enabled' => true,
        ]);
    }

    private function product(Category $cat, Brand $brand, string $name): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => $cat->id,
            'brand_id' => $brand->id,
            'status' => 'published',
        ]);
    }

    private function offer(Product $p, Merchant $m, float $price): Offer
    {
        return Offer::create([
            'product_id' => $p->id,
            'merchant_id' => $m->id,
            'current_price' => $price,
            'currency' => 'GBP',
            'availability' => 'in_stock',
            'affiliate_url' => 'https://'.$m->slug.'.test/offer',
            'status' => 'active',
        ]);
    }

    public function test_scrape_requires_editor_permission(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'analyst@tulona.test')->firstOrFail());

        $comparison = Comparison::create(['title' => 'T', 'slug' => 't', 'status' => 'draft']);

        $this->post(route('admin.comparisons.scrape', $comparison), ['entries' => []])->assertForbidden();
    }

    public function test_scrape_detects_products_common_to_multiple_merchants(): void
    {
        $this->actingContent();
        $cat = Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);

        $m1 = $this->merchant('Shop One');
        $m2 = $this->merchant('Shop Two');
        $prodA = $this->product($cat, $brand, 'Mouse A');
        $prodB = $this->product($cat, $brand, 'Mouse B');
        $comparison = Comparison::create(['title' => 'Mice', 'slug' => 'mice', 'status' => 'draft']);

        $mock = Mockery::mock(UrlScrapeService::class);
        $mock->shouldReceive('scrape')->twice()->andReturnUsing(function (ImportBatch $batch) use ($m1, $prodA, $prodB) {
            $ids = $batch->merchant_id === $m1->id ? [$prodA->id, $prodB->id] : [$prodA->id];
            foreach ($ids as $i => $pid) {
                ImportItem::create([
                    'import_batch_id' => $batch->id,
                    'source_identifier' => "sku-{$batch->merchant_id}-{$i}",
                    'product_id' => $pid,
                    'match_type' => 'gtin',
                    'status' => 'matched',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $batch->update(['status' => 'preview', 'total_rows' => count($ids)]);
        });
        $this->app->instance(UrlScrapeService::class, $mock);

        $this->post(route('admin.comparisons.scrape', $comparison), [
            'entries' => [
                ['merchant_id' => $m1->id, 'source_url' => 'https://shop-one.test/products'],
                ['merchant_id' => $m2->id, 'source_url' => 'https://shop-two.test/products'],
            ],
        ])->assertRedirect(route('admin.comparisons.edit', $comparison))
            ->assertSessionHas('comparisonScrape', fn ($v) => isset($v['common_ids']) && in_array($prodA->id, $v['common_ids']) && ! in_array($prodB->id, $v['common_ids']));
    }

    public function test_attach_common_adds_products_and_their_offers(): void
    {
        $this->actingContent();
        $cat = Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);

        $m1 = $this->merchant('Shop One');
        $m2 = $this->merchant('Shop Two');
        $prodA = $this->product($cat, $brand, 'Mouse A');
        $prodB = $this->product($cat, $brand, 'Mouse B');
        $oA1 = $this->offer($prodA, $m1, 19.99);
        $oA2 = $this->offer($prodA, $m2, 21.50);
        $oB1 = $this->offer($prodB, $m1, 29.99);
        $comparison = Comparison::create(['title' => 'Mice', 'slug' => 'mice', 'status' => 'draft']);

        $this->post(route('admin.comparisons.attach-common', $comparison), [
            'product_ids' => [$prodA->id, $prodB->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('comparison_offer', ['comparison_id' => $comparison->id, 'offer_id' => $oA1->id]);
        $this->assertDatabaseHas('comparison_offer', ['comparison_id' => $comparison->id, 'offer_id' => $oA2->id]);
        $this->assertDatabaseHas('comparison_offer', ['comparison_id' => $comparison->id, 'offer_id' => $oB1->id]);
        $this->assertDatabaseHas('comparison_product', ['comparison_id' => $comparison->id, 'product_id' => $prodA->id]);
        $this->assertDatabaseHas('comparison_product', ['comparison_id' => $comparison->id, 'product_id' => $prodB->id]);
    }
}
