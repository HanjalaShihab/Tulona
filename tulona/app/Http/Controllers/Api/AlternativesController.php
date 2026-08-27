<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RecommendationService;

class AlternativesController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $alts = app(RecommendationService::class)->alternativesFor($product);

        return response()->json([
            'similar' => $alts['similar']->map(fn ($p) => ['name' => $p->name, 'slug' => $p->slug]),
            'cheaper' => $alts['cheaper']->map(fn ($p) => ['name' => $p->name, 'slug' => $p->slug]),
        ]);
    }
}
