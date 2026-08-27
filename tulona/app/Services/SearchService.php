<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Database-backed search across products, brands, categories, merchants
 * and articles with lightweight typo tolerance (§9).
 * Swappable later for Meilisearch/Typesense without touching controllers.
 */
class SearchService
{
    public function search(string $query): array
    {
        $q = trim($query);

        if ($q === '') {
            return ['products' => collect(), 'categories' => collect(), 'brands' => collect(), 'merchants' => collect(), 'articles' => collect()];
        }

        $terms = preg_split('/\s+/', strtolower($q));
        // Typo tolerance: expand terms with a loose prefix variant ("iphon" → iphon%)
        $loose = substr($q, 0, max(4, (int) floor(strlen($q) * 0.8)));

        return [
            'products' => $this->products($terms, $loose),
            'categories' => Category::where('is_active', true)->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%"))->limit(6)->get(),
            'brands' => Brand::where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%"))->limit(6)->get(),
            'merchants' => Merchant::where('status', 'active')->where('name', 'like', "%{$q}%")->limit(4)->get(),
            'articles' => Article::published()->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('excerpt', 'like', "%{$q}%"))->limit(5)->get(),
        ];
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function products(array $terms, string $loose): Collection
    {
        return Product::query()
            ->where('status', 'published')
            ->with(['brand', 'category'])
            ->withCount(['activeOffers as offer_count'])
            ->where(function ($w) use ($terms, $loose) {
                foreach ($terms as $t) {
                    $safe = $this->escapeLike($t);
                    $w->where(function ($s) use ($safe) {
                        $s->where('name', 'like', "%{$safe}%")
                            ->orWhere('short_description', 'like', "%{$safe}%")
                            ->orWhere('model_number', 'like', "{$safe}%")
                            ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$safe}%"))
                            ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$safe}%"));
                    });
                }
                if ($loose !== '') {
                    $safeLoose = $this->escapeLike($loose);
                    $w->orWhere('name', 'like', "%{$safeLoose}%");
                }
            })
            ->orderByDesc('popularity_score')
            ->limit(24)
            ->get();
    }
}
