<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Click;
use App\Models\Comparison;
use App\Models\LandingPage;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Admin Analytics (§ visitor/engagement analytics): only real, tracked data is
 * rendered — visitor/impression metrics that lack backend tracking must show an
 * honest "awaiting tracking" state, never fabricated numbers.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAnalyst(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'analyst@tulona.test')->firstOrFail());
    }

    protected function fixture(): array
    {
        $category = Category::create(['name' => 'Gaming Gear', 'slug' => 'gaming-gear', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Razer', 'slug' => 'razer']);
        $merchant = Merchant::create(['name' => 'Star Tech', 'slug' => 'star-tech', 'status' => 'active']);
        $product = Product::create([
            'name' => 'Logitech G102', 'slug' => Str::slug('Logitech G102'),
            'category_id' => $category->id, 'brand_id' => $brand->id, 'status' => 'published',
        ]);
        $offer = Offer::create([
            'product_id' => $product->id, 'merchant_id' => $merchant->id,
            'affiliate_url' => 'https://track.example/go', 'current_price' => 1790,
            'status' => 'active',
        ]);

        return compact('category', 'brand', 'merchant', 'product', 'offer');
    }

    protected function click(Product $product, Merchant $merchant, Offer $offer, array $extra = []): void
    {
        Click::create(array_merge([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'merchant_id' => $merchant->id,
            'referrer_page' => '/product/'.$product->slug,
            'ip_hash' => hash('sha256', '1.2.3.4'),
            'user_agent_family' => 'desktop',
            'clicked_at' => now(),
            'clicked_on' => now()->toDateString(),
        ], $extra));
    }

    public function test_analytics_pages_require_view_analytics_permission(): void
    {
        $this->actingAnalyst();
        $this->get(route('admin.analytics'))->assertOk();

        $this->actingAs(User::where('email', 'product@tulona.test')->firstOrFail());
        $this->get(route('admin.analytics'))->assertForbidden();
    }

    public function test_overview_shows_real_click_metrics_and_honest_awaited_states(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $this->click($f['product'], $f['merchant'], $f['offer']);

        $this->get(route('admin.analytics', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Affiliate Clicks', false)
            ->assertSee('awaits tracking', false)
            ->assertSee('Logitech G102')
            ->assertSee('Star Tech')
            ->assertSee('Opportunities')
            ->assertSee('Gaming Gear');
    }

    public function test_period_selector_filters_click_metrics(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $this->click($f['product'], $f['merchant'], $f['offer'], ['clicked_at' => now()->subDays(2), 'clicked_on' => now()->subDays(2)->toDateString()]);

        $this->get(route('admin.analytics', ['period' => 'today']))
            ->assertOk()
            ->assertDontSee('Star Tech');

        $this->get(route('admin.analytics', ['period' => '7d']))
            ->assertOk()
            ->assertSee('Star Tech');
    }

    public function test_visitors_page_shows_awaited_states_and_clicker_signals(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $this->click($f['product'], $f['merchant'], $f['offer']);

        $this->get(route('admin.analytics.visitors'))
            ->assertOk()
            ->assertSee('Unique Visitors', false)
            ->assertSee('Visitor tracking is not enabled')
            ->assertSee('Unique clickers (hashed)')
            ->assertSee('Product pages');
    }

    public function test_products_page_sorts_by_real_clicks_and_marks_views_ctr_awaited(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $this->click($f['product'], $f['merchant'], $f['offer']);

        $this->get(route('admin.analytics.products'))
            ->assertOk()
            ->assertSee('Logitech G102')
            ->assertSee('Most Clicked')
            ->assertSee('Most Viewed', false);
    }

    public function test_clicks_page_breaks_down_by_merchant(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $this->click($f['product'], $f['merchant'], $f['offer']);

        $this->get(route('admin.analytics.clicks'))
            ->assertOk()
            ->assertSee('Star Tech')
            ->assertSee('Merchant breakdown');
    }

    public function test_search_page_never_fabricates_queries(): void
    {
        $this->actingAnalyst();

        $this->get(route('admin.analytics.search'))
            ->assertOk()
            ->assertSee('Search queries are not logged yet')
            ->assertSee('Searches with no results');
    }

    public function test_comparisons_page_counts_clicks_from_comparison_urls(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $comparison = Comparison::create([
            'title' => 'Best Gaming Mouse Under ৳3,000',
            'slug' => 'best-gaming-mouse-under-3000',
            'status' => 'published', 'published_at' => now(),
        ]);
        $this->click($f['product'], $f['merchant'], $f['offer'], ['referrer_page' => '/'.$comparison->slug]);

        $this->get(route('admin.analytics.comparisons'))
            ->assertOk()
            ->assertSee('Best Gaming Mouse Under ৳3,000')
            ->assertSee('Clicks from comparison pages');
    }

    public function test_categories_page_groups_clicks_by_category(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $this->click($f['product'], $f['merchant'], $f['offer']);

        $this->get(route('admin.analytics.categories'))
            ->assertOk()
            ->assertSee('Gaming Gear');
    }

    public function test_sources_and_devices_pages_are_honest(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        $this->click($f['product'], $f['merchant'], $f['offer']);

        $this->get(route('admin.analytics.sources'))
            ->assertOk()
            ->assertSee('External referrers are not recorded')
            ->assertSee('Product pages');

        $this->get(route('admin.analytics.devices'))
            ->assertOk()
            ->assertSee('Desktop')
            ->assertSee('Device split (clicks)');
    }

    public function test_landing_pages_page_counts_referrer_clicks(): void
    {
        $this->actingAnalyst();
        $f = $this->fixture();
        LandingPage::create(['title' => 'Best Gaming Mouse', 'slug' => 'best-gaming-mouse', 'status' => 'published', 'published_at' => now()]);
        $this->click($f['product'], $f['merchant'], $f['offer'], ['referrer_page' => '/landing/best-gaming-mouse']);

        $this->get(route('admin.analytics.landing-pages'))
            ->assertOk()
            ->assertSee('Best Gaming Mouse');
    }

    public function test_sidebar_and_tabs_exist_on_analytics_pages(): void
    {
        $this->actingAnalyst();

        $this->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Visitors', false)
            ->assertSee('Traffic Sources', false)
            ->assertSee('Landing Pages', false);
    }
}
