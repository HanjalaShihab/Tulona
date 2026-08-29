<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\ProductsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedSite(): void
    {
        // ProductsSeeder internally runs ProductsCatalogSeeder as well.
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductsSeeder::class);
        $this->seed(ContentSeeder::class);
    }

    public function test_homepage_renders_with_sections(): void
    {
        $this->seedSite();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Find the right product', false);
        $response->assertSee('Popular Categories');
    }

    public function test_product_page_shows_store_comparison_and_chart(): void
    {
        $this->seedSite();
        $product = Product::where('slug', 'nvidia-rtx-5070')->with('activeOffers')->firstOrFail();

        $response = $this->get('/product/nvidia-rtx-5070');
        $response->assertOk()
            ->assertSee('Compare Stores')
            ->assertSee('Price History')
            ->assertSee('Buy now');

        // Every offer CTA points at our tracked redirect — never raw affiliate URLs
        $this->assertStringNotContainsString('affiliate_url', $response->getContent());
        Offer::where('product_id', $product->id)->each(function ($o) use ($response, $product) {
            $response->assertSee('/go/'.$product->slug.'/'.$o->merchant->slug, false);
        });
    }

    public function test_search_finds_products_with_typo_tolerance(): void
    {
        $this->seedSite();

        $this->get('/search?q=rtx+5070')->assertOk()->assertSee('RTX 5070');
        $this->get('/search?q=nvid')->assertOk()->assertSee('GeForce');
    }

    public function test_category_listing_filters_and_sorts(): void
    {
        $this->seedSite();

        $this->get('/category/graphics-cards')->assertOk()->assertSee('RTX 5070 Ti 16GB OC');
        $this->get('/category/graphics-cards?sort=price_asc')->assertOk();
        $this->get('/category/graphics-cards?brand=gigabyte')->assertOk()->assertSee('RTX 5070 Ti 16GB OC');
    }

    public function test_compare_page_compares_two_products(): void
    {
        $this->seedSite();

        $this->get('/compare?products=nvidia-rtx-5070,galaxy-s26-ultra')
            ->assertOk()
            ->assertSee('Best price')
            ->assertSee('View Deal');
    }

    public function test_deals_price_drops_guides_merchants_sitemap(): void
    {
        $this->seedSite();

        $this->get('/deals')->assertOk();
        $this->get('/price-drops')->assertOk();
        $this->get('/guides')->assertOk()->assertSee('Best GPUs Under');
        $this->get('/guides/best-gpus-under-100000-bd')->assertOk()->assertSee('Affiliate disclosure');
        $this->get('/merchant/daraz')->assertOk()->assertSee('Shopping at Daraz BD');
        $this->get('/brand/gigabyte')->assertOk();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
        $this->get('/privacy-policy')->assertOk();
    }

    public function test_public_api_endpoints_respond(): void
    {
        $this->seedSite();

        $this->getJson('/api/products')->assertOk();
        $this->getJson('/api/products/nvidia-rtx-5070')->assertOk()->assertJsonPath('slug', 'nvidia-rtx-5070');
        $this->getJson('/api/products/nvidia-rtx-5070/offers')->assertOk()->assertJsonCount(5, 'offers');
        $this->getJson('/api/categories')->assertOk();
        $this->getJson('/api/search?q=iphone')->assertOk();
    }
}
