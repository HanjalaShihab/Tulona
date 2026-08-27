@extends('layouts.app')

@section('content')
<section class="hero">
  <div class="container">
    <h1>{{ App\Models\Setting::get('homepage.hero_title', 'Find the right product at the right price.') }}</h1>
    <p>{{ App\Models\Setting::get('homepage.hero_subtitle', 'Compare products, prices, deals and trusted stores before you buy — all in one place, with honest data.') }}</p>
    <form class="search-form" role="search" action="{{ route('search.index') }}" method="get">
      <input type="text" class="search-input" name="q" placeholder="Search phones, laptops, GPUs, skincare, AI tools…" aria-label="Search products">
      <button class="search-btn" type="submit">Search</button>
    </form>
    <p class="disclose">We may earn a commission when you buy through our links — it never changes the price you pay.</p>
  </div>
</section>

<div class="container">
  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">Browse</span><h2>Popular Categories</h2></div></div>
    <div class="cat-grid">
      @forelse($categories as $c)
        <a class="cat-tile" href="{{ route('categories.show', $c->slug) }}">
          <div class="ico">{{ $c->icon ?? '🛍️' }}</div><div class="nm">{{ $c->name }}</div>
        </a>
      @empty
        @include('partials.empty', ['icon' => '🗂️', 'text' => 'Categories will appear here once added.'])
      @endforelse
    </div>
  </section>

  @if(!$deals->isEmpty() && (App\Models\Setting::get('homepage.show_deals', true)))
  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">🔥 Limited time</span><h2>Today's Best Deals</h2></div><a href="{{ route('deals.index') }}">All deals →</a></div>
    <div class="deal-grid">
      @foreach($deals as $p)
        @php
          $dImg = $p->images->firstWhere('is_main') ?: $p->images->first();
          $dOffer = $p->activeOffers->filter(fn($o) => $o->status === 'active' && $o->current_price !== null)
            ->filter(fn($o) => $o->original_price && $o->original_price > $o->current_price)
            ->sortByDesc(fn($o) => $o->discountPercent())->first();
        @endphp
        <a class="deal-card" href="{{ route('product.show', $p->slug) }}">
          <div class="dc-img">
            @if($dImg)<img src="{{ str_starts_with($dImg->path,'http') ? $dImg->path : asset('storage/'.$dImg->path) }}" alt="{{ $dImg->alt_text ?: $p->name }}" loading="lazy" width="96" height="84">@else{{ substr($p->brand->name ?? 'T', 0, 1) }}@endif
          </div>
          <div>
            <span class="dc-brand">{{ $p->brand->name ?? '' }}</span>
            <span class="dc-name">{{ $p->name }}</span>
            <div class="dc-prices">
              @if($dOffer)
                <span class="dc-new">{{ \App\Support\Currency::format((float)$dOffer->current_price, $dOffer->currency) }}</span>
                <span class="dc-old">{{ \App\Support\Currency::format((float)$dOffer->original_price, $dOffer->currency) }}</span>
              @endif
            </div>
            @if($dOffer)
            <div class="dc-save">
              <span class="amount">Save {{ \App\Support\Currency::format((float)$dOffer->original_price - (float)$dOffer->current_price, $dOffer->currency) }}</span>
              <span class="drop">↓{{ round($dOffer->discountPercent(), 1) }}%</span>
            </div>
            @endif
          </div>
          <span class="dc-cta">View Deal →</span>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  @if(!$drops->isEmpty() && (App\Models\Setting::get('homepage.show_price_drops', true)))
  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">↓ Falling prices</span><h2>Recent Price Drops</h2></div><a href="{{ route('drops.index') }}">See more →</a></div>
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

  @if(!$trending->isEmpty() && (App\Models\Setting::get('homepage.show_trending', true)))
  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">📈 Hot right now</span><h2>Trending Products</h2></div></div>
    <div class="prod-grid">@foreach($trending as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  </section>
  @endif

  @if(!$featured->isEmpty())
  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">★ Handpicked</span><h2>Featured</h2></div></div>
    <div class="prod-grid">@foreach($featured as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  </section>
  @endif

  @if(!empty($comparisons) && !$comparisons->isEmpty())
  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">⚖ Head to head</span><h2>Shop Smarter: Comparisons</h2></div><a href="{{ route('compare.index') }}">Compare products →</a></div>
    <div class="cat-grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr));grid-auto-rows:1fr">
      @foreach($comparisons as $c)@include('partials.comparison-card', ['comparison' => $c])@endforeach
    </div>
  </section>
  @endif

  @if(!$guides->isEmpty())
  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">📚 Learn before you buy</span><h2>Buying Guides</h2></div><a href="{{ route('guides.index') }}">All guides →</a></div>
    <div class="cat-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));grid-auto-rows:1fr">
      @foreach($guides as $g)
        <a class="guide-card" href="{{ route('articles.show', $g->slug) }}">
          <span class="tag">Buying Guide</span>
          <h3>{{ $g->title }}</h3>
          <p>{{ \Illuminate\Support\Str::limit(strip_tags($g->excerpt), 110) }}</p>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  <section class="section">
    <div class="sec-head"><div><span class="sec-eyebrow">🏬 Trusted sources</span><h2>Popular Stores</h2></div></div>
    <div class="store-row">
      @foreach($merchants as $m)
        <a class="store-pill" href="{{ route('merchants.show', $m->slug) }}"><span class="sp-av">{{ strtoupper(substr($m->name, 0, 1)) }}</span>{{ $m->name }}</a>
      @endforeach
    </div>
  </section>

  <section class="section">
    <div class="sec-head"><h2>Why use Tulona?</h2></div>
    <div class="trust-grid">
      <div class="panel trust-item"><span class="ico" aria-hidden="true">⚖️</span><div><strong>Compare multiple stores</strong><small>See who really has the lowest price, side by side.</small></div></div>
      <div class="panel trust-item"><span class="ico" aria-hidden="true">📉</span><div><strong>Track better prices</strong><small>Verified price history — not fake discounts.</small></div></div>
      <div class="panel trust-item"><span class="ico" aria-hidden="true">🔎</span><div><strong>Research before buying</strong><small>Guides and reviews that tell both sides.</small></div></div>
      <div class="panel trust-item"><span class="ico" aria-hidden="true">🤝</span><div><strong>We don't sell anything</strong><small>You always buy from the store you choose.</small></div></div>
    </div>
  </section>
</div>
@endsection
