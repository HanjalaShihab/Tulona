@extends('admin._shell')
@section('page-title')
{{ $product->exists ? 'Edit: '.$product->name : 'New product' }}
@endsection

@section('page')
<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
  @csrf @if($product->exists)@method('PUT')@endif
  <div class="pane form-grid">
    <div class="field"><label>Name *</label><input type="text" name="name" value="{{ old('name', $product->name) }}" required></div>
    <div class="field"><label>Slug (auto if empty)</label><input type="text" name="slug" value="{{ old('slug', $product->slug) }}"></div>
    <div class="field"><label>Category *</label>
      <select name="category_id" required><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ old('category_id', $product->category_id)==$c->id ? 'selected' : '' }}>{{ $c->parent?->name.' → ' }}{{ $c->name }}</option>@endforeach</select></div>
    <div class="field"><label>Brand</label>
      <select name="brand_id"><option value="">—</option>@foreach($brands as $b)<option value="{{ $b->id }}" {{ old('brand_id', $product->brand_id)==$b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach</select></div>
    <div class="field"><label>Type</label>
      <select name="product_type">@foreach(['physical','digital'] as $t)<option value="{{ $t }}" {{ old('product_type', $product->product_type ?? 'physical')===$t?'selected':'' }}>{{ ucfirst($t) }}</option>@endforeach</select></div>
    <div class="field"><label>SKU</label><input type="text" name="sku" value="{{ old('sku', $product->sku) }}"></div>
    <div class="field"><label>Model number</label><input type="text" name="model_number" value="{{ old('model_number', $product->model_number) }}"></div>
    <div class="field"><label>GTIN / UPC / EAN</label><input type="text" name="gtin" value="{{ old('gtin', $product->gtin) }}"></div>
    <div class="field"><label>Short description</label><input type="text" name="short_description" value="{{ old('short_description', $product->short_description) }}" maxlength="500"></div>
    <div class="field"><label>Status</label>
      <select name="status">@foreach(['draft','pending_review','published','archived'] as $s)<option value="{{ $s }}" {{ old('status', $product->status ?? 'draft')===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
    <div class="field"><label>Editorial summary</label><textarea name="summary_editorial" rows="2">{{ old('summary_editorial', $product->summary_editorial) }}</textarea></div>
    <div class="field"><label>Description</label><textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea></div>
    <div class="field"><label>Rating (editorial only, 0–5)</label><input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $product->rating) }}"></div>
  </div>

  <div class="pane form-grid" style="margin-top:14px">
    <label class="check"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured)?'checked':'' }}> Featured</label>
    <label class="check"><input type="checkbox" name="is_trending" value="1" {{ old('is_trending', $product->is_trending)?'checked':'' }}> Trending</label>
    <label class="check"><input type="checkbox" name="is_editors_pick" value="1" {{ old('is_editors_pick', $product->is_editors_pick)?'checked':'' }}> Editor's Pick</label>
    <label class="check"><input type="checkbox" name="is_best_value" value="1" {{ old('is_best_value', $product->is_best_value)?'checked':'' }}> Best Value</label>
    <label class="check"><input type="checkbox" name="is_budget_pick" value="1" {{ old('is_budget_pick', $product->is_budget_pick)?'checked':'' }}> Budget Pick</label>
    <label class="check"><input type="checkbox" name="is_premium_pick" value="1" {{ old('is_premium_pick', $product->is_premium_pick)?'checked':'' }}> Premium Pick</label>
  </div>

  <button class="btn btn-primary" style="margin-top:14px">💾 Save product</button>
</form>
{{-- Attributes / specs --}}
@if($product->exists && $product->category?->attributeDefinitions->isNotEmpty())
<form method="POST" action="{{ route('admin.products.attributes', $product) }}" style="margin-top:22px">
  @csrf
  <h2 style="font-size:16px;margin-bottom:10px">Specifications ({{ $product->category->name }})</h2>
  <div class="pane form-grid">
    @foreach($product->category->attributeDefinitions as $def)
      @php($existing = $product->attributes->firstWhere('attribute_definition_id', $def->id))
      <div class="field"><label>{{ $def->name }}{{ $def->unit ? " ({$def->unit})" : '' }}</label>
        <input type="{{ $def->data_type === 'number' ? 'number' : 'text' }}" step="any"
               name="attributes[{{ $def->id }}]" value="{{ old("attributes.{$def->id}", $existing?->value_text ?? '') }}"></div>
    @endforeach
  </div>
  <button class="btn btn-outline" style="margin-top:12px">Save specifications</button>
</form>
@endif

{{-- Offers --}}
@if($product->exists)
<h2 style="font-size:16px;margin:26px 0 10px">Offers</h2>
<table class="data-table" style="margin-bottom:16px">
  <thead><tr><th>Merchant</th><th>Price</th><th>Original</th><th>Availability</th><th>Status</th><th style="width:110px"></th></tr></thead>
  @forelse($product->offers as $o)
    <tr>
      <td>{{ $o->merchant->name }}</td>
      <td>{{ \App\Support\Currency::format($o->current_price !== null ? (float)$o->current_price : null, $o->currency) }}</td>
      <td>{{ $o->original_price ? \App\Support\Currency::format((float)$o->original_price, $o->currency) : '—' }}</td>
      <td>{{ str_replace('_',' ',ucfirst($o->availability)) }}</td>
      <td><span class="status-pill status-{{ $o->status }}">{{ ucfirst($o->status) }}</span></td>
      <td><form method="POST" action="{{ route('admin.offers.destroy', $o) }}" onsubmit="return confirm('Remove offer?')">@csrf @method('DELETE')
          <button class="btn btn-danger btn-sm">Remove</button></form></td>
    </tr>
  @empty
    <tr><td colspan="6">No offers yet — add one below.</td></tr>
  @endforelse
</table>

<form method="POST" action="{{ route('admin.products.offers.store', $product) }}" class="pane form-grid">
  @csrf
  <div class="field"><label>Merchant *</label>
    <select name="merchant_id" required><option value="">—</option>@foreach($merchants as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
  <div class="field"><label>Affiliate URL *</label><input type="url" name="affiliate_url" required placeholder="https://…"></div>
  <div class="field"><label>Current price</label><input type="number" step="0.01" min="0" name="current_price"></div>
  <div class="field"><label>Original price (real discounts only)</label><input type="number" step="0.01" min="0" name="original_price"></div>
  <div class="field"><label>Currency</label><select name="currency">@foreach(['BDT','USD','INR','EUR','GBP'] as $cur)<option>{{ $cur }}</option>@endforeach</select></div>
  <div class="field"><label>Availability</label><select name="availability">@foreach(['in_stock','out_of_stock','preorder','unknown'] as $av)<option>{{ $av }}</option>@endforeach</select></div>
  <div style="grid-column:1/-1"><button class="btn btn-primary btn-sm">＋ Save offer</button></div>
</form>

{{-- Images --}}
@if($product->exists)
<h2 style="font-size:16px;margin:26px 0 10px">Images ({{ $product->images->count() }} total)</h2>
<div class="pane" style="margin-bottom:16px">
  @forelse($product->images as $i)
    <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--line)">
      <div style="width:56px;height:56px;border-radius:8px;overflow:hidden;flex:0 0 56px;background:var(--bg-2);display:flex;align-items:center;justify-content:center">
        <img src="{{ $i->path }}" alt="{{ $i->alt_text }}" style="max-width:100%;max-height:100%;object-fit:contain">
      </div>
      <form method="POST" action="{{ route('admin.images.update', $i) }}" style="display:flex;align-items:center;gap:10px;flex:1">
        @csrf @method('PUT')
        <input type="text" name="alt_text" value="{{ $i->alt_text }}" placeholder="Alt text" style="flex:1;min-width:160px">
        <button class="btn btn-outline btn-sm">Save</button>
      </form>
      <div style="display:flex;gap:6px;align-items:center">
        @if(!$i->is_main)
          <form method="POST" action="{{ route('admin.images.main', $i) }}">@csrf<button class="btn btn-outline btn-sm" title="Set as primary">★ Main</button></form>
        @else
          <span class="status-pill status-published" title="Primary image">Primary</span>
        @endif
        <form method="POST" action="{{ route('admin.images.move', $i) }}?dir=up">@csrf<button class="btn btn-outline btn-sm" title="Move up">↑</button></form>
        <form method="POST" action="{{ route('admin.images.move', $i) }}?dir=down">@csrf<button class="btn btn-outline btn-sm" title="Move down">↓</button></form>
        <form method="POST" action="{{ route('admin.images.destroy', $i) }}" onsubmit="return confirm('Remove this image?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">✕</button></form>
      </div>
    </div>
  @empty
    <p style="color:var(--ink-3)">No images yet — add one below. The first image added becomes the primary.</p>
  @endforelse
</div>

<form method="POST" action="{{ route('admin.products.images.store', $product) }}" class="pane form-grid" style="margin-bottom:16px">
  @csrf
  <div class="field" style="grid-column:1/-1"><label>Image URL or storage path</label><input type="url" name="path" required placeholder="https://… or /storage/products/foo.jpg"></div>
  <div class="field" style="grid-column:1/-1"><label>Alt text</label><input type="text" name="alt_text" placeholder="Describe the image (SEO)"></div>
  <div style="grid-column:1/-1"><button class="btn btn-primary btn-sm">＋ Add image</button></div>
</form>
@endif
@endif

<p style="margin-top:18px"><a href="{{ route('admin.products.index') }}">← Back to products</a></p>
@endsection
