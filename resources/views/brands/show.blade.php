@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [['name' => 'Home', 'url' => route('home')], ['name' => $brand->name]]])
    <h1 data-reveal>{{ $brand->name }}</h1>
    <p data-reveal data-delay="80">{{ $brand->description ?: 'Explore ' . $brand->name . ' products with side-by-side store pricing and honest history.' }}</p>
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ $products->total() }} products</span>
      <span>Live pricing</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:24px">
  @if($products->isEmpty())
    @include('partials.empty', ['icon' => '&#127991;', 'text' => 'No ' . $brand->name . ' products listed yet.'])
  @else
    <div class="prod-grid" data-reveal>
      @foreach($products as $p)
        @include('partials.product-card', ['product' => $p])
      @endforeach
    </div>
    {{ $products->links('partials.pagination') }}
  @endif
</div>
@endsection
