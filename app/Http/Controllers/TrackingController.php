<?php

namespace App\Http\Controllers;

use App\Services\PageViewService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Anonymous page-view beacon. Returns a tiny, cache-busting, noindex response
 * and never blocks the page render.
 */
class TrackingController extends Controller
{
    public function track(Request $request, PageViewService $service): Response
    {
        $service->record($request);

        return response('', 204);
    }
}
