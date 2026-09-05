@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [
      ['name' => 'Home', 'url' => route('home')],
      ['name' => 'All Brands'],
    ]])
    <h1 data-reveal>All Brands</h1>
    <p data-reveal data-delay="80">Every brand on Tulona — compare prices across trusted stores.</p>
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ number_format($brands->sum('product_count')) }} products</span>
      <span>{{ $brands->count() }} brands</span>
      <span>Live pricing</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:48px;padding-bottom:64px">
  @if($brands->isEmpty())
    @include('partials.empty', ['icon' => '&#127991;', 'text' => 'No brands yet — the catalog is being built.'])
  @else
    <div class="cat-index" role="list">
      @foreach($brands as $b)
        <a class="cat-index-row" href="{{ route('brands.show', $b->slug) }}" data-reveal data-delay="{{ $loop->index % 12 * 40 }}" role="listitem">
          <span class="ci-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
          <span class="ci-name">{{ $b->name }}</span>
          <span class="ci-count">{{ number_format($b->product_count ?? 0) }} products</span>
          <span class="ci-arrow" aria-hidden="true">&#8594;</span>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection
