<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Comparison publishing placements (§37) — homepage featured + category round-ups. */
class ComparisonPlacementTest extends TestCase
{
    use RefreshDatabase;

    private function comparison(bool $featured = true, string $slug = 'best-gaming-mice'): Comparison
    {
        $category = Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);
        $product = Product::firstOrCreate(
            ['slug' => 'logitech-g102'],
            ['name' => 'Logitech G102', 'category_id' => $category->id, 'brand_id' => $brand->id, 'status' => 'published']
        );

        $comparison = Comparison::create([
            'title' => 'Best Gaming Mice',
            'slug' => $slug,
            'introduction' => 'Our top mice comparison.',
            'status' => 'published',
            'featured' => $featured,
            'published_at' => now(),
        ]);
        $comparison->products()->sync([$product->id => ['sort_order' => 0]]);

        return $comparison;
    }

    public function test_featured_comparison_appears_on_homepage(): void
    {
        $this->markTestSkipped('Global comparison (Shop Smarter) removed — Store Compare on PDP remains.');
    }

    public function test_non_featured_comparison_is_hidden_from_homepage(): void
    {
        $this->markTestSkipped('Global comparison removed.');
    }

    public function test_relevant_comparison_appears_on_category_page(): void
    {
        $this->markTestSkipped('Category comparison round-ups removed with product comparison feature.');
    }

    public function test_comparison_is_publicly_visible(): void
    {
        $this->markTestSkipped('Published comparison public route removed (replaced by product Store Compare).');
    }
}
