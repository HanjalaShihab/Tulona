<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Offer;
use App\Models\PriceDropEvent;
use App\Models\Product;
use App\Services\PriceTrackingService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected PriceTrackingService $priceTracking,
        protected RecommendationService $recommendations,
    ) {}

    public function trending(): View
    {
        $products = Product::published()
            ->where('is_trending', true)
            ->with(['brand', 'activeOffers.merchant', 'images', 'latestDrop'])
            ->withCount(['activeOffers as offer_count'])
            ->orderByDesc('popularity_score')
            ->limit(12)
            ->get();

        return view('trending.index', [
            'products' => $products,
            'seo' => [
                'title' => 'Trending Products — Tulona',
                'description' => 'The products everyone is comparing this week on Tulona — ranked, with live store prices and honest history.',
            ],
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $product = Product::where('slug', $slug)
            ->published()
            ->with(['brand', 'category.parent', 'images', 'attributes.definition'])
            ->firstOrFail();

        // Transparent offer ranking (§90): availability → price → freshness
        $offers = Offer::query()
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->with(['merchant'])
            ->orderByRaw('CASE WHEN current_price IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw("CASE availability WHEN 'in_stock' THEN 0 WHEN 'preorder' THEN 1 WHEN 'unknown' THEN 2 ELSE 3 END")
            ->orderBy('current_price')
            ->orderByRaw('COALESCE(last_synced_at, updated_at) DESC')
            ->get();

        $availableOffers = $offers->filter(fn ($o) => $o->current_price !== null);

        [$minPrice, $maxPrice] = $availableOffers->isEmpty()
            ? [null, null]
            : [(float) $availableOffers->min('current_price'), (float) $availableOffers->max('current_price')];

        $summaries = $this->priceTracking->summariesFor($availableOffers);

        $history = $offers->map(fn ($o) => [
            'offer' => $o,
            'summary' => $summaries[$o->id] ?? null,
        ])->filter(fn ($h) => $h['summary'] !== null);

        // Precompute SVG-polyline chart data so the Blade view stays simple (§14).
        $chartData = $history->map(function ($h) {
            $s = $h['summary'];
            $pts = collect($s['points']);
            $n = max($pts->count() - 1, 1);
            $range = max((float) $s['highest'] - (float) $s['lowest'], 0.01);

            return [
                'merchant' => $h['offer']->merchant->name,
                'currency' => $h['offer']->currency,
                'current' => (float) $h['offer']->current_price,
                'lowest' => (float) $s['lowest'],
                'highest' => (float) $s['highest'],
                'average' => (float) $s['average'],
                'points' => $pts->map(fn ($p, $i) => round(($i / $n) * 600, 1).','.round(110 - (($p - $s['lowest']) / $range) * 100, 1))->implode(' '),
            ];
        });

        $alts = $this->recommendations->alternativesFor($product);

        return view('products.show', [
            'product' => $product,
            'offers' => $offers,
            'bestOffer' => $availableOffers->first(),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'chartData' => $chartData,
            'averagePrice' => $availableOffers->isNotEmpty() ? round($availableOffers->avg('current_price'), 2) : null,
            'history' => $history,
            'similar' => $alts['similar'],
            'cheaper' => $alts['cheaper'],
            'relatedGuides' => Article::published()
                ->where(function ($w) use ($product) {
                    $w->whereHas('products', fn ($p) => $p->where('products.id', $product->id))
                        ->orWhereHas('category', fn ($c) => $c->whereIn('categories.id', [$product->category_id]));
                })
                ->limit(3)->get(),
            'latestDrop' => PriceDropEvent::where('product_id', $product->id)->latest('occurred_at')->first(),
            'freshnessHours' => (int) config('tulona.freshness_hours', 72),
            'schema' => $this->schemaFor($product, $offers, $minPrice, $maxPrice),
            'seo' => $this->seoFor($product),
        ]);
    }

    /** Schema.org Product + AggregateOffer — mirrors visible page data only (§38). */
    protected function schemaFor(Product $product, $offers, ?float $min, ?float $max): ?array
    {
        if ($min === null || $max === null) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags((string) ($product->short_description ?: $product->name)),
            'sku' => $product->sku,
            'gtin' => $product->gtin,
            'mpn' => $product->model_number,
            'brand' => ['@type' => 'Brand', 'name' => (string) $product->brand?->name],
            'offers' => [
                '@type' => 'AggregateOffer',
                'lowPrice' => (float) $min,
                'highPrice' => (float) $max,
                'priceCurrency' => $offers->first()->currency ?? 'BDT',
                'offerCount' => $offers->whereNotNull('current_price')->count(),
                'offers' => $offers->whereNotNull('current_price')->map(fn ($o) => [
                    '@type' => 'Offer',
                    'price' => (float) $o->current_price,
                    'priceCurrency' => $o->currency,
                    'availability' => $this->schemaAvailability($o->availability),
                    'seller' => ['@type' => 'Organization', 'name' => $o->merchant->name],
                    'url' => route('go.redirect', [$product->slug, $o->merchant->slug]),
                ])->values()->all(),
            ],
            'aggregateRating' => $product->rating
                ? ['@type' => 'AggregateRating', 'ratingValue' => (float) $product->rating, 'bestRating' => 5, 'ratingCount' => 1, 'name' => 'Editorial rating']
                : null,
        ];
    }

    protected function schemaAvailability(string $a): string
    {
        return match ($a) {
            'in_stock' => 'https://schema.org/InStock',
            'out_of_stock' => 'https://schema.org/OutOfStock',
            'preorder' => 'https://schema.org/PreOrder',
            default => 'https://schema.org/limitedAvailability',
        };
    }

    protected function seoFor(Product $product): array
    {
        $image = $product->images->firstWhere('is_main') ?: $product->images->first();

        return [
            'title' => "{$product->name} — Price Comparison & Best Deals",
            'description' => $product->short_description
                ? Str::limit(strip_tags($product->short_description), 155)
                : "Compare prices for {$product->name} across multiple stores, see price history and find the best deal.",
            'og_type' => 'product',
            'og_image' => $image
                ? (str_starts_with($image->path, 'http') ? $image->path : asset('storage/'.$image->path))
                : null,
        ];
    }
}
