@extends('layouts.app')

@section('content')
<div class="container">
  @include('partials.breadcrumbs', ['items' => [['name'=>'Home','url'=>route('home')],['name'=>$merchant->name]]])
  <header style="padding-bottom:12px">
    <h1>Shopping at {{ $merchant->name }}</h1>
    <p style="color:var(--ink-2);max-width:760px">
      {{ $merchant->description ?: "Products available at {$merchant->name}, as listed on Tulona. Clicking through takes you to {$merchant->name}'s own website where you complete your purchase." }}
    </p>
    <p style="font-size:13px;color:var(--ink-3);margin-top:6px">
      {{ $productCount }} products · Country: {{ $merchant->country }} · Currencies: {{ implode(', ', $merchant->currencies) }}
      · Data last synced: {{ $merchant->last_synced_at?->diffForHumans() ?? 'recently (imported data)' }}
    </p>
    <p class="note">Tulona is an independent comparison service and is not owned by or affiliated with {{ $merchant->name }} unless explicitly stated.</p>
  </header>

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
