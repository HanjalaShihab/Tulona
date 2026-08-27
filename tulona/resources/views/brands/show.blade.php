@extends('layouts.app')

@section('content')
<div class="container">
  @include('partials.breadcrumbs', ['items' => [['name'=>'Home','url'=>route('home')],['name'=>$brand->name]]])
  <header style="padding-bottom:12px">
    <h1>{{ $brand->name }}</h1>
    {!! $brand->description ? '<p style="color:var(--ink-2);max-width:720px">'.e($brand->description).'</p>' : '' !!}
  </header>

  @if($products->isEmpty())
    @include('partials.empty', ['icon'=>'🏷️','text'=>'No '.$brand->name.' products listed yet.'])
  @else
    <div class="prod-grid">@foreach($products as $p)@include('partials.product-card', ['product' => $p])@endforeach</div>
    {{ $products->links('partials.pagination') }}
  @endif
</div>
@endsection
