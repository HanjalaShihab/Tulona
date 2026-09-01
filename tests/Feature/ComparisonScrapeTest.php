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
        $this->markTestSkipped('Comparison admin routes removed with product comparison feature.');
    }

    public function test_scrape_detects_products_common_to_multiple_merchants(): void
    {
        $this->markTestSkipped('Comparison admin routes removed.');
    }

    public function test_attach_common_adds_products_and_their_offers(): void
    {
        $this->markTestSkipped('Comparison admin routes removed.');
    }
}
