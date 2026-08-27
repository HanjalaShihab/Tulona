<?php

namespace Tests\Unit;

use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Services\PriceTrackingService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Price history + drop detection core logic (§27, §28). */
class PriceTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    protected function offerWithPrice(float $price): Offer
    {
        $product = Product::create(['category_id' => 1, 'name' => 'T', 'slug' => 't'.uniqid()]);
        $merchant = Merchant::create([
            'name' => 'M', 'slug' => 'm'.uniqid(), 'currencies' => ['BDT'],
            'base_affiliate_url' => 'https://m.example/aff',
        ]);

        return Offer::create([
            'product_id' => $product->id, 'merchant_id' => $merchant->id,
            'affiliate_url' => 'https://m.example/aff?x=1',
            'current_price' => $price, 'currency' => 'BDT',
        ]);
    }

    public function test_price_drop_creates_history_and_event(): void
    {
        $service = app(PriceTrackingService::class);
        $offer = $this->offerWithPrice(50000);

        $service->recordPrice($offer, 50000);
        $this->assertTrue($service->recordPrice($offer, 45000));

        // ৳50,000 → ৳45,000 = 10% (Build.md §28 example)
        $event = $offer->priceDropEvents()->first();
        $this->assertNotNull($event);
        $this->assertSame(10.0, (float) $event->drop_percent);
        $this->assertSame(5000.0, (float) $event->drop_amount);
        $this->assertSame(2, $offer->priceHistory()->count());
    }

    public function test_unchanged_price_creates_no_duplicate_history(): void
    {
        $service = app(PriceTrackingService::class);
        $offer = $this->offerWithPrice(100);

        $this->assertTrue($service->recordPrice($offer, 100));
        $this->assertFalse($service->recordPrice($offer, 100));
        $this->assertFalse($service->recordPrice($offer, null));
        $this->assertSame(1, $offer->priceHistory()->count());
    }

    public function test_summary_returns_null_without_enough_data(): void
    {
        $service = app(PriceTrackingService::class);
        $offer = $this->offerWithPrice(100);

        $this->assertNull($service->summaryFor($offer));
        $service->recordPrice($offer, 90);
        $this->assertNull($service->summaryFor($offer)); // still <2 points for meaningful stats

        $service->recordPrice($offer, 110);
        $summary = $service->summaryFor($offer);
        $this->assertSame(90.0, $summary['lowest']);
    }
}
