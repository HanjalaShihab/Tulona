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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/app.css">
@yield('schema')
</head>
<body>
<header class="site-header">
  <div class="header-in">
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <a class="logo" href="{{ route('home') }}">Tulo<span>na</span></a>
    <nav class="main-nav" aria-label="Main">
      <div class="has-menu">
        <a href="{{ route('products.index') }}">Categories ▾</a>
        <div class="mega">
          @foreach(App\Models\Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get() as $c)
            <a href="{{ route('categories.show', $c->slug) }}">{{ ($c->icon ? $c->icon.' ' : '') }}{{ $c->name }}</a>
          @endforeach
        </div>
      </div>
      <a href="{{ route('deals.index') }}">Deals</a>
      <a href="{{ route('drops.index') }}">Price Drops</a>
      <a href="{{ route('compare.index') }}">Compare</a>
      <a href="{{ route('guides.index') }}">Guides</a>
      <a href="{{ route('reviews.index') }}">Reviews</a>
    </nav>
    <form class="search-form head-search" role="search" action="{{ route('search.index') }}" method="get">
      <div class="search-wrap">
        <input type="text" class="search-input" name="q" value="{{ request()->q ?? '' }}"
               placeholder="Search products, brands, categories…" data-suggest aria-label="Search products" autocomplete="off">
        <ul class="suggest"></ul>
      </div>
      <button class="search-btn icon-btn" type="submit" aria-label="Search">Search</button>
    </form>
    <div class="nav-cta">
      <a class="btn btn-outline btn-sm" href="{{ route('compare.index') }}">Compare</a>
    </div>
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
        @foreach(\App\Models\Merchant::where('status','active')->orderBy('name')->get() as $m)
          <a href="{{ route('merchants.show', $m->slug) }}">{{ $m->name }}</a>
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
    <p class="footer-note">Prices and availability are provided for informational purposes and may change. Clicking a retailer link will take you to the retailer's website, where final pricing and availability are determined. Tulona does not sell products or process payments.</p>
  </div>
</footer>

{{-- Organization + WebSite schema sitewide (§38) --}}
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@graph' => [
    ['@type' => 'Organization', 'name' => 'Tulona', 'url' => url('/'),
     'logo' => asset('img/logo.png'), 'description' => 'Smart shopping research and product comparison platform for Bangladesh.'],
    ['@type' => 'WebSite', 'name' => 'Tulona', 'url' => url('/'),
     'potentialAction' => ['@type' => 'SearchAction',
       'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
       'query-input' => 'required name=search_term_string']],
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script src="/js/app.js" defer></script>
@yield('scripts')
</body>
</html>
