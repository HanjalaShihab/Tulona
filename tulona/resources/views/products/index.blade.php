@extends('layouts.app')

@section('content')
<div class="container">
  @include('partials.breadcrumbs', ['items' => [['name'=>'Home','url'=>route('home')],['name'=>'All Products']]])
  <header style="padding-bottom:10px"><h1>All Products</h1></header>

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
    <div class="prod-grid">@foreach($products as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
  @endif
  {{ $products->links('partials.pagination') }}
</div>
@endsection
