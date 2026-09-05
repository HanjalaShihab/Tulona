@extends('admin._shell')
@section('page-title')
Products
@endsection

@section('page')
<div class="ad-head">
  <div class="ad-icon">&#9638;</div>
  <div class="ad-head-text">
    <h1 class="ad-title">Products</h1>
    <div class="ad-meta">Manage the catalog &#8212; search, filter, bulk-publish or archive in one place.</div>
  </div>
  <div class="ad-head-actions">
    <a class="btn btn-outline" href="{{ route('admin.merchants.index') }}">Merchants</a>
    <a class="btn btn-primary" href="{{ route('admin.products.create') }}">&#43; New product</a>
  </div>
</div>

<div class="ad-chips">
  <div class="ad-chip"><b class="tone-brand">{{ $stats['published'] ?? 0 }}</b><span>Published</span></div>
  <div class="ad-chip"><b class="tone-accent">{{ $stats['draft'] ?? 0 }}</b><span>Drafts</span></div>
  <div class="ad-chip"><b class="tone-warn">{{ $stats['pending'] ?? 0 }}</b><span>Pending review</span></div>
  <div class="ad-chip"><b>{{ $stats['total'] ?? $products->total() }}</b><span>Total</span></div>
</div>

<div class="ad-pane">
  <form method="GET" class="toolbar" style="margin:0;border:0;border-bottom:1px solid var(--line);box-shadow:none;border-radius:0">
    <input type="text" class="input" name="q" value="{{ request('q') }}" placeholder="Search products&#8230;" aria-label="Search products" style="flex:1;min-width:160px">
    <select name="status" class="input">
      <option value="">Any status</option>
      @foreach(['draft','pending_review','published','archived'] as $s)
        <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
      @endforeach
    </select>
    <select name="category_id" class="input">
      <option value="">All categories</option>
      @foreach($categories as $c)
        <option value="{{ $c->id }}" {{ request('category_id')==(string)$c->id?'selected':'' }}>{{ $c->parent?->name.' → ' }}{{ $c->name }}</option>
      @endforeach
    </select>
    <select name="brand_id" class="input">
      <option value="">All brands</option>
      @foreach($brands as $b)
        <option value="{{ $b->id }}" {{ request('brand_id')==(string)$b->id?'selected':'' }}>{{ $b->name }}</option>
      @endforeach
    </select>
    <select name="merchant_id" class="input">
      <option value="">All merchants</option>
      @foreach($merchants as $m)
        <option value="{{ $m->id }}" {{ request('merchant_id')==(string)$m->id?'selected':'' }}>{{ $m->name }}</option>
      @endforeach
    </select>
    <button class="btn btn-outline">Filter</button>
    @if(count(request()->query()))
      <a class="btn btn-outline" href="{{ route('admin.products.index') }}">Clear</a>
    @endif
  </form>

  <form method="POST" action="{{ route('admin.products.bulk') }}" id="bulk-form">
    @csrf
    <div style="display:flex;gap:8px;align-items:center;margin:14px 20px 0;flex-wrap:wrap">
      <label style="display:flex;align-items:center;gap:6px;font-weight:600;font-size:13px">
        <input type="checkbox" id="select-all" onclick="toggleAll(this)"> Select page
      </label>
      <span style="color:var(--ink-3);font-size:13px" id="selected-count">0 selected</span>
      <select name="action" id="bulk-action" class="input" style="flex:1;min-width:150px;max-width:220px">
        <option value="">&#8212; Bulk action &#8212;</option>
        <option value="publish">Publish</option>
        <option value="unpublish">Unpublish (draft)</option>
        <option value="archive">Archive</option>
        <option value="delete">Delete permanently</option>
        <option value="category">Move to category&#8230;</option>
        <option value="brand">Set brand&#8230;</option>
      </select>
      <select name="category_id" class="input" id="bulk-category" style="min-width:160px" disabled>
        <option value="">Select category&#8230;</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}">{{ $c->parent?->name.' → ' }}{{ $c->name }}</option>
        @endforeach
      </select>
      <select name="brand_id" class="input" id="bulk-brand" style="min-width:160px" disabled>
        <option value="">Select brand&#8230;</option>
        @foreach($brands as $b)
          <option value="{{ $b->id }}">{{ $b->name }}</option>
        @endforeach
      </select>
      <button class="btn btn-outline">Apply</button>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:28px"></th>
            <th>Name</th>
            <th>Category / Brand</th>
            <th>Offers</th>
            <th>Status</th>
            <th style="width:120px"></th>
          </tr>
        </thead>
        @forelse($products as $p)
          @php($img = $p->images->firstWhere('is_main') ?: $p->images->first())
          <tr>
            <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" class="row-check"></td>
            <td>
              <div class="ad-cell-main">
                <span class="ad-thumb" style="color:var(--ink-2)">
                  @if($img)
                    <img src="{{ str_starts_with($img->path,'http') ? $img->path : asset('storage/'.$img->path) }}" alt="">
                  @else
                    {{ strtoupper(substr($p->name,0,1)) }}
                  @endif
                </span>
                <span>
                  <a href="{{ route('admin.products.edit', $p) }}"><strong>{{ $p->name }}</strong></a>
                  <small>/{{ $p->slug }}</small>
                </span>
              </div>
            </td>
            <td>
              {{ $p->category?->name }}
              <br><small style="color:var(--ink-3)">{{ $p->brand?->name ?? '—' }}</small>
            </td>
            <td>{{ $p->offers_count }}</td>
            <td>
              <span class="status-pill status-{{ $p->trashed() ? 'archived' : $p->status }}">
                {{ $p->trashed() ? 'Archived' : ucfirst(str_replace('_',' ',$p->status)) }}
              </span>
            </td>
            <td>
              <div class="ad-actions">
                <a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $p) }}">Edit</a>
                @if(!$p->trashed())
                  <button form="archive-form-{{ $p->id }}" class="btn btn-danger btn-sm" onclick="return confirm('Archive this product?')">Archive</button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="ad-table-empty">
                <b>No products found</b>
                Try clearing the filters, or add your first product.
              </div>
            </td>
          </tr>
        @endforelse
      </table>
    </div>
  </form>

  @foreach($products as $p)
    @if(!$p->trashed())
      <form id="archive-form-{{ $p->id }}" method="POST" action="{{ route('admin.products.destroy', $p) }}" hidden>
        @csrf @method('DELETE')
      </form>
    @endif
  @endforeach

  <div style="padding:16px 20px;border-top:1px solid var(--line-light)">
    {{ $products->links('partials.pagination') }}
  </div>
</div>

<script>
function toggleAll(src) {
  document.querySelectorAll('.row-check').forEach(function (c) { c.checked = src.checked; });
  updateCount();
}
function updateCount() {
  document.getElementById('selected-count').textContent = document.querySelectorAll('.row-check:checked').length + ' selected';
}
document.querySelectorAll('.row-check').forEach(function (c) { c.addEventListener('change', updateCount); });

var actionSel = document.getElementById('bulk-action'),
    catSel = document.getElementById('bulk-category'),
    brandSel = document.getElementById('bulk-brand');

actionSel.addEventListener('change', function () {
  catSel.disabled = actionSel.value !== 'category';
  brandSel.disabled = actionSel.value !== 'brand';
});

document.getElementById('bulk-form').addEventListener('submit', function (e) {
  var action = actionSel.value,
      n = document.querySelectorAll('.row-check:checked').length;
  if (!action) { e.preventDefault(); alert('Choose a bulk action.'); return; }
  if (!n) { e.preventDefault(); alert('Select at least one product.'); return; }
  if (action === 'category' && !catSel.value) { e.preventDefault(); alert('Choose a category.'); return; }
  if (action === 'brand' && !brandSel.value) { e.preventDefault(); alert('Choose a brand.'); return; }
  if ((action === 'archive' || action === 'delete') && !confirm('Apply "' + action + '" to ' + n + ' product(s)?')) e.preventDefault();
});
</script>
@endsection
