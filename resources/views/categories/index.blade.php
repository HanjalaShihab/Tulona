@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [
      ['name' => 'Home', 'url' => route('home')],
      ['name' => 'All Categories'],
    ]])
    <h1 data-reveal>All Categories</h1>
    <p data-reveal data-delay="80">Every category on Tulona &#8212; multi-store price comparison with honest history.</p>
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ number_format($categories->sum('product_count')) }} products</span>
      <span>{{ $categories->count() }} categories</span>
      <span>Live pricing</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:48px;padding-bottom:64px">
  @if($categories->isEmpty())
    @include('partials.empty', ['icon' => '&#9633;', 'text' => 'No categories yet — the catalog is being built.'])
  @else
    <div class="cat-index" role="list">
      @foreach($categories as $c)
        <a class="cat-index-row" href="{{ route('categories.show', $c->slug) }}" data-reveal data-delay="{{ $loop->index % 12 * 40 }}" role="listitem">
          <span class="ci-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
          <span class="ci-name">{{ $c->name }}</span>
          <span class="ci-count">{{ number_format($c->product_count ?? 0) }} products</span>
          <span class="ci-arrow" aria-hidden="true">&#8594;</span>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection