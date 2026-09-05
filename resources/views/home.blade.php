@extends('layouts.app')

@section('content')
@php
  $heroProducts = $featured->take(3);
  if ($heroProducts->isEmpty()) $heroProducts = $trending->take(3);
  if ($heroProducts->isEmpty()) $heroProducts = $newArrivals->take(3);
@endphp

<section class="home-hero">
  <div class="hero-aurora" aria-hidden="true">
    <span class="ha-orb ha-orb--a"></span>
    <span class="ha-orb ha-orb--b"></span>
    <span class="ha-orb ha-orb--c"></span>
    <span class="ha-grid"></span>
  </div>
  <div class="container hero-grid">
    <div class="hero-copy">
      <div class="hero-eyebrow reveal" data-reveal>
        <span class="eyebrow-dot"></span>
        Trusted by researchers &amp; bargain hunters in Bangladesh
        <span class="eyebrow-live"><span class="live-ping"></span>live prices</span>
      </div>

      <h1 class="hero-title" data-reveal>
        Find the right
        <span class="hero-accent">product</span>
        at the right price
      </h1>

      <p class="hero-sub" data-reveal data-delay="80">
        {{ App\Models\Setting::get('homepage.hero_subtitle', 'We compare products, prices and trusted stores before you buy — honest data, verified history, no sponsored ranking.') }}
      </p>

      <form class="hero-search" role="search" action="{{ route('search.index') }}" method="get" data-reveal data-delay="160">
        <div class="hs-input-wrap">
          <span class="hs-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></span>
          <input type="text" name="q" placeholder="Search phones, laptops, books, headphones&#8230;" aria-label="Search products" autocomplete="off">
        </div>
        <button class="btn btn-primary hs-btn" type="submit">Search<span class="btn-arrow">&#8594;</span></button>
      </form>

      <div class="hero-chips" data-reveal data-delay="240">
        <span>Try:</span>
        <a href="{{ route('search.index') }}?q=phone">Phones</a>
        <a href="{{ route('search.index') }}?q=laptop">Laptops</a>
        <a href="{{ route('search.index') }}?q=books">Books</a>
        <a href="{{ route('search.index') }}?q=headphones">Headphones</a>
      </div>

      <div class="hero-stats" data-reveal data-delay="320">
        <div class="hs-stat">
          <b data-count="{{ \App\Models\Product::where('status','published')->count() }}" data-suffix="+">0</b>
          <span>Products tracked</span>
        </div>
        <div class="hs-stat">
          <b data-count="{{ \App\Models\Merchant::where('status','active')->count() }}">0</b>
          <span>Trusted stores</span>
        </div>
        <div class="hs-stat">
          <b>72h</b>
          <span>Freshness promise</span>
        </div>
      </div>
    </div>

    <div class="hero-visual" data-reveal data-delay="200" aria-hidden="false">
      <div class="hv-blob hv-blob--a"></div>
      <div class="hv-blob hv-blob--b"></div>
      <div class="hv-orb" aria-hidden="true"></div>

      <div class="hv-stack">
        @if($heroProducts->isNotEmpty())
          @php
            $hp = $heroProducts->first();
            $hpImg = $hp->images->firstWhere('is_main') ?: $hp->images->first();
            $hpOffer = $hp->activeOffers->whereNotNull('current_price')->sortBy('current_price')->first();
          @endphp
          <div class="hv-card hv-card--main" data-tilt>
            <div class="hv-card-img">
              @if($hpImg)
                <img src="{{ str_starts_with($hpImg->path, 'http') ? $hpImg->path : asset('storage/' . $hpImg->path) }}" alt="" loading="eager">
              @else
                <span class="hv-fallback">{{ substr($hp->name, 0, 1) }}</span>
              @endif
            </div>
            <div class="hv-card-body">
              <span class="hv-brand">{{ $hp->brand->name ?? $hp->category->name }}</span>
              <span class="hv-name">{{ \Illuminate\Support\Str::limit($hp->name, 34) }}</span>
              @if($hpOffer)
                <div class="hv-price">
                  <span class="hv-now">{{ \App\Support\Currency::format((float) $hpOffer->current_price, $hpOffer->currency) }}</span>
                  @if($hpOffer->original_price && $hpOffer->original_price > $hpOffer->current_price)
                    <span class="hv-old">{{ \App\Support\Currency::format((float) $hpOffer->original_price, $hpOffer->currency) }}</span>
                    <span class="hv-save">-{{ round($hpOffer->discountPercent(), 0) }}%</span>
                  @endif
                </div>
                <span class="hv-store">from {{ $hpOffer->merchant->name }} &#8226; <em>Best price</em></span>
              @endif
            </div>
            <span class="hv-card-glow" aria-hidden="true"></span>
          </div>
        @endif

        <div class="hv-mini-row">
          <div class="hv-mini" data-reveal data-delay="300">
            <span class="hv-mini-ico">&#9878;</span>
            <strong>Store Comparison</strong>
            <small>Side-by-side prices per product</small>
          </div>
          <div class="hv-mini" data-reveal data-delay="380">
            <span class="hv-mini-ico">&#8595;</span>
            <strong>Price History</strong>
            <small>Real drops, not fake discounts</small>
          </div>
        </div>

        @if($drops->isNotEmpty())
          <div class="hv-drop" data-reveal data-delay="460">
            <span class="hv-drop-badge">&#8595; {{ round($drops->first()->live_drop_percent) }}%</span>
            <span class="hv-drop-name">{{ \Illuminate\Support\Str::limit($drops->first()->product->name, 28) }}</span>
            <span class="hv-drop-hint">{{ $drops->first()->price_label ?? 'recent drop' }}</span>
          </div>
        @endif
      </div>
    </div>
  </div>
</section>

@if(!$newArrivals->isEmpty())
<section class="new-marquee" data-new-marquee>
  <div class="nm-viewport">
    <div class="nm-track">
      <div class="nm-group">
        @foreach($newArrivals as $p)
          @php
            $nmImg = $p->images->firstWhere('is_main') ?: $p->images->first();
            $nmOffer = $p->activeOffers->whereNotNull('current_price')->sortBy('current_price')->first();
            $nmOrig = $nmOffer && $nmOffer->original_price && $nmOffer->original_price > $nmOffer->current_price ? $nmOffer->original_price : null;
          @endphp
          <a class="nm-tile" href="{{ route('product.show', $p->slug) }}">
            <span class="nm-img">
              @if($nmImg)
                <img src="{{ str_starts_with($nmImg->path, 'http') ? $nmImg->path : asset('storage/' . $nmImg->path) }}" alt="" loading="lazy">
              @else
                <span class="nm-fallback">{{ strtoupper(substr($p->name, 0, 1)) }}</span>
              @endif
            </span>
            <span class="nm-body">
              <span class="nm-brand">{{ $p->brand->name ?? 'Tulona' }}</span>
              <span class="nm-name">{{ $p->name }}</span>
            </span>
            <span class="nm-price">
              <span class="nm-now">@if($nmOffer){{ \App\Support\Currency::format((float) $nmOffer->current_price, $nmOffer->currency ?? 'BDT') }}@else&#8212;@endif</span>
              @if($nmOrig)
                <span class="nm-old">{{ \App\Support\Currency::format((float) $nmOrig, $nmOffer->currency ?? 'BDT') }}</span>
                <span class="nm-save">-{{ round($nmOffer->discountPercent(), 1) }}%</span>
              @endif
            </span>
          </a>
        @endforeach
      </div>
    </div>
  </div>
  <div class="nm-controls-row" data-reveal data-delay="100">
    <div class="nm-controls">
      <button type="button" class="nm-btn" data-nm-prev aria-label="Previous arrivals"><span aria-hidden="true">&#8592;</span></button>
      <button type="button" class="nm-btn" data-nm-next aria-label="Next arrivals"><span aria-hidden="true">&#8594;</span></button>
    </div>
  </div>
</section>
@endif

@if(!$trending->isEmpty() && App\Models\Setting::get('homepage.show_trending', true))
<section class="trend-home">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">Hot right now</span>
        <h2>Trending Products</h2>
        <p class="sec-sub">Products gaining attention this week — ranked by real clicks, views and engagement.</p>
      </div>
      <a class="hs-link" href="{{ route('trending.index') }}">View all &#8594;</a>
    </div>

    <div class="trend-grid">
      @foreach($trending as $p)
        @php
          $cImg = $p->images->firstWhere('is_main') ?: $p->images->first();
          $cOffer = $p->activeOffers->whereNotNull('current_price')->sortBy('current_price')->first();
          $cOrig = $p->activeOffers
            ->filter(fn($o) => $o->original_price && $o->original_price > $o->current_price)
            ->sortByDesc(fn($o) => $o->discountPercent())->first();
          $cStores = $p->active_offers_count ?? $p->activeOffers->count();
        @endphp
        <article class="tcard" data-reveal data-delay="{{ ($loop->index % 4) * 60 }}">
          <a class="tcard-img" href="{{ route('product.show', $p->slug) }}" aria-hidden="true" tabindex="-1">
            @if($cImg)
              <img src="{{ str_starts_with($cImg->path, 'http') ? $cImg->path : asset('storage/' . $cImg->path) }}" alt="{{ $cImg->alt_text ?: $p->name }}" loading="lazy">
            @else
              <span class="tcard-fallback">{{ strtoupper(substr($p->name, 0, 1)) }}</span>
            @endif
          </a>
          <span class="tcard-rank">No. {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>

          <div class="tcard-body">
            <span class="tcard-brand">{{ $p->brand->name ?? 'Tulona' }}</span>
            <a class="tcard-name" href="{{ route('product.show', $p->slug) }}">{{ $p->name }}</a>
            <span class="tcard-stores">From {{ $cStores }} {{ $cStores == 1 ? 'store' : 'stores' }}</span>

            <div class="tcard-price">
              @if($cOffer)
                <span class="tcard-now">{{ \App\Support\Currency::format((float) $cOffer->current_price, $cOffer->currency ?? 'BDT') }}</span>
                @if($cOrig)
                  <span class="tcard-old">{{ \App\Support\Currency::format((float) $cOrig->original_price, $cOrig->currency ?? 'BDT') }}</span>
                  <span class="tcard-save">-{{ round($cOrig->discountPercent(), 1) }}%</span>
                @endif
              @else
                <span class="tcard-na">Price unavailable</span>
              @endif
            </div>

            <div class="tcard-actions">
              @if($cOffer?->merchant)
                <a class="tcard-cta tcard-cta--deal" href="{{ route('go.redirect', [$p->slug, $cOffer->merchant->slug]) }}" target="_blank" rel="nofollow sponsored noopener">View deal</a>
              @endif
            </div>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!$deals->isEmpty() && App\Models\Setting::get('homepage.show_deals', true))
<section class="deals-premium">
  <div class="deals-backdrop" aria-hidden="true">
    <span class="deals-tex"></span>
    <span class="deals-orb deals-orb--a"></span>
    <span class="deals-orb deals-orb--b"></span>
  </div>
  <div class="container">
    <div class="deals-ticker" data-reveal>
      <span class="dtc-pulse" aria-hidden="true"></span>
      <span class="dtc-text">Verified live cuts &mdash; updated today</span>
      <span class="dtc-note">Genuine &#8805;15% discount only</span>
    </div>

    <div class="deals-head" data-reveal>
      <div class="deals-head-copy">
        <span class="deals-eyebrow"><span class="de-eyebrow-dot" aria-hidden="true"></span>Limited-time price cuts</span>
        <h2 class="deals-title">Today&rsquo;s <em>Best Deals</em></h2>
        <p class="deals-sub">Genuine &#8805;15% savings — no inflated &ldquo;was&rdquo; prices, ever. Sorted by biggest discount first.</p>
      </div>
      <div class="deals-head-meta">
        <span class="deals-count">{{ $deals->count() }}<small>live today</small></span>
        <a class="deals-link" href="{{ route('deals.index') }}">All deals <span aria-hidden="true">&#8594;</span></a>
      </div>
    </div>

    <div class="deals-rule" aria-hidden="true"><span class="dr-diamond"></span></div>

    <div class="deals-grid" data-reveal>
      @foreach($deals as $p)
        @php
          $dImg = $p->images->firstWhere('is_main') ?: $p->images->first();
          $dOffer = $p->activeOffers
            ->filter(fn($o) => $o->status === 'active' && $o->current_price !== null)
            ->filter(fn($o) => $o->original_price && $o->original_price > $o->current_price)
            ->filter(fn($o) => ($o->discountPercent() ?? 0) >= 15)
            ->sortByDesc(fn($o) => $o->discountPercent())
            ->first();
          $dStore = $p->activeOffers
            ->filter(fn($o) => $o->status === 'active' && $o->merchant)
            ->first()?->merchant;
          $dGoSlug = $dOffer?->merchant?->slug ?? $dStore?->slug;
        @endphp
        <article class="deal-ticket{{ $loop->first ? ' deal-ticket--pick' : '' }}">
          <a class="dt-media" href="{{ route('product.show', $p->slug) }}" aria-hidden="true" tabindex="-1">
            @if($dImg)
              <img class="dt-image" src="{{ str_starts_with($dImg->path, 'http') ? $dImg->path : asset('storage/' . $dImg->path) }}" alt="{{ $dImg->alt_text ?: $p->name }}" loading="lazy">
            @else
              <span class="dt-fallback">{{ substr($p->brand->name ?? $p->name, 0, 1) }}</span>
            @endif
            @if($dOffer)
              <span class="dt-discount">&#8722;{{ round($dOffer->discountPercent()) }}<small>%</small></span>
            @endif
            <span class="dt-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            @if($loop->first)
              <span class="dt-tag dt-tag--pick">Top pick today</span>
            @endif
          </a>
          <div class="dt-body">
            <div class="dt-row1">
              <span class="dt-brand">{{ $p->brand->name ?? $p->category->name }}</span>
            </div>
            <a class="dt-name" href="{{ route('product.show', $p->slug) }}">{{ $p->name }}</a>
            @if($dOffer)
              <div class="dt-price-row">
                <span class="dt-price">{{ \App\Support\Currency::format((float) $dOffer->current_price, $dOffer->currency) }}</span>
                <span class="dt-old">{{ \App\Support\Currency::format((float) $dOffer->original_price, $dOffer->currency) }}</span>
              </div>
              <div class="dt-save">
                <span class="amount">You save {{ \App\Support\Currency::format((float) $dOffer->original_price - (float) $dOffer->current_price, $dOffer->currency) }}</span>
                <span class="min">{{ $p->active_offers_count ?? $p->activeOffers->count() }} store(s)</span>
              </div>
            @endif
          </div>
          @if($dGoSlug)
            <a class="dt-cta" href="{{ route('go.redirect', [$p->slug, $dGoSlug]) }}" target="_blank" rel="nofollow sponsored noopener"><span>View deal</span><span class="dt-cta-arrow" aria-hidden="true">&#8594;</span></a>
          @else
            <a class="dt-cta" href="{{ route('product.show', $p->slug) }}"><span>View deal</span><span class="dt-cta-arrow" aria-hidden="true">&#8594;</span></a>
          @endif
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!$drops->isEmpty() && App\Models\Setting::get('homepage.show_price_drops', true))
<section class="home-section">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">Falling prices</span>
        <h2>Recent price drops</h2>
      </div>
      <a class="hs-link" href="{{ route('drops.index') }}">See all &#8594;</a>
    </div>
    <div class="drop-ticker marquee" data-marquee>
      <div class="marquee-track">
        <div class="marquee-group">
          @foreach($drops as $d)
            <a class="drop-pill" href="{{ route('product.show', $d->product->slug) }}">
              <span class="dp-badge">&#8595;{{ round($d->live_drop_percent) }}%</span>
              <span class="dp-name">{{ $d->product->name }}</span>
              @if(isset($d->price_label))
                <span class="dp-label">{{ $d->price_label }}</span>
              @endif
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>
@endif

@if(!$topSelling->isEmpty())
<section class="topsel-section">
  <div class="container">
    <div class="topsel-head" data-reveal>
      <div>
        <span class="topsel-eyebrow"><span class="te-dot" aria-hidden="true"></span>Bangladesh is buying</span>
        <h2 class="topsel-title">Most Clicked <em>Products</em></h2>
        <p class="topsel-sub">Products receiving the most outbound clicks this month — ranked by real demand, never sponsored.</p>
      </div>
      <a class="topsel-link" href="{{ route('products.index') }}">All products <span aria-hidden="true">&#8594;</span></a>
    </div>

    @php $top = $topSelling->first(); @endphp
    @if($top)
      @php
        $tImg = $top->images->firstWhere('is_main') ?: $top->images->first();
        $tBest = $top->activeOffers
          ->filter(fn($o) => $o->status === 'active' && $o->current_price !== null)
          ->min('current_price');
        $tOrig = $top->activeOffers
          ->filter(fn($o) => $o->original_price && $o->original_price > $o->current_price)
          ->sortByDesc(fn($o) => $o->discountPercent())
          ->first();
      @endphp
      <div class="topsel-layout" data-reveal>
        <a class="topsel-feature" href="{{ route('product.show', $top->slug) }}">
          <div class="topsel-fmedia">
            @if($tImg)
              <img class="topsel-fimage" src="{{ str_starts_with($tImg->path, 'http') ? $tImg->path : asset('storage/' . $tImg->path) }}" alt="{{ $tImg->alt_text ?: $top->name }}" loading="lazy">
            @else
              <span class="topsel-ffallback">{{ substr($top->brand->name ?? $top->name, 0, 1) }}</span>
            @endif
            <span class="topsel-frank">01</span>
            <span class="topsel-fbadge">No. 1 most clicked</span>
          </div>
          <div class="topsel-fbody">
            <span class="topsel-fbrand">{{ $top->brand->name ?? $top->category->name }}</span>
            <h3 class="topsel-fname">{{ $top->name }}</h3>
            <div class="topsel-fprice">
              @if($tBest !== null)
                <span class="topsel-fnow">{{ \App\Support\Currency::format((float) $tBest) }}</span>
                @if($tOrig)
                  <span class="topsel-fold">{{ \App\Support\Currency::format((float) $tOrig->original_price, $tOrig->currency) }}</span>
                  <span class="badge badge-deal">&#8722;{{ round($tOrig->discountPercent(), 1) }}%</span>
                @endif
              @else
                <span class="badge badge-out">Price unavailable</span>
              @endif
            </div>
            <span class="topsel-fmeta">{{ number_format($top->period_clicks ?? 0) }} clicks this month &#8226; {{ $top->activeOffers->count() }} store(s)</span>
          </div>
          <span class="topsel-fcta"><span>Shop it now</span><span aria-hidden="true">&#8594;</span></span>
        </a>

        <div class="topsel-list" role="list">
          @foreach($topSelling->skip(1) as $p)
            @php
              $rImg = $p->images->firstWhere('is_main') ?: $p->images->first();
              $rBest = $p->activeOffers
                ->filter(fn($o) => $o->status === 'active' && $o->current_price !== null)
                ->min('current_price');
            @endphp
            <a class="topsel-row" href="{{ route('product.show', $p->slug) }}" role="listitem">
              <span class="topsel-rank">{{ str_pad($loop->iteration + 1, 2, '0', STR_PAD_LEFT) }}</span>
              <span class="topsel-thumb">
                @if($rImg)
                  <img src="{{ str_starts_with($rImg->path, 'http') ? $rImg->path : asset('storage/' . $rImg->path) }}" alt="{{ $rImg->alt_text ?: $p->name }}" loading="lazy">
                @else
                  <span class="topsel-tfallback">{{ substr($p->brand->name ?? $p->name, 0, 1) }}</span>
                @endif
              </span>
              <span class="topsel-info">
                <span class="topsel-brand">{{ $p->brand->name ?? $p->category->name }} &#8226; {{ $p->activeOffers->count() }} store(s)</span>
                <span class="topsel-name">{{ $p->name }}</span>
              </span>
              <span class="topsel-price">
                @if($rBest !== null)
                  <span class="topsel-now">{{ \App\Support\Currency::format((float) $rBest) }}</span>
                  @if($drop = $p->latestDrop ?? null)
                    <span class="badge badge-drop">&#8595; {{ round($drop->drop_percent) }}%</span>
                  @endif
                @else
                  <span class="badge badge-out">&#8212;</span>
                @endif
              </span>
            </a>
          @endforeach
          <a class="topsel-more" href="{{ route('products.index') }}">Browse all products <span aria-hidden="true">&#8594;</span></a>
        </div>
      </div>
    @endif
  </div>
</section>
@endif

@if(!$featured->isEmpty())
<section class="home-section">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">Handpicked</span>
        <h2>Featured</h2>
      </div>
    </div>
    <div class="prod-grid prod-grid--home">
      @foreach($featured as $p)
        @include('partials.product-card', ['product' => $p])
      @endforeach
    </div>
  </div>
</section>
@endif

@if(isset($campaigns) && $campaigns->isNotEmpty())
@foreach($campaigns as $campaign)
<section class="home-section">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">{{ $campaign->theme === 'flash' ? '⚡ Flash Deal' : 'Campaign' }}</span>
        <h2>{{ $campaign->name }}</h2>
        @if($campaign->description)<p class="sec-sub">{{ $campaign->description }}</p>@endif
      </div>
    </div>
    <div class="prod-grid prod-grid--home">
      @foreach($campaign->products->take(4) as $p)
        @include('partials.product-card', ['product' => $p])
      @endforeach
    </div>
  </div>
</section>
@endforeach
@endif

@if(!$guides->isEmpty())
<section class="home-section">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">Learn before you buy</span>
        <h2>Buying guides</h2>
      </div>
      <a class="hs-link" href="{{ route('guides.index') }}">All guides &#8594;</a>
    </div>
    <div class="guide-grid">
      @foreach($guides as $g)
        <a class="guide-card" href="{{ route('articles.show', $g->slug) }}" data-reveal>
          <span class="tag">Buying Guide</span>
          <h3>{{ $g->title }}</h3>
          <p>{{ \Illuminate\Support\Str::limit(strip_tags($g->excerpt), 110) }}</p>
          <span class="guide-cta">Read guide &#8594;</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="home-section">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">Trusted sources</span>
        <h2>Popular stores</h2>
      </div>
    </div>
    <div class="store-row">
      @foreach($merchants as $m)
        <a class="store-pill" href="{{ route('merchants.show', $m->slug) }}">
          <span class="sp-av">{{ strtoupper(substr($m->name, 0, 1)) }}</span>{{ $m->name }}
        </a>
      @endforeach
    </div>
  </div>
</section>

@if(isset($categories) && $categories->isNotEmpty())
<section class="home-section">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">Explore</span>
        <h2>Browse by category</h2>
      </div>
      <a class="hs-link" href="{{ route('categories.index') }}">All categories &#8594;</a>
    </div>
    <div class="cat-index" role="list">
      @foreach($categories->take(8) as $c)
        <a class="cat-index-row" href="{{ route('categories.show', $c->slug) }}" data-reveal data-delay="{{ $loop->index * 40 }}" role="listitem">
          <span class="ci-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
          <span class="ci-name">{{ $c->name }}</span>
          <span class="ci-count">{{ number_format($c->product_count ?? 0) }} products</span>
          <span class="ci-arrow" aria-hidden="true">&#8594;</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="home-section">
  <div class="container">
    <div class="hs-head" data-reveal>
      <div>
        <span class="sec-eyebrow">Why Tulona</span>
        <h2>Built for honest decisions</h2>
      </div>
    </div>
    <div class="trust-grid">
      <div class="trust-item">
        <span class="ico" aria-hidden="true">&#9878;</span>
        <div>
          <strong>Compare multiple stores</strong>
          <small>See who really has the lowest price, side by side &#8212; per product.</small>
        </div>
      </div>
      <div class="trust-item">
        <span class="ico" aria-hidden="true">&#8595;</span>
        <div>
          <strong>Track better prices</strong>
          <small>Verified price history &#8212; not fake discounts. Stale offers are flagged.</small>
        </div>
      </div>
      <div class="trust-item">
        <span class="ico" aria-hidden="true">&#9678;</span>
        <div>
          <strong>Research before buying</strong>
          <small>Guides and thoughtful filters that tell both sides.</small>
        </div>
      </div>
      <div class="trust-item">
        <span class="ico" aria-hidden="true">&#9825;</span>
        <div>
          <strong>We don&#8217;t sell anything</strong>
          <small>You always buy from the store you choose. We just bring clarity.</small>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
