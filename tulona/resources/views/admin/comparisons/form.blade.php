@extends('admin._shell')
@section('page-title')
{{ $comparison->exists ? 'Edit comparison: '.$comparison->title : 'New comparison' }}
@endsection
@section('page')
<form method="POST" action="{{ $comparison->exists ? route('admin.comparisons.update', $comparison) : route('admin.comparisons.store') }}" class="pane form-grid" style="max-width:980px">
  @csrf @if($comparison->exists)@method('PUT')@endif

  <div class="field"><label>Title *</label><input type="text" name="title" value="{{ old('title', $comparison->title) }}" required oninput="this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'')"></div>
  <div class="field"><label>Slug (auto)</label><input type="text" name="slug" value="{{ old('slug', $comparison->slug) }}"></div>
  <div class="field" style="grid-column:1/-1"><label>Introduction</label><textarea name="introduction" rows="2" style="width:100%">{{ old('introduction', $comparison->introduction) }}</textarea></div>
  <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="description" rows="5" style="width:100%">{{ old('description', $comparison->description) }}</textarea></div>
  <div class="field" style="grid-column:1/-1"><label>Verdict (§36)</label><textarea name="verdict" rows="4" style="width:100%">{{ old('verdict', $comparison->verdict) }}</textarea></div>
  <div class="field" style="grid-column:1/-1"><label>Notes</label><textarea name="notes" rows="3" style="width:100%">{{ old('notes', $comparison->notes) }}</textarea></div>
  <div class="field"><label>CTA text</label><input type="text" name="cta_text" value="{{ old('cta_text', $comparison->cta_text) }}" placeholder="Buy now"></div>
  <div class="field"><label>Status</label><select name="status">@foreach(['draft','published','archived'] as $s)<option {{ old('status',$comparison->status??'draft')===$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div>
  <div class="field" style="display:flex;align-items:center;gap:8px"><label>Featured</label><input type="checkbox" name="featured" value="1" style="width:auto" {{ old('featured',$comparison->featured)?'checked':'' }}></div>

  <div class="field"><label>SEO title</label><input type="text" name="seo_title" value="{{ old('seo_title', $comparison->seo_title) }}"></div>
  <div class="field"><label>SEO description</label><input type="text" name="seo_description" value="{{ old('seo_description', $comparison->seo_description) }}"></div>
  <div class="field"><label>Canonical URL</label><input type="url" name="canonical_url" value="{{ old('canonical_url', $comparison->canonical_url) }}"></div>

  <button class="btn btn-primary" style="grid-column:1/-1">💾 Save comparison</button>
</form>

@if($comparison->exists)
  <h2 style="font-size:16px;margin:26px 0 10px">Products in this comparison (§34)</h2>
  <p style="font-size:13px;color:var(--ink-2);margin-bottom:10px">Reorder by arrow buttons, add a pick label (Best Price / Best Overall Deal), and optional editorial note. Save the form above to persist product changes.</p>
  <form method="POST" action="{{ route('admin.comparisons.sync-products', $comparison) }}" style="max-width:980px" id="products-form">
    @csrf @method('PUT')
    <div class="pane" style="padding:16px;margin-bottom:16px">
      @forelse($comparison->products as $i => $p)
        <div class="prod-row" style="display:flex;gap:10px;align-items:center;border-bottom:1px solid var(--line);padding:10px 0;flex-wrap:wrap">
          <input type="hidden" name="products[{{ $p->id }}][product_id]" value="{{ $p->id }}">
          <strong style="min-width:220px">{{ $p->name }} <small style="color:var(--ink-3)">{{ $p->brand?->name }}</small></strong>
          <input type="hidden" name="products[{{ $p->id }}][sort_order]" value="{{ $p->pivot->sort_order }}">
          <input type="text" name="products[{{ $p->id }}][pick_label]" value="{{ $p->pivot->pick_label }}" placeholder="pick label (e.g. Best Price)" style="flex:1;min-width:160px">
          <input type="text" name="products[{{ $p->id }}][editorial_notes]" value="{{ $p->pivot->editorial_notes }}" placeholder="editorial note" style="flex:2;min-width:180px">

          <div style="display:flex;gap:4px">
            <button class="btn btn-outline btn-sm" type="button" onclick="moveRow(this,-1)">↑</button>
            <button class="btn btn-outline btn-sm" type="button" onclick="moveRow(this,1)">↓</button>
            <button class="btn btn-danger btn-sm" type="button" onclick="this.closest('.prod-row').remove()">✕</button>
          </div>
        </div>
      @empty
        <p style="color:var(--ink-3)">No products yet. Add some using the picker below.</p>
      @endforelse

      <div style="margin-top:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap" id="product-picker">
        <select id="pick-product">
          <option value="">+ Add product…</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}" data-name="{{ $p->name }} ({{ $p->brand?->name }})">{{ $p->name }}</option>
          @endforeach
        </select>
        <button class="btn btn-outline btn-sm" type="button" onclick="addProduct()">Add</button>
      </div>

      <div style="margin-top:14px">
        <button class="btn btn-primary">💾 Save products</button>
      </div>
    </div>
  </form>

  <h2 style="font-size:16px;margin:26px 0 10px">Merchant offer overrides (§34/§35)</h2>
  @php($allOffers = $comparison->offers()->get()->groupBy('product_id'))
  <form method="POST" action="{{ route('admin.comparisons.add-offer', $comparison) }}" class="pane" style="max-width:980px;margin-bottom:18px">
    @csrf
    <div class="field" style="grid-column:1/-1"><label>Add a merchant offer to a product</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <select name="product_id">
          @foreach($comparison->products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
        </select>
        <select name="offer_ids[]" multiple size="1" style="flex:1">
          @foreach($products as $p)
            @foreach($p->offers as $o)
              <option value="{{ $o->id }}">{{ $p->name }} · {{ $o->merchant->name }} — {{ $o->current_price }}</option>
            @endforeach
          @endforeach
        </select>
        <button class="btn btn-outline btn-sm">Attach offer</button>
      </div>
    </div>
  </form>

  <form method="POST" action="{{ route('admin.comparisons.sync-offer-overrides', $comparison) }}" style="max-width:980px">
    @csrf @method('PUT')
    <div class="pane" style="padding:16px">
      @forelse($comparison->products as $p)
        <h3 style="font-size:14px;margin:14px 0 8px;border-bottom:1px solid var(--line);padding-bottom:6px">{{ $p->name }}</h3>
        @php($pOffers = $allOffers->get($p->id, collect()))
        @forelse($pOffers as $o)
          <div class="offer-row" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;border-bottom:1px dashed var(--line);padding:8px 0">
            <input type="hidden" name="offers[{{ $o->id }}][offer_id]" value="{{ $o->id }}">
            <strong style="min-width:120px">{{ $o->merchant->name }}</strong>
            <label style="color:var(--ink-2)"><input type="checkbox" name="offers[{{ $o->id }}][is_hidden]" value="1" style="width:auto" {{ $o->pivot->is_hidden?'checked':'' }}> hidden</label>
            <label>price <input type="number" step="0.01" name="offers[{{ $o->id }}][override_price]" value="{{ $o->pivot->override_price }}" style="width:90px" placeholder="{{ $o->current_price }}"></label>
            <label>avail <select name="offers[{{ $o->id }}][override_availability]">
              <option value="">default</option>
              @foreach(['in_stock','out_of_stock','preorder','unknown'] as $a)<option {{ $o->pivot->override_availability===$a?'selected':'' }}>{{ $a }}</option>@endforeach
            </select></label>
            <label>warranty <input type="text" name="offers[{{ $o->id }}][override_warranty]" value="{{ $o->pivot->override_warranty }}" style="width:120px" placeholder="1 year"></label>
            <label>shipping <input type="text" name="offers[{{ $o->id }}][override_shipping]" value="{{ $o->pivot->override_shipping }}" style="width:120px" placeholder="Free"></label>
            <label>order <input type="number" name="offers[{{ $o->id }}][sort_order]" value="{{ $o->pivot->sort_order }}" style="width:60px"></label>
          </div>
        @empty
          <p style="color:var(--ink-3)">No offers attached for this product.</p>
        @endforelse
      @empty
        <p style="color:var(--ink-3)">Add products first to manage their merchant offers.</p>
      @endforelse
      <button class="btn btn-primary" style="margin-top:12px">💾 Save offer overrides</button>
    </div>
  </form>
@endif

<p style="margin-top:18px"><a href="{{ route('admin.comparisons.index') }}">← Back to comparisons</a></p>

<script>
function moveRow(btn, dir) {
  const row = btn.closest('.prod-row');
  const box = row.parentElement;
  const rows = [...box.querySelectorAll('.prod-row')];
  const idx = rows.indexOf(row);
  const target = idx + dir;
  if (target < 0 || target >= rows.length) return;
  box.insertBefore(row, target > idx ? rows[target].nextSibling : rows[target]);
  rows.forEach((r, i) => r.querySelector('input[name$="[sort_order]"]') && (r.querySelector('input[name$="[sort_order]"]').value = i));
}
function addProduct() {
  const sel = document.getElementById('pick-product');
  const val = sel.value, name = sel.options[sel.selectedIndex]?.dataset?.name;
  if (!val) return;
  const box = document.querySelector('#products-form .pane');
  const row = document.createElement('div');
  row.className = 'prod-row';
  row.style.cssText = 'display:flex;gap:10px;align-items:center;border-bottom:1px solid var(--line);padding:10px 0;flex-wrap:wrap';
  row.innerHTML = `<input type="hidden" name="products[${val}][product_id]" value="${val}">
    <strong style="min-width:220px">${name}</strong>
    <input type="hidden" name="products[${val}][sort_order]" value="100">
    <input type="text" name="products[${val}][pick_label]" placeholder="pick label" style="flex:1;min-width:160px">
    <input type="text" name="products[${val}][editorial_notes]" placeholder="editorial note" style="flex:2;min-width:180px">
    <div style="display:flex;gap:4px"><button class="btn btn-outline btn-sm" type="button" onclick="moveRow(this,-1)">↑</button><button class="btn btn-outline btn-sm" type="button" onclick="moveRow(this,1)">↓</button><button class="btn btn-danger btn-sm" type="button" onclick="this.closest('.prod-row').remove()">✕</button></div>`;
  box.insertBefore(row, document.getElementById('product-picker'));
  sel.selectedIndex = 0;
}
</script>
@endsection