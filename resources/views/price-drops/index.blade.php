@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>Recent Price Drops</h1>
    <p data-reveal data-delay="80">Products whose prices recently went down at trusted stores - ranked using our own verified price history.</p>
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ $drops->total() ?? $drops->count() }} drops</span>
      <span>Live history</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:24px">
  <div class="sort-bar chip-links" style="padding-top:10px">
    <a href="{{ route('drops.index', ['sort' => 'percent']) }}" {{ $sort === 'percent' ? 'class=on' : '' }}>Largest % drop</a>
    <a href="{{ route('drops.index', ['sort' => 'amount']) }}" {{ $sort === 'amount' ? 'class=on' : '' }}>Biggest amount saved</a>
    <a href="{{ route('drops.index', ['sort' => 'recent']) }}" {{ $sort === 'recent' ? 'class=on' : '' }}>Most recent</a>
  </div>

  @if($drops->isEmpty())
    @include('partials.empty', ['icon' => '&#128201;', 'text' => 'No verified price drops in the selected window yet. As merchants update prices, genuine drops appear here.'])
  @else
    <div class="pane tbl-scroll" style="margin-bottom:24px;padding:0;border-radius:var(--radius)">
      <table class="compare-stores">
        <thead>
          <tr>
            <th>Product</th>
            <th>Store</th>
            <th>Was &#8594; Now</th>
            <th>Drop</th>
            <th>When</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($drops as $d)
            @php $isReverted = $d['is_reverted'] ?? false; @endphp
            <tr class="{{ $isReverted ? 'drop-reverted' : '' }}">
              <td><strong>{{ $d['product']->name }}</strong></td>
              <td>{{ $d['merchant']?->name }}</td>
              <td>
                <s class="price-old">{{ \App\Support\Currency::format((float)$d['live_previous'], $d['currency']) }}</s>
                &#8594;
                <strong>{{ \App\Support\Currency::format((float)$d['live_price'], $d['currency']) }}</strong>
              </td>
              <td>
                @if($isReverted)
                  <span class="badge badge-out">price moved back up</span>
                @else
                  <span class="badge badge-drop">&#8595; {{ round((float)$d['live_drop_percent'], 1) }}% &middot; -{{ \App\Support\Currency::format((float)$d['live_drop_amount'], $d['currency']) }}</span>
                @endif
              </td>
              <td>@if(isset($d['occurred_at']) && $d['occurred_at']){{ $d['occurred_at']->diffForHumans() }}@endif</td>
              <td><a class="btn btn-outline btn-sm" href="{{ route('product.show', $d['product']->slug) }}">Compare Prices</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  {{ $drops->links('partials.pagination') }}
</div>
@endsection
