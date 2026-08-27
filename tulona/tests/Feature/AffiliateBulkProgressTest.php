<?php

namespace Tests\Feature;

use App\Jobs\ProcessAffiliateGenerations;
use App\Models\AffiliateGenerationRun;
use App\Models\AffiliateOffer;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Bulk affiliate generation progress run + polling UI (§23). */
class AffiliateBulkProgressTest extends TestCase
{
    use RefreshDatabase;

    private function actingManager(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'product@tulona.test')->firstOrFail());
    }

    private function pendingOffer(): AffiliateOffer
    {
        $merchant = Merchant::create(['name' => 'Shop', 'slug' => 'shop', 'status' => 'active']);
        $cat = Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);
        $product = Product::create(['name' => 'Mouse', 'slug' => 'mouse', 'category_id' => $cat->id, 'brand_id' => $brand->id, 'status' => 'published']);
        $offer = Offer::create(['product_id' => $product->id, 'merchant_id' => $merchant->id, 'current_price' => 9.99, 'currency' => 'GBP', 'affiliate_url' => 'https://x.test/a', 'status' => 'active']);

        return AffiliateOffer::create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'merchant_id' => $merchant->id,
            'status' => 'pending',
        ]);
    }

    public function test_bulk_generate_creates_run_and_dispatches_job(): void
    {
        Queue::fake();
        $this->actingManager();
        $offer = $this->pendingOffer();

        $this->post(route('admin.affiliate.bulk-generate'), ['merchant_id' => ''])
            ->assertRedirect()
            ->assertSessionHas('status');

        $run = AffiliateGenerationRun::first();
        $this->assertNotNull($run);
        $this->assertSame('queued', $run->status);
        $this->assertSame(1, $run->total);
        Queue::assertPushed(ProcessAffiliateGenerations::class, fn ($job) => $job->runId === $run->id);
        $this->assertSame($offer->id, AffiliateOffer::find($offer->id)->id);
    }

    public function test_generation_progress_endpoint_returns_json(): void
    {
        $this->actingManager();
        $run = AffiliateGenerationRun::create([
            'status' => 'processing',
            'total' => 100,
            'processed' => 40,
            'generated' => 30,
            'failed' => 10,
            'created_by' => auth()->id(),
        ]);

        $this->getJson(route('admin.affiliate.generation-progress', $run))
            ->assertOk()
            ->assertJson([
                'id' => $run->id,
                'status' => 'processing',
                'total' => 100,
                'processed' => 40,
                'generated' => 30,
                'failed' => 10,
                'running' => true,
                'completed' => false,
            ]);
    }

    public function test_generation_requires_permission(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'analyst@tulona.test')->firstOrFail());
        $run = AffiliateGenerationRun::create(['status' => 'queued', 'total' => 0]);

        $this->post(route('admin.affiliate.bulk-generate'))->assertForbidden();
        $this->getJson(route('admin.affiliate.generation-progress', $run))->assertForbidden();
    }
}
