@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [['name'=>'Home','url'=>route('home')],['name'=>$merchant->name]]])
    <h1 data-reveal>Shopping at {{ $merchant->name }}</h1>
    <p data-reveal data-delay="80">{{ $merchant->description ?: "Products available at {$merchant->name}, as listed on Tulona. Clicking through takes you to {$merchant->name}'s own website where you complete your purchase." }}</p>
    <div class="hero-meta" data-reveal data-delay="160"><span>{{ $productCount }} products</span><span>{{ $merchant->country }}</span><span>Last synced {{ $merchant->last_synced_at?->diffForHumans() ?? 'recently' }}</span></div>
    <p class="note" style="color:#64748b; margin-top:10px;">Tulona is independent and not affiliated with {{ $merchant->name }} unless stated.</p>
  </div>
</div>
<div class="container" style="margin-top:24px">

  @if($categories->isNotEmpty())
    <div class="chip-links" style="padding-bottom:8px">
      @foreach($categories as $c)<a href="{{ route('categories.show', $c->slug) }}">{{ $c->icon ?? '🗂️' }} {{ $c->name }}</a>@endforeach
    </div>
  @endif

  @if($offers->isEmpty())
    @include('partials.empty', ['icon'=>'🏬','text'=>'No active offers from this store yet.'])
  @else
    <div class="prod-grid" style="padding-top:10px">
      @foreach($offers as $o)
        @include('partials.product-card', ['product' => $o->product])
      @endforeach
    </div>
    {{ $offers->links('partials.pagination') }}
  @endif
</div>
@endsection
