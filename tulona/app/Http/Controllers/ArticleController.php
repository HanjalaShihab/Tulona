<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function guides(): View
    {
        return view('articles.index', [
            'articles' => Article::published()->where('type', 'guide')->latest('published_at')->paginate(12),
            'type' => 'guide',
            'seo' => ['title' => 'Buying Guides — Make Smarter Choices', 'description' => 'Independent, genuinely useful buying guides: what to look for, what to avoid, and the best options right now.'],
        ]);
    }

    public function reviews(): View
    {
        return view('articles.index', [
            'articles' => Article::published()->where('type', 'review')->latest('published_at')->paginate(12),
            'type' => 'review',
            'seo' => ['title' => 'Editorial Reviews — Tulona', 'description' => 'In-depth, honest product reviews with real pros and cons. We disclose affiliate relationships on every page.'],
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::published()->where('slug', $slug)->with(['products.brand', 'products.activeOffers'])->firstOrFail();

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id))
            ->latest('published_at')->limit(4)->get();

        return view('articles.show', [
            'article' => $article,
            'related' => $related,
            'seo' => [
                'title' => $article->seo_title ?: "{$article->title} — Tulona",
                'description' => $article->seo_description ?: Str::limit(strip_tags($article->excerpt ?? $article->content), 155),
                'canonical' => $article->canonical_url,
                'og_image' => $article->og_image ?: $article->featured_image,
                'published_at' => $article->published_at,
                'og_type' => 'article',
            ],
        ]);
    }
}
