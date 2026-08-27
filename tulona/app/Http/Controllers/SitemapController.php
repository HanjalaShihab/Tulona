<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** XML sitemap — only valuable pages are indexed (§36, §86). */
    public function index(): Response
    {
        $e = collect([
            route('home'), route('deals.index'), route('drops.index'),
            route('guides.index'), route('reviews.index'),
        ]);

        Category::where('is_active', true)->get()->each(fn ($c) => $e->push(route('categories.show', $c->slug)));
        Brand::get()->each(fn ($b) => $e->push(route('brands.show', $b->slug)));
        Merchant::where('status', 'active')->get()->each(fn ($m) => $e->push(route('merchants.show', $m->slug)));
        Product::published()->latest('updated_at')->limit(5000)->each(fn ($p) => $e->push(route('product.show', $p->slug)));
        Article::published()->each(fn ($a) => $e->push(route('articles.show', $a->slug)));
        Comparison::published()->each(fn ($c) => $e->push(route('comparisons.show', $c->slug)));

        $entries = $e->unique()->values();

        return response(view('sitemap.xml', ['entries' => $entries])->render(), 200)
            ->header('Content-Type', 'application/xml');
    }
}
