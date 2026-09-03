@extends('layouts.app')

@section('schema')
@if(!empty($schema))
<script type="application/ld+json">@json($schema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endif
@endsection

@section('content')
<div class="container">
  @include('partials.breadcrumbs', ['items' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => $product->category->parent?->name ?? $product->category->name, 'url' => $product->category->parent ? route('categories.show', $product->category->parent->slug) : route('categories.show', $product->category->slug)],
    ['name' => $product->category->parent ? $product->category->name : null, 'url' => $product->category->parent ? route('categories.show', $product->category->slug) : null],
    ['name' => $product->name],
  ]])

  <div class="pdp">
    <div class="pdp-gallery" data-reveal>
      <div class="pdp-media">
        @if($img = $product->images->firstWhere('is_main') ?: $product->images->first())
          <img src="{{ str_starts_with($img->path, 'http') ? $img->path : asset('storage/' . $img->path) }}" alt="{{ $img->alt_text ?: $product->name }}" fetchpriority="high">
        @else
          <span class="main-fallback">{{ strtoupper(substr($product->brand->name ?? 'T', 0, 1)) }}</span>
        @endif
      </div>
      @php
        $bestOrig = $bestOffer && $bestOffer->original_price && (float) $bestOffer->original_price > (float) $bestOffer->current_price
          ? (float) $bestOffer->original_price : null;
      @endphp
      @if($bestOrig)
        <div class="pdp-savebanner">
          <span>You save the difference</span>
          <strong>{{ \App\Support\Currency::format($bestOrig - (float) $bestOffer->current_price, $bestOffer->currency ?? 'BDT') }}</strong>
          <span>at the best store</span>
        </div>
      @endif
    </div>

    <div data-reveal data-delay="100">
      <span class="pdp-brand">{{ $product->brand?->name }}</span>
      <h1 class="pdp-title">{{ $product->name }}</h1>
      <div class="pdp-meta">
        <a href="{{ route('categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
        @if($product->brand)
          <a href="{{ route('brands.show', $product->brand->slug) }}">{{ $product->brand->name }}</a>
        @endif
        @if($product->rating)
          <span>&#9733; {{ $product->rating }}/5 <small>(editorial)</small></span>
        @endif
        @if($latestDrop)
          <span class="badge badge-drop">Price dropped {{ round($latestDrop->drop_percent) }}%</span>
        @endif
      </div>

      @if($minPrice !== null)
        <div class="best-price-box">
          <div class="bpb-price">
            <small class="bpb-label">Best price</small>
            <div class="bpb-row">
              <span class="price-xl">{{ \App\Support\Currency::format($minPrice, $bestOffer->currency) }}</span>
              @if($bestOrig)
                <s class="price-old pdp-old">{{ \App\Support\Currency::format($bestOrig, $bestOffer->currency) }}</s>
                <span class="badge badge-deal">-{{ round($bestOffer->discountPercent(), 1) }}%</span>
              @endif
            </div>
            <small class="bpb-sub">incl. all store offers &middot; verified within {{ $freshnessHours }}h</small>
          </div>
          <div class="bpb-info">
            <span>Available from <strong>{{ $offers->whereNotNull('current_price')->count() }}</strong>
              {{ $offers->whereNotNull('current_price')->count() == 1 ? 'store' : 'stores' }}</span>
            @if($maxPrice > $minPrice)
              <span>Save up to <strong>{{ \App\Support\Currency::format($maxPrice - $minPrice, $bestOffer->currency) }}</strong> at another store</span>
            @endif
          </div>
          <div class="bpb-actions">
            <a class="btn btn-buy btn-lg" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$product->slug, $bestOffer->merchant->slug]) }}">Buy now &#8594;</a>
          </div>
        </div>
      @else
        <div class="alert alert-err">Price unavailable right now &#8212; offers below may update shortly.</div>
      @endif

      @if($product->summary_editorial || $product->short_description)
        <p class="pdp-summary">{{ $product->summary_editorial ?: $product->short_description }}</p>
      @endif

      @if($product->pros || $product->cons)
        <div class="pdp-fastfacts">
          @if($product->pros)
            <ul class="ff-list ff-pros">
              @foreach(array_slice($product->pros, 0, 4) as $pro)<li><span class="ff-ico">&#10003;</span>{{ $pro }}</li>@endforeach
            </ul>
          @endif
          @if($product->cons)
            <ul class="ff-list ff-cons">
              @foreach(array_slice($product->cons, 0, 4) as $con)<li><span class="ff-ico">&#10007;</span>{{ $con }}</li>@endforeach
            </ul>
          @endif
        </div>
      @endif
    </div>
  </div>

  <section class="section" id="offers" style="margin-top:12px">
    <div class="sec-head" data-reveal>
      <h2>Store Comparison</h2>
      <span class="sr-only">Compare Stores</span>
    </div>
    @if($offers->isEmpty())
      @include('partials.empty', ['icon' => '&#9633;', 'text' => 'No active offers for this product yet.'])
    @else
      <div class="tbl-scroll" data-reveal>
        <table class="compare-stores">
          <thead>
            <tr>
              <th scope="col">Store</th>
              <th scope="col">Price</th>
              <th scope="col">Availability</th>
              <th scope="col">Discount</th>
              <th scope="col">Updated</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($offers as $o)
              <tr @if($loop->first && $o->current_price !== null) class="row-best" @endif>
                <td>
                  <strong>{{ $o->merchant->name }}</strong>
                  @if($loop->first && $o->current_price !== null)
                    <span class="badge badge-deal">Best price</span>
                  @endif
                </td>
                <td>
                  @if($o->current_price !== null)
                    <strong>{{ \App\Support\Currency::format((float) $o->current_price, $o->currency) }}</strong>
                    @if($o->original_price && $o->original_price > $o->current_price)
                      <s class="price-old">{{ \App\Support\Currency::format((float) $o->original_price, $o->currency) }}</s>
                    @endif
                  @else
                    <em>Price unavailable</em>
                  @endif
                </td>
                <td>
                  @if($o->availability === 'in_stock')
                    <span style="color:var(--color-ok)">&#10003; In stock</span>
                  @elseif($o->availability === 'out_of_stock')
                    <span class="badge badge-out">Out of stock</span>
                  @elseif($o->availability === 'preorder')
                    Pre-order
                  @else
                    <span title="Source did not provide status">Unknown</span>
                  @endif
                </td>
                <td>{{ $o->discountPercent() ? '-' . round($o->discountPercent(), 1) . '%' : '—' }}</td>
                <td>
                  @if($o->isStale($freshnessHours))
                    <span class="badge badge-stale" title="This offer hasn&#8217;t been verified recently &#8212; price may be outdated.">May be outdated</span>
                  @else
                    {{ optional($o->last_synced_at ?? $o->updated_at)->diffForHumans() }}
                  @endif
                </td>
                <td>
                  <a class="btn btn-buy btn-sm" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$product->slug, $o->merchant->slug]) }}">Buy now</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </section>

  @if($chartData->isNotEmpty())
  <section class="chart" data-reveal style="margin-top:24px">
    <div class="sec-head">
      <h2 style="font-size:18px">Price History</h2>
    </div>
    @foreach($chartData as $c)
      <div style="margin-bottom:16px">
        <strong>{{ $c['merchant'] }}</strong>
        <svg viewBox="0 0 600 120" preserveAspectRatio="none" width="100%" height="90" role="img" aria-label="Price history chart for {{ $c['merchant'] }}">
          <line x1="0" y1="115" x2="600" y2="115" stroke="#e2e8f0"/>
          <polyline fill="none" stroke="#0b5cff" stroke-width="2.5" points="{{ $c['points'] }}"/>
        </svg>
        <div class="stats-row">
          <div class="stat">
            <b>{{ \App\Support\Currency::format($c['current'], $c['currency']) }}</b>
            <span>Current</span>
          </div>
          <div class="stat">
            <b>{{ \App\Support\Currency::format($c['lowest'], $c['currency']) }}</b>
            <span>Lowest recorded</span>
          </div>
          <div class="stat">
            <b>{{ \App\Support\Currency::format($c['highest'], $c['currency']) }}</b>
            <span>Highest recorded</span>
          </div>
          <div class="stat">
            <b>{{ \App\Support\Currency::format($c['average'], $c['currency']) }}</b>
            <span>Average</span>
          </div>
          @if($c['current'] > $c['lowest'])
            <div class="stat">
              <b style="color:var(--warn)">+{{ round(($c['current'] - $c['lowest']) / $c['lowest'] * 100, 1) }}%</b>
              <span>Above lowest</span>
            </div>
          @endif
        </div>
      </div>
    @endforeach
  </section>
  @endif

  @if($product->attributes->isNotEmpty())
  <section class="section" style="margin-top:24px">
    <div class="sec-head" data-reveal>
      <h2>Key Specifications</h2>
    </div>
    <div class="pane" style="overflow:hidden" data-reveal>
      <table class="spec-table">
        @foreach($product->attributes->sortBy('definition.sort_order') as $a)
          <tr>
            <td>{{ $a->definition->name }}</td>
            <td>{{ trim(($a->value_text ?? '') . ((is_numeric($a->value_number ?? null) && $a->definition->unit) ? ' ' . trim($a->definition->unit) : '')) }}</td>
          </tr>
        @endforeach
      </table>
    </div>
  </section>
  @endif

  @if($product->pros || $product->cons)
    <div class="pros-cons section" style="margin-top:24px">
      @if($product->pros)
        <div class="pane">
          <strong style="color:var(--ok)">&#10003; Pros</strong>
          <ul style="padding-left:18px;margin-top:8px;line-height:1.8">
            @foreach($product->pros as $pro)
              <li>{{ $pro }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      @if($product->cons)
        <div class="pane">
          <strong style="color:var(--danger)">&#10007; Cons</strong>
          <ul style="padding-left:18px;margin-top:8px;line-height:1.8">
            @foreach($product->cons as $con)
              <li>{{ $con }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  @endif

  <section class="section" style="padding-bottom:40px;margin-top:12px">
    <div class="sec-head" data-reveal>
      <h2>Similar Products</h2>
    </div>
    @if($similar->isEmpty())
      @include('partials.empty', ['icon' => '&#9633;', 'text' => 'No similar products found in this category yet.'])
    @else
      <div class="prod-grid">
        @foreach($similar->take(4) as $p)
          @include('partials.product-card', ['product' => $p])
        @endforeach
      </div>
    @endif

    @if($cheaper->isNotEmpty())
      <div class="sec-head" style="margin-top:28px">
        <h2>Want something cheaper?</h2>
      </div>
      <div class="prod-grid">
        @foreach($cheaper as $p)
          @include('partials.product-card', ['product' => $p])
        @endforeach
      </div>
    @endif

    @if($relatedGuides->isNotEmpty())
      <div class="sec-head" style="margin-top:28px">
        <h2>Related Reading</h2>
      </div>
      <ul style="padding-left:20px;line-height:2">
        @foreach($relatedGuides as $g)
          <li><a href="{{ route('articles.show', $g->slug) }}">{{ $g->title }}</a></li>
        @endforeach
      </ul>
    @endif
  </section>
</div>
@endsection
