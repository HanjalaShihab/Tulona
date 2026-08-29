<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\LandingPage;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /** XML sitemap — only valuable pages are indexed (§36, §86). Rendered XML
     *  (plain string over the DB cache, never Eloquent objects) is memoized. */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, fn () => $this->render());

        return response('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.$xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    protected function render(): string
    {
        $e = collect([
            ['url' => route('home'), 'lastmod' => null],
            ['url' => route('deals.index'), 'lastmod' => null],
            ['url' => route('drops.index'), 'lastmod' => null],
            ['url' => route('guides.index'), 'lastmod' => null],
            ['url' => route('reviews.index'), 'lastmod' => null],
        ]);

        Category::where('is_active', true)->get()
            ->each(fn ($c) => $e->push(['url' => route('categories.show', $c->slug), 'lastmod' => $c->updated_at]));
        Brand::get()->each(fn ($b) => $e->push(['url' => route('brands.show', $b->slug), 'lastmod' => $b->updated_at ?? null]));
        Merchant::where('status', 'active')->get()
            ->each(fn ($m) => $e->push(['url' => route('merchants.show', $m->slug), 'lastmod' => $m->updated_at ?? null]));
        Product::published()->latest('updated_at')->limit(5000)->get()
            ->each(fn ($p) => $e->push(['url' => route('product.show', $p->slug), 'lastmod' => $p->updated_at]));
        Article::published()->get()
            ->each(fn ($a) => $e->push(['url' => route('articles.show', $a->slug), 'lastmod' => $a->published_at ?? $a->updated_at]));
        Comparison::published()->get()
            ->each(fn ($c) => $e->push(['url' => route('comparisons.show', $c->slug), 'lastmod' => $c->updated_at ?? null]));
        LandingPage::published()->get()
            ->each(fn ($l) => $e->push(['url' => route('landing-pages.show', $l->slug), 'lastmod' => $l->published_at ?? null]));

        $entries = $e->unique('url')->values();

        return view('sitemap.xml', ['entries' => $entries])->render();
    }
}
