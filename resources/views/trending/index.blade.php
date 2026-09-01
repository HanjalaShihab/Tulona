@extends('layouts.app')

@section('content')
@php
  $glance = function ($p) {
    $offers = $p->activeOffers->filter(fn($o) => $o->status === 'active' && $o->current_price !== null);
    $best = $offers->min('current_price');
    $storeCount = $p->offer_count ?? $p->activeOffers->count();
    $img = $p->images->firstWhere('is_main') ?: $p->images->first();
    $origOffer = $offers->filter(fn($o) => $o->original_price && (float) $o->original_price > (float) $o->current_price)
      ->sortByDesc(fn($o) => $o->discountPercent())->first();
    return [
      'best' => $best,
      'orig' => $origOffer ? (float) $origOffer->original_price : null,
      'discount' => $origOffer ? round($origOffer->discountPercent(), 1) : null,
      'storeCount' => $storeCount,
      'drop' => $p->latestDrop ?? null,
      'img' => $img ? (str_starts_with($img->path, 'http') ? $img->path : asset('storage/'.$img->path)) : null,
      'alt' => $img->alt_text ?? $p->name,
    ];
  };
  $avgPrice = $products->map(fn($p) => $glance($p)['best'])->filter()->avg();
  $totalDrops = $products->filter(fn($p) => $glance($p)['drop'] !== null)->count();
@endphp

<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [
      ['name' => 'Home', 'url' => route('home')],
      ['name' => 'Trending'],
    ]])
    <h1 data-reveal>Trending Products</h1>
    <p data-reveal data-delay="80">What shoppers are comparing most this week &#8212; ranked from live research on Tulona.</p>
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ number_format($products->count()) }} products</span>
      <span>Live pricing</span>
      <span>72h freshness</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:56px;padding-bottom:88px">
  @if($products->isEmpty())
    @include('partials.empty', ['icon' => '&#9633;', 'text' => 'Nothing is trending yet &#8212; check back soon.'])
  @else
    <div class="tl-split">
      <aside class="tl-side">
        <h3 class="tl-side-kicker">The week on Tulona</h3>
        <p class="tl-side-txt">Rankings update as comparisons happen. Prices are checked from stores within the last 72 hours.</p>
        <ul class="tl-stats">
          <li><span class="tl-stat-num">{{ number_format($products->count()) }}</span><span class="tl-stat-lbl">trending items</span></li>
          <li><span class="tl-stat-num">@if($avgPrice){{ \App\Support\Currency::format(round($avgPrice)) }}@else&#8212;@endif</span><span class="tl-stat-lbl">avg. price</span></li>
          <li><span class="tl-stat-num">{{ $totalDrops }}</span><span class="tl-stat-lbl">price drops</span></li>
        </ul>
        <a class="hs-link" href="{{ route('products.index') }}">Browse full catalog &#8594;</a>
      </aside>

      <ol class="tl-side-list">
        @foreach($products as $i => $p)
          @php $g = $glance($p); @endphp
          <li class="tl-side-row">
            <a href="{{ route('product.show', $p->slug) }}">
              <span class="tl-row-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
              @if($g['img'])
                <span class="tl-side-thumb" aria-hidden="true"><img src="{{ $g['img'] }}" alt="" loading="lazy"></span>
              @endif
              <span class="tl-row-txt">
                <span class="tl-row-brand">{{ $p->brand->name ?? 'Tulona' }}</span>
                <span class="tl-row-name">{{ $p->name }}</span>
              </span>
              <span class="tl-row-price">
                @if($g['best'] !== null)
                  <span class="rk-now">{{ \App\Support\Currency::format((float) $g['best']) }}</span>
                  @if($g['orig'])
                    <span class="rk-old">{{ \App\Support\Currency::format($g['orig']) }}</span>
                    @if($g['discount'])<span class="rk-discount">-{{ $g['discount'] }}%</span>@endif
                  @endif
                @else
                  <span class="rk-na">Pending</span>
                @endif
              </span>
              <span class="rk-arrow" aria-hidden="true">&#8594;</span>
            </a>
          </li>
        @endforeach
      </ol>
    </div>
  @endif
</div>
@endsection