<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Services\ComparisonEngineService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Best-deal manual override in the comparison editor (§36). */
class BestDealOverrideTest extends TestCase
{
    use RefreshDatabase;

    private function actingContent(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'content@tulona.test')->firstOrFail());
    }

    private function merchant(string $name): Merchant
    {
        return Merchant::create(['name' => $name, 'slug' => Str::slug($name), 'status' => 'active']);
    }

    private function makeFixture(): array
    {
        $cat = Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);
        $p = Product::create(['name' => 'Mouse', 'slug' => 'mouse', 'category_id' => $cat->id, 'brand_id' => $brand->id, 'status' => 'published']);
        $m1 = $this->merchant('Store A');
        $m2 = $this->merchant('Store B');
        $o1 = Offer::create(['product_id' => $p->id, 'merchant_id' => $m1->id, 'current_price' => 49, 'currency' => 'GBP', 'affiliate_url' => 'https://a.test/x', 'status' => 'active']);
        $o2 = Offer::create(['product_id' => $p->id, 'merchant_id' => $m2->id, 'current_price' => 39, 'currency' => 'GBP', 'affiliate_url' => 'https://b.test/x', 'status' => 'active']);
        $comparison = Comparison::create(['title' => 'Mice', 'slug' => 'mice', 'status' => 'draft']);
        $comparison->offers()->attach([
            $o1->id => ['product_id' => $p->id, 'sort_order' => 0, 'is_hidden' => false],
            $o2->id => ['product_id' => $p->id, 'sort_order' => 1, 'is_hidden' => false],
        ]);

        return compact('comparison', 'p', 'o1', 'o2');
    }

    public function test_sync_offer_overrides_persists_best_deal_flag(): void
    {
        $this->markTestSkipped('Comparison admin routes removed with product comparison feature.');
    }

    public function test_only_one_best_deal_allowed_per_product(): void
    {
        $this->markTestSkipped('Comparison admin routes removed with product comparison feature.');
    }

    public function test_best_deal_override_wins_over_heuristic(): void
    {
        $this->actingContent();
        ['comparison' => $c, 'o1' => $o1, 'o2' => $o2] = $this->makeFixture();

        // o2 is cheaper (heuristic picks o2), but admin flags o1 as the best deal.
        $comparison = $c->fresh();
        $comparison->offers()->updateExistingPivot($o1->id, ['is_best_deal' => true]);

        $best = app(ComparisonEngineService::class)->bestDeal($comparison->fresh());

        $this->assertNotNull($best);
        $this->assertSame($o1->id, $best['offer']->id);
    }

    public function test_best_deal_overrides_require_content_permission(): void
    {
        $this->markTestSkipped('Comparison admin routes removed with product comparison feature.');
    }
}
