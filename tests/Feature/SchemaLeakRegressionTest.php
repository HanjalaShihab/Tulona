<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\ProductsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaLeakRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_blade_context_directive_source_leaks_into_json_ld(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductsSeeder::class);
        $this->seed(ContentSeeder::class);

        $pages = ['/', '/product/nvidia-rtx-5070', '/category/electronics', '/guides/best-gpus-under-100000-bd'];

        foreach ($pages as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('__contextArgs', $html);
            $this->assertStringNotContainsString('<?php $__context', $html);
            $this->assertStringContainsString('"@context":"https://schema.org"', $html);
            $this->assertStringContainsString('application/ld+json', $html);
        }
    }
}
