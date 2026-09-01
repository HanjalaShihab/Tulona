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
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231C1A17'/%3E%3Ccircle cx='16' cy='16' r='9' fill='%23E2542D'/%3E%3Ctext x='16' y='21.5' font-family='sans-serif' font-size='15' font-weight='bold' text-anchor='middle' fill='%23fff'%3ET%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800;9..144,900&family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ $assetCss }}">
@yield('schema')
</head>
<body>
  {{-- Live trust ticker — CSS animated, duplicated by JS — --}}
  <div class="ticker" aria-hidden="true">
    <div class="ticker-track">
      <div class="ticker-group">
        <span class="ticker-item">✓ Verified price history — no fake discounts</span>
        <span class="ticker-item">⚖ Side-by-side store comparison on every product</span>
        <span class="ticker-item">↻ Freshness promise — offers refreshed within 72h</span>
        <span class="ticker-item">✭ Independent rankings — no sponsored placement</span>
      </div>
    </div>
  </div>

  <header class="site-header">
    <div class="header-in">
      <button class="nav-toggle" data-drawer-open type="button" aria-label="Open menu" aria-expanded="false"><span></span><span></span><span></span></button>
      <a class="logo" href="{{ route('home') }}"><span class="logo-mark" aria-hidden="true">T</span><span class="logo-text">Tulona<span>na</span></span></a>
      <nav class="main-nav" aria-label="Main">
        <a class="nav-link" href="{{ route('deals.index') }}">Deals</a>
        <a class="nav-link" href="{{ route('drops.index') }}">Price Drops</a>
        <a class="nav-link" href="{{ route('guides.index') }}">Guides</a>
        <a class="nav-link" href="{{ route('reviews.index') }}">Reviews</a>
      </nav>
      <div class="header-tools">
        <div class="cat-menu has-menu">
          <button class="cat-trigger" type="button" aria-expanded="false"><span class="ct-ico">▦</span><span class="ct-label">Categories</span><span class="ct-caret">⌄</span></button>
          <div class="mega">
            <div class="mega-head">Browse categories</div>
            <div class="mega-grid">
              @foreach($navCategories as $c)
              <a href="{{ route('categories.show', $c['slug']) }}"><span class="mega-ico">{{ $c['icon'] ?? '▦' }}</span><span>{{ $c['name'] }}</span></a>
              @endforeach
            </div>
          </div>
        </div>
        <form class="search-form" role="search" action="{{ route('search.index') }}" method="get">
          <div class="search-wrap">
            <svg class="search-ico" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <input type="text" class="search-input" name="q" value="{{ request()->q ?? '' }}" placeholder="Search products, brands, stores…" data-suggest aria-label="Search products" autocomplete="off">
            <ul class="suggest"></ul>
          </div>
          <button class="search-btn" type="submit" aria-label="Search"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></button>
        </form>
      </div>
    </div>
  </header>

  {{-- Mobile drawer --}}
  <div class="drawer" data-drawer aria-hidden="true">
    <div class="drawer-head">
      <a class="logo" href="{{ route('home') }}"><span class="logo-mark" aria-hidden="true">T</span><span class="logo-text">Tulona<span>na</span></span></a>
      <button class="drawer-close" data-drawer-close type="button" aria-label="Close menu">×</button>
    </div>
    <form class="drawer-search" role="search" action="{{ route('search.index') }}" method="get">
      <input type="text" name="q" placeholder="Search products, brands…" aria-label="Search" autocomplete="off">
      <button type="submit" aria-label="Search">⌕</button>
    </form>
    <nav class="drawer-nav" aria-label="Mobile">
      <a href="{{ route('deals.index') }}"><span>🔥</span> Deals</a>
      <a href="{{ route('drops.index') }}"><span>📉</span> Price Drops</a>
      <a href="{{ route('guides.index') }}"><span>📖</span> Guides</a>
      <a href="{{ route('reviews.index') }}"><span>⭐</span> Reviews</a>
    </nav>
    @if(!empty($navCategories))
    <div class="drawer-title">Categories</div>
    <nav class="drawer-cats" aria-label="Categories">
      @foreach($navCategories as $c)
      <a href="{{ route('categories.show', $c['slug']) }}">{{ $c['icon'] ?? '▦' }} {{ $c['name'] }}</a>
      @endforeach
    </nav>
    @endif
    <div class="drawer-foot">Free forever · Honest data · You always buy from the store you choose</div>
  </div>
  <div class="scrim" data-scrim hidden></div>

  <main id="main">
    @if (session('status'))<div class="container"><div class="alert alert-ok">{{ session('status') }}</div></div>@endif
    @if (session('error'))<div class="container"><div class="alert alert-err">{{ session('error') }}</div></div>@endif

    @yield('content')
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid footer-top">
        <div class="footer-brand">
          <a class="logo logo--light" href="{{ route('home') }}"><span class="logo-mark" aria-hidden="true">T</span><span class="logo-text">Tulona<span>na</span></span></a>
          <p>Find the right product at the right price. We compare products, prices and deals from trusted stores so you can buy with confidence.</p>
          <div class="footer-trust">
            <span>✓ No fake discounts</span><span>✓ Verified price history</span><span>✓ You buy from the store</span>
          </div>
        </div>
        <div class="footer-col">
          <h4>Explore</h4>
          <a href="{{ route('deals.index') }}">Deals</a>
          <a href="{{ route('drops.index') }}">Price Drops</a>
          <a href="{{ route('guides.index') }}">Buying Guides</a>
          <a href="{{ route('reviews.index') }}">Reviews</a>
        </div>
        <div class="footer-col">
          <h4>Stores</h4>
          @foreach($footerMerchants as $m)<a href="{{ route('merchants.show', $m['slug']) }}">{{ $m['name'] }}</a>@endforeach
        </div>
        <div class="footer-col">
          <h4>Company</h4>
          <a href="{{ url('/about') }}">About</a>
          <a href="{{ url('/contact') }}">Contact</a>
          <a href="{{ url('/affiliate-disclosure') }}">Affiliate Disclosure</a>
          <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
          <a href="{{ url('/terms-of-use') }}">Terms of Use</a>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© {{ date('Y') }} Tulona — smart shopping research for Bangladesh. Prices change; the store always wins.</span>
        <span>Made with <span class="heart">♥</span> for smart shoppers</span>
      </div>
    </div>
  </footer>

  <button class="to-top" data-totop type="button" aria-label="Back to top">↑</button>

  {{-- Organization + WebSite schema sitewide (§38) --}}
  @php
  $siteSchema = [
    '@context' => 'https://schema.org',
    '@graph' => [
      ['@type' => 'Organization', 'name' => 'Tulona', 'url' => url('/'),
       'logo' => asset('img/logo.png'), 'description' => 'Smart shopping researchand product comparison platform for Bangladesh.'],
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
