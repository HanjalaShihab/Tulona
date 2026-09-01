@extends('layouts.app')

@section('content')
<div class="container">
  @include('partials.breadcrumbs', ['items' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => ($category->parent?->name ?? '').' Category', 'url' => $category->parent ? route('categories.show', $category->parent->slug) : null],
    ['name' => $category->name],
  ]])

  <header style="padding-bottom:10px">
    <h1>{{ $category->name }}</h1>
    {!! $category->intro_content ? '<p style="color:var(--ink-2);max-width:760px">'.e($category->description).'</p>' : ($category->description ? '<p style="color:var(--ink-2);max-width:760px">'.e($category->description).'</p>' : '') !!}
  </header>

  @if($subcategories->isNotEmpty())
    <div class="chip-links" style="padding-bottom:8px">
      @foreach($subcategories as $sc)<a href="{{ route('categories.show', $sc->slug) }}">{{ $sc->name }}</a>@endforeach
    </div>
  @endif

  <div class="listing">
    <aside>
      <button class="btn btn-outline btn-sm" data-toggle="filters" aria-expanded="false"
              style="margin-bottom:10px;width:100%">☰ Filters</button>
      <div class="filters" aria-label="Product filters">
        <form method="GET" action="{{ route('categories.show', $category->slug) }}">
          <h3>Search in {{ $category->name }}</h3>
          <input type="text" name="sq" value="{{ request('sq') }}" placeholder="e.g. RTX, Pro Max…" style="width:100%;padding:9px;border:1px solid var(--line);border-radius:8px;font:inherit">

          <h3>Price range (৳)</h3>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" min="0" style="width:50%;padding:8px;border:1px solid var(--line);border-radius:8px">
            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" min="0" style="width:50%;padding:8px;border:1px solid var(--line);border-radius:8px">
          </div>

          <h3>Brand</h3>
          @foreach($brands->take(12) as $b)
            <label><input type="checkbox" name="brand[]" value="{{ $b->slug }}" {{ in_array($b->slug, (array) request('brand')) ? 'checked' : '' }}> {{ $b->name }}</label>
          @endforeach

          <h3>Store</h3>
          @foreach($merchants->take(10) as $m)
            <label><input type="radio" name="merchant" value="{{ $m->slug }}" {{ request('merchant') === $m->slug ? 'checked' : '' }}> {{ $m->name }}</label>
          @endforeach

          <label style="margin-top:10px"><input type="checkbox" name="in_stock" value="1" {{ request()->boolean('in_stock') ? 'checked' : '' }}> In stock only</label>

          <button class="btn btn-primary btn-sm btn-block" type="submit" style="margin-top:12px">Apply filters</button>
        </form>
      </div>
    </aside>

    <div>
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
        @include('partials.empty', ['icon'=>'🔍','text'=>'No products match these filters yet. Try clearing filters or check back soon.'])
      @else
        <div class="prod-grid">
          @foreach($products as $p)@include('partials.product-card', ['product' => $p])@endforeach
        </div>
      @endif

      {{ $products->links('partials.pagination') }}


    </div>
  </div>
</div>
@endsection
