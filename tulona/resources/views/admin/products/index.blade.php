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

<form method="POST" action="{{ route('admin.products.bulk') }}" id="bulk-form">
  @csrf
  <div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap">
    <label style="display:flex;align-items:center;gap:6px;font-weight:600">
      <input type="checkbox" id="select-all" onclick="toggleAll(this)"> Select page</label>
    <span style="color:var(--ink-3);font-size:13px" id="selected-count">0 selected</span>
    <select name="action" id="bulk-action" class="input" style="flex:1;min-width:140px;max-width:220px">
      <option value="">— Bulk action —</option>
      <option value="publish">Publish</option>
      <option value="unpublish">Unpublish (draft)</option>
      <option value="archive">Archive</option>
      <option value="delete">Delete permanently</option>
      <option value="category">Move to category…</option>
    </select>
    <select name="category_id" class="input" id="bulk-category" style="min-width:160px" disabled>
      <option value="">Select category…</option>
      @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->parent?->name.' → ' }}{{ $c->name }}</option>@endforeach
    </select>
    <button class="btn btn-outline">Apply</button>
  </div>

  <div class="table-wrap">
  <table class="data-table">
    <thead><tr><th style="width:28px"></th><th>Name</th><th>Category / Brand</th><th>Offers</th><th>Status</th><th style="width:150px"></th></tr></thead>
    @forelse($products as $p)
      <tr>
        <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" class="row-check"></td>
        <td><a href="{{ route('admin.products.edit', $p) }}"><strong>{{ $p->name }}</strong></a><br><small style="color:var(--ink-3)">/{{ $p->slug }}</small></td>
        <td>{{ $p->category?->name }}<br><small style="color:var(--ink-3)">{{ $p->brand?->name }}</small></td>
        <td>{{ $p->offers_count }}</td>
        <td><span class="status-pill status-{{ $p->status }}{{ $p->trashed() ? ' status-archived' : '' }}">{{ $p->trashed() ? 'Archived' : ucfirst($p->status) }}</span></td>
        <td style="text-align:right">
          <a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $p) }}">Edit</a>
          @if(!$p->trashed())
          <button form="archive-form-{{ $p->id }}" class="btn btn-danger btn-sm"
                  onclick="return confirm('Archive this product?')">Archive</button>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="6">No products found.</td></tr>
    @endforelse
  </table>
  </div>
</form>
@foreach($products as $p)
  @if(!$p->trashed())
  <form id="archive-form-{{ $p->id }}" method="POST" action="{{ route('admin.products.destroy', $p) }}" hidden>@csrf @method('DELETE')</form>
  @endif
@endforeach
{{ $products->links('partials.pagination') }}
<script>
function toggleAll(src){ document.querySelectorAll('.row-check').forEach(c => c.checked = src.checked); updateCount(); }
function updateCount(){ document.getElementById('selected-count').textContent = document.querySelectorAll('.row-check:checked').length + ' selected'; }
document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', updateCount));
const actionSel = document.getElementById('bulk-action'), catSel = document.getElementById('bulk-category');
actionSel.addEventListener('change', () => { catSel.disabled = actionSel.value !== 'category'; });
document.getElementById('bulk-form').addEventListener('submit', function(e){
  const action = actionSel.value, n = document.querySelectorAll('.row-check:checked').length;
  if(!action){ e.preventDefault(); alert('Choose a bulk action.'); return; }
  if(!n){ e.preventDefault(); alert('Select at least one product.'); return; }
  if(action === 'category' && !catSel.value){ e.preventDefault(); alert('Choose a category.'); return; }
  if((action === 'archive' || action === 'delete') && !confirm('Apply "'+action+'" to '+n+' product(s)?')) e.preventDefault();
});
</script>
@endsection
