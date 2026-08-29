@extends('admin._shell')
@section('page-title')
Review &amp; Post Product
@endsection
@section('page')
@if(session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif
@if($errors->any())
  <div class="alert alert-err">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
@endif

@unless(is_array($draft))
  <div class="alert alert-err">No scraped product in this session. <a href="{{ route('admin.scrape-post.index') }}">Scrape a product first</a>.</div>
@else
@php
  $selCategoryId = old('category_id', $draft['category_id'] ?? null);
  $selCategory = $selCategoryId ? ($categories['byId'][(int) $selCategoryId] ?? null) : null;
@endphp
<div class="pane" style="max-width:860px;margin-bottom:20px">
  <div class="steps">
    <span class="step-pill done">✓ 1. Scrape</span>
    <span class="step-pill now">2. Review &amp; categorize</span>
    <span class="step-pill">3. Post</span>
  </div>

  <h2 style="font-size:16px;margin-bottom:4px">{{ $draft['name'] ?: 'Untitled product' }}</h2>
  <p style="font-size:13px;color:var(--ink-3)">Source: <a href="{{ $draft['source_url'] }}" target="_blank" rel="noopener">{{ $draft['source_url'] }}</a></p>

  <form method="POST" action="{{ route('admin.scrape-post.post') }}" class="form-grid" id="post-product-form" style="margin-top:14px">
    @csrf

    <h4 style="grid-column:1/-1;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);margin:6px 0 2px">Product</h4>
    <div class="field" style="grid-column:1/-1">
      <label>Image</label>
      <div style="display:flex;align-items:flex-start;gap:12px">
        @if(! empty($draft['image'] ?? null))
          <img src="{{ $draft['image'] }}" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:8px;background:var(--bg-2)">
        @endif
        <input type="url" name="image" value="{{ old('image', $draft['image'] ?? '') }}" placeholder="https://.../image.jpg">
      </div>
    </div>
    <div class="field" style="grid-column:1/-1"><label>Name *</label><input type="text" name="name" value="{{ old('name', $draft['name'] ?? '') }}" required></div>
    <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="description" rows="4">{{ old('description', $draft['description'] ?? '') }}</textarea></div>
    <div class="field"><label>SKU / product code</label><input type="text" name="sku" value="{{ old('sku', $draft['sku'] ?? '') }}"></div>

    <h4 style="grid-column:1/-1;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);margin:14px 0 2px">Price &amp; availability</h4>
    <div class="field"><label>Price (৳)</label><input type="number" step="0.01" min="0" name="current_price" value="{{ old('current_price', $draft['price'] ?? '') }}"></div>
    <div class="field"><label>Original price (৳)</label><input type="number" step="0.01" min="0" name="original_price" value="{{ old('original_price', $draft['original_price'] ?? '') }}"></div>
    <div class="field"><label>Currency</label><input type="text" name="currency" value="{{ old('currency', $draft['currency'] ?? 'BDT') }}" maxlength="3"></div>
    <div class="field">
      <label>Availability</label>
      <select name="availability">
        @foreach(['in_stock','out_of_stock','preorder','unknown'] as $av)
          <option value="{{ $av }}" {{ old('availability', $draft['availability'] ?? 'unknown') === $av ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($av)) }}</option>
        @endforeach
      </select>
    </div>

    <h4 style="grid-column:1/-1;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);margin:14px 0 2px">Store &amp; category</h4>
    <div class="field"><label>Merchant *</label>
      <select name="merchant_id" required>
        @foreach($merchants as $m)
          <option value="{{ $m->id }}" {{ old('merchant_id', $draft['merchant_id'] ?? '') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
        @endforeach
      </select>
    </div>
    @include('partials.category-cascade', ['categoryTree' => $categories, 'selCategory' => $selCategory])
    <div class="field" style="grid-column:1/-1"><label>Affiliate link *</label><input type="url" name="affiliate_url" value="{{ old('affiliate_url', $draft['affiliate_url'] ?? '') }}" placeholder="https://track.rokkomari.example/?pid=..." required><small style="color:var(--ink-3)">Tracked link used by the "Buy from «Merchant»" button on the product page.</small></div>

    <h4 style="grid-column:1/-1;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);margin:14px 0 2px">Show on homepage</h4>
    <div class="field" style="grid-column:1/-1;display:flex;gap:20px;flex-wrap:wrap;align-items:center">
      <label class="check"><input type="checkbox" name="is_trending" value="1" {{ old('is_trending') ? 'checked' : '' }}> Trending</label>
      <label class="check"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}> Featured</label>
      <label class="check"><input type="checkbox" name="is_top_selling" value="1" {{ old('is_top_selling') ? 'checked' : '' }}> Top selling</label>
      <small style="color:var(--ink-3);width:100%">Tick any section to showcase this product on the landing page. When adding another store to an existing product, these only ever promote — they never hide an existing section flag.</small>
    </div>

    <div style="grid-column:1/-1;display:flex;gap:10px;margin-top:6px">
      <button class="btn btn-primary">Post product</button>
      <button type="submit" class="btn btn-outline" style="color:var(--danger)" formaction="{{ route('admin.scrape-post.reset') }}" formmethod="POST" onclick="return confirm('Remove this scraped product without posting?');">Remove / New search</button>
    </div>
  </form>
</div>
@endif

<p style="margin-top:18px"><a href="{{ route('admin.scrape-post.index') }}">← Back to scrape</a></p>
@endsection