<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Landing page CMS (§38, §47) — admin create → public render. */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);

        return Product::firstOrCreate(
            ['slug' => 'logitech-g102'],
            ['name' => 'Logitech G102', 'category_id' => $category->id, 'brand_id' => $brand->id, 'status' => 'published']
        );
    }

    public function test_content_manager_can_create_published_landing_page(): void
    {
        $this->seed(CatalogSeeder::class);
        $product = $this->product();

        $editor = User::where('email', 'content@tulona.test')->firstOrFail();

        $this->actingAs($editor)
            ->post(route('admin.landing-pages.store'), [
                'title' => 'Best Gaming Mouse',
                'slug' => 'best-gaming-mouse',
                'excerpt' => 'Top picks.',
                'status' => 'published',
                'seo_title' => 'Best Gaming Mouse 2026',
                'products' => [$product->id],
                'comparisons' => [],
                'sections' => [
                    [
                        'type' => 'hero',
                        'heading' => 'Hero heading',
                        'subheading' => 'Sub',
                        'cta_text' => 'Shop',
                        'cta_url' => '/deals',
                        'image_url' => '',
                    ],
                    [
                        'type' => 'products',
                        'title' => 'Top picks',
                        'description' => 'Best of the best',
                    ],
                    [
                        'type' => 'faq',
                        'heading' => 'FAQ',
                        'faq_json' => '[{"question":"In stock?","answer":"Yes."}]',
                    ],
                    ['type' => ''],
                ],
            ])
            ->assertSessionHasNoErrors();

        $page = LandingPage::where('slug', 'best-gaming-mouse')->firstOrFail();
        $this->assertSame('published', $page->status);
        $this->assertNotNull($page->published_at);
        $this->assertSame(3, count($page->sections));
        $this->assertSame('hero', $page->sections[0]['type']);
        $this->assertSame('products', $page->sections[1]['type']);
        $this->assertSame('FAQ', $page->sections[2]['heading']);
        $this->assertSame('Yes.', $page->sections[2]['items'][0]['answer']);
        $this->assertTrue($page->products->contains($product->id));
    }

    public function test_published_landing_page_renders_publicly(): void
    {
        $this->seed(CatalogSeeder::class);
        $product = $this->product();

        $page = LandingPage::create([
            'title' => 'Best Gaming Mouse',
            'slug' => 'best-gaming-mouse',
            'status' => 'published',
            'published_at' => now(),
            'sections' => [
                ['type' => 'hero', 'heading' => 'The Best Picks', 'subheading' => 'Curated', 'cta_text' => '', 'cta_url' => '', 'image_url' => ''],
                ['type' => 'products', 'title' => 'Top picks', 'description' => 'Best of the best'],
            ],
        ]);
        $page->products()->sync([$product->id => ['sort_order' => 0]]);

        $this->get('/landing/best-gaming-mouse')
            ->assertOk()
            ->assertSee('Best Gaming Mouse')
            ->assertSee('The Best Picks')
            ->assertSee('Top picks')
            ->assertSee('Logitech G102');
    }

    public function test_draft_landing_page_is_not_publicly_visible(): void
    {
        $this->seed(CatalogSeeder::class);

        LandingPage::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'status' => 'draft',
        ]);

        $this->get('/landing/draft-page')->assertNotFound();
    }

    public function test_slug_must_be_lowercase_hyphenated(): void
    {
        $this->seed(CatalogSeeder::class);
        $editor = User::where('email', 'content@tulona.test')->firstOrFail();

        $this->actingAs($editor)
            ->post(route('admin.landing-pages.store'), [
                'title' => 'Bad Slug',
                'slug' => 'Bad Slug!',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame(0, LandingPage::count());
    }
}
