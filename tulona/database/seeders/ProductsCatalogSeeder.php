<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Database\Seeder;

/** The demo catalog: products + multi-store offers + honest price history. */
class ProductsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::all()->keyBy('slug');

        // [slug, name, category, brand, base BDT, specs, featured, trending]
        $catalog = [
            ['nvidia-rtx-5070', 'NVIDIA GeForce RTX 5070 12GB', 'graphics-cards', 'Gigabyte', 62000,
                ['vram' => 12, 'memory_type' => 'GDDR7', 'class' => 'High-end'], true, true],
            ['rtx-5070-ti', 'GeForce RTX 5070 Ti 16GB OC', 'graphics-cards', 'Gigabyte', 88000,
                ['vram' => 16, 'memory_type' => 'GDDR7', 'class' => 'Enthusiast'], true, false],
            ['galaxy-s26-ultra', 'Samsung Galaxy S26 Ultra 256GB', 'smartphones', 'Samsung', 165000,
                ['ram' => 12, 'storage' => 256, 'battery' => 5000], false, true],
            ['iphone-17-pro', 'Apple iPhone 17 Pro 256GB Natural Titanium', 'iphone', 'Apple', 185000,
                ['ram' => 8, 'storage' => 256, 'battery' => 4300], true, true],
            ['redmi-note-15-pro', 'Xiaomi Redmi Note 15 Pro 128GB', 'android-phones', 'Xiaomi', 28500,
                ['ram' => 8, 'storage' => 128, 'battery' => 5500], false, true],
            ['asus-rog-g14', 'ASUS ROG Zephyrus G14 Gaming Laptop RTX 5060', 'laptops', 'ASUS', 195000,
                ['ram' => 32, 'storage' => 1024, 'screen' => 14, 'cpu' => 'AMD Ryzen AI 9'], true, false],
            ['logitech-mx-mechanical', 'Logitech MX Mechanical Wireless Keyboard', 'keyboards', 'Logitech', 12500, [], false, true],
            ['sony-wh-1200xm6', 'Sony WH-1200XM6 Wireless Headphones', 'headphones', 'Sony', 34000, [], true, false],
            ['anker-prime-27k', 'Anker Prime Power Bank 27000mAh 250W', 'power-banks', 'Anker', 13500, [], false, true],
            ['ordinary-niacinamide', 'The Ordinary Niacinamide 10% + Zinc 30ml', 'skincare', 'The Ordinary', 1600, [], false, true],
            ['antler-jet-cabin', 'Antler Jet Cabin Suitcase 40L', 'luggage', 'Antler', 22000, [], false, false],
            ['notion-ai-yearly', 'Notion AI Workspace Plan (Yearly)', 'ai-tools', 'Notion AI', 17500, ['platform' => 'Web / Desktop / Mobile'], true, true],
        ];

        foreach ($catalog as $row) {
            [$slug, $name, $catSlug, $brandName, $baseBdt, $specs, $featured, $trending] = $row;
            $digital = in_array($catSlug, ['ai-tools']);
            $product = Product::create([
                'category_id' => Category::where('slug', $catSlug)->value('id'),
                'brand_id' => Brand::where('name', $brandName)->value('id'),
                'name' => $name, 'slug' => $slug,
                'sku' => strtoupper(substr(md5($slug), 0, 10)),
                'model_number' => strtoupper(substr(str_replace('-', '', $slug), 0, 9)),
                'gtin' => '08'.str_pad((string) random_int(10000000, 99999999), 11, '0'),
                'short_description' => "{$name} — compare current prices across Tulona partner stores.",
                'summary_editorial' => $digital
                    ? 'Digital product: subscription pricing applies at the merchant. Free trial availability may vary.'
                    : 'Verified listing sourced via permitted channels. Availability and warranty vary by store.',
                'pros' => [$brandName.' build quality', 'Strong value for this price class'],
                'cons' => ['Stock fluctuates between stores'],
                'rating' => $featured ? 4.5 : 4.0,
                'product_type' => $digital ? 'digital' : 'physical',
                'pricing_model' => $digital ? 'subscription' : null,
                'has_free_plan' => $digital,
                'is_featured' => $featured, 'is_trending' => $trending,
                'popularity_score' => mt_rand(30, 95),
            ]);

            foreach ($specs as $key => $value) {
                if ($def = AttributeDefinition::where('key', $key)->first()) {
                    $product->attributes()->create(match ($def->data_type) {
                        'number' => ['attribute_definition_id' => $def->id, 'value_number' => (float) $value, 'value_text' => (string) $value],
                        default => ['attribute_definition_id' => $def->id, 'value_text' => (string) $value],
                    });
                }
            }

            foreach ($merchants as $mSlug => $merchant) {
                // Not every store carries every SKU — realistic sparse coverage
                $cur = $merchant->currencies[0] ?? 'BDT';
                if ($merchant->country === 'US' && $baseBdt < 20000 && mt_rand(0, 1)) {
                    continue;
                }
                $usd = round($baseBdt / 118, 2);
                $cheaperStore = str_contains($mSlug, 'aliexpress');
                $price = $cur === 'USD'
                    ? max($usd * ($cheaperStore ? 0.9 : 1.02), 2)
                    : round($baseBdt * mt_rand(96, 108) / 100, 2);

                $original = $cheaperStore || mt_rand(0, 2) === 0 ? round($price / 0.92, 2) : null;

                $offer = $product->offers()->create([
                    'merchant_id' => $merchant->id,
                    'external_product_id' => substr(md5($product->slug.$mSlug), 0, 10),
                    'external_url' => "https://{$mSlug}.example/product/{$slug}",
                    'affiliate_url' => $merchant->base_affiliate_url.'?sku='.substr(md5($slug), 0, 8),
                    'current_price' => $price,
                    'original_price' => $original,
                    'currency' => $cur,
                    'availability' => collect(['in_stock', 'in_stock', 'in_stock', 'preorder', 'unknown'])->random(),
                    'source' => 'feed',
                    'last_synced_at' => now()->subHours(mt_rand(1, 90)),
                ]);

                // Honest demo history: gently declining series over the last month (not fabricated as "real")
                $points = 12;
                $series = $price * 1.18;
                foreach (range(1, $points) as $i) {
                    PriceHistory::create([
                        'offer_id' => $offer->id, 'price' => round($series, 2),
                        'currency' => $offer->currency, 'recorded_at' => now()->subDays($points - $i),
                    ]);
                    $series = max($price, $series - $price * 0.018);
                }
            }
        }
    }
}
