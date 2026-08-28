<?php

namespace App\Services;

use App\Models\Click;
use App\Models\Offer;
use Illuminate\Http\Request;

/**
 * Central outbound affiliate tracking: records the click anonymously,
 * resolves the destination, appends click-subid tracking when configured.
 * Never accepts user-supplied redirect destinations — only stored offer URLs (§5, §55).
 */
class AffiliateRedirectService
{
    public function resolveAndTrack(Offer $offer, Request $request): string
    {
        $click = Click::create([
            'offer_id' => $offer->id,
            'product_id' => $offer->product_id,
            'merchant_id' => $offer->merchant_id,
            'referrer_page' => $this->safeInternalReferrer($request),
            'ip_hash' => hash('sha256', $request->ip().config('app.key')),
            'user_agent_family' => $this->agentFamily($request->userAgent()),
            'clicked_at' => now(),
            'clicked_on' => now()->toDateString(),
        ]);

        // Fire-and-forget counter increment; never blocks the redirect
        dispatch(function () use ($offer): void {
            $offer->increment('clicks_count');
            optional($offer->product)->increment('clicks_count');
        })->afterResponse();

        return $this->buildDestination($offer, $click->id);
    }

    protected function buildDestination(Offer $offer, int $clickId): string
    {
        $url = $offer->resolvedAffiliateUrl();
        $template = $offer->merchant->tracking_template;

        if ($template) {
            return str_replace(
                ['{affiliate_url}', '{offer_id}', '{click_id}'],
                [$url, (string) $offer->id, (string) $clickId],
                $template
            );
        }

        if ($url) {
            $glue = str_contains($url, '?') ? '&' : '?';

            return "{$url}{$glue}subid={$clickId}";
        }

        // Last-resort fallback must never be a user-controlled URL
        return $offer->merchant->website_url ?? url('/');
    }

    /** Keep only internal paths as referrer analytics; drop query/external values. */
    protected function safeInternalReferrer(Request $request): ?string
    {
        $ref = (string) $request->header('referer', '');

        if ($ref === '') {
            return null;
        }

        $parsed = parse_url($ref);
        $host = $parsed['host'] ?? '';
        $siteHost = parse_url(url('/'))['host'] ?? '';

        if ($host !== $siteHost || ! str_starts_with($ref, url('/'))) {
            return null;
        }

        return $parsed['path'] ?? '/';
    }

    protected function agentFamily(?string $ua): ?string
    {
        return match (true) {
            ! $ua => null,
            str_contains(strtolower($ua), 'mobile') => 'mobile',
            default => 'desktop',
        };
    }
}
