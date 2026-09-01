@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>Today's Best Deals</h1>
    <p data-reveal data-delay="80">Genuine, data-backed discounts — every deal below shows a real verified previous price from our own history. No fake urgency, no invented countdowns.</p>
    <div class="hero-meta" data-reveal data-delay="160"><span>{{ $products->total() }} deals</span><span>Verified history</span><span>No sponsored ranking</span></div>
  </div>
</div>
<div class="container" style="margin-top:24px">

  <div class="chip-links" style="padding:12px 0">
    <a href="{{ route('deals.index') }}" {{ !$activeMerchant ? 'class=on' : '' }}>All stores</a>
    @foreach($merchants as $m)
      <a href="{{ route('deals.index', ['merchant' => $m->slug]) }}" {{ $activeMerchant === $m->slug ? 'class=on' : '' }}>{{ $m->name }}</a>
    @endforeach
  </div>

  @if($products->isEmpty())
    @include('partials.empty', ['icon'=>'💸','text'=>'No active deals right now — check back soon or browse the catalog.'])
  @else
    <div class="prod-grid" data-reveal>
      @foreach($products as $p)
        @include('partials.product-card', ['product' => $p])
      @endforeach
    </div>
  @endif
  {{ $products->links('partials.pagination') }}


</div>
@endsection
