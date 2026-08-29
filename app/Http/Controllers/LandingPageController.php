<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Renders a published landing page (§38, §47).
 * Uses a dedicated /landing/{slug} namespace so it never collides with
 * comparison clean-slugs resolved by the public catch-all router.
 */
class LandingPageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $page = LandingPage::published()
            ->where('slug', $slug)
            ->with(['products' => fn ($q) => $q->where('status', 'published')->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])])
            ->with(['comparisons' => fn ($q) => $q->withCount('products')])
            ->firstOrFail();

        return view('landing-pages.show', [
            'page' => $page,
            'seo' => [
                'title' => $page->seo_title ?: $page->title,
                'description' => $page->seo_description ?: ($page->excerpt ?: null),
                'canonical' => $page->canonical_url ?: route('landing-pages.show', $page->slug),
                'og_type' => 'article',
                'published_at' => $page->published_at,
            ],
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $page->seo_title ?: $page->title,
                'description' => $page->seo_description ?: ($page->excerpt ?: null),
                'datePublished' => $page->published_at?->toIso8601String(),
                'author' => ['@type' => 'Organization', 'name' => 'Tulona'],
                'publisher' => ['@type' => 'Organization', 'name' => 'Tulona'],
            ],
        ]);
    }
}
