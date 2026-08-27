@extends('admin._shell')
@section('page-title')
Products
@endsection

@section('page')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px;flex:1;flex-wrap:wrap">
    <input type="text" class="input" name="q" value="{{ request('q') }}" placeholder="Search products…" aria-label="Search products" style="flex:1;min-width:180px">
    <select name="status" class="input"><option value="">Any status</option>@foreach(['draft','pending_review','published','archived'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
    <select name="category_id" class="input"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category_id')==(string)$c->id?'selected':'' }}>{{ $c->parent?->name.' → ' }}{{ $c->name }}</option>@endforeach</select>
    <select name="brand_id" class="input"><option value="">All brands</option>@foreach($brands as $b)<option value="{{ $b->id }}" {{ request('brand_id')==(string)$b->id?'selected':'' }}>{{ $b->name }}</option>@endforeach</select>
    <select name="merchant_id" class="input"><option value="">All merchants</option>@foreach($merchants as $m)<option value="{{ $m->id }}" {{ request('merchant_id')==(string)$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach</select>
    <input type="number" step="0.01" min="0" name="price_min" class="input" value="{{ request('price_min') }}" placeholder="Min price" style="width:110px">
    <input type="number" step="0.01" min="0" name="price_max" class="input" value="{{ request('price_max') }}" placeholder="Max price" style="width:110px">
    <button class="btn btn-outline">Filter</button>
    @if(count(request()->query()))
      <a class="btn btn-outline" href="{{ route('admin.products.index') }}">Clear</a>
    @endif
  </form>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="{{ route('admin.products.create') }}">＋ New product</a>
</div>

<div class="table-wrap">
<table class="data-table">
  <thead><tr><th>Name</th><th>Category / Brand</th><th>Offers</th><th>Status</th><th style="width:150px"></th></tr></thead>
  @forelse($products as $p)
    <tr>
      <td><a href="{{ route('admin.products.edit', $p) }}"><strong>{{ $p->name }}</strong></a><br><small style="color:var(--ink-3)">/{{ $p->slug }}</small></td>
      <td>{{ $p->category?->name }}<br><small style="color:var(--ink-3)">{{ $p->brand?->name }}</small></td>
      <td>{{ $p->offers_count }}</td>
      <td><span class="status-pill status-{{ $p->status }}{{ $p->trashed() ? ' status-archived' : '' }}">{{ $p->trashed() ? 'Archived' : ucfirst($p->status) }}</span></td>
      <td style="text-align:right">
        <a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $p) }}">Edit</a>
        @if(!$p->trashed())
        <form method="POST" action="{{ route('admin.products.destroy', $p) }}" style="display:inline"
              onsubmit="return confirm('Archive this product?')">@csrf @method('DELETE')
          <button class="btn btn-danger btn-sm">Archive</button></form>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="5">No products found.</td></tr>
  @endforelse
</table>
</div>
{{ $products->links('partials.pagination') }}
@endsection
