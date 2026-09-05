@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>Today's Best Deals</h1>
    <p data-reveal data-delay="80">Genuine, data-backed discounts - every deal below shows a real verified previous price from our own history. No fake urgency, no invented countdowns.</p>
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ $products->total() }} deals</span>
      <span>Verified history</span>
      <span>No sponsored ranking</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:24px">
  <div class="sort-bar chip-links" style="padding:12px 0">
    <a href="{{ route('deals.index') }}" {{ !$activeMerchant ? 'class=on' : '' }}>All stores</a>
    @foreach($merchants as $m)
      <a href="{{ route('deals.index', ['merchant' => $m->slug]) }}" {{ $activeMerchant === $m->slug ? 'class=on' : '' }}>{{ $m->name }}</a>
    @endforeach
  </div>

  @if($products->isEmpty())
    @include('partials.empty', ['icon' => '&#128176;', 'text' => 'No active deals right now - check back soon or browse the catalog.'])
  @else
    <div class="deals-index-grid">
      @foreach($products as $p)
        @php
          $dImg = $p->images->firstWhere('is_main') ?: $p->images->first();
          $dOffer = $p->activeOffers
            ->filter(fn($o) => $o->status === 'active' && $o->current_price !== null)
            ->filter(fn($o) => $o->original_price && $o->original_price > $o->current_price)
            ->sortByDesc(fn($o) => $o->discountPercent())
            ->first();
          $dDiscount = $dOffer ? round($dOffer->discountPercent(), 1) : (isset($p->best_ratio) ? round((1 - (float) $p->best_ratio) * 100, 1) : null);
          $dCurrent = $dOffer ? (float) $dOffer->current_price : (float) ($p->best_price ?? 0);
          $dOriginal = $dOffer ? (float) $dOffer->original_price : (float) ($p->max_original ?? 0);
          $dStores = $p->offers_count ?? ($p->active_offers_count ?? $p->activeOffers->count());
          $dMerchantSlug = $dOffer?->merchant?->slug;
        @endphp
        <article class="tcard">
          <a class="tcard-img" href="{{ route('product.show', $p->slug) }}" aria-hidden="true" tabindex="-1">
            @if($dImg)
              <img src="{{ str_starts_with($dImg->path, 'http') ? $dImg->path : asset('storage/' . $dImg->path) }}" alt="{{ $dImg->alt_text ?: $p->name }}" loading="lazy">
            @else
              <span class="tcard-fallback">{{ strtoupper(substr($p->name, 0, 1)) }}</span>
            @endif
            @if($dDiscount !== null)
              <span class="tcard-off">&minus;{{ rtrim(rtrim(number_format($dDiscount, 1, '.', ''), '0'), '.') }}<small>%</small></span>
            @endif
          </a>
          <div class="tcard-body">
            <div class="tcard-meta">
              <span class="tcard-brand">{{ $p->brand->name ?? 'Tulona' }}</span>
              <span class="tcard-stores">From {{ $dStores }} {{ $dStores == 1 ? 'store' : 'stores' }}</span>
            </div>
            <a class="tcard-name" href="{{ route('product.show', $p->slug) }}">{{ $p->name }}</a>

            <div class="tcard-price">
              @if($dCurrent > 0)
                <span class="tcard-now">{{ \App\Support\Currency::format($dCurrent, $dOffer->currency ?? 'BDT') }}</span>
                @if($dOriginal > $dCurrent)
                  <span class="tcard-old">{{ \App\Support\Currency::format($dOriginal, $dOffer->currency ?? 'BDT') }}</span>
                  <span class="tcard-save">Save {{ \App\Support\Currency::format((float) $dOriginal - (float) $dCurrent, $dOffer->currency ?? 'BDT') }}</span>
                @endif
              @else
                <span class="tcard-na">Price unavailable</span>
              @endif
            </div>

            @if($dMerchantSlug)
              <a class="tcard-cta" href="{{ route('go.redirect', [$p->slug, $dMerchantSlug]) }}" target="_blank" rel="nofollow sponsored noopener">View deal</a>
            @else
              <a class="tcard-cta" href="{{ route('product.show', $p->slug) }}">View deal</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif

  {{ $products->links('partials.pagination') }}
</div>
@endsection
