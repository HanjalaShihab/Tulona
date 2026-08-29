<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(protected SearchService $search) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $results = $q !== '' ? $this->search->search($q) : [];

        return view('search', [
            'q' => $q,
            'results' => $results,
            'seo' => ['title' => $q !== '' ? "Search results for “{$q}” — Tulona" : 'Search — Tulona', 'robots' => 'noindex'],
        ]);
    }

    /** Lightweight JSON suggestions for the header search box. */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%'.addcslashes($q, '%_\\').'%';

        $products = Product::published()
            ->where('name', 'like', $like)
            ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
            ->with('brand:id,name')
            ->limit(8)
            ->get(['id', 'name', 'slug']);

        return response()->json($products->map(fn ($p) => [
            'name' => $p->name,
            'brand' => $p->brand?->name,
            'url' => route('product.show', $p->slug),
        ]));
    }
}
