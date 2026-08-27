<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Editorial content: guides + review with FAQ, homepage settings (§20, §21). */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $guide = Article::create([
            'title' => 'Best GPUs Under ৳100,000 in Bangladesh (2026)',
            'slug' => 'best-gpus-under-100000-bd',
            'type' => 'guide',
            'excerpt' => 'Real benchmark-per-taka picks for 1080p and 1440p gaming — with live price comparison across BD stores.',
            'content' => '<p>GPU pricing in Bangladesh swings week to week depending on import channels and stock. Below are the cards that consistently deliver the best frames per taka <em>at current Tulona-tracked prices</em>.</p><h3>What to check before buying</h3><p>Confirm warranty channel (official vs grey import), because that alone can change the effective value by 15%.</p>',
            'category_id' => Category::where('slug', 'pc-components')->value('id'),
            'author' => 'Tulona Hardware Desk',
            'status' => 'published', 'published_at' => now()->subDays(2),
            'seo_title' => 'Best GPUs Under ৳100,000 in Bangladesh — Compared',
            'seo_description' => 'Data-backed GPU recommendations under ৳100k: what we picked, what we skipped, and why.',
            'selection_criteria' => ['1080p/1440p real-world performance', 'Local warranty availability', 'Live multi-store price on Tulona'],
            'faqs' => [
                ['question' => 'Do you update these prices?', 'answer' => 'Yes — every price on the linked product pages reflects our latest tracked data from partner stores.'],
                ['question' => 'Is a grey-import card worth saving ৳8,000?', 'answer' => 'Usually not in Bangladesh: local warranty service can be decisive for expensive components.'],
            ],
        ]);

        $rtx5070 = Product::where('slug', 'nvidia-rtx-5070')->first();
        $ti = Product::where('slug', 'rtx-5070-ti')->first();
        $iphone = Product::where('slug', 'iphone-17-pro')->first();

        $guidePicks = [];
        if ($rtx5070) {
            $guidePicks[$rtx5070->id] = ['pick_label' => 'Best Overall', 'blurb' => 'The best 1440p price-to-performance right now.', 'sort_order' => 0];
        }
        if ($ti) {
            $guidePicks[$ti->id] = ['pick_label' => 'Premium Pick', 'blurb' => 'Worth it if you want headroom at 1440p ultra.', 'sort_order' => 1];
        }
        if (! empty($guidePicks)) {
            $guide->products()->attach($guidePicks);
        }

        Article::create([
            'title' => 'iPhone 17 Pro Review: The Boring Upgrade That Mostly Makes Sense',
            'slug' => 'iphone-17-pro-review',
            'type' => 'review',
            'excerpt' => 'After three weeks with Apple’s latest Pro: who should upgrade, who should absolutely not, and where to buy it cheapest in BD.',
            'content' => '<h2>Overview</h2><p>The 17 Pro refines rather than reinvents. Battery life is genuinely better; the camera system is iterative.</p><h2>Who shouldn’t buy it</h2><p>If you own an iPhone 15 Pro, nothing here justifies the price delta.</p>',
            'category_id' => Category::where('slug', 'mobile')->value('id'),
            'author' => 'Tulona Mobile Desk',
            'status' => 'published', 'published_at' => now()->subWeek(),
            'selection_criteria' => ['3 weeks of daily-driver use', 'Battery logging with local SIMs', 'Price tracked across 4 stores'],
            'faqs' => [['question' => 'Does it support Bangla keyboard well?', 'answer' => 'Yes, iOS ships a native Avro-style phonetic layout.']],
        ])->products()->attach($iphone ? [$iphone->id => ['pick_label' => null, 'blurb' => null, 'sort_order' => 0]] : []);

        Setting::put('homepage', [
            'hero_title' => 'Find the right product at the right price.',
            'hero_subtitle' => 'Compare products, prices, deals and trusted stores before you buy — powered by verified data, not marketing claims.',
            'show_deals' => true, 'show_price_drops' => true, 'show_trending' => true,
        ]);
    }
}
