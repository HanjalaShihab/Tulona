<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductImageSeeder extends Seeder
{
    protected array $colors = [
        'nvidia-rtx-5070' => ['#76b900', '#4a7a00'],
        'rtx-5070-ti' => ['#76b900', '#2d5a00'],
        'galaxy-s26-ultra' => ['#1428a0', '#0a1450'],
        'iphone-17-pro' => ['#555555', '#222222'],
        'redmi-note-15-pro' => ['#ff6900', '#cc4400'],
        'asus-rog-g14' => ['#bc0000', '#660000'],
        'logitech-mx-mechanical' => ['#00a8e8', '#005f8a'],
        'sony-wh-1200xm6' => ['#000000', '#333333'],
        'anker-prime-27k' => ['#0066cc', '#003d7a'],
        'ordinary-niacinamide' => ['#f5e6d3', '#c9a96e'],
        'antler-jet-cabin' => ['#1a1a2e', '#16213e'],
        'notion-ai-yearly' => ['#ffffff', '#e0e0e0'],
    ];

    protected array $icons = [
        'nvidia-rtx-5070' => 'GPU',
        'rtx-5070-ti' => 'GPU',
        'galaxy-s26-ultra' => '📱',
        'iphone-17-pro' => '📱',
        'redmi-note-15-pro' => '📱',
        'asus-rog-g14' => '💻',
        'logitech-mx-mechanical' => '⌨️',
        'sony-wh-1200xm6' => '🎧',
        'anker-prime-27k' => '🔋',
        'ordinary-niacinamide' => '💧',
        'antler-jet-cabin' => '🧳',
        'notion-ai-yearly' => 'AI',
    ];

    public function run(): void
    {
        Storage::disk('public')->makeDirectory('products');

        Product::each(function (Product $product) {
            $slug = $product->slug;
            $colors = $this->colors[$slug] ?? ['#6366f1', '#4338ca'];
            $icon = $this->icons[$slug] ?? strtoupper(substr($product->brand->name ?? 'P', 0, 1));
            $name = $product->name;

            $svg = $this->generateSvg($colors, $icon, $name);
            $path = "products/{$slug}.svg";
            Storage::disk('public')->put($path, $svg);

            $product->images()->create([
                'path' => $path,
                'alt_text' => "{$product->name} — product image",
                'is_main' => true,
                'sort_order' => 0,
            ]);
        });
    }

    protected function generateSvg(array $colors, string $label, string $name): string
    {
        $safeName = htmlspecialchars($name, ENT_XML1);
        $safeLabel = htmlspecialchars($label, ENT_XML1);
        $textSize = strlen($label) > 3 ? 28 : 48;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$colors[0]}"/>
      <stop offset="100%" stop-color="{$colors[1]}"/>
    </linearGradient>
    <pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse">
      <circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.06)"/>
    </pattern>
  </defs>
  <rect width="800" height="600" fill="url(#bg)"/>
  <rect width="800" height="600" fill="url(#dots)"/>
  <text x="400" y="280" font-family="system-ui,-apple-system,sans-serif" font-size="{$textSize}" font-weight="700" fill="rgba(255,255,255,0.9)" text-anchor="middle" dominant-baseline="middle">{$safeLabel}</text>
  <text x="400" y="340" font-family="system-ui,-apple-system,sans-serif" font-size="14" fill="rgba(255,255,255,0.5)" text-anchor="middle">{$safeName}</text>
</svg>
SVG;
    }
}
