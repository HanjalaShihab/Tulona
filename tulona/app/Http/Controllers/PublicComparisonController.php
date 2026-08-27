<?php

namespace App\Http\Controllers;

use App\Models\Comparison;
use App\Services\ComparisonEngineService;
use Illuminate\View\View;

/** Public rendering of a published comparison (§35–§37). */
class PublicComparisonController extends Controller
{
    public function show(string $slug, ComparisonEngineService $engine): View
    {
        $comparison = Comparison::published()->where('slug', $slug)->firstOrFail();

        $rows = $engine->build($comparison);
        $bestPrice = $engine->bestPrice($comparison);
        $bestDeal = $engine->bestDeal($comparison);

        return view('comparisons.show', [
            'comparison' => $comparison,
            'rows' => $rows,
            'bestPrice' => $bestPrice,
            'bestDeal' => $bestDeal,
            'specifications_shown' => $comparison->specifications_shown ?? [],
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $comparison->seo_title ?: $comparison->title,
                'description' => $comparison->seo_description ?: ($comparison->introduction ?: null),
                'datePublished' => $comparison->published_at?->toIso8601String(),
                'author' => ['@type' => 'Organization', 'name' => 'Tulona'],
                'publisher' => ['@type' => 'Organization', 'name' => 'Tulona'],
            ],
        ]);
    }
}
