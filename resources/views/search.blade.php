@extends('layouts.app')

@section('content')
@php
  $hasQuery = $q !== null && $q !== '';
  $showRelated = $results['categories']->isNotEmpty() || $results['brands']->isNotEmpty() || $results['merchants']->isNotEmpty();
@endphp

<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>{{ $hasQuery ? 'Search results for "' . $q . '"' : 'Search' }}</h1>
    <p data-reveal data-delay="80">{{ $hasQuery ? 'Honest store pricing - no sponsored ranking.' : 'Search products, brands, categories and guides.' }}</p>
    @if($hasQuery)
      <div class="hero-meta" data-reveal data-delay="160">
        <span>{{ $results['products']->count() }} products</span>
        <span>{{ $results['categories']->count() }} categories</span>
        <span>{{ $results['brands']->count() }} brands</span>
      </div>
    @endif
  </div>
</div>

<div class="container" style="margin-top:24px">
  @if(!$hasQuery)
    @include('partials.empty', ['icon' => '&#128269;', 'text' => 'Type something in the search box above - e.g. "RTX 5070", "iPhone", "skincare".'])
  @else
    <section class="section">
      <div class="sec-head" data-reveal><h2>Products</h2></div>
      @if($results['products']->isEmpty())
        @include('partials.empty', ['icon' => '&#128869;', 'text' => 'No products matched. Check the spelling or try a shorter term.'])
      @else
        <div class="prod-grid">
          @foreach($results['products'] as $p)
            @include('partials.product-card', ['product' => $p])
          @endforeach
        </div>
      @endif
    </section>

    @if($showRelated)
      <section class="section" style="margin-top:8px">
        <div class="sec-head" data-reveal><h2>Related</h2></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
          @foreach($results['categories'] as $c)
            <a class="store-pill" href="{{ route('categories.show', $c->slug) }}"><span class="sp-av">&#128450;</span>{{ $c->name }}</a>
          @endforeach
          @foreach($results['brands'] as $b)
            <a class="store-pill" href="{{ route('brands.show', $b->slug) }}"><span class="sp-av">&#127991;</span>{{ $b->name }}</a>
          @endforeach
          @foreach($results['merchants'] as $m)
            <a class="store-pill" href="{{ route('merchants.show', $m->slug) }}"><span class="sp-av">&#127979;</span>{{ $m->name }}</a>
          @endforeach
        </div>
      </section>
    @endif

    @if($results['articles']->isNotEmpty())
      <section class="section" style="margin-top:8px;padding-bottom:40px">
        <div class="sec-head" data-reveal><h2>Guides &amp; Reviews</h2></div>
        <div class="guide-grid">
          @foreach($results['articles'] as $a)
            <a class="guide-card" href="{{ route('articles.show', $a->slug) }}">
              <span class="tag">{{ $a->type === 'guide' ? 'Buying Guide' : 'Review' }}</span>
              <h3>{{ $a->title }}</h3>
              <span class="guide-cta">Read <span aria-hidden="true">&#8594;</span></span>
            </a>
          @endforeach
        </div>
      </section>
    @endif
  @endif
</div>
@endsection
