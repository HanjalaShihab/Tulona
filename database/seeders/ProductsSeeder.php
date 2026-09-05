<?php

namespace Database\Seeders;

use App\Models\AffiliateNetwork;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\Merchant;
use Illuminate\Database\Seeder;

/** Attribute definitions, merchants, and the demo catalog data (§82). */
class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = collect([
            ['Daraz BD', 'daraz', 'BD', 'BDT', 'daraz-affiliate'],
            ['Amazon Global', 'amazon', 'US', 'USD', 'amazon'],
            ['AliExpress', 'aliexpress', 'CN', 'USD', 'aliexpress'],
            ['Star Tech', 'star-tech', 'BD', 'BDT', null],
            ['Rokomari', 'rokomari', 'BD', 'BDT', null, true, 'html'],
        ])->map(fn ($m) => Merchant::create([
            'name' => $m[0], 'slug' => $m[1], 'country' => $m[2], 'currencies' => [$m[3]],
            'affiliate_network_id' => $m[4] ? AffiliateNetwork::where('slug', $m[4])->value('id') : null,
            'description' => "{$m[0]} offers on Tulona. Purchases happen on the merchant's own website.",
            'website_url' => "https://www.{$m[1]}.com",
            'base_affiliate_url' => "https://{$m[1]}.com/affiliate",
            'connector_type' => ($m[5] ?? false) ? 'url' : null,
            'product_import_method' => $m[6] ?? 'csv',
        ]))->keyBy('slug');

        $attrs = [
            'graphics-cards' => [['VRAM', 'vram', 'number', 'GB'], ['Memory Type', 'memory_type', 'string', null, false], ['Performance Class', 'class', 'string', null, true]],
            'smartphones' => [['RAM', 'ram', 'number', 'GB'], ['Storage', 'storage', 'number', 'GB'], ['Battery', 'battery', 'number', 'mAh'], ['5G', '5g', 'boolean', null, false]],
            'laptops' => [['RAM', 'ram', 'number', 'GB'], ['Storage', 'storage', 'number', 'GB'], ['Screen Size', 'screen', 'number', 'inch'], ['CPU', 'cpu', 'string', null, false]],
            'ai-tools' => [['Platform', 'platform', 'string', null, false]],
        ];
        foreach ($attrs as $slug => $defs) {
            foreach ($defs as $i => $def) {
                [$name, $key, $type, $unit] = $def;
                $isFilterable = $def[4] ?? ($type === 'number');
                AttributeDefinition::create(['category_id' => Category::where('slug', $slug)->value('id'),
                    'name' => $name, 'key' => $key, 'data_type' => $type, 'unit' => $unit,
                    'is_filterable' => $isFilterable, 'sort_order' => $i]);
            }
        }

        $this->call(ProductsCatalogSeeder::class);
    }
}
