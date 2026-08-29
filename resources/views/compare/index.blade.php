@extends('layouts.app')

@section('content')
<div class="container" style="padding-top:22px;padding-bottom:40px">
  <header style="margin-bottom:16px">
    <h1>Compare Products</h1>
    <p style="color:var(--ink-2)">Add up to 4 products. Comparison is category-aware: phones show phone specs, GPUs show GPU specs.</p>
  </header>

  <form method="get" action="{{ route('compare.index') }}" class="form-grid" style="max-width:520px;gap:8px;margin-bottom:26px">
    <input type="text" name="products" value="{{ $slugs->implode(',') }}" placeholder="product-slug-1, product-slug-2…"
           aria-label="Product slugs to compare" style="grid-column:1/-1;padding:10px 14px;border:1px solid var(--line);border-radius:8px;font:inherit">
    <button class="btn btn-primary btn-sm" type="submit">Compare</button>
  </form>

  @if($slugs->count() > 0 && $products->count() < 2)
    @include('partials.empty', ['icon'=>'⚖️','text'=>'Could not find those products (or fewer than 2 matched). Open a product page and use "Add to compare".'])
  @elseif($products->count() >= 2)
    <div class="tbl-scroll pane" style="padding:0">
      <table class="compare-matrix" style="width:100%;border-collapse:collapse">
        <thead>
          <tr><th scope="col"></th>
          @foreach($products as $p)
            <th scope="col">
              <a href="{{ route('product.show', $p->slug) }}"><strong>{{ $p->name }}</strong></a><br>
              <small style="font-weight:400;color:var(--ink-3)">{{ $p->brand?->name }}</small>
            </th>
          @endforeach
        </tr></thead>
        <tbody>
          <tr><td>Best price</td>
            @php($prices = $products->map(fn($p)=>$p->activeOffers->whereNotNull('current_price')->min('current_price')))
            @foreach($products as $i => $p)
              <td class="{{ $prices[$i] !== null && (float)$prices[$i] === (float)$prices->min() ? 'best-cell' : '' }}">
                {{ $prices[$i] !== null ? \App\Support\Currency::format((float)$prices[$i], $p->activeOffers->first()->currency ?? 'BDT') : 'Price unavailable' }}
                @if($p->activeOffers->isNotEmpty())<br><small style="color:var(--ink-3)">{{ $p->activeOffers->count() }} store(s)</small>@endif
              </td>
            @endforeach
          </tr>
          <tr><td>Category</td>@foreach($products as $p)<td>{{ $p->category->name }}</td>@endforeach</tr>
          <tr><td>Rating</td>@foreach($products as $p)<td>{{ $p->rating ? '★ '.$p->rating.'/5 (editorial)' : '—' }}</td>@endforeach</tr>
          @forelse($attributes as $def)
            <tr>
              <td>{{ $def->name }}</td>
              @foreach($products as $p)
                @php($attr = $p->attributes->firstWhere('attribute_definition_id', $def->id))
                <td>{{ $attr ? trim(($attr->value_text ?? '').((is_numeric($attr->value_number ?? null) && $def->unit)?' '.trim($def->unit):'')) : '—' }}</td>
              @endforeach
            </tr>
          @empty
            @foreach([1=>1] as $_)
            @endforeach
          @endforelse
          <tr><td>Editorial picks</td>
            @foreach($products as $p)
              <td>
                @if($p->is_editors_pick)<span class="badge badge-pick">Editor's Pick</span>@endif
                @if($p->is_best_value)<span class="badge badge-pick">Best Value</span>@endif
                @if($p->is_budget_pick)<span class="badge badge-pick">Budget Pick</span>@endif
                @if($p->is_premium_pick)<span class="badge badge-pick">Premium Pick</span>@endif
                @if(!$p->is_editors_pick && !$p->is_best_value && !$p->is_budget_pick && !$p->is_premium_pick)—@endif
              </td>
            @endforeach
          </tr>
          <tr><td>Buy</td>
            @foreach($products as $p)
              @php($best = $p->activeOffers->whereNotNull('current_price')->sortBy(fn ($o) => (float) $o->current_price)->first())
              <td>@if($best)<a class="btn btn-primary btn-sm" rel="nofollow sponsored noopener" href="{{ route('go.redirect', [$p->slug, $best->merchant->slug]) }}">View Deal</a>
                  @else<span class="badge badge-out">Unavailable</span>@endif</td>
            @endforeach
          </tr>
        </tbody>
      </table>
    </div>
  @else
    @include('partials.empty', ['icon'=>'⚖️','text'=>'Open any product page and press “Add to compare” to build a comparison.'])
  @endif
</div>
@endsection
