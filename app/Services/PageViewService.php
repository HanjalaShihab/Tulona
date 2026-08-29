<?php

namespace App\Services;

use App\Models\PageView;
use Illuminate\Http\Request;

/**
 * Anonymous page-view tracking for privacy-friendly analytics.
 *
 * Records only coarse, non-personal data: the internal path, an internal
 * referrer path, a salted sha256 hash of the IP (never the raw IP), and a
 * coarse UA family. No cookies, no query strings, no external referrers.
 */
class PageViewService
{
    public function record(Request $request): void
    {
        $path = $this->safeInternalPath((string) $request->input('path', ''));

        if ($path === null) {
            return;
        }

        PageView::create([
            'path' => $path,
            'referrer_page' => $this->safeInternalPath((string) $request->input('ref', '')),
            'ip_hash' => $this->ipHash($request),
            'user_agent_family' => $this->agentFamily($request->userAgent()),
            'viewed_at' => now(),
        ]);
    }

    /** Keep only internal, query-stripped paths; drop everything else. */
    protected function safeInternalPath(string $input): ?string
    {
        $input = trim($input);

        if ($input === '' || str_starts_with($input, '//')) {
            return null;
        }

        $host = parse_url($input, PHP_URL_HOST);
        if ($host !== null) {
            return null;
        }

        if ($input[0] !== '/') {
            $input = '/'.$input;
        }

        $path = parse_url($input, PHP_URL_PATH) ?: '/';

        $length = strlen($path);
        if ($length > 500) {
            return null;
        }

        return $path === '' ? '/' : $path;
    }

    protected function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? hash('sha256', $ip.config('app.key')) : null;
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
