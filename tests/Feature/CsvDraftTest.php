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
}
