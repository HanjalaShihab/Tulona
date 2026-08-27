<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Bulk catalogue actions (§48). */
class BulkProductTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): Category
    {
        return Category::firstOrCreate(['slug' => 'gaming'], ['name' => 'Gaming', 'is_active' => true]);
    }

    private function makeProduct(Category $cat, string $name, string $status = 'draft'): Product
    {
        $brand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);

        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => $cat->id,
            'brand_id' => $brand->id,
            'status' => $status,
        ]);
    }

    private function actingManager(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'product@tulona.test')->firstOrFail());
    }

    public function test_bulk_publish_and_unpublish(): void
    {
        $this->actingManager();
        $cat = $this->makeCategory();
        $a = $this->makeProduct($cat, 'Mouse A', 'draft');
        $b = $this->makeProduct($cat, 'Mouse B', 'draft');

        $this->post(route('admin.products.bulk'), ['ids' => [$a->id, $b->id], 'action' => 'publish']);
        $this->assertSame('published', $a->fresh()->status);
        $this->assertSame('published', $b->fresh()->status);

        $this->post(route('admin.products.bulk'), ['ids' => [$a->id], 'action' => 'unpublish']);
        $this->assertSame('draft', $a->fresh()->status);
    }

    public function test_bulk_archive_soft_deletes(): void
    {
        $this->actingManager();
        $cat = $this->makeCategory();
        $p = $this->makeProduct($cat, 'Mouse C', 'published');

        $this->post(route('admin.products.bulk'), ['ids' => [$p->id], 'action' => 'archive']);
        $this->assertTrue($p->fresh()->trashed());
        $this->assertSame('archived', $p->fresh()->status);
    }

    public function test_bulk_publish_restores_archived(): void
    {
        $this->actingManager();
        $cat = $this->makeCategory();
        $p = $this->makeProduct($cat, 'Mouse D', 'archived');
        $p->delete();

        $this->post(route('admin.products.bulk'), ['ids' => [$p->id], 'action' => 'publish']);
        $this->assertFalse($p->fresh()->trashed());
        $this->assertSame('published', $p->fresh()->status);
    }

    public function test_bulk_move_to_category(): void
    {
        $this->actingManager();
        $cat = $this->makeCategory();
        $other = Category::firstOrCreate(['slug' => 'office'], ['name' => 'Office', 'is_active' => true]);
        $p = $this->makeProduct($cat, 'Mouse E', 'published');

        $this->post(route('admin.products.bulk'), ['ids' => [$p->id], 'action' => 'category', 'category_id' => $other->id]);
        $this->assertSame($other->id, $p->fresh()->category_id);
    }

    public function test_bulk_delete_force_removes(): void
    {
        $this->actingManager();
        $cat = $this->makeCategory();
        $p = $this->makeProduct($cat, 'Mouse F', 'draft');

        $this->post(route('admin.products.bulk'), ['ids' => [$p->id], 'action' => 'delete']);
        $this->assertNull(Product::find($p->id));
    }

    public function test_analyst_cannot_run_bulk_actions(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::where('email', 'analyst@tulona.test')->firstOrFail());

        $cat = $this->makeCategory();
        $p = $this->makeProduct($cat, 'Mouse G');

        $this->post(route('admin.products.bulk'), ['ids' => [$p->id], 'action' => 'publish'])->assertForbidden();
    }
}
