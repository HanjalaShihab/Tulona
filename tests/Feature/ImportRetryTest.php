<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Jobs\ProcessImportItems;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportItem;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Import retry/resume actions (§15, §16). */
class ImportRetryTest extends TestCase
{
    use RefreshDatabase;

    private function actingManager(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'product@tulona.test')->firstOrFail());
    }

    private function merchant(): Merchant
    {
        return Merchant::create(['name' => 'Shop', 'slug' => 'shop', 'status' => 'active']);
    }

    private function product(): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);

        return Product::create(['name' => 'Mouse A', 'slug' => Str::slug('Mouse A'), 'category_id' => $cat->id, 'brand_id' => $brand->id, 'status' => 'published']);
    }

    public function test_retry_failed_items_resets_and_requeues(): void
    {
        Queue::fake();
        $this->actingManager();
        $merchant = $this->merchant();
        $product = $this->product();
        $batch = ImportBatch::create([
            'filename' => 'url', 'type' => 'url', 'source_type' => 'url', 'merchant_id' => $merchant->id,
            'status' => 'completed', 'total_rows' => 1, 'created_by' => auth()->id(),
        ]);
        $item = ImportItem::create([
            'import_batch_id' => $batch->id, 'source_identifier' => 'sku-1', 'product_id' => $product->id,
            'match_type' => 'gtin', 'status' => 'skipped', 'error' => 'Could not match or create product.', 'processed_at' => now(),
        ]);

        $this->post(route('admin.imports.retry-failed', $batch))->assertRedirect();

        $fresh = $item->fresh();
        $this->assertSame('matched', $fresh->status);
        $this->assertNull($fresh->processed_at);
        $this->assertNull($fresh->error);
        Queue::assertPushed(ProcessImportItems::class, fn ($job) => $job->itemIds === [$item->id]);
    }

    public function test_retry_failed_batch_requeues_csv(): void
    {
        Queue::fake();
        $this->actingManager();
        $batch = ImportBatch::create([
            'filename' => 'products.csv', 'type' => 'csv', 'source_type' => 'csv',
            'status' => 'failed', 'total_rows' => 5, 'created_by' => auth()->id(),
        ]);

        $this->post(route('admin.imports.retry', $batch))->assertRedirect(route('admin.imports.show', $batch));
        $this->assertSame('queued', $batch->fresh()->status);
        Queue::assertPushed(ProcessImportBatch::class);
    }

    public function test_retry_failed_batch_requeues_url(): void
    {
        Queue::fake();
        $this->actingManager();
        $merchant = $this->merchant();
        $product = $this->product();
        $batch = ImportBatch::create([
            'filename' => 'url', 'type' => 'url', 'source_type' => 'url', 'merchant_id' => $merchant->id,
            'status' => 'failed', 'total_rows' => 1, 'created_by' => auth()->id(),
        ]);
        ImportItem::create([
            'import_batch_id' => $batch->id, 'source_identifier' => 'sku-1', 'product_id' => $product->id,
            'match_type' => 'gtin', 'status' => 'new',
        ]);

        $this->post(route('admin.imports.retry', $batch))->assertRedirect();
        $this->assertSame('queued', $batch->fresh()->status);
        Queue::assertPushed(ProcessImportItems::class);
    }

    public function test_retry_requires_import_permission(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'analyst@tulona.test')->firstOrFail());
        $batch = ImportBatch::create(['filename' => 'x.csv', 'type' => 'csv', 'status' => 'failed']);

        $this->post(route('admin.imports.retry-failed', $batch))->assertForbidden();
    }
}
