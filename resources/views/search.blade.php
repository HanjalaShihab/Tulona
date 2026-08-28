@extends('layouts.app')

@section('content')
<div class="container">
  <h1 style="padding:20px 0 6px">{{ $q ? 'Search results for “'.$q.'”' : 'Search' }}</h1>

  @if($q === '')
    @include('partials.empty', ['icon'=>'🔍','text'=>'Type something in the search box above — e.g. “RTX 5070”, “iPhone”, “skincare”.'])
  @else
    {{-- Products --}}
    <section class="section">
      <div class="sec-head"><h2>Products</h2></div>
      @if($results['products']->isEmpty())
        @include('partials.empty', ['icon'=>'🫥','text'=>'No products matched. Check the spelling or try a shorter term.'])
      @else
        <div class="prod-grid">@foreach($results['products'] as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
      @endif
    </section>

    @if($results['categories'] || $results['brands'] || $results['merchants'])
    <section class="section">
      <div class="sec-head"><h2>Related</h2></div>
      <ul style="line-height:2.2;padding-left:18px">
        @foreach($results['categories'] as $c)<li>🗂️ Category: <a href="{{ route('categories.show', $c->slug) }}">{{ $c->name }}</a></li>@endforeach
        @foreach($results['brands'] as $b)<li>🏷️ Brand: <a href="{{ route('brands.show', $b->slug) }}">{{ $b->name }}</a></li>@endforeach
        @foreach($results['merchants'] as $m)<li>🏬 Store: <a href="{{ route('merchants.show', $m->slug) }}">{{ $m->name }}</a></li>@endforeach
      </ul>
    </section>
    @endif

    @if($results['articles']->isNotEmpty())
    <section class="section" style="padding-bottom:40px">
      <div class="sec-head"><h2>Guides & Reviews</h2></div>
      <ul style="line-height:2.2;padding-left:18px">
        @foreach($results['articles'] as $a)<li>📄 <a href="{{ route('articles.show', $a->slug) }}">{{ $a->title }}</a></li>@endforeach
      </ul>
    </section>
    @endif
  @endif
</div>
@endsection
