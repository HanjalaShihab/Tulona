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
@endif

<p style="margin-top:18px"><a href="{{ route('admin.products.index') }}">← Back to products</a></p>
@endsection
