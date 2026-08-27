@extends('layouts.app')

@section('content')
<div class="container">
  <header style="padding:22px 0 4px">
    <h1>Today's Best Deals</h1>
    <p style="color:var(--ink-2);max-width:700px">Genuine, data-backed discounts — every deal below shows a real verified previous price from our own history. No fake urgency, no invented countdowns.</p>
  </header>

  <div class="chip-links" style="padding:12px 0">
    <a href="{{ route('deals.index') }}" {{ !$activeMerchant ? 'class=on' : '' }}>All stores</a>
    @foreach($merchants as $m)
      <a href="{{ route('deals.index', ['merchant' => $m->slug]) }}" {{ $activeMerchant === $m->slug ? 'class=on' : '' }}>{{ $m->name }}</a>
    @endforeach
  </div>

  @if($products->isEmpty())
    @include('partials.empty', ['icon'=>'💸','text'=>'No active deals right now — check back soon or browse the catalog.'])
  @else
    <div class="prod-grid">
      @foreach($products as $p)
        @include('partials.product-card', ['product' => $p])
      @endforeach
    </div>
  @endif
  {{ $products->links('partials.pagination') }}

  @if(!$comparisons->isEmpty())
  <section style="margin-top:32px">
    <div class="sec-head"><div><span class="sec-eyebrow">⚖ Round-ups</span><h2>Comparison round-ups</h2></div><a href="{{ route('compare.index') }}">Compare products →</a></div>
    <div class="cat-grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr));grid-auto-rows:1fr">
      @foreach($comparisons as $c)@include('partials.comparison-card', ['comparison' => $c])@endforeach
    </div>
  </section>
  @endif
</div>
@endsection
