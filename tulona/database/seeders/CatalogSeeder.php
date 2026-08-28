<?php

namespace Database\Seeders;

use App\Models\AffiliateNetwork;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Admin users, affiliate networks, brands, category tree (§1, §4, §33, §57). */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Super Admin', 'email' => 'admin@tulona.test', 'password' => bcrypt('password'), 'role' => 'super_admin', 'is_active' => true]);
        User::create(['name' => 'Product Manager', 'email' => 'product@tulona.test', 'password' => bcrypt('password'), 'role' => 'product_manager', 'is_active' => true]);
        User::create(['name' => 'Content Editor', 'email' => 'content@tulona.test', 'password' => bcrypt('password'), 'role' => 'content_manager', 'is_active' => true]);
        User::create(['name' => 'Analyst (read-only)', 'email' => 'analyst@tulona.test', 'password' => bcrypt('password'), 'role' => 'analyst', 'is_active' => true]);

        foreach (['Amazon Associates' => 'amazon', 'Daraz Affiliate' => 'daraz-affiliate', 'AliExpress Portals' => 'aliexpress'] as $name => $slug) {
            AffiliateNetwork::create(['name' => $name, 'slug' => $slug]);
        }

        foreach (['Apple', 'Samsung', 'Xiaomi', 'ASUS', 'Gigabyte', 'Logitech', 'Sony', 'Anker', 'The Ordinary', 'Antler', 'Notion AI'] as $b) {
            Brand::create(['name' => $b, 'slug' => str()->slug($b), 'website_url' => null]);
        }

        // Hierarchical tree — unlimited depth supported (§33)
        $tree = [
            ['Electronics', 'electronics', '⚡', [
                ['Computers', 'computers', [['Laptops', 'laptops'], ['Desktops', 'desktops']]],
                ['PC Components', 'pc-components', [
                    ['Graphics Cards', 'graphics-cards'],
                    ['Processors', 'processors'],
                    ['Memory', 'memory'],
                ]],
                ['PC Accessories', 'pc-accessories', [['Keyboards', 'keyboards'], ['Mice', 'mice'], ['Headphones', 'headphones']]],
            ]],
            ['Mobile', 'mobile', '📱', [
                ['Smartphones', 'smartphones', [['Android', 'android-phones'], ['iPhone', 'iphone']]],
                ['Mobile Accessories', 'mobile-accessories', [['Power Banks', 'power-banks'], ['Cases', 'phone-cases']]],
            ]],
            ['Fashion', 'fashion', '👕'],
            ['Beauty & Skincare', 'beauty-skincare', '💄', [['Skincare', 'skincare']]],
            ['Home & Kitchen', 'home-kitchen', '🏠'],
            ['Software & AI Tools', 'software-ai-tools', '🤖', [
                ['AI Tools', 'ai-tools'],
                ['Productivity Software', 'productivity-software'],
            ]],
            ['Travel Products', 'travel-products', '✈️', [['Luggage', 'luggage']]],
            ['Books', 'books', '📚'],
        ];

        foreach ($tree as $node) {
            [$name, $slug, $icon] = $node;
            $children = $node[3] ?? [];
            $parent = Category::create(['name' => $name, 'slug' => $slug, 'icon' => $icon,
                'description' => "Compare {$name} prices across trusted Bangladeshi and international stores.",
                'intro_content' => "Compare {$name} prices across trusted stores."]);
            foreach ((array) $children as $child) {
                $sub = Category::create(['name' => $child[0], 'slug' => $child[1], 'parent_id' => $parent->id,
                    'description' => "Compare {$child[0]} deals and price drops."]);
                foreach (($child[2] ?? []) as $g) {
                    Category::create(['name' => $g[0], 'slug' => $g[1], 'parent_id' => $sub->id]);
                }
            }
        }
    }
}
