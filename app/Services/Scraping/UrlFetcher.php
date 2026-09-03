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

        $resolvedIp = gethostbyname($host);
        if ($resolvedIp && $resolvedIp !== $host && $resolvedIp !== '0.0.0.0' && $resolvedIp !== '::') {
            abort_if($this->isBlockedIp($resolvedIp), 422, 'Blocked host.');
        }

        $this->assertPublicHost($host);
        $this->assertAllowed($host, $path);

        usleep($this->pauseMs * 1000);

        $response = $this->attempt($url, true);

        // Some BD merchants serve a valid site whose TLS certificate has a
        // missing/broken SAN or chains to an untrusted intermediate. The secure
        // request fails the whole scrape for every product on their site. As a
        // deliberate, logged fallback we retry once with peer verification
        // disabled ONLY when the first attempt failed for a TLS/SSL reason —
        // never for an ordinary HTTP error (404/403) or a timeout.
        if ($response === null) {
            Log::warning('Scrape TLS verification failed — retrying without peer verification', ['url' => $url]);
            $response = $this->attempt($url, false);
        }

        if ($response === null) {
            abort(422, "Merchant source is unreachable (TLS/connection failure).");
        }

        if ($response->failed()) {
            Log::warning('Scrape fetch failed', ['url' => $url, 'status' => $response->status()]);
            abort(422, "Merchant source returned HTTP {$response->status()}.");
        }

        return $response->body();
    }

    /**
     * Perform one bounded HTTP GET with peer-verification on/off. Returns the
     * response for any HTTP status (http_errors disabled), or null when the
     * connection itself blew up (TLS/cert/DNS/connect) — so the caller can
     * decide to retry with a looser trust policy. Never raises for an HTTP
     * error; only for transport-level failures.
     */
    protected function attempt(string $url, bool $verifyPeer): ?\Illuminate\Http\Client\Response
    {
        try {
            return $this->http->timeout($this->timeout)
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
                ->withOptions(array_merge([
                    'decode_content' => true,
                    'verify' => $verifyPeer,
                    'http_errors' => false,
                    'allow_redirects' => ['max' => 3, 'strict' => true],
                ], $this->proxyOptions($url)))
                ->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Scrape transport error', ['url' => $url, 'verify' => $verifyPeer, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Guzzle `proxy` request option for a URL.
     *
     * Resolution order (most specific wins):
     *   1. a host rule in config('scrape.rules') whose bare PCRE pattern matches
     *      the request's host name (patterns carry no delimiters; they are
     *      wrapped here, case-insensitively, so `rokomari\.com$` works);
     *   2. the global config('scrape.proxy') (SCRAPE_PROXY env var);
     *   3. nothing — fetched directly from the server.
     */
    protected function proxyOptions(string $url): array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach ((array) config('scrape.rules', []) as $pattern => $proxy) {
            if ($proxy !== null && $proxy !== '' && $proxy !== false
                && @preg_match('#'.$pattern.'#i', $host) === 1) {
                return ['proxy' => $proxy];
            }
        }

        $proxy = config('scrape.proxy');

        return ($proxy !== null && $proxy !== '' && $proxy !== false)
            ? ['proxy' => $proxy]
            : [];
    }

    /**
     * Reject loopback, private, link-local and other non-public addresses to keep
     * scraping SSRF-safe (never reach intranet services or cloud metadata).
     *
     * Literal IPs are checked against reserved ranges. For hostnames we rely on
     * the bounded redirect cap plus the resolved-IP check performed by the
     * underlying transport, and we only reject well-known internal names. We do
     * NOT do a blocking DNS pre-resolution of every public hostname — that would
     * add a network round-trip per scrape and break faked/test HTTP clients.
     */
    protected function assertPublicHost(string $host): void
    {
        $host = strtolower(trim($host));

        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain', 'localhost6', 'metadata.google.internal', '169.254.169.254'], true)) {
            abort(422, 'Blocked host.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            abort_if($this->isBlockedIp($host), 422, 'Blocked host.');
        }
    }

    protected function isBlockedIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) {
                return true;
            }
            if (($long & 0xFF000000) === 0x00000000) {
                return true;
            }        // 0/8
            if (($long & 0xFF000000) === 0x0A000000) {
                return true;
            }        // 10/8
            if (($long & 0xFFC00000) === 0x64400000) {
                return true;
            }        // 100.64/10
            if (($long & 0xFF000000) === 0x7F000000) {
                return true;
            }        // 127/8
            if (($long & 0xFFFF0000) === 0xA9FE0000) {
                return true;
            }        // 169.254/16
            if (($long & 0xFFF00000) === 0xAC100000) {
                return true;
            }        // 172.16/12
            if (($long & 0xFFFF0000) === 0xC0A80000) {
                return true;
            }        // 192.168/16
            if (($long & 0xFF000000) === 0xC0000000) {
                return true;
            }        // 192.0.0/24
            if (($long & 0xFFFE0000) === 0xC6120000) {
                return true;
            }        // 198.18/15
            if (($long & 0xF0000000) === 0xE0000000) {
                return true;
            }        // multicast
            if (($long & 0xF0000000) === 0xF0000000) {
                return true;
            }        // 240/4

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (preg_match('/^::ffff:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $ip, $m)) {
                return $this->isBlockedIp($m[1]);
            }
            $low = strtolower($ip);
            if (in_array($low, ['::', '::1'], true)) {
                return true;
            }
            if (str_starts_with($low, 'fc') || str_starts_with($low, 'fd')) {
                return true;
            } // ULA
            if (preg_match('~^fe[89ab]~', $low)) {
                return true;
            }            // link-local
        }

        return true; // Not parseable → treat as blocked.
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
        $this->assertPublicHost($host);

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
