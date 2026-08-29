<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Product Generator (§14/§16): live HTML scrape + category merge-on-match input. */
class ProductGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function actingManager(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'product@tulona.test')->firstOrFail());
    }

    private function merchant(): Merchant
    {
        return Merchant::create([
            'name' => 'Store', 'slug' => 'store', 'connector_type' => 'url',
            'product_import_method' => 'html', 'status' => 'active',
        ]);
    }

    private function listingHtml(): string
    {
        return '<!DOCTYPE html><html><head><title>Tablet PC</title></head><body>
        <table class="latest-product-list table-bordered">
          <tr><th>Tablet PC List</th><th>Price</th></tr>
          <tr class="latest-product"><td class="product-name"><a href="https://shop.test/tecno-megapad-2">TECNO MEGAPAD 2</a></td><td class="product-price text-right">36,999৳</td></tr>
          <tr class="latest-product"><td class="product-name"><a href="https://shop.test/lenovo-tab-k11">Lenovo Tab K11 GEN 2</a></td><td class="product-price text-right">52,500৳</td></tr>
        </table>
        <nav class="pagination"><a href="/products?page=2">2</a><a href="/products?page=3">3</a></nav>
        </body></html>';
    }

    private function page2Html(): string
    {
        return '<!DOCTYPE html><html><body>
        <table class="latest-product-list table-bordered">
          <tr class="latest-product"><td class="product-name"><a href="https://shop.test/page2-product">PAGE TWO PRODUCT</a></td><td class="product-price text-right">1,000৳</td></tr>
        </table></body></html>';
    }

    private function detailHtml(): string
    {
        return '<!DOCTYPE html><html><head>
          <meta property="og:image" content="https://shop.test/img/photo1.jpg">
          <meta property="og:description" content="A great product description for the catalog.">
          </head><body>
          <div class="product-image"><img src="https://shop.test/img/photo2.jpg"></div>
          <span class="product-code">SKU123</span>
        </body></html>';
    }

    private function fakeHtml(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();
            if ($url === 'https://shop.test/products?page=2') {
                return Http::response($this->page2Html(), 200, ['Content-Type' => 'text/html']);
            }
            if (str_contains($url, '/tecno-megapad-2') || str_contains($url, '/lenovo-tab-k11')) {
                return Http::response($this->detailHtml(), 200, ['Content-Type' => 'text/html']);
            }

            return Http::response($this->listingHtml(), 200, ['Content-Type' => 'text/html']);
        });
    }

    public function test_html_scrape_yields_products_without_selector_config(): void
    {
        $this->actingManager();
        $this->fakeHtml();
        $merchant = $this->merchant();

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/products',
        ])->assertRedirect()->assertSessionHas('status');

        $batch = ImportBatch::latest('id')->first();
        $this->assertSame('preview', $batch->status);
        $this->assertGreaterThanOrEqual(2, $batch->items()->count());
        $names = $batch->items()->get()->map(fn ($i) => $i->normalized_data['name'] ?? null)->filter();
        $this->assertContains('TECNO MEGAPAD 2', $names);
        $this->assertContains('Lenovo Tab K11 GEN 2', $names);

        $this->get(route('admin.imports.show', $batch))
            ->assertOk()
            ->assertSee('TECNO MEGAPAD 2')
            ->assertSee('Remove')
            ->assertDontSee('page-item')
            ->assertDontSee('rel="next"');
    }

    public function test_category_input_merges_with_existing_category(): void
    {
        $this->actingManager();
        $this->fakeHtml();
        $merchant = $this->merchant();
        $existing = Category::create(['name' => 'Gaming Mice', 'slug' => Str::slug('Gaming Mice'), 'is_active' => true]);

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/products',
            'category' => 'Gaming Mice',
        ])->assertRedirect();

        $this->assertSame($existing->id, Category::where('slug', 'gaming-mice')->first()->id);
        $batch = ImportBatch::latest('id')->first();
        $this->assertSame('gaming-mice', $batch->category_slug);
        $this->assertSame(1, Category::where('slug', 'gaming-mice')->count());
    }

    public function test_category_input_creates_new_category_when_no_match(): void
    {
        $this->actingManager();
        $this->fakeHtml();
        $merchant = $this->merchant();

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/products',
            'category' => 'Brand New Category',
        ])->assertRedirect();

        $category = Category::where('slug', Str::slug('Brand New Category'))->first();
        $this->assertNotNull($category);
        $this->assertSame('Brand New Category', $category->name);
        $batch = ImportBatch::latest('id')->first();
        $this->assertSame($category->slug, $batch->category_slug);
    }

    public function test_scrape_only_generates_first_page_products(): void
    {
        $this->actingManager();
        $this->fakeHtml();
        $merchant = $this->merchant();

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/products',
        ])->assertRedirect();

        $batch = ImportBatch::latest('id')->first();
        $names = $batch->items()->get()->map(fn ($i) => $i->normalized_data['name'] ?? null)->filter();
        $this->assertContains('TECNO MEGAPAD 2', $names);
        $this->assertNotContains('PAGE TWO PRODUCT', $names);
        $this->assertSame('preview', $batch->status);
    }

    public function test_scrape_enriches_products_with_images_and_description(): void
    {
        $this->actingManager();
        $this->fakeHtml();
        $merchant = $this->merchant();

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/products',
        ])->assertRedirect();

        $batch = ImportBatch::latest('id')->first();
        $megapad = $batch->items()->get()->firstWhere('normalized_data.name', 'TECNO MEGAPAD 2');
        $this->assertNotNull($megapad);
        $this->assertContains('https://shop.test/img/photo1.jpg', $megapad->normalized_data['images']);
        $this->assertStringContainsString('great product description', $megapad->normalized_data['description']);
    }

    public function test_can_remove_single_item_from_preview_before_import(): void
    {
        $this->actingManager();
        $this->fakeHtml();
        $merchant = $this->merchant();

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/products',
        ])->assertRedirect();

        $batch = ImportBatch::latest('id')->first();
        $megapad = $batch->items()->get()->firstWhere('normalized_data.name', 'TECNO MEGAPAD 2');

        $this->delete(route('admin.imports.items.destroy', [$batch, $megapad]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $names = $batch->items()->get()->map(fn ($i) => $i->normalized_data['name'] ?? null)->filter();
        $this->assertNotContains('TECNO MEGAPAD 2', $names);
        $this->assertContains('Lenovo Tab K11 GEN 2', $names);
    }

    public function test_can_remove_selected_items_from_preview_before_import(): void
    {
        $this->actingManager();
        $this->fakeHtml();
        $merchant = $this->merchant();

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/products',
        ])->assertRedirect();

        $batch = ImportBatch::latest('id')->first();
        $ids = $batch->items()->get()
            ->whereIn('status', ['new', 'matched'])
            ->where('normalized_data.name', 'TECNO MEGAPAD 2')
            ->pluck('id');

        $this->post(route('admin.imports.remove-selected', $batch), ['items' => $ids->all()])
            ->assertRedirect()
            ->assertSessionHas('status');

        $names = $batch->items()->get()->map(fn ($i) => $i->normalized_data['name'] ?? null)->filter();
        $this->assertNotContains('TECNO MEGAPAD 2', $names);
        $this->assertContains('Lenovo Tab K11 GEN 2', $names);
    }

    public function test_out_of_stock_products_do_not_get_sibling_prices(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();

        // Generic grid (triggers the universal fallback) with an out-of-stock
        // card sitting between two priced cards — must NOT inherit their prices.
        $html = '<!DOCTYPE html><html><body><main><div class="grid">
          <div class="card"><a href="https://shop.test/items/a"><img src="/img/a.jpg" alt="Product A"><h2>Product A</h2></a><span>৳ 1,000</span></div>
          <div class="card"><a href="https://shop.test/items/b"><img src="/img/b.jpg" alt="Product B"><h2>Product B</h2></a><span class="sold-out">Out of stock</span></div>
          <div class="card"><a href="https://shop.test/items/c"><img src="/img/c.jpg" alt="Product C"><h2>Product C</h2></a><span>৳ 2,500</span></div>
        </div></main></body></html>';

        Http::fake([
            'https://shop.test/generic' => Http::response($html, 200, ['Content-Type' => 'text/html']),
            'https://shop.test/items/*' => Http::response('<html><body>product</body></html>', 200, ['Content-Type' => 'text/html']),
        ])->preventStrayRequests();

        $this->post(route('admin.imports.scrape'), [
            'merchant_id' => $merchant->id,
            'source_url' => 'https://shop.test/generic',
        ])->assertRedirect();

        $batch = ImportBatch::latest('id')->first();
        $items = $batch->items()->get();
        $byName = fn ($n) => $items->firstWhere('normalized_data.name', $n);

        $this->assertEquals(1000, $byName('Product A')->normalized_data['price']);
        $this->assertNull($byName('Product B')->normalized_data['price']);
        $this->assertEquals(2500, $byName('Product C')->normalized_data['price']);
        $this->assertSame('out_of_stock', $byName('Product B')->normalized_data['availability'] ?? null);
    }

    public function test_generator_home_offers_url_fetch_form_and_draft_count(): void
    {
        $this->actingManager();
        $this->merchant();

        $this->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Fetch multiple products from a URL')
            ->assertSee('/admin/csv-drafts/generate')
            ->assertSee('Open drafts (0)');
    }
}
