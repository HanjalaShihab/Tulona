<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP proxy for scraping
    |--------------------------------------------------------------------------
    |
    | Some merchants (e.g. Rokomari) block datacenter/shared-hosting IPs with a
    | Cloudflare challenge and only serve shoppers behind individual/business
    | IPs. To fetch those sources reliably, route scraping through a proxy with
    | a better-reputation egress IP (a small VPS or a residential/rotating
    | proxy provider).
    |
    | `proxy` (from SCRAPE_PROXY) is the GLOBAL default: when set, every site is
    | fetched through it, unless a more specific host rule below matches.
    |
    | `rules` lets you proxy only specific host name patterns while keeping the
    | rest direct — e.g. send only Rokomari through the proxy. Each key is a
    | bare PCRE pattern (NO surrounding delimiters) matched case-insensitively
    | against the host name; the first match wins and overrides the global
    | `proxy`. Values are passed verbatim to Guzzle's `proxy` option:
    |
    |   'tcp://127.0.0.1:8125'
    |   'socks5://user:pass@127.0.0.1:1080'
    |   array('http' => '...', 'https' => '...')
    |
    | Leave rules empty and proxy null to scrape everything directly.
    |
    */

    'proxy' => env('SCRAPE_PROXY', null),

    /*
    | Host-specific proxies (optional). Keys are bare PCRE patterns WITHOUT
    | delimiters, matched against the host name. First match wins, overrides the
    | global `proxy`. Uncomment the example to route only Rokomari through a
    | proxy while scraping every other merchant directly:
    */
    'rules' => array_filter([
        // '^api\.rokomari\.com$' => env('SCRAPE_PROXY_ROKOMARI'),
        // '(^|\.)rokomari\.com$' => env('SCRAPE_PROXY_ROKOMARI'),
    ]),
];