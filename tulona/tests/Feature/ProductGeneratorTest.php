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

    private function fakeHtml(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Tablet PC</title></head><body>
        <table class="latest-product-list table-bordered">
          <tr><th>Tablet PC List</th><th>Price</th></tr>
          <tr class="latest-product"><td class="product-name"><a href="https://shop.test/tecno-megapad-2">TECNO MEGAPAD 2</a></td><td class="product-price text-right">36,999৳</td></tr>
          <tr class="latest-product"><td class="product-name"><a href="https://shop.test/lenovo-tab-k11">Lenovo Tab K11 GEN 2</a></td><td class="product-price text-right">52,500৳</td></tr>
        </table></body></html>';

        Http::fake(['https://shop.test/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);
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
}
