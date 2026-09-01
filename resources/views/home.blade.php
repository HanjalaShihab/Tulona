@extends('layouts.app')

@section('content')
@php
  $heroProducts = $featured->take(3);
  if($heroProducts->isEmpty()) $heroProducts = $trending->take(3);
  if($heroProducts->isEmpty()) $heroProducts = $newArrivals->take(3);
  $dealCount = $deals->count();
  $trendCount = $trending->count();
@endphp

<!-- ── HERO — Cinematic, editorial, glass search ── -->
<section class="home-hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <div class="hero-eyebrow">
        <span class="eyebrow-dot"></span>
        Trusted by researchers & bargain hunters in Bangladesh
        <span class="eyebrow-live">● live prices</span>
      </div>

      <h1 class="hero-title">
        Find the right
        <span class="hero-accent">product</span>
        at the right price
      </h1>

      <p class="hero-sub">
        {{ App\Models\Setting::get('homepage.hero_subtitle', 'We compare products, prices and trusted stores before you buy — honest data, verified history, no sponsored ranking.') }}
      </p>

      <form class="hero-search" role="search" action="{{ route('search.index') }}" method="get">
        <div class="hs-input-wrap">
          <span class="hs-icon" aria-hidden="true">⌕</span>
          <input type="text" name="q" placeholder="Search phones, laptops, books, headphones…" aria-label="Search products" autocomplete="off">
        </div>
        <button class="btn btn-primary hs-btn" type="submit">Search</button>
      </form>

      <div class="hero-chips">
        <span>Try:</span>
        <a href="{{ route('search.index') }}?q=phone">Phones</a>
        <a href="{{ route('search.index') }}?q=laptop">Laptops</a>
        <a href="{{ route('search.index') }}?q=books">Books</a>
        <a href="{{ route('search.index') }}?q=headphones">Headphones</a>
      </div>

      <div class="hero-stats">
        <div class="hs-stat"><b>{{ \App\Models\Product::where('status','published')->count() }}+</b><span>Products tracked</span></div>
        <div class="hs-stat"><b>{{ \App\Models\Merchant::where('status','active')->count() }}</b><span>Trusted stores</span></div>
        <div class="hs-stat"><b>72h</b><span>Freshness promise</span></div>
      </div>
    </div>

    <div class="hero-visual" aria-hidden="true">
      <div class="hv-blob hv-blob--a"></div>
      <div class="hv-blob hv-blob--b"></div>

      <div class="hv-stack">
        <!-- Showcase card -->
        @if($heroProducts->isNotEmpty())
          @php $hp = $heroProducts->first(); $hpImg = $hp->images->firstWhere('is_main') ?: $hp->images->first(); $hpOffer = $hp->activeOffers->whereNotNull('current_price')->sortBy('current_price')->first(); @endphp
          <div class="hv-card hv-card--main">
            <div class="hv-card-img">
              @if($hpImg)<img src="{{ str_starts_with($hpImg->path,'http') ? $hpImg->path : asset('storage/'.$hpImg->path) }}" alt="" loading="eager">@else<span class="hv-fallback">{{ substr($hp->name,0,1) }}</span>@endif
            </div>
            <div class="hv-card-body">
              <span class="hv-brand">{{ $hp->brand->name ?? $hp->category->name }}</span>
              <span class="hv-name">{{ \Illuminate\Support\Str::limit($hp->name, 34) }}</span>
              @if($hpOffer)
              <div class="hv-price">
                <span class="hv-now">{{ \App\Support\Currency::format((float)$hpOffer->current_price, $hpOffer->currency) }}</span>
                @if($hpOffer->original_price && $hpOffer->original_price > $hpOffer->current_price)
                  <span class="hv-old">{{ \App\Support\Currency::format((float)$hpOffer->original_price, $hpOffer->currency) }}</span>
                  <span class="hv-save">-{{ round($hpOffer->discountPercent(),0) }}%</span>
                @endif
              </div>
              <span class="hv-store">from {{ $hpOffer->merchant->name }} • <em>Best price</em></span>
              @endif
            </div>
          </div>
        @endif

        <div class="hv-mini-row">
          <div class="hv-mini">
            <span class="hv-mini-ico">⚖</span>
            <strong>Compare Stores</strong>
            <small>Side-by-side prices per product</small>
          </div>
          <div class="hv-mini">
            <span class="hv-mini-ico">📉</span>
            <strong>Price History</strong>
            <small>Real drops, not fake discounts</small>
          </div>
        </div>

        @if($drops->isNotEmpty())
          <div class="hv-drop">
            <span class="hv-drop-badge">↓ {{ round($drops->first()->drop_percent) }}%</span>
            <span class="hv-drop-name">{{ \Illuminate\Support\Str::limit($drops->first()->product->name, 28) }}</span>
            <span class="hv-drop-hint">recent drop</span>
          </div>
        @endif
      </div>
    </div>
  </div>
</section>

<div class="container">

  <!-- ── CATEGORIES — bento tiles ── -->
  @if(isset($categories) && $categories->isNotEmpty())
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Explore</span>
        <h2>Browse by category</h2>
      </div>
      <a class="hs-link" href="{{ route('products.index') }}">All categories →</a>
    </div>
    <div class="cat-bento">
      @foreach($categories->take(8) as $c)
        <a class="cat-bento-tile" href="{{ route('categories.show', $c->slug) }}">
          <span class="cbt-icon">{{ $c->icon ?? '▦' }}</span>
          <span class="cbt-name">{{ $c->name }}</span>
          <span class="cbt-go">→</span>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  <!-- ── TRENDING ── -->
  @if(!$trending->isEmpty() && (App\Models\Setting::get('homepage.show_trending', true)))
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Hot right now</span>
        <h2>Trending Products</h2>
        <p class="sec-sub">Most viewed and compared this week.</p>
      </div>
      <a class="hs-link" href="{{ route('search.index') }}?sort=popular">View all →</a>
    </div>
    <div class="prod-grid prod-grid--home">@foreach($trending as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  </section>
  @endif

</div>

<!-- ── TODAY'S BEST DEALS — dark band ── -->
@if(!$deals->isEmpty() && (App\Models\Setting::get('homepage.show_deals', true)))
<section class="home-band">
  <div class="container">
    <div class="hs-head hs-head--light">
      <div>
        <span class="sec-eyebrow sec-eyebrow--light">Limited time</span>
        <h2 style="color:#fff">Today's Best Deals</h2>
        <p class="sec-sub" style="color:#94a3b8">Genuine ≥5% verified discounts only — no inflated “was” prices.</p>
      </div>
      <a class="hs-link hs-link--light" href="{{ route('deals.index') }}">All deals →</a>
    </div>
    <div class="deal-grid">
      @foreach($deals as $p)
        @php
          $dImg = $p->images->firstWhere('is_main') ?: $p->images->first();
          $dOffer = $p->activeOffers->filter(fn($o) => $o->status === 'active' && $o->current_price !== null)
            ->filter(fn($o) => $o->original_price && $o->original_price > $o->current_price)
            ->sortByDesc(fn($o) => $o->discountPercent())->first();
          $bestPrice = $dOffer ? (float)$dOffer->current_price : null;
        @endphp
        <a class="deal-card deal-card--dark" href="{{ route('product.show', $p->slug) }}">
          <div class="dc-img">
            @if($dImg)<img src="{{ str_starts_with($dImg->path,'http') ? $dImg->path : asset('storage/'.$dImg->path) }}" alt="{{ $dImg->alt_text ?: $p->name }}" loading="lazy">@else<span class="dc-fallback">{{ substr($p->brand->name ?? $p->name, 0, 1) }}</span>@endif
          </div>
          <div class="dc-body">
            <span class="dc-brand">{{ $p->brand->name ?? $p->category->name }}</span>
            <span class="dc-name">{{ $p->name }}</span>
            @if($dOffer)
            <div class="dc-prices">
              <span class="dc-new">{{ \App\Support\Currency::format((float)$dOffer->current_price, $dOffer->currency) }}</span>
              <span class="dc-old">{{ \App\Support\Currency::format((float)$dOffer->original_price, $dOffer->currency) }}</span>
            </div>
            <div class="dc-save">
              <span class="amount">Save {{ \App\Support\Currency::format((float)$dOffer->original_price - (float)$dOffer->current_price, $dOffer->currency) }}</span>
              <span class="drop">↓{{ round($dOffer->discountPercent(), 1) }}%</span>
            </div>
            @endif
          </div>
          <span class="dc-cta">View →</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<div class="container">

  <!-- ── PRICE DROPS — horizontal scroller ── -->
  @if(!$drops->isEmpty() && (App\Models\Setting::get('homepage.show_price_drops', true)))
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Falling prices</span>
        <h2>Recent price drops</h2>
      </div>
      <a class="hs-link" href="{{ route('drops.index') }}">See all →</a>
    </div>
    <div class="drop-ticker">
      @foreach($drops as $d)
        <a class="drop-pill" href="{{ route('product.show', $d->product->slug) }}">
          <span class="dp-badge">↓{{ round($d->drop_percent) }}%</span>
          <span class="dp-name">{{ $d->product->name }}</span>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  <!-- ── TOP SELLING ── -->
  @if(!$topSelling->isEmpty())
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Best sellers</span>
        <h2>Top Selling Products</h2>
      </div>
    </div>
    <div class="prod-grid prod-grid--home">@foreach($topSelling as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  </section>
  @endif

  <!-- ── NEW ARRIVALS ── -->
  @if(!$newArrivals->isEmpty())
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Just added</span>
        <h2>New Arrivals</h2>
      </div>
    </div>
    <div class="prod-grid prod-grid--home">@foreach($newArrivals as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  </section>
  @endif

  <!-- ── FEATURED ── -->
  @if(!$featured->isEmpty())
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Handpicked</span>
        <h2>Featured</h2>
      </div>
    </div>
    <div class="prod-grid prod-grid--home">@foreach($featured as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  </section>
  @endif

  <!-- ── GUIDES ── -->
  @if(!$guides->isEmpty())
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Learn before you buy</span>
        <h2>Buying guides</h2>
      </div>
      <a class="hs-link" href="{{ route('guides.index') }}">All guides →</a>
    </div>
    <div class="guide-grid">
      @foreach($guides as $g)
        <a class="guide-card" href="{{ route('articles.show', $g->slug) }}">
          <span class="tag">Buying Guide</span>
          <h3>{{ $g->title }}</h3>
          <p>{{ \Illuminate\Support\Str::limit(strip_tags($g->excerpt), 110) }}</p>
          <span class="guide-cta">Read guide →</span>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  <!-- ── POPULAR STORES ── -->
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Trusted sources</span>
        <h2>Popular stores</h2>
      </div>
    </div>
    <div class="store-row">
      @foreach($merchants as $m)
        <a class="store-pill" href="{{ route('merchants.show', $m->slug) }}"><span class="sp-av">{{ strtoupper(substr($m->name, 0, 1)) }}</span>{{ $m->name }}</a>
      @endforeach
    </div>
  </section>

  <!-- ── WHY TULONA — bento trust ── -->
  <section class="home-section">
    <div class="hs-head">
      <div>
        <span class="sec-eyebrow">Why Tulona</span>
        <h2>Built for honest decisions</h2>
      </div>
    </div>
    <div class="trust-grid">
      <div class="panel trust-item"><span class="ico" aria-hidden="true">⚖</span><div><strong>Compare multiple stores</strong><small>See who really has the lowest price, side by side — per product.</small></div></div>
      <div class="panel trust-item"><span class="ico" aria-hidden="true">📉</span><div><strong>Track better prices</strong><small>Verified price history — not fake discounts. Stale offers are flagged.</small></div></div>
      <div class="panel trust-item"><span class="ico" aria-hidden="true">◎</span><div><strong>Research before buying</strong><small>Guides and thoughtful filters that tell both sides.</small></div></div>
      <div class="panel trust-item"><span class="ico" aria-hidden="true">♡</span><div><strong>We don't sell anything</strong><small>You always buy from the store you choose. We just bring clarity.</small></div></div>
    </div>
  </section>

</div>
@endsection
