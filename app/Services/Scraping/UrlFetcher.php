<?php

namespace App\Services\Scraping;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Polite fetcher for merchant product-list pages/feeds (§14, §15).
 * Respects robots.txt disallow rules and applies a short rate-limit pause so
 * bulk scraping never hammers a merchant. Never bypasses access controls.
 */
class UrlFetcher
{
    public function __construct(
        protected Http $http,
        protected int $timeout = 20,
        protected int $pauseMs = 250,
    ) {}

    /** @return string body */
    public function fetch(string $url): string
    {
        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');
        $path = rawurldecode($parsed['path'] ?? '/');

        abort_unless(in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true), 422, 'Unsupported URL scheme.');
        $this->assertAllowed($host, $path);

        usleep($this->pauseMs * 1000);

        $response = $this->http->timeout($this->timeout)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,bn;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ])
            ->withOptions([
                'decode_content' => true,
                'verify' => true,
                'http_errors' => false,
            ])
            ->get($url);

        if ($response->failed()) {
            Log::warning('Scrape fetch failed', ['url' => $url, 'status' => $response->status()]);
            abort(422, "Merchant source returned HTTP {$response->status()}.");
        }

        return $response->body();
    }

    protected function assertAllowed(string $host, string $path): void
    {
        $rules = Cache::remember("robots:{$host}", now()->addHours(6), function () use ($host) {
            return $this->loadRobots($host);
        });

        foreach ($rules as $disallow) {
            if ($disallow !== '' && preg_match($this->patternFor($disallow), $path)) {
                abort(403, 'Robots.txt disallows scraping this path.');
            }
        }
    }

    protected function loadRobots(string $host): array
    {
        try {
            $body = $this->http->timeout(8)->withUserAgent('TulonaBot/1.0')->get("https://{$host}/robots.txt")->body();
        } catch (\Throwable $e) {
            return []; // no robots data → assume crawl allowed
        }

        $rules = [];
        $inGroup = false;

        foreach (preg_split('/\R/', $body) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'Disallow:')) {
                if ($inGroup) {
                    $rules[] = trim(substr($line, strlen('Disallow:')));
                }

                continue;
            }
            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $m)) {
                $inGroup = trim($m[1]) === '*' || str_contains($m[1], 'Tulona');
            }
        }

        return array_values(array_filter($rules));
    }

    protected function patternFor(string $disallow): string
    {
        $escaped = preg_quote($disallow, '/');
        $escaped = str_replace(['\*', '\$'], ['.*', '$'], $escaped);

        return '/^'.$escaped.'/i';
    }
}
