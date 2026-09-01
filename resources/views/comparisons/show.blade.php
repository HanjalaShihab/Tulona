@extends('layouts.app')

@section('schema')
@if(!empty($schema))
<script type="application/ld+json">@json($schema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endif
@endsection

@section('content')
<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [
      ['name' => 'Home', 'url' => route('home')],
      ['name' => $comparison->title],
    ]])
    <h1 data-reveal>{{ $comparison->title }}</h1>
    @if($comparison->introduction)<p data-reveal data-delay="80">{{ $comparison->introduction }}</p>@endif
    <div class="hero-meta" data-reveal data-delay="160">@if($comparison->published_at)<span>Last updated {{ $comparison->updated_at->format('F j, Y') }}</span>@endif@if($rows->count())<span>{{ $rows->count() }} products</span>@endif@if($bestPrice)<span>Best price computed</span>@endif</div>
  </div>
</div>
<div class="container">

  @if($bestPrice && $bestDeal)
    <div class="verdict-grid">
      @foreach([
        ['k'=>'Best Price','v'=>\App\Support\Currency::format($bestPrice['price'], $bestPrice['merchant']->currency ?? 'BDT'),'s'=>$bestPrice['product']->name .' · '. $bestPrice['merchant']->name,'u'=>route('go.redirect', [$bestPrice['product']->slug, $bestPrice['merchant']->slug])],
        ['k'=>'Best Overall Deal','v'=>\App\Support\Currency::format($bestDeal['price'], $bestDeal['merchant']->currency ?? 'BDT'),'s'=>$bestDeal['product']->name .' · '. $bestDeal['merchant']->name,'u'=>route('go.redirect', [$bestDeal['product']->slug, $bestDeal['merchant']->slug])],
      ] as $card)
        <div class="pane verdict-card">
          <span class="badge badge-pick">{{ $card['k'] }}</span>
          <span class="price-xl">{{ $card['v'] }}</span>
          <span style="font-size:14px;color:var(--ink-2)">{{ $card['s'] }}</span>
          <a class="btn btn-buy btn-sm" rel="nofollow sponsored noopener" href="{{ $card['u'] ?: '#' }}">Buy now →</a>
        </div>
      @endforeach
    </div>
  @endif

  @if($comparison->description)
    <div class="pane" style="margin-bottom:22px">{{ $comparison->description }}</div>
  @endif

  @forelse($rows as $row)
    @php($prod = $row['product'])
    <section class="section">
      <div class="sec-head">
        <h2 style="margin-right:auto">{{ $prod->name }}
          @if($row['pick_label'])<span class="badge badge-pick" style="margin-left:8px">{{ $row['pick_label'] }}</span>@endif
        </h2>
        <a class="btn btn-outline btn-sm" href="{{ route('product.show', $prod->slug) }}">View product</a>
      </div>
      @if($row['editorial_notes'])<p style="color:var(--ink-2);margin-bottom:12px">{{ $row['editorial_notes'] }}</p>@endif

      <div class="tbl-scroll pane" style="padding:0">
        <table class="compare-matrix" style="width:100%;border-collapse:collapse">
          <thead>
            <tr><th scope="col" style="text-align:left">Merchant</th>
              @foreach($row['columns'] as $col)
                <th scope="col">
                  <strong>{{ $col['merchant_name'] }}</strong>
                  @if($col['is_override'])<br><small style="font-weight:400;color:var(--ink-3)">verified price</small>@endif
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Price</td>
              @foreach($row['columns'] as $col)
                <td class="{{ ($row['stats']['lowest'] !== null && $col['price'] === $row['stats']['lowest']) ? 'best-cell' : '' }}">
                  @if($col['price'] !== null){{ \App\Support\Currency::format($col['price'], $col['currency']) }}
                    @if($col['discount_pct'] !== null)<br><small style="color:var(--accent)">-{{ $col['discount_pct'] }}%</small>@endif
                  @else<span style="color:var(--ink-3)">—</span>@endif
                </td>
              @endforeach
            </tr>
            <tr><td>Availability</td>
              @foreach($row['columns'] as $col)
                <td>{{ $col['availability'] ? str_replace('_',' ',ucfirst($col['availability'])) : '—' }}</td>
              @endforeach
            </tr>
            <tr><td>Warranty</td>
              @foreach($row['columns'] as $col)<td>{{ $col['warranty'] ?: '—' }}</td>@endforeach
            </tr>
            <tr><td>Shipping</td>
              @foreach($row['columns'] as $col)<td>{{ $col['shipping'] ?: '—' }}</td>@endforeach
            </tr>
            @if($row['stats']['difference'] !== null)
              <tr class="total-row"><td>Price gap</td>
                @foreach($row['columns'] as $col)
                  <td>{{ (float)$col['price'] === $row['stats']['lowest'] ? 'Cheapest' : ($col['price'] !== null ? '+'. \App\Support\Currency::format((float)$col['price']-$row['stats']['lowest'], $col['currency']) : '—') }}</td>
                @endforeach
              </tr>
            @endif
            <tr>
              <td></td>
              @foreach($row['columns'] as $col)
                <td>
                  @if($col['affiliate_url'])
                    <a class="btn btn-buy btn-sm" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$prod->slug, $col['merchant']->slug]) }}">{{ $comparison->cta_text ?: 'Buy now' }} — {{ $col['merchant_name'] }}</a>
                  @else
                    <span style="color:var(--ink-3);font-size:13px">No link</span>
                  @endif
                </td>
              @endforeach
            </tr>
          </tbody>
        </table>
      </div>

      @if($specifications_shown)
        <div class="tbl-scroll pane" style="padding:0;margin-top:12px">
          <table class="compare-matrix" style="width:100%;border-collapse:collapse">
            <thead><tr><th style="text-align:left">Specification</th>@foreach($row['columns'] as $col)<th style="text-align:left">{{ $col['merchant_name'] }}</th>@endforeach</tr></thead>
            <tbody>
              @foreach($specifications_shown as $specKey)
                <tr><td>{{ $specKey }}</td>
                  @foreach($row['columns'] as $col)<td>—</td>@endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>
  @empty
    @include('partials.empty', ['icon'=>'&#9878;','text'=>'No published products in this comparison yet.'])
  @endforelse

  @if($comparison->verdict)
    <section class="section">
      <div class="sec-head"><h2>Our Verdict</h2></div>
      <div class="pane">{{ $comparison->verdict }}</div>
    </section>
  @endif

  <p class="disclosure" style="margin-top:22px"><span aria-hidden="true">&#8505;</span><span>Some "Buy now" links are affiliate links &#8212; Tulona may earn a commission at no extra cost to you. Prices and availability may change without notice.</span></p>
</div>
@endsection