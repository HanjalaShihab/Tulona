<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DealsController;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\PriceDropEvent;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only public JSON API (§54) — clean REST surface that mirrors the site,
 * ready for the future mobile app / partner sites (§94).
 */
class ApiController extends Controller
{
    public function __construct(protected SearchService $search) {}

    public function products(Request $request): JsonResponse
    {
        $q = Product::where('status', 'published')->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->withCount('activeOffers');

        if ($s = $request->query('search')) {
            $ids = $this->search->search($s)['products']->pluck('id');
            $q->whereIn('id', $ids);
        }
        if ($cat = $request->query('category')) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $cat));
        }
        if ($brand = $request->query('brand')) {
            $q->whereHas('brand', fn ($b) => $b->where('slug', $brand));
        }

        return response()->json($q->paginate(min((int) $request->query('per_page', 24), 100))
            ->through(fn ($p) => [
                'name' => $p->name, 'slug' => $p->slug, 'brand' => $p->brand?->name,
                'category' => $p->category?->name, 'offers_count' => $p->active_offers_count,
                'best_price' => $p->bestOffer()?->current_price,
                'url' => route('product.show', $p->slug),
            ]));
    }

    public function product(string $slug): JsonResponse
    {
        $p = Product::where('slug', $slug)->where('status', 'published')
            ->with(['brand', 'category', 'images', 'attributes.definition', 'activeOffers.merchant'])
            ->firstOrFail();

        return response()->json([
            'name' => $p->name, 'slug' => $p->slug, 'brand' => $p->brand?->name,
            'category' => $p->category?->name, 'short_description' => $p->short_description,
            'attributes' => $p->attributes->mapWithKeys(fn ($a) => [$a->definition->key => trim(($a->value_text ?? '').($a->definition->unit ? " {$a->definition->unit}" : ''))]),
            'offers' => $p->activeOffers->map(fn ($o) => [
                'merchant' => $o->merchant->name, 'price' => $o->current_price, 'currency' => $o->currency,
                'availability' => $o->availability, 'go_url' => route('go.redirect', [$p->slug, $o->merchant->slug]),
            ]),
            'guides' => Article::published()->whereHas('products', fn ($w) => $w->where('products.id', $p->id))->pluck('title', 'slug'),
        ]);
    }

    public function offers(string $slug): JsonResponse
    {
        return response()->json(Product::where('slug', $slug)->firstOrFail()
            ->activeOffers()->with('merchant:id,name,slug')->orderBy('current_price')->get());
    }

    public function categories(): JsonResponse
    {
        return response()->json(Category::whereNull('parent_id')->where('is_active', true)
            ->with('children:id,parent_id,name,slug')->select('id', 'parent_id', 'name', 'slug')->get());
    }

    public function categoryProducts(string $slug, Request $request): JsonResponse
    {
        $c = Category::where('slug', $slug)->firstOrFail();

        return response()->json(Product::where('status', 'published')
            ->whereIn('category_id', $c->descendantsAndSelf())
            ->with(['brand:id,name,slug'])->paginate(24));
    }

    public function brand(string $slug): JsonResponse
    {
        $b = Brand::where('slug', $slug)->firstOrFail();

        return response()->json([
            'name' => $b->name, 'slug' => $b->slug, 'description' => $b->description,
            'products' => Product::where('brand_id', $b->id)->where('status', 'published')
                ->select('name', 'slug')->limit(50)->get(),
        ]);
    }

    public function merchants(): JsonResponse
    {
        return response()->json(Merchant::where('status', 'active')
            ->select('name', 'slug', 'country', 'website_url')->get());
    }

    public function merchant(string $slug): JsonResponse
    {
        $m = Merchant::where('slug', $slug)->where('status', 'active')->firstOrFail();

        return response()->json([
            'name' => $m->name, 'slug' => $m->slug, 'description' => $m->description,
            'product_count' => $m->offers()->distinct('product_id')->count('product_id'),
            'last_synced_at' => $m->last_synced_at,
        ]);
    }

    public function deals(): JsonResponse
    {
        return response()->json(DealsController::dealQuery()
            ->limit(50)->get()
            ->map(fn ($p) => [
                'name' => $p->name, 'slug' => $p->slug,                 'best_price' => (float) $p->best_price,
                'original_from' => $p->max_original !== null ? (float) $p->max_original : null,
            ]));
    }

    public function priceDrops(): JsonResponse
    {
        return response()->json(
            PriceDropEvent::with('product:id,name,slug')->latest('occurred_at')->limit(50)->get()
        );
    }

    public function search(Request $request): JsonResponse
    {
        return response()->json($this->search->search((string) $request->query('q', '')));
    }

    public function compare(Request $request): JsonResponse
    {
        $slugs = collect(explode(',', (string) $request->query('products', '')))->take(4);

        return response()->json(Product::whereIn('slug', $slugs)->where('status', 'published')
            ->with(['brand:id,name', 'category:id,name', 'activeOffers.merchant:id,name'])
            ->get());
    }
}
