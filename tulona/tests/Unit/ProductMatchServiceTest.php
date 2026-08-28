<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductMatchService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductMatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->service = app(ProductMatchService::class);
    }

    private function category(string $slug): Category
    {
        return Category::where('slug', $slug)->firstOrFail();
    }

    private function save(array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'A4Tech HS-19-1 Headset Grey';
        $overrides['slug'] ??= Str::slug($name).'-'.Str::lower(Str::random(6));

        return Product::create(array_merge([
            'category_id' => $this->category('headphones')->id,
            'name' => $name,
            'status' => 'published',
        ], $overrides));
    }

    private function candidate(array $overrides = []): Product
    {
        return new Product(array_merge([
            'category_id' => $this->category('headphones')->id,
            'name' => 'A4 Tech HS-19-1 Headset Gray',
        ], $overrides));
    }

    public function test_matches_by_gtin_even_with_different_names(): void
    {
        $existing = $this->save(['name' => 'Gaming Headset Deluxe', 'gtin' => '0886989870011']);

        $this->assertSame(
            $existing->id,
            $this->service->find($this->candidate(['name' => 'Totally Different Title', 'gtin' => '0886989870011']))?->id
        );
    }

    public function test_matches_by_brand_and_model_number(): void
    {
        $sony = Brand::where('slug', 'sony')->firstOrFail();
        $existing = $this->save(['brand_id' => $sony->id, 'model_number' => 'WH-1000XM4', 'name' => 'Sony Noise Cancelling Headset']);

        $this->assertSame(
            $existing->id,
            $this->service->find($this->candidate([
                'brand_id' => $sony->id,
                'model_number' => 'WH-1000XM4',
                'name' => 'Sony WH-1000XM4 Wireless Headphones',
            ]))?->id
        );
    }

    public function test_matches_similar_name_within_same_top_level_category(): void
    {
        $existing = $this->save(['name' => 'A4Tech HS-19-1 Headset Grey']);

        $this->assertSame(
            $existing->id,
            $this->service->find($this->candidate(['name' => 'A4 Tech HS-19-1 Headset (Gray)']))?->id
        );
    }

    public function test_matches_exact_normalized_name_across_categories(): void
    {
        $existing = $this->save(['category_id' => $this->category('books')->id, 'name' => 'The Alchemist — Deluxe Edition']);

        $this->assertSame(
            $existing->id,
            $this->service->find($this->candidate([
                'category_id' => $this->category('electronics')->id,
                'name' => 'The Alchemist Deluxe Edition!',
            ]))?->id
        );
    }

    public function test_does_not_match_different_sizes_of_same_model(): void
    {
        $this->save([
            'category_id' => $this->category('graphics-cards')->id,
            'brand_id' => Brand::where('slug', 'gigabyte')->firstOrFail()->id,
            'name' => 'NVIDIA GeForce RTX 5070 12GB',
        ]);

        $this->assertNull($this->service->find($this->candidate([
            'category_id' => $this->category('graphics-cards')->id,
            'brand_id' => Brand::where('slug', 'gigabyte')->firstOrFail()->id,
            'name' => 'NVIDIA GeForce RTX 5070 16GB',
        ])));
    }

    public function test_does_not_match_unrelated_products_in_same_category(): void
    {
        $this->save(['name' => 'Logitech Wireless Mouse M185']);

        $this->assertNull($this->service->find($this->candidate(['name' => 'Corsair K55 RGB Mechanical Keyboard'])));
    }

    public function test_does_not_match_similar_name_in_a_different_top_level_category(): void
    {
        $this->save(['category_id' => $this->category('books')->id, 'name' => 'A4Tech HS-19-1 Headset Grey']);

        $this->assertNull($this->service->find($this->candidate(['name' => 'A4 Tech HS-19-1 Headset (Gray)'])));
    }

    public function test_returns_null_for_blank_or_unknown_candidates(): void
    {
        $this->save();

        $this->assertNull($this->service->find(new Product(['name' => '', 'category_id' => 1])));
        $this->assertNull($this->service->find(null));
    }
}
