@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    <h1 data-reveal>{{ $q ? 'Search results for "' . $q . '"' : 'Search' }}</h1>
    <p data-reveal data-delay="80">{{ $q ? 'Honest store pricing — no sponsored ranking.' : 'Search products, brands, categories and guides.' }}</p>
    @if($q)<div class="hero-meta" data-reveal data-delay="160"><span>{{ $results['products']->count() }} products</span><span>{{ $results['categories']->count() }} categories</span><span>{{ $results['brands']->count() }} brands</span></div>@endif
  </div>
</div>
<div class="container" style="margin-top:24px">

  @if($q === '')
    @include('partials.empty', ['icon'=>'🔍','text'=>'Type something in the search box above — e.g. "RTX 5070", "iPhone", "skincare".'])
  @else
    {{-- Products --}}
    <section class="section">
      <div class="sec-head" data-reveal><h2>Products</h2></div>
      @if($results['products']->isEmpty())
        @include('partials.empty', ['icon'=>'🫥','text'=>'No products matched. Check the spelling or try a shorter term.'])
      @else
        <div class="prod-grid">@foreach($results['products'] as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
      @endif
    </section>

    @if($results['categories'] || $results['brands'] || $results['merchants'])
    <section class="section" style="margin-top:8px">
      <div class="sec-head" data-reveal><h2>Related</h2></div>
      <div style="display:flex;flex-wrap:wrap;gap:10px">
        @foreach($results['categories'] as $c)
          <a class="store-pill" href="{{ route('categories.show', $c->slug) }}"><span class="sp-av">🗂️</span>{{ $c->name }}</a>
        @endforeach
        @foreach($results['brands'] as $b)
          <a class="store-pill" href="{{ route('brands.show', $b->slug) }}"><span class="sp-av">🏷️</span>{{ $b->name }}</a>
        @endforeach
        @foreach($results['merchants'] as $m)
          <a class="store-pill" href="{{ route('merchants.show', $m->slug) }}"><span class="sp-av">🏬</span>{{ $m->name }}</a>
        @endforeach
      </div>
    </section>
    @endif

    @if($results['articles']->isNotEmpty())
    <section class="section" style="padding-bottom:40px;margin-top:8px">
      <div class="sec-head" data-reveal><h2>Guides & Reviews</h2></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach($results['articles'] as $a)
          <a class="guide-card" href="{{ route('articles.show', $a->slug) }}" style="padding:20px">
            <span class="tag">{{ $a->type === 'guide' ? 'Buying Guide' : 'Review' }}</span>
            <h3>{{ $a->title }}</h3>
            <span class="guide-cta">Read →</span>
          </a>
        @endforeach
      </div>
    </section>
    @endif
  @endif
</div>
@endsection
