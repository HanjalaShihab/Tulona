@extends('layouts.app')

@section('schema')
@if(!empty($schema))
<script type="application/ld+json">@json($schema)</script>
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
    <div class="pdp-gallery">
      @if($img = $product->images->firstWhere('is_main') ?: $product->images->first())
        <div class="main-img"><img src="{{ str_starts_with($img->path,'http') ? $img->path : asset('storage/'.$img->path) }}" alt="{{ $img->alt_text ?: $product->name }}" width="480" height="360"></div>
      @else
        <div class="main-img">{{ strtoupper(substr($product->brand->name ?? 'T',0,1)) }}</div>
      @endif
      <p style="margin-top:12px"><a class="btn btn-outline btn-sm" href="#" onclick="return tulonaAddCompare('{{ $product->slug }}')">＋ Add to compare</a></p>
    </div>

    <div>
      <span class="card-brand">{{ $product->brand?->name }}</span>
      <h1 class="pdp-title">{{ $product->name }}</h1>
      <div class="pdp-meta">
        <a href="{{ route('categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
        @if($product->brand)<a href="{{ route('brands.show', $product->brand->slug) }}">{{ $product->brand->name }}</a>@endif
        @if($product->rating)<span>★ {{ $product->rating }}/5 <small>(editorial)</small></span>@endif
        @if($latestDrop)<span class="badge badge-drop">Price dropped {{ round($latestDrop->drop_percent) }}%</span>@endif
      </div>
      @if($minPrice !== null)
        <div class="best-price-box">
          <div><small style="color:var(--ink-2)">Best price</small><br><span class="price-xl">{{ \App\Support\Currency::format($minPrice, $bestOffer->currency) }}</span></div>
          <div style="font-size:13.5px;color:var(--ink-2)">Available from <strong>{{ $offers->whereNotNull('current_price')->count() }}</strong> {{ $offers->whereNotNull('current_price')->count() == 1 ? 'store' : 'stores' }}
            @if($maxPrice > $minPrice)<br>Save up to <strong>{{ \App\Support\Currency::format($maxPrice - $minPrice, $bestOffer->currency) }}</strong> at another store.@endif
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-left:auto">
            <a class="btn btn-outline" href="#offers">Compare Prices</a>
            <a class="btn btn-primary btn-lg" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$product->slug, $bestOffer->merchant->slug]) }}">Buy Now — {{ $bestOffer->merchant->name }}</a>
          </div>
        </div>
      @else
        <div class="alert alert-err">Price unavailable right now — offers below may update shortly.</div>
      @endif
      @if($product->summary_editorial || $product->short_description)
        <p style="margin-top:14px;color:var(--ink-2)">{{ $product->summary_editorial ?: $product->short_description }}</p>
      @endif
    </div>
  </div>

  <section class="section" id="offers">
    <div class="sec-head"><h2>Compare Stores</h2></div>
    @if($offers->isEmpty())
      @include('partials.empty', ['icon'=>'🏬','text'=>'No active offers for this product yet.'])
    @else
      <div class="tbl-scroll">
        <table class="compare-stores">
          <thead><tr><th scope="col">Store</th><th scope="col">Price</th><th scope="col">Availability</th><th scope="col">Discount</th><th scope="col">Updated</th><th scope="col">Action</th></tr></thead>
          <tbody>
          @foreach($offers as $o)
            <tr @if($loop->first && $o->current_price !== null)class="row-best"@endif>
              <td><strong>{{ $o->merchant->name }}</strong>@if($loop->first && $o->current_price !== null) <span class="badge badge-deal">Best price</span>@endif</td>
              <td>@if($o->current_price !== null)<strong>{{ \App\Support\Currency::format((float)$o->current_price, $o->currency) }}</strong>
                  @if($o->original_price && $o->original_price > $o->current_price)<s class="price-old">{{ \App\Support\Currency::format((float)$o->original_price, $o->currency) }}</s>@endif
                @else<em>Price unavailable</em>@endif
              </td>
              <td>@if($o->availability === 'in_stock')✅ In stock@elseif($o->availability==='out_of_stock')<span class="badge badge-out">Out of stock</span>@elseif($o->availability==='preorder')Pre-order@else<span title="Source did not provide status">Unknown</span>@endif</td>
              <td>{{ $o->discountPercent() ? '-'.round($o->discountPercent(),1).'%' : '—' }}</td>
              <td>@if($o->isStale($freshnessHours))<span class="badge badge-stale" title="This offer hasn't been verified recently — price may be outdated.">May be outdated</span>@else{{ optional($o->last_synced_at ?? $o->updated_at)->diffForHumans() }}@endif</td>
              <td><a class="btn btn-primary btn-sm" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$product->slug, $o->merchant->slug]) }}">Buy from {{ $o->merchant->name }}</a></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <p class="disclosure" style="margin-top:12px"><span aria-hidden="true">ℹ️</span><span>You'll be redirected to the merchant to complete your purchase. Prices and availability may change without notice — Tulona never sells or ships products.</span></p>
    @endif
  </section>

  @if($chartData->isNotEmpty())
  <section class="chart">
    <div class="sec-head"><h2 style="font-size:18px">Price History</h2></div>
    @foreach($chartData as $c)
      <div style="margin-bottom:16px">
        <strong>{{ $c['merchant'] }}</strong>
        <svg viewBox="0 0 600 120" preserveAspectRatio="none" width="100%" height="90" role="img" aria-label="Price history chart for {{ $c['merchant'] }}">
          <line x1="0" y1="115" x2="600" y2="115" stroke="#e2e8f0"/>
          <polyline fill="none" stroke="#0b5cff" stroke-width="2.5" points="{{ $c['points'] }}"/>
        </svg>
        <div class="stats-row">
          <div class="stat"><b>{{ \App\Support\Currency::format($c['current'], $c['currency']) }}</b><span>Current</span></div>
          <div class="stat"><b>{{ \App\Support\Currency::format($c['lowest'], $c['currency']) }}</b><span>Lowest recorded</span></div>
          <div class="stat"><b>{{ \App\Support\Currency::format($c['highest'], $c['currency']) }}</b><span>Highest recorded</span></div>
          <div class="stat"><b>{{ \App\Support\Currency::format($c['average'], $c['currency']) }}</b><span>Average</span></div>
          @if($c['current'] > $c['lowest'])
            <div class="stat"><b style="color:var(--warn)">+{{ round(($c['current']-$c['lowest'])/$c['lowest']*100,1) }}%</b><span>Above lowest</span></div>
          @endif
        </div>
      </div>
    @endforeach
  </section>
  @endif

  @if($product->attributes->isNotEmpty())
  <section class="section">
    <div class="sec-head"><h2>Key Specifications</h2></div>
    <div class="pane"><table class="spec-table">
      @foreach($product->attributes->sortBy('definition.sort_order') as $a)
        <tr><td>{{ $a->definition->name }}</td><td>{{ trim(($a->value_text ?? '').((is_numeric($a->value_number ?? null) && $a->definition->unit) ? ' '.trim($a->definition->unit) : '')) }}</td></tr>
      @endforeach
    </table></div>
  </section>
  @endif

  @if($product->pros || $product->cons)
    <div class="pros-cons section">
      @if($product->pros)<div class="pane"><strong style="color:var(--ok)">Pros</strong><ul>@foreach($product->pros as $pro)<li>{{ $pro }}</li>@endforeach</ul></div>@endif
      @if($product->cons)<div class="pane"><strong style="color:var(--danger)">Cons</strong><ul>@foreach($product->cons as $con)<li>{{ $con }}</li>@endforeach</ul></div>@endif
    </div>
  @endif

  <section class="section" style="padding-bottom:40px">
    <div class="sec-head"><h2>Similar Products</h2></div>
    @if($similar->isEmpty())
      @include('partials.empty', ['icon'=>'🧭','text'=>'No similar products found in this category yet.'])
    @else
      <div class="prod-grid">@foreach($similar->take(4) as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
    @endif

    @if($cheaper->isNotEmpty())
      <div class="sec-head" style="margin-top:26px"><h2>Want something cheaper?</h2></div>
      <div class="prod-grid">@foreach($cheaper as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
    @endif

    @if($relatedGuides->isNotEmpty())
      <div class="sec-head" style="margin-top:26px"><h2>Related Reading</h2></div>
      <ul style="padding-left:20px;line-height:2">
        @foreach($relatedGuides as $g)<li><a href="{{ route('articles.show', $g->slug) }}">{{ $g->title }}</a></li>@endforeach
      </ul>
    @endif
  </section>
</div>

@endsection

@section('scripts')
<script>
function tulonaAddCompare(slug){
  var cur = JSON.parse(localStorage.getItem('tulona_compare') || '[]');
  if(!cur.includes(slug)) cur.push(slug);
  cur = cur.slice(-4);
  localStorage.setItem('tulona_compare', JSON.stringify(cur));
  window.location = '/compare?products=' + encodeURIComponent(cur.join(','));
  return false;
}
</script>
@endsection
