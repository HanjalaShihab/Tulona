@extends('admin._shell')
@section('page-title')
Products
@endsection

@section('page')
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px;max-width:420px;flex:1">
    <input type="text" class="input" name="q" value="{{ request('q') }}" placeholder="Search products…" aria-label="Search products">
    <button class="btn btn-outline">Search</button>
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
