<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Scrape & Post (§: single product detail → edit → publish into a category). */
class ScrapePostTest extends TestCase
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
            'name' => 'Rokomari', 'slug' => 'rokomari', 'connector_type' => 'url',
            'product_import_method' => 'html', 'status' => 'active',
        ]);
    }

    private function bookPage(): string
    {
        return '<!DOCTYPE html><html><head>
            <meta property="og:title" content="The Alchemist">
            <meta property="og:image" content="https://shop.test/img/alchemist.jpg">
            <meta property="og:description" content="A beautiful book about following your dreams.">
            <script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"The Alchemist","image":"https://shop.test/img/alchemist.jpg","description":"A beautiful book about following your dreams.","sku":"BK-1001","offers":{"@type":"Offer","price":"450","priceCurrency":"BDT","availability":"https://schema.org/InStock"}}</script>
            </head><body><h1>The Alchemist</h1><span>৳ 450</span></body></html>';
    }

    public function test_scrape_single_product_fills_editable_draft(): void
    {
        $this->actingManager();
        Http::fake(['https://shop.test/books/alchemist' => Http::response($this->bookPage(), 200, ['Content-Type' => 'text/html'])]);

        $this->post(route('admin.scrape-post.scrape'), [
            'source_url' => 'https://shop.test/books/alchemist',
        ])->assertRedirect(route('admin.scrape-post.edit'))->assertSessionHas('status');

        $draft = session('scrape_post.draft');
        $this->assertIsArray($draft);
        $this->assertSame('The Alchemist', $draft['name']);
        $this->assertEquals(450, $draft['price']);
        $this->assertContains('https://shop.test/img/alchemist.jpg', $draft['images']);
        $this->assertStringContainsString('dreams', $draft['description']);
        $this->assertNull($draft['merchant_id']);
    }

    public function test_scrape_requires_only_the_url(): void
    {
        $this->actingManager();

        $this->post(route('admin.scrape-post.scrape'), [])
            ->assertSessionHasErrors('source_url');
    }

    public function test_merchant_is_auto_detected_from_url_host(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        $merchant->update(['website_url' => 'https://www.rokomari.com']);
        Http::fake(['https://www.rokomari.com/book/12345' => Http::response($this->bookPage(), 200, ['Content-Type' => 'text/html'])]);

        $this->post(route('admin.scrape-post.scrape'), [
            'source_url' => 'https://www.rokomari.com/book/12345',
        ])->assertRedirect(route('admin.scrape-post.edit'));

        $draft = session('scrape_post.draft');
        $this->assertSame($merchant->id, $draft['merchant_id']);
    }

    public function test_scrape_keeps_discounted_price_and_del_original(): void
    {
        $this->actingManager();
        Http::fake(['https://shop.test/books/sale' => Http::response(
            '<!DOCTYPE html><html><head>
                <meta property="og:title" content="Discounted Book">
                <meta property="og:image" content="https://shop.test/img/book.jpg">
            </head><body>
                <h1>Discounted Book</h1>
                <span class="sale-price">৳ 450</span>
                <del class="regular-price">৳ 500</del>
            </body></html>',
            200, ['Content-Type' => 'text/html'])]);

        $this->post(route('admin.scrape-post.scrape'), [
            'source_url' => 'https://shop.test/books/sale',
        ])->assertRedirect(route('admin.scrape-post.edit'));

        $draft = session('scrape_post.draft');
        $this->assertEquals(450, $draft['price']);
        $this->assertEquals(500, $draft['original_price']);
    }

    public function test_dom_sale_pair_overrides_jsonld_reference_price(): void
    {
        $this->actingManager();
        Http::fake(['https://shop.test/books/sale-json' => Http::response(
            '<!DOCTYPE html><html><head>
                <meta property="og:title" content="Sale Book">
                <script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"Sale Book","offers":{"@type":"Offer","price":"500","priceCurrency":"BDT"}}</script>
            </head><body>
                <h1>Sale Book</h1>
                <span itemprop="price">৳ 450</span>
                <del>৳ 500</del>
            </body></html>',
            200, ['Content-Type' => 'text/html'])]);

        $this->post(route('admin.scrape-post.scrape'), [
            'source_url' => 'https://shop.test/books/sale-json',
        ])->assertRedirect(route('admin.scrape-post.edit'));

        $draft = session('scrape_post.draft');
        $this->assertEquals(450, $draft['price']);
        $this->assertEquals(500, $draft['original_price']);
    }

    public function test_edit_page_renders_scraped_draft(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        session(['scrape_post.draft' => [
            'source_url' => 'https://shop.test/books/alchemist',
            'external_url' => 'https://shop.test/books/alchemist',
            'merchant_id' => $merchant->id,
            'name' => 'The Alchemist',
            'price' => 450,
            'description' => 'A beautiful book.',
            'image' => 'https://shop.test/img/alchemist.jpg',
            'category' => 'Books',
        ]]);

        $this->get(route('admin.scrape-post.edit'))
            ->assertOk()
            ->assertSee('The Alchemist')
            ->assertSee('Post product')
            ->assertSee('Remove / New search');
    }

    public function test_post_creates_published_product_in_category_with_subcategory_offer_and_affiliate(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        session(['scrape_post.draft' => [
            'source_url' => 'https://shop.test/books/alchemist',
            'external_url' => 'https://shop.test/books/alchemist',
        ]]);

        $this->post(route('admin.scrape-post.post'), [
            'name' => 'The Alchemist',
            'merchant_id' => $merchant->id,
            'category' => 'Books',
            'subcategory' => 'Bengali Fiction',
            'affiliate_url' => 'https://track.rokkomari.example/?pid=123',
            'current_price' => 450,
            'original_price' => 500,
            'currency' => 'BDT',
            'description' => 'A beautiful book about following your dreams.',
            'image' => 'https://shop.test/img/alchemist.jpg',
            'availability' => 'in_stock',
        ])->assertRedirect(route('admin.scrape-post.index'))->assertSessionHas('status');

        $this->assertNull(session('scrape_post.draft'));

        $parent = Category::where('slug', 'books')->first();
        $sub = Category::where('slug', 'bengali-fiction')->first();
        $this->assertNotNull($parent);
        $this->assertNotNull($sub);
        $this->assertSame($parent->id, $sub->parent_id);

        $product = Product::where('slug', Str::slug('The Alchemist'))->first();
        $this->assertNotNull($product);
        $this->assertSame('published', $product->status);
        $this->assertSame($sub->id, $product->category_id);
        $this->assertSame('https://shop.test/img/alchemist.jpg', $product->images()->first()->path);

        $offer = Offer::where('product_id', $product->id)->where('merchant_id', $merchant->id)->first();
        $this->assertNotNull($offer);
        $this->assertEquals(450, $offer->current_price);
        $this->assertSame('in_stock', $offer->availability);
        $this->assertSame('https://track.rokkomari.example/?pid=123', $offer->affiliateOffer->affiliate_url);

        // Appears on the landing page and in the Books category section.
        $this->get(route('home'))->assertOk()->assertSee('The Alchemist')->assertSee('New Arrivals');
        $this->get(route('categories.show', 'books'))->assertOk()->assertSee('The Alchemist');
    }

    public function test_subcategory_optional_posts_directly_into_category(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        session(['scrape_post.draft' => ['external_url' => null, 'source_url' => 'https://shop.test/x']]);

        $this->post(route('admin.scrape-post.post'), [
            'name' => 'A Simple Book',
            'merchant_id' => $merchant->id,
            'category' => 'Books',
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'current_price' => 300,
            'affiliate_url' => 'https://track.rokkomari.example/?pid=999',
        ])->assertRedirect();

        $product = Product::where('slug', 'a-simple-book')->first();
        $books = Category::where('slug', 'books')->first();
        $this->assertNotNull($product);
        $this->assertSame($books->id, $product->category_id);
    }

    public function test_post_requires_merchant_category_and_affiliate_link(): void
    {
        $this->actingManager();
        $this->merchant();
        session(['scrape_post.draft' => ['external_url' => 'https://shop.test/books/x', 'source_url' => 'https://shop.test/books/x']]);

        $this->post(route('admin.scrape-post.post'), [
            'name' => 'Incomplete Book',
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'current_price' => 100,
        ])->assertSessionHasErrors(['merchant_id', 'category', 'affiliate_url']);
    }

    public function test_reset_discards_scraped_draft(): void
    {
        $this->actingManager();
        session(['scrape_post.draft' => ['name' => 'The Alchemist']]);

        $this->post(route('admin.scrape-post.reset'))
            ->assertRedirect(route('admin.scrape-post.index'))
            ->assertSessionHas('status');

        $this->assertNull(session('scrape_post.draft'));
    }

    public function test_posting_same_product_from_second_merchant_merges_into_one_compare_page(): void
    {
        $this->actingManager();
        $rokomari = $this->merchant();
        $daraz = Merchant::create([
            'name' => 'Daraz BD', 'slug' => 'daraz-bd', 'connector_type' => 'url',
            'product_import_method' => 'html', 'status' => 'active',
        ]);

        session(['scrape_post.draft' => ['external_url' => 'https://www.rokomari.com/book/456', 'source_url' => 'https://www.rokomari.com/book/456']]);
        $this->post(route('admin.scrape-post.post'), [
            'name' => 'The Alchemist',
            'merchant_id' => $rokomari->id,
            'category' => 'Books',
            'affiliate_url' => 'https://track.rokomari.example/?a=1',
            'current_price' => 450,
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'image' => 'https://shop.test/img/alchemist-jacket.jpg',
        ])->assertRedirect(route('admin.scrape-post.index'));

        session(['scrape_post.draft' => ['external_url' => 'https://www.daraz.example/b/bd/alchemist', 'source_url' => 'https://www.daraz.example/b/bd/alchemist']]);
        $this->post(route('admin.scrape-post.post'), [
            'name' => 'The Alchemist',
            'merchant_id' => $daraz->id,
            'category' => 'Books',
            'affiliate_url' => 'https://track.daraz.example/?x=2',
            'current_price' => 425,
            'original_price' => 480,
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'image' => 'https://shop.test/img/alchemist-daraz.jpg',
        ])->assertRedirect(route('admin.scrape-post.index'));

        // One product row — with one offer per merchant.
        $this->assertSame(1, Product::count());
        $product = Product::first();
        $this->assertSame('published', $product->status);
        $this->assertSame(2, $product->offers()->count());

        // Both stores show up side by side in the Compare Stores section.
        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('Compare Stores')
            ->assertSee('Rokomari')
            ->assertSee('Daraz BD');
    }

    public function test_similarly_named_product_from_second_merchant_merges_keeping_main_image(): void
    {
        $this->actingManager();
        $rokomari = $this->merchant();
        $daraz = Merchant::create([
            'name' => 'Daraz BD', 'slug' => 'daraz-bd', 'connector_type' => 'url',
            'product_import_method' => 'html', 'status' => 'active',
        ]);

        session(['scrape_post.draft' => ['external_url' => 'https://www.rokomari.com/electronics/abc', 'source_url' => 'https://www.rokomari.com/electronics/abc']]);
        $this->post(route('admin.scrape-post.post'), [
            'name' => 'A4Tech HS-19-1 Headset Grey',
            'merchant_id' => $rokomari->id,
            'category' => 'Electronics',
            'subcategory' => 'Headphones',
            'affiliate_url' => 'https://track.rokomari.example/?p=1',
            'current_price' => 1500,
            'original_price' => 1800,
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'image' => 'https://shop.test/img/headset-rokomari.jpg',
        ])->assertRedirect(route('admin.scrape-post.index'));

        $first = Product::first();

        session(['scrape_post.draft' => ['external_url' => 'https://www.daraz.example/p/headset', 'source_url' => 'https://www.daraz.example/p/headset']]);
        $this->post(route('admin.scrape-post.post'), [
            'name' => 'A4 Tech HS-19-1 Headset (Gray)',
            'merchant_id' => $daraz->id,
            'category' => 'Electronics',
            'subcategory' => 'Headphones',
            'affiliate_url' => 'https://track.daraz.example/?p=2',
            'current_price' => 1400,
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'image' => 'https://shop.test/img/headset-daraz.jpg',
        ])->assertRedirect(route('admin.scrape-post.index'));

        $this->assertSame(1, Product::count());
        $product = Product::findOrFail($first->id);
        $this->assertSame($first->id, $product->id);
        $this->assertSame(2, $product->offers()->count());

        // The first merchant's image stays as the main image when merging.
        $this->assertSame('https://shop.test/img/headset-rokomari.jpg', $product->images()->where('is_main', true)->first()->path);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('Compare Stores')
            ->assertSee('Rokomari')
            ->assertSee('Daraz BD');
    }

    public function test_edit_page_lists_landing_page_categories_as_fixed_options(): void
    {
        $this->actingManager();
        $this->merchant();
        session(['scrape_post.draft' => [
            'source_url' => 'https://shop.test/books/alchemist',
            'external_url' => 'https://shop.test/books/alchemist',
            'name' => 'The Alchemist',
            'price' => 450,
        ]]);

        $this->get(route('admin.scrape-post.edit'))
            ->assertOk()
            ->assertSee('landing-page category')
            ->assertSee('Books')
            ->assertSee('Other / new category');
    }

    public function test_post_using_fixed_category_id_publishes_into_that_category(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        $books = Category::where('slug', 'books')->firstOrFail();
        session(['scrape_post.draft' => ['external_url' => 'https://shop.test/books/x', 'source_url' => 'https://shop.test/books/x']]);

        $this->post(route('admin.scrape-post.post'), [
            'name' => 'Fixed Category Book',
            'merchant_id' => $merchant->id,
            'category_id' => $books->id,
            'subcategory' => '',
            'affiliate_url' => 'https://track.rokkomari.example/?pid=777',
            'current_price' => 350,
            'currency' => 'BDT',
            'availability' => 'in_stock',
        ])->assertRedirect(route('admin.scrape-post.index'))->assertSessionHas('status');

        $product = Product::where('slug', 'fixed-category-book')->first();
        $this->assertNotNull($product);
        $this->assertSame($books->id, $product->category_id);
        $this->assertSame('published', $product->status);
    }

    public function test_post_requires_landing_page_category_or_new_category_name(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        session(['scrape_post.draft' => ['external_url' => 'https://shop.test/x', 'source_url' => 'https://shop.test/x']]);

        $this->post(route('admin.scrape-post.post'), [
            'name' => 'No Category Book',
            'merchant_id' => $merchant->id,
            'category_id' => '',
            'category' => '',
            'affiliate_url' => 'https://track.rokkomari.example/?pid=1',
            'currency' => 'BDT',
            'availability' => 'in_stock',
        ])->assertSessionHasErrors('category');

        $this->assertSame(0, Product::count());
    }

    public function test_post_with_homepage_flags_promotes_product_into_landing_page_sections(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        session(['scrape_post.draft' => ['external_url' => 'https://shop.test/books/x', 'source_url' => 'https://shop.test/books/x']]);

        $this->post(route('admin.scrape-post.post'), [
            'name' => 'Trending Banner Book',
            'merchant_id' => $merchant->id,
            'category' => 'Books',
            'affiliate_url' => 'https://track.rokkomari.example/?pid=33',
            'current_price' => 250,
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'is_trending' => '1',
            'is_featured' => '1',
            'is_top_selling' => '1',
        ])->assertRedirect(route('admin.scrape-post.index'))->assertSessionHas('status');

        $product = Product::where('slug', 'trending-banner-book')->first();
        $this->assertNotNull($product);
        $this->assertTrue((bool) $product->is_trending);
        $this->assertTrue((bool) $product->is_featured);
        $this->assertTrue((bool) $product->is_top_selling);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Top Selling Products')
            ->assertSee('Trending Products')
            ->assertSee('Featured');
    }

    public function test_merge_of_second_merchant_keeps_and_only_promotes_homepage_flags(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();
        session(['scrape_post.draft' => ['external_url' => 'https://shop.test/books/x', 'source_url' => 'https://shop.test/books/x']]);

        $this->post(route('admin.scrape-post.post'), [
            'name' => 'Shared Flags Book',
            'merchant_id' => $merchant->id,
            'category' => 'Books',
            'affiliate_url' => 'https://track.rokkomari.example/?pid=44',
            'current_price' => 400,
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'is_top_selling' => '1',
        ])->assertRedirect();

        // Second store for the same product, no homepage toggles ticked.
        $daraz = Merchant::create([
            'name' => 'Daraz BD', 'slug' => 'daraz-bd', 'connector_type' => 'url',
            'product_import_method' => 'html', 'status' => 'active',
        ]);
        session(['scrape_post.draft' => ['external_url' => 'https://shop.test/books/x2', 'source_url' => 'https://shop.test/books/x2']]);
        $this->post(route('admin.scrape-post.post'), [
            'name' => 'Shared Flags Book',
            'merchant_id' => $daraz->id,
            'category' => 'Books',
            'affiliate_url' => 'https://track.rokkomari.example/?pid=45',
            'current_price' => 380,
            'currency' => 'BDT',
            'availability' => 'in_stock',
        ])->assertRedirect();

        $this->assertSame(1, Product::count());
        $product = Product::first();
        $this->assertTrue((bool) $product->is_top_selling);
    }
}
