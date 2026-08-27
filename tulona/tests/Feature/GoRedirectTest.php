<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The heart of the affiliate system (§5, §55). */
class GoRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function seedOne(): array
    {
        $this->seedTestDb();

        $product = Product::firstOrFail();
        $offer = Offer::where('product_id', $product->id)->firstOrFail();

        return [$product, $offer];
    }

    /** Run seeders via artisan so --force works in tests. */
    protected function seedTestDb(): void
    {
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\CatalogSeeder', '--force' => true]);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\ProductsSeeder', '--force' => true]);
    }

    public function test_click_is_recorded_and_user_redirected_to_affiliate_url(): void
    {
        [$product, $offer] = $this->seedOne();

        $before = Click::count();

        $response = $this->get("/go/{$product->slug}/{$offer->merchant->slug}", [
            'referer' => url("/product/{$product->slug}"),
        ]);

        $response->assertStatus(302);
        $destination = $response->headers->get('Location');
        $this->assertStringStartsWith($offer->merchant->base_affiliate_url, $destination);
        $this->assertStringContainsString('subid=', $destination); // tracking preserved

        $this->assertSame($before + 1, Click::count());
        $click = Click::latest('id')->first();
        $this->assertSame($offer->id, $click->offer_id);
        $this->assertEquals("/product/{$product->slug}", $click->referrer_page); // internal path kept
        $this->assertSame(64, strlen((string) $click->ip_hash)); // hashed, never raw IP
    }

    public function test_invalid_combinations_are_404(): void
    {
        [$product, $offer] = $this->seedOne();

        $this->get("/go/{$product->slug}/does-not-exist")->assertNotFound();
        $this->get('/go/nope/nope')->assertNotFound();

        // No clicks recorded for failed lookups
        $this->assertSame(0, Click::count());
    }
}
