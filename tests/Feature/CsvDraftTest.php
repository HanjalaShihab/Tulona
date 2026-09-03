<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductDraft;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Upload CSV → review → post products individually (Product Generator). */
class CsvDraftTest extends TestCase
{
    use RefreshDatabase;

    private function actingManager(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'product@tulona.test')->firstOrFail());
    }

    private function merchant(string $slug = 'rokomari'): Merchant
    {
        return Merchant::create([
            'name' => Str::title($slug), 'slug' => $slug, 'connector_type' => 'url',
            'product_import_method' => 'html', 'status' => 'active',
            'website_url' => 'https://www.'.$slug.'.com',
        ]);
    }

    private function csvPayload(string $contents, string $filename = 'products.csv'): UploadedFile
    {
        Storage::fake('private');

        return UploadedFile::fake()->createWithContent($filename, $contents);
    }

    public function test_upload_csv_creates_one_editable_draft_per_row(): void
    {
        $this->actingManager();
        $this->merchant();

        $csv = "name,category_slug,merchant_slug,price,currency,affiliate_url,description\n"
            ."The Alchemist,books,rokomari,450,BDT,https://track.rokomari.example/?pid=1,A book\n"
            ."Atomic Habits,self-help,rokomari,650,BDT,https://track.rokomari.example/?pid=2,Another book\n";

        $this->post(route('admin.csv-drafts.upload'), ['file' => $this->csvPayload($csv)])
            ->assertRedirect(route('admin.csv-drafts.index'))
            ->assertSessionHas('status');

        $this->assertSame(2, ProductDraft::count());
        $this->assertSame('draft', ProductDraft::first()->status);
        $first = ProductDraft::first();
        $this->assertSame('The Alchemist', $first->data['name']);
        $this->assertSame('rokomari', $first->merchant->slug);
    }

    public function test_upload_csv_auto_detects_merchant_from_url_when_no_slug(): void
    {
        $this->actingManager();
        $merchant = $this->merchant('rokomari');

        $csv = "name,category_slug,price,currency,external_url\n"
            ."Some Book,books,250,BDT,https://www.rokomari.com/book/123\n";

        $this->post(route('admin.csv-drafts.upload'), ['file' => $this->csvPayload($csv)])
            ->assertRedirect(route('admin.csv-drafts.index'));

        $draft = ProductDraft::first();
        $this->assertSame($merchant->id, $draft->data['merchant_id']);
    }

    public function test_edit_page_shows_draft_data_for_review(): void
    {
        $this->actingManager();
        $merchant = $this->merchant('editstore');

        $draft = ProductDraft::create([
            'data' => [
                'name' => 'The Alchemist',
                'description' => 'A book',
                'merchant_id' => $merchant->id,
                'current_price' => '450',
                'currency' => 'BDT',
                'affiliate_url' => 'https://track.rokomari.example/?pid=1',
            ],
            'merchant_id' => $merchant->id,
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);

        $this->get(route('admin.csv-drafts.edit', $draft))
            ->assertOk()
            ->assertSee('The Alchemist')
            ->assertSee('450')
            ->assertSee('Editstore');
    }

    public function test_post_draft_creates_published_product_and_offer(): void
    {
        $this->actingManager();
        $merchant = $this->merchant('rokomari');

        $draft = ProductDraft::create([
            'data' => ['name' => 'The Alchemist', 'external_url' => 'https://www.rokomari.com/book/1'],
            'merchant_id' => $merchant->id,
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);

        $this->post(route('admin.csv-drafts.post', $draft), [
            'name' => 'The Alchemist',
            'merchant_id' => $merchant->id,
            'category' => 'Books',
            'subcategory' => 'Bengali Fiction',
            'affiliate_url' => 'https://track.rokomari.example/?pid=123',
            'current_price' => '450',
            'original_price' => '600',
            'currency' => 'BDT',
            'availability' => 'in_stock',
            'description' => 'A beautiful book.',
        ])->assertRedirect(route('admin.csv-drafts.index'))->assertSessionHas('status');

        $product = Product::where('slug', 'the-alchemist')->first();
        $sub = Category::where('slug', 'bengali-fiction')->first();
        $this->assertNotNull($product);
        $this->assertSame('published', $product->status);
        $this->assertSame($sub->id, $product->category_id);

        $offer = Offer::where('product_id', $product->id)->where('merchant_id', $merchant->id)->first();
        $this->assertNotNull($offer);
        $this->assertEquals(450, $offer->current_price);
        $this->assertSame('https://track.rokomari.example/?pid=123', $offer->affiliateOffer->affiliate_url);

        $draft->refresh();
        $this->assertSame('posted', $draft->status);
    }

    public function test_post_requires_merchant_category_and_affiliate_link(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();

        $draft = ProductDraft::create([
            'data' => ['name' => 'A Simple Book'],
            'merchant_id' => $merchant->id,
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);

        $this->post(route('admin.csv-drafts.post', $draft), [
            'name' => 'A Simple Book',
            'merchant_id' => $merchant->id,
            'category' => '',
            'affiliate_url' => 'not-a-url',
        ])->assertSessionHasErrors(['category', 'affiliate_url']);
    }

    public function test_cannot_post_a_draft_twice(): void
    {
        $this->actingManager();
        $merchant = $this->merchant();

        $draft = ProductDraft::create([
            'data' => ['name' => 'Posted Book'],
            'merchant_id' => $merchant->id,
            'created_by' => auth()->id(),
            'status' => 'posted',
        ]);

        $this->post(route('admin.csv-drafts.post', $draft), [
            'name' => 'Posted Book',
            'merchant_id' => $merchant->id,
            'category' => 'Books',
            'affiliate_url' => 'https://track.rokomari.example/?pid=9',
            'currency' => 'BDT',
            'availability' => 'in_stock',
        ])->assertSessionHasErrors('draft');

        $this->assertSame(0, Product::count());
    }

    public function test_draft_list_marks_posted_and_allows_deletion(): void
    {
        $this->actingManager();
        $this->merchant();

        $draft = ProductDraft::create([
            'data' => ['name' => 'Draft Product'],
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);

        $this->get(route('admin.csv-drafts.index'))
            ->assertOk()
            ->assertSee('Draft Product');

        $this->delete(route('admin.csv-drafts.destroy', $draft))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(0, ProductDraft::where('id', $draft->id)->count());
    }

    private function listingPage(array $products): string
    {
        $json = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $products];

        return '<!DOCTYPE html><html><head>'
            .'<script type="application/ld+json">'.json_encode($json, JSON_UNESCAPED_SLASHES).'</script>'
            .'</head><body></body></html>';
    }

    private function productNode(string $name, float $price, string $id): array
    {
        return [
            '@type' => 'Product',
            'name' => $name,
            'sku' => $id,
            'image' => "https://shop.test/img/{$id}.jpg",
            'description' => "Description for {$name}.",
            'offers' => ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => 'BDT', 'availability' => 'https://schema.org/InStock'],
        ];
    }

    public function test_generate_from_url_creates_one_editable_draft_per_product(): void
    {
        $this->actingManager();
        $merchant = $this->merchant('shop');
        Http::fake(['https://shop.test/books/listing' => Http::response(
            $this->listingPage([
                $this->productNode('The Alchemist', 450, 'BK-1'),
                $this->productNode('Atomic Habits', 650, 'BK-2'),
            ]),
            200, ['Content-Type' => 'text/html'])]);

        $this->post(route('admin.csv-drafts.generate'), [
            'source_url' => 'https://shop.test/books/listing',
            'merchant_id' => $merchant->id,
        ])->assertRedirect(route('admin.csv-drafts.index'))->assertSessionHas('status');

        $this->assertSame(2, ProductDraft::count());
        $names = ProductDraft::get()->map(fn ($d) => $d->data['name'])->sort()->values()->toArray();
        $this->assertSame(['Atomic Habits', 'The Alchemist'], $names);
        $this->assertSame('450', ProductDraft::where('data->name', 'The Alchemist')->first()->data['current_price']);
        $this->assertSame('https://shop.test/img/BK-1.jpg', ProductDraft::where('data->name', 'The Alchemist')->first()->data['image']);
        $this->assertSame('draft', ProductDraft::where('data->name', 'The Alchemist')->first()->status);
    }

    public function test_generate_from_url_prefills_chosen_category_slug(): void
    {
        $this->actingManager();
        $merchant = $this->merchant('shop');
        $books = Category::where('slug', 'books')->firstOrFail();
        Http::fake(['https://shop.test/books/listing' => Http::response(
            $this->listingPage([$this->productNode('The Alchemist', 450, 'BK-1')]),
            200, ['Content-Type' => 'text/html'])]);

        $this->post(route('admin.csv-drafts.generate'), [
            'source_url' => 'https://shop.test/books/listing',
            'category_id' => $books->id,
            'merchant_id' => $merchant->id,
        ])->assertRedirect(route('admin.csv-drafts.index'))->assertSessionHas('status');

        $draft = ProductDraft::first();
        $this->assertSame('books', $draft->data['category_slug']);
    }

    public function test_generate_from_url_keeps_drafts_when_no_products_parse(): void
    {
        $this->actingManager();
        Http::fake(['https://shop.test/books/empty' => Http::response(
            '<!DOCTYPE html><html><head></head><body><p>No products here.</p></body></html>',
            200, ['Content-Type' => 'text/html'])]);

        $this->post(route('admin.csv-drafts.generate'), [
            'source_url' => 'https://shop.test/books/empty',
        ])->assertSessionHasErrors('generate');

        $this->assertSame(0, ProductDraft::count());
    }

    public function test_post_all_publishes_every_pending_draft_with_valid_required_fields(): void
    {
        $this->actingManager();
        $merchant = $this->merchant('rokomari');

        $valid = ProductDraft::create([
            'data' => [
                'name' => 'The Alchemist', 'merchant_id' => $merchant->id,
                'category_slug' => 'books', 'affiliate_url' => 'https://track.rokomari.example/?p=1',
                'current_price' => '450', 'currency' => 'BDT', 'availability' => 'in_stock',
            ],
            'merchant_id' => $merchant->id, 'created_by' => auth()->id(), 'status' => 'draft',
        ]);

        $invalid = ProductDraft::create([
            'data' => ['name' => 'No Affiliate'], // missing merchant + affiliate + category
            'created_by' => auth()->id(), 'status' => 'draft',
        ]);

        $this->post(route('admin.csv-drafts.post-all'))
            ->assertRedirect(route('admin.csv-drafts.index'))
            ->assertSessionHas('status');

        $valid->refresh();
        $invalid->refresh();
        $this->assertSame('posted', $valid->status);

        $this->assertSame(1, Product::count());
        $this->assertNotNull(Product::where('slug', 'the-alchemist')->first());
        $this->assertSame('error', $invalid->status);
    }

    public function test_post_all_with_no_pending_drafts_is_a_noop(): void
    {
        $this->actingManager();

        $this->post(route('admin.csv-drafts.post-all'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(0, Product::count());
    }

    public function test_export_downloads_all_drafts_as_csv(): void
    {
        $this->actingManager();
        $merchant = $this->merchant('rokomari');
        ProductDraft::create([
            'data' => ['name' => 'The Alchemist', 'merchant_id' => $merchant->id, 'current_price' => '450'],
            'merchant_id' => $merchant->id,
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);
        ProductDraft::create([
            'data' => ['name' => 'Atomic Habits'],
            'created_by' => auth()->id(),
            'status' => 'posted',
        ]);

        $response = $this->get(route('admin.csv-drafts.export'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('name,current_price,original_price,currency,category,brand,merchant', $csv);
        $this->assertStringContainsString('The Alchemist', $csv);
        $this->assertStringContainsString('Atomic Habits', $csv);
        $this->assertStringContainsString('450', $csv);
    }
}
