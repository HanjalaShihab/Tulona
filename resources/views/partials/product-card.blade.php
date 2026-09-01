@php
  $best = $product->activeOffers
    ->filter(fn($o) => $o->status === 'active' && $o->current_price !== null)
    ->min('current_price');
@endphp
<article class="card">
  <a href="{{ route('product.show', $product->slug) }}" class="card-img" aria-hidden="true" tabindex="-1">
    @if($img = $product->images->firstWhere('is_main') ?: $product->images->first())
      <img src="{{ str_starts_with($img->path, 'http') ? $img->path : asset('storage/' . $img->path) }}" alt="{{ $img->alt_text ?: $product->name }}" loading="lazy" width="320" height="240">
    @else
      {{ substr($product->brand->name ?? 'T', 0, 1) }}
    @endif
  </a>
  <div class="card-body">
    <span class="card-brand">{{ $product->brand->name ?? '' }}</span>
    <a class="card-name" href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
    <div class="stores-n">
      From {{ $product->active_offers_count ?? $product->activeOffers->count() }}
      {{ ($product->active_offers_count ?? $product->activeOffers->count()) == 1 ? 'store' : 'stores' }}
    </div>

    @if($best !== null)
      <div class="card-price-row">
        <span class="price-now">{{ \App\Support\Currency::format((float) $best) }}</span>
        @if($drop = $product->latestDrop ?? null)
          <span class="badge badge-drop">&#8595; {{ round($drop->drop_percent) }}%</span>
        @endif
        @php
          $orig = $product->activeOffers->filter(fn($o) => $o->original_price && $o->original_price > $o->current_price);
        @endphp
        @if($orig->isNotEmpty())
          @php $o2 = $orig->sortByDesc(fn($o) => $o->discountPercent())->first(); @endphp
          <span class="price-old">{{ \App\Support\Currency::format((float) $o2->original_price, $o2->currency) }}</span>
          <span class="badge badge-deal">-{{ round($o2->discountPercent(), 1) }}%</span>
        @endif
      </div>
    @else
      <div class="card-price-row">
        <span class="badge badge-out">Price unavailable</span>
      </div>
    @endif

    @if($product->is_editors_pick || $product->is_budget_pick || $product->is_premium_pick || $product->is_best_value)
      <div>
        @if($product->is_editors_pick)<span class="badge badge-pick">Editor&#8217;s Pick</span>@endif
        @if($product->is_best_value)<span class="badge badge-pick">Best Value</span>@endif
        @if($product->is_budget_pick)<span class="badge badge-pick">Budget Pick</span>@endif
        @if($product->is_premium_pick)<span class="badge badge-pick">Premium Pick</span>@endif
      </div>
    @endif

    <div class="card-actions">
      <a class="btn btn-primary btn-sm" href="{{ route('product.show', $product->slug) }}">Compare Prices</a>
      @if($cheap = $product->activeOffers
        ->whereNotNull('current_price')
        ->sortBy(fn($o) => [$o->availability === 'in_stock' ? 0 : 1, (float) $o->current_price])
        ->first())
            <a class="btn btn-outline btn-sm view-deal-btn"
           rel="nofollow sponsored noopener"
           href="{{ route('go.redirect', [$product->slug, optional($cheap->merchant)->slug]) }}">View Deal</a>
      @endif
    </div>
  </div>
</article>
