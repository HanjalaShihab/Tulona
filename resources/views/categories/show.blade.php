@extends('layouts.app')

@section('content')
<div class="premium-hero">
  <div class="container">
    @include('partials.breadcrumbs', ['items' => [
      ['name' => 'Home', 'url' => route('home')],
      ['name' => ($category->parent?->name ?? '') . ' Category', 'url' => $category->parent ? route('categories.show', $category->parent->slug) : null],
      ['name' => $category->name],
    ]])
    <h1 data-reveal>{{ $category->name }}</h1>
    @if($category->description || $category->intro_content)
      <p data-reveal data-delay="80">{!! $category->intro_content ? e($category->intro_content) : e($category->description) !!}</p>
    @else
      <p data-reveal data-delay="80">Compare {{ mb_strtolower($category->name) }} prices across trusted stores - verified history, no fake discounts.</p>
    @endif
    <div class="hero-meta" data-reveal data-delay="160">
      <span>{{ number_format($products->total()) }} products</span>
      <span>{{ $brands->count() }} brands</span>
      <span>Live store pricing</span>
    </div>
  </div>
</div>

<div class="container" style="margin-top:24px">
  @if($subcategories->isNotEmpty())
    <div class="chip-links" style="padding-bottom:8px">
      @foreach($subcategories as $sc)
        <a href="{{ route('categories.show', $sc->slug) }}">{{ $sc->name }}</a>
      @endforeach
    </div>
  @endif

  <div class="listing">
    <aside>
      <button class="btn btn-outline btn-sm" data-toggle="filters" aria-expanded="false"
              style="margin-bottom:10px;width:100%">&#9776; Filters</button>
      <div class="filters" aria-label="Product filters">
        <form method="GET" action="{{ route('categories.show', $category->slug) }}">
          <div class="field">
            <label for="sq">Search in {{ $category->name }}</label>
            <input type="text" id="sq" name="sq" value="{{ request('sq') }}" placeholder="e.g. RTX, Pro Max">
          </div>

          <div class="field" style="margin-top:16px">
            <label>Price range (&#2547;)</label>
            <div class="price-range">
              <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" min="0" aria-label="Minimum price">
              <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" min="0" aria-label="Maximum price">
            </div>
          </div>

          @if($brands->isNotEmpty())
            <div class="field" style="margin-top:16px">
              <label>Brand</label>
              @foreach($brands->take(12) as $b)
                <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-weight:500;font-size:.9rem;color:var(--color-ink-2)">
                  <input type="checkbox" name="brand[]" value="{{ $b->slug }}" {{ in_array($b->slug, (array) request('brand')) ? 'checked' : '' }}> {{ $b->name }}
                </label>
              @endforeach
            </div>
          @endif

          @if($merchants->isNotEmpty())
            <div class="field" style="margin-top:16px">
              <label>Store</label>
              @foreach($merchants->take(10) as $m)
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
        @include('partials.empty', ['icon' => '&#128269;', 'text' => 'No products match these filters yet. Try clearing filters or check back soon.'])
      @else
        <div class="prod-grid" data-reveal>
          @foreach($products as $p)
            @include('partials.product-card', ['product' => $p])
          @endforeach
        </div>
      @endif

      {{ $products->links('partials.pagination') }}
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
