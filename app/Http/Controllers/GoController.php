<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Services\AffiliateRedirectService;
use Illuminate\Http\Request;

class GoController extends Controller
{
    /**
     * GET /go/{productSlug}/{merchantSlug}
     * Tracked affiliate redirect — the ONLY outbound path we expose.
     * Destinations always come from stored offers; never user input (§5, §55).
     */
    public function redirect(Request $request, string $productSlug, string $merchantSlug)
    {
        $offer = Offer::query()
            ->where('status', 'active')
            ->whereHas('product', fn ($p) => $p->where('slug', $productSlug)->published())
            ->whereHas('merchant', fn ($m) => $m->where('slug', $merchantSlug)->where('status', 'active'))
            ->first();

        abort_unless($offer !== null, 404, 'This offer is not currently available.');

        $destination = app(AffiliateRedirectService::class)->resolveAndTrack($offer, $request);

        return redirect()->away($destination)->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
