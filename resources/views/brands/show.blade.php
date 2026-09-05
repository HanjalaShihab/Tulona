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
  <div class="listing">
    <aside>
      <button class="btn btn-outline btn-sm" data-toggle="filters" aria-expanded="false" style="margin-bottom:10px;width:100%">&#9776; Filters</button>
      <div class="filters" aria-label="Product filters">
        <form method="GET" action="{{ route('brands.show', $brand->slug) }}">
          <div class="field">
            <label for="sq">Search in {{ $brand->name }}</label>
            <input type="text" id="sq" name="sq" value="{{ request('sq') }}" placeholder="e.g. Pro Max, TWS">
          </div>

          <div class="field" style="margin-top:16px">
            <label>Price range (&#2547;)</label>
            <div class="price-range">
              <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" min="0" aria-label="Minimum price">
              <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" min="0" aria-label="Maximum price">
            </div>
          </div>

          @if($categories->isNotEmpty())
            <div class="field" style="margin-top:16px">
              <label>Category</label>
              @foreach($categories as $c)
                <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-weight:500;font-size:.9rem;color:var(--color-ink-2)">
                  <input type="checkbox" name="category[]" value="{{ $c->slug }}" {{ in_array($c->slug, (array) request('category')) ? 'checked' : '' }}> {{ $c->name }}
                </label>
              @endforeach
            </div>
          @endif

          @if($merchants->isNotEmpty())
            <div class="field" style="margin-top:16px">
              <label>Store</label>
              @foreach($merchants as $m)
                <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-weight:500;font-size:.9rem;color:var(--color-ink-2)">
                  <input type="radio" name="merchant" value="{{ $m->slug }}" {{ request('merchant') === $m->slug ? 'checked' : '' }}> {{ $m->name }}
                </label>
              @endforeach
            </div>
          @endif

          <label style="display:flex;align-items:center;gap:8px;margin-top:16px;font-weight:500;font-size:.9rem;color:var(--color-ink-2)">
            <input type="checkbox" name="in_stock" value="1" {{ request()->boolean('in_stock') ? 'checked' : '' }}> In stock only
          </label>

          <button class="btn btn-primary btn-sm btn-block" type="submit" style="margin-top:16px">Apply filters</button>
          @if(request()->query())
            <a class="btn btn-outline btn-sm btn-block" href="{{ route('brands.show', $brand->slug) }}" style="margin-top:8px">Clear filters</a>
          @endif
        </form>
      </div>
    </aside>

    <div>
      <div class="sort-bar">
        <label for="sort">Sort:</label>
        <select id="sort" onchange="window.location=this.value">
          @foreach(['relevance' => 'Relevance', 'price_asc' => 'Price low to high', 'price_desc' => 'Price high to low', 'discount' => 'Highest discount', 'popular' => 'Most popular', 'rating' => 'Best rated', 'newest' => 'Newest'] as $k => $label)
            <option value="{{ Request::fullUrlWithQuery(['sort' => $k]) }}" {{ $sort === $k ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <span style="font-size:13px;color:var(--ink-3)">{{ number_format($products->total()) }} products</span>
      </div>

      @if($products->isEmpty())
        @include('partials.empty', ['icon' => '&#127991;', 'text' => 'No ' . $brand->name . ' products match these filters.'])
      @else
        <div class="prod-grid" data-reveal>
          @foreach($products as $p)
            @include('partials.product-card', ['product' => $p])
          @endforeach
        </div>
        {{ $products->links('partials.pagination') }}
      @endif
    </div>
  </div>
</div>

<script>
(function(){
  document.querySelectorAll('[data-toggle="filters"]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var panel = btn.nextElementSibling;
      if(!panel || !panel.classList.contains('filters')) panel = btn.parentElement.querySelector('.filters');
      if(!panel) return;
      var open = panel.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.innerHTML = (open ? '&#10005; Hide filters' : '&#9776; Filters');
    });
  });
})();
</script>
@endsection
