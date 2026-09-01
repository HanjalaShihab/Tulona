@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [['name'=>'Home','url'=>route('home')],['name'=>'All Products']]])
    <h1 data-reveal>All Products</h1>
    <p data-reveal data-delay="80">Browse every product on Tulona — multi-store price comparison with honest history.</p>
    <div class="hero-meta" data-reveal data-delay="160"><span>{{ number_format($products->total()) }} products</span><span>Live pricing</span><span>72h freshness</span></div>
  </div>
</div>
<div class="container" style="margin-top:24px">

  <div class="sort-bar">
    <label for="sort">Sort:</label>
    <select id="sort" onchange="window.location=this.value">
      @foreach(['relevance'=>'Relevance','price_asc'=>'Price low→high','price_desc'=>'Price high→low','discount'=>'Highest discount','popular'=>'Most popular','rating'=>'Best rated','newest'=>'Newest'] as $k => $label)
        <option value="{{ Request::fullUrlWithQuery(['sort'=>$k]) }}" {{ $sort === $k ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
    </select>
    <span style="font-size:13px;color:var(--ink-3)">{{ number_format($products->total()) }} products</span>
  </div>

  @if($products->isEmpty())
    @include('partials.empty', ['icon'=>'📦','text'=>'No products yet — the catalog is being built.'])
  @else
    <div class="prod-grid" data-reveal>@foreach($products as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  @endif
  {{ $products->links('partials.pagination') }}
</div>
@endsection
