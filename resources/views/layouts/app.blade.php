<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $seo['title'] ?? 'Tulona — Smart Shopping Research' }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit($seo['description'] ?? 'Compare products, prices, deals and trusted stores before you buy.', 160) }}">
@if($seo['robots'] ?? null)<meta name="robots" content="{{ $seo['robots'] }}">@endif
<link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
{{-- Open Graph / Twitter cards (§36) --}}
<meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
<meta property="og:title" content="{{ $seo['title'] ?? 'Tulona' }}">
<meta property="og:description" content="{{ $seo['description'] ?? '' }}">
<meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
@if($seo['og_image'] ?? $seo['published_at'] ?? null)
<meta property="og:image" content="{{ $seo['og_image'] ?? '' }}">
@endif
@if($seo['published_at'] ?? null)<meta property="article:published_time" content="{{ $seo['published_at']->toIso8601String() }}">@endif
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%23111927'/%3E%3Ctext x='16' y='22' font-family='sans-serif' font-size='18' font-weight='bold' text-anchor='middle' fill='%23f97316'%3ET%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://rokbucket.rokomari.io" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ $assetCss }}">
@yield('schema')
</head>
<body>
<header class="site-header">
  <div class="header-in">
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <a class="logo" href="{{ route('home') }}">Tulo<span>na</span></a>
    <nav class="main-nav" aria-label="Main">
      <a href="{{ route('deals.index') }}">Deals</a>
      <a href="{{ route('drops.index') }}">Price Drops</a>
      <a href="{{ route('compare.index') }}">Compare</a>
      <a href="{{ route('guides.index') }}">Guides</a>
      <a href="{{ route('reviews.index') }}">Reviews</a>
    </nav>
    <div class="nav-cta">
      <a class="btn btn-outline btn-sm" href="{{ route('compare.index') }}">Compare</a>
    </div>
  </div>
  <div class="header-tools">
    <div class="cat-menu has-menu">
      <button type="button" class="cat-trigger" aria-expanded="false">☰ Categories ▾</button>
      <div class="mega">
        @foreach($navCategories as $c)
          <a href="{{ route('categories.show', $c['slug']) }}">{{ ($c['icon'] ? $c['icon'].' ' : '') }}{{ $c['name'] }}</a>
        @endforeach
      </div>
    </div>
    <form class="search-form" role="search" action="{{ route('search.index') }}" method="get">
      <div class="search-wrap">
        <input type="text" class="search-input" name="q" value="{{ request()->q ?? '' }}"
               placeholder="Search products, brands, categories…" data-suggest aria-label="Search products" autocomplete="off">
        <ul class="suggest"></ul>
      </div>
      <button class="search-btn" type="submit" aria-label="Search">Search</button>
    </form>
  </div>
</header>

<main id="main">
  @if (session('status'))<div class="container"><div class="alert alert-ok">{{ session('status') }}</div></div>@endif
  @if (session('error'))<div class="container"><div class="alert alert-err">{{ session('error') }}</div></div>@endif

  @yield('content')
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <h4>Tulona</h4>
        <p>Find the right product at the right price. We compare products, prices and deals from trusted stores so you can buy with confidence.</p>
      </div>
      <div>
        <h4>Explore</h4>
        <a href="{{ route('deals.index') }}">Deals</a>
        <a href="{{ route('drops.index') }}">Price Drops</a>
        <a href="{{ route('guides.index') }}">Buying Guides</a>
        <a href="{{ route('compare.index') }}">Compare Products</a>
      </div>
      <div>
        <h4>Stores</h4>
        @foreach($footerMerchants as $m)
          <a href="{{ route('merchants.show', $m['slug']) }}">{{ $m['name'] }}</a>
        @endforeach
      </div>
      <div>
        <h4>Company</h4>
        <a href="{{ url('/about') }}">About</a>
        <a href="{{ url('/contact') }}">Contact</a>
        <a href="{{ url('/affiliate-disclosure') }}">Affiliate Disclosure</a>
        <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
        <a href="{{ url('/terms-of-use') }}">Terms of Use</a>
      </div>
    </div>
  </div>
</footer>

{{-- Organization + WebSite schema sitewide (§38) --}}
@php
  $siteSchema = [
    '@context' => 'https://schema.org',
    '@graph' => [
      ['@type' => 'Organization', 'name' => 'Tulona', 'url' => url('/'),
       'logo' => asset('img/logo.png'), 'description' => 'Smart shopping research and product comparison platform for Bangladesh.'],
      ['@type' => 'WebSite', 'name' => 'Tulona', 'url' => url('/'),
       'potentialAction' => ['@type' => 'SearchAction',
         'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
         'query-input' => 'required name=search_term_string']],
    ],
  ];
@endphp
<script type="application/ld+json">@json($siteSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
<script src="{{ $assetJs }}" defer></script>
{{-- Anonymous page-view beacon: coarse path + internal referrer only (no raw IP, no cookies) --}}
<script>
(function () {
  var trackUrl = '{{ url('/tulona/track') }}';
  var ref = '';
  try { var r = document.referrer; if (r && r.indexOf(location.origin) === 0) { ref = new URL(r).pathname; } } catch (e) {}
  var payload = 'path=' + encodeURIComponent(location.pathname) + '&ref=' + encodeURIComponent(ref);
  if (navigator.sendBeacon) {
    try { navigator.sendBeacon(trackUrl + '?' + payload); return; } catch (e) {}
  }
  var img = new Image();
  img.src = trackUrl + '?' + payload;
})();
</script>
@yield('scripts')
</body>
</html>
