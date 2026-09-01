@extends('admin._shell')
@section('page-title')
{{ $product->exists ? 'Edit: '.$product->name : 'New product' }}
@endsection

@section('page')
<style>
  .prod-form-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
  .prod-form-head .spacer{flex:1}
  .pill-status{font-size:12px;font-weight:700;border-radius:999px;padding:4px 10px;border:1px solid var(--line)}
  .pill-draft{background:#fffbeb;border-color:#fde68a;color:#92400e}
  .pill-published{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
  .pill-archived{background:#fef2f2;border-color:#fecaca;color:#991b1b}
  .tabs-admin{display:flex;gap:4px;border-bottom:1px solid var(--line);margin-bottom:18px;overflow-x:auto}
  .tabs-admin button{padding:10px 14px;font-size:13.5px;font-weight:650;border:0;background:none;border-bottom:2px solid transparent;white-space:nowrap;cursor:pointer;color:var(--ink-2)}
  .tabs-admin button.active{color:var(--brand);border-bottom-color:var(--brand)}
  .admin-section{display:none}
  .admin-section.active{display:block}
  .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
  @media(max-width:760px){.form-grid-2,.form-grid-3{grid-template-columns:1fr}}
  .field-hint{font-size:12px;color:var(--ink-3);margin-top:4px}
  .card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
  .card-head h3{font-size:14px;font-weight:750;letter-spacing:-.01em}
  .flag-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}
  .flag-item{display:flex;gap:10px;align-items:center;background:var(--surface);border:1px solid var(--line);border-radius:10px;padding:10px 12px}
  .flag-item input{width:16px;height:16px}
  .offer-table td{vertical-align:middle}
  .img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
  .img-card{background:var(--surface);border:1px solid var(--line);border-radius:12px;overflow:hidden}
  .img-card .img-thumb{aspect-ratio:4/3;background:var(--surface-2);display:flex;align-items:center;justify-content:center;overflow:hidden}
  .img-card .img-thumb img{width:100%;height:100%;object-fit:contain;padding:8px}
  .img-card .img-actions{display:flex;gap:6px;padding:8px;border-top:1px solid var(--line-light);flex-wrap:wrap}
  .slug-row{display:flex;gap:8px;align-items:flex-end}
  .slug-row .field{flex:1}
</style>

<div class="prod-form-head">
  <a class="btn btn-outline btn-sm" href="{{ route('admin.products.index') }}">← Products</a>
  <span class="pill-status pill-{{ $product->status ?? 'draft' }}">{{ ucfirst(str_replace('_',' ', $product->status ?? 'draft')) }}</span>
  @if($product->exists)
    <a class="btn btn-outline btn-sm" href="{{ route('product.show', $product->slug) }}" target="_blank" rel="noopener">View on site ↗</a>
  @endif
  <div class="spacer"></div>
  @if($product->exists)
    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Archive this product? Offers stay history but it will be soft-deleted.')">
      @csrf @method('DELETE')
      <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#fecaca">Archive</button>
    </form>
  @endif
</div>

@if(isset($errors) && $errors->any())
  <div class="alert alert-err" style="margin-bottom:14px">
    <strong>Fix these:</strong>
    <ul style="margin:6px 0 0 18px">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form id="product-main-form" method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
  @csrf @if($product->exists)@method('PUT')@endif

  <div class="tabs-admin" role="tablist">
    <button type="button" class="active" data-tab="general">General</button>
    <button type="button" data-tab="content">Content</button>
    <button type="button" data-tab="merch">Merchandising</button>
    @if($product->exists)
      <button type="button" data-tab="offers">Offers ({{ $product->offers->count() }})</button>
      <button type="button" data-tab="media">Media ({{ $product->images->count() }})</button>
      @if($product->category?->attributeDefinitions->isNotEmpty())
        <button type="button" data-tab="specs">Specs</button>
      @endif
    @endif
  </div>

  {{-- GENERAL --}}
  <div class="admin-section active" id="tab-general">
    <div class="pane">
      <div class="card-head"><h3>Core details</h3><span class="text-meta">* required</span></div>

      <div class="field" style="margin-bottom:12px">
        <label>Name *</label>
        <input type="text" id="field-name" name="name" value="{{ old('name', $product->name) }}" required placeholder="e.g. Xiaomi Capsule In-Ear Headphones — Space Gray">
        @error('name')<div class="field-hint" style="color:var(--danger)">{{ $message }}</div>@enderror
      </div>

      <div class="slug-row" style="margin-bottom:12px">
        <div class="field">
          <label>Slug <span style="font-weight:400;color:var(--ink-3)">— auto if empty</span></label>
          <input type="text" id="field-slug" name="slug" value="{{ old('slug', $product->slug) }}" placeholder="xiaomi-capsule-in-ear-headphones-space-gray">
          <div class="field-hint">URL: /product/<span id="slug-preview">{{ $product->slug ?: 'your-product-slug' }}</span></div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" id="btn-genslug" style="height:38px">Generate</button>
      </div>

      <div class="form-grid-2">
        <div class="field"><label>Category *</label>
          <select name="category_id" required>
            <option value="">— Select category —</option>
            @foreach($categories as $c)
              <option value="{{ $c->id }}" {{ (int)old('category_id', $product->category_id) === (int)$c->id ? 'selected' : '' }}>
                {{ $c->parent?->name ? $c->parent->name.' → ' : '' }}{{ $c->name }}
              </option>
            @endforeach
          </select>
          @error('category_id')<div class="field-hint" style="color:var(--danger)">{{ $message }}</div>@enderror
        </div>
        <div class="field"><label>Brand</label>
          <select name="brand_id">
            <option value="">— No brand —</option>
            @foreach($brands as $b)
              <option value="{{ $b->id }}" {{ (int)old('brand_id', $product->brand_id) === (int)$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="form-grid-3" style="margin-top:12px">
        <div class="field"><label>Type</label>
          <select name="product_type">
            @foreach(['physical'=>'Physical','digital'=>'Digital'] as $k=>$label)
              <option value="{{ $k }}" {{ old('product_type', $product->product_type ?? 'physical')===$k?'selected':'' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="field"><label>Status</label>
          <select name="status">
            @foreach(['draft'=>'Draft','pending_review'=>'Pending review','published'=>'Published','archived'=>'Archived'] as $k=>$label)
              <option value="{{ $k }}" {{ old('status', $product->status ?? 'draft')===$k?'selected':'' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="field"><label>Editorial rating (0–5)</label>
          <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $product->rating) }}" placeholder="e.g. 4.5">
          <div class="field-hint">Shown as “★ X/5 (editorial)” on product page.</div>
        </div>
      </div>

      <div class="form-grid-3" style="margin-top:12px">
        <div class="field"><label>SKU</label><input type="text" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Optional"></div>
        <div class="field"><label>Model number</label><input type="text" name="model_number" value="{{ old('model_number', $product->model_number) }}" placeholder="e.g. HS-19"></div>
        <div class="field"><label>GTIN / UPC / EAN</label><input type="text" name="gtin" value="{{ old('gtin', $product->gtin) }}" placeholder="Barcode — used for de-duplication">
          <div class="field-hint">If set, future imports with same GTIN merge into Store Compare instead of creating duplicates.</div>
        </div>
      </div>
    </div>
  </div>

  {{-- CONTENT --}}
  <div class="admin-section" id="tab-content">
    <div class="pane">
      <div class="card-head"><h3>Content & SEO</h3></div>

      <div class="field" style="margin-bottom:12px">
        <label>Short description <span style="font-weight:400;color:var(--ink-3)">— max 500, shown on cards & SEO</span></label>
        <input type="text" name="short_description" value="{{ old('short_description', $product->short_description) }}" maxlength="500" placeholder="One-line pitch for listing cards">
        <div class="field-hint" id="sd-count">{{ mb_strlen(old('short_description', $product->short_description) ?? '') }}/500</div>
      </div>

      <div class="field" style="margin-bottom:12px">
        <label>Editorial summary</label>
        <textarea name="summary_editorial" rows="3" placeholder="2–3 sentences, your voice, why it matters. Shown under best-price box.">{{ old('summary_editorial', $product->summary_editorial) }}</textarea>
      </div>

      <div class="field">
        <label>Description <span style="font-weight:400;color:var(--ink-3)">— supports HTML, shown lower on page</span></label>
        <textarea name="description" rows="6" placeholder="Full description, features, what's in the box…">{{ old('description', $product->description) }}</textarea>
      </div>
    </div>
  </div>

  {{-- MERCH --}}
  <div class="admin-section" id="tab-merch">
    <div class="pane">
      <div class="card-head"><h3>Merchandising flags</h3><small style="color:var(--ink-3)">Controls homepage & listing placement.</small></div>
      <div class="flag-grid">
        @foreach(['is_featured'=>'★ Featured','is_trending'=>'📈 Trending','is_top_selling'=>'🏆 Top Selling','is_editors_pick'=>" Editor's Pick",'is_best_value'=>'💎 Best Value','is_budget_pick'=>'💰 Budget Pick','is_premium_pick'=>'👑 Premium Pick'] as $key=>$label)
          <label class="flag-item">
            <input type="checkbox" name="{{ $key }}" value="1" {{ old($key, $product->$key) ? 'checked' : '' }}>
            <span style="font-size:13.5px;font-weight:600">{{ $label }}</span>
          </label>
        @endforeach
      </div>
      <div class="field-hint" style="margin-top:10px">Tip: keep flags sparse — too many “featured” products dilute the homepage.</div>
    </div>
  </div>

  <div style="display:flex;gap:10px;margin-top:16px;position:sticky;bottom:0;background:linear-gradient(to top, var(--bg) 60%, transparent);padding:10px 0;z-index:2">
    <button class="btn btn-primary">💾 Save product</button>
    <a class="btn btn-outline" href="{{ route('admin.products.index') }}">Cancel</a>
    @if($product->exists)
      <span class="text-meta" style="margin-left:auto;align-self:center">ID #{{ $product->id }} • Updated {{ $product->updated_at?->diffForHumans() }}</span>
    @endif
  </div>
</form>

{{-- OFFERS (outside main form) --}}
@if($product->exists)
<div class="admin-section" id="tab-offers">
  <div class="pane">
    <div class="card-head"><h3>Store offers — Compare Stores</h3><span class="badge badge-pick">{{ $product->offers->count() }} offers</span></div>
    <p class="field-hint" style="margin-bottom:10px">Each merchant gets one row. Same product from a new store appears side-by-side on the product page (availability → price → freshness).</p>

    <div class="table-wrap" style="margin-bottom:14px">
      <table class="data-table offer-table">
        <thead><tr><th>Merchant</th><th>Price</th><th>Original</th><th>Availability</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @forelse($product->offers->sortBy(fn($o)=>[( $o->availability==='in_stock'?0:1), (float)($o->current_price ?? 999999)]) as $o)
            <tr>
              <td><strong>{{ $o->merchant->name }}</strong><br><small style="color:var(--ink-3)">{{ $o->merchant->slug }}</small></td>
              <td>@if($o->current_price!==null)<strong>{{ \App\Support\Currency::format((float)$o->current_price, $o->currency) }}</strong>@else<em style="color:var(--ink-3)">— unavailable</em>@endif<br><small style="color:var(--ink-3)">{{ $o->currency }}</small></td>
              <td>{{ $o->original_price ? \App\Support\Currency::format((float)$o->original_price, $o->currency) : '—' }}
                @if($o->discountPercent())<br><span class="badge badge-deal">-{{ round($o->discountPercent(),1) }}%</span>@endif
              </td>
              <td>
                <span class="badge {{ $o->availability==='in_stock'?'badge-drop':($o->availability==='out_of_stock'?'badge-out':'badge-stale') }}">
                  {{ str_replace('_',' ', $o->availability) }}
                </span>
              </td>
              <td><span class="status-pill status-{{ $o->status }}">{{ ucfirst($o->status) }}</span></td>
              <td style="white-space:nowrap">
                <form method="POST" action="{{ route('admin.offers.destroy', $o) }}" onsubmit="return confirm('Remove this store offer?')" style="display:inline">
                  @csrf @method('DELETE')
                  <button class="btn btn-danger btn-sm">Remove</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--ink-3);padding:22px">No offers yet — add the first store below. Future imports for the same product will merge here automatically.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <form method="POST" action="{{ route('admin.products.offers.store', $product) }}" class="pane" style="background:var(--surface-2);border-style:dashed">
      @csrf
      <h4 style="font-size:14px;margin-bottom:10px">Add / update store offer</h4>
      <p class="field-hint" style="margin-bottom:10px">If the merchant already has an offer for this product, saving updates it in place (upsert).</p>
      <div class="form-grid-2">
        <div class="field"><label>Merchant *</label>
          <select name="merchant_id" required>
            <option value="">— Select —</option>
            @foreach($merchants as $m)<option value="{{ $m->id }}">{{ $m->name }} ({{ $m->slug }})</option>@endforeach
          </select>
        </div>
        <div class="field"><label>Currency *</label>
          <select name="currency" required>
            @foreach(['BDT','USD','INR','EUR','GBP'] as $cur)<option {{ $cur==='BDT'?'selected':'' }}>{{ $cur }}</option>@endforeach
          </select>
        </div>
      </div>
      <div class="field" style="margin-top:10px"><label>Affiliate URL * <span style="font-weight:400;color:var(--ink-3)">(where “Buy now” goes)</span></label>
        <input type="url" name="affiliate_url" required placeholder="https://…">
      </div>
      <div class="form-grid-3" style="margin-top:10px">
        <div class="field"><label>Current price</label><input type="number" step="0.01" min="0" name="current_price" placeholder="Leave empty if unavailable"></div>
        <div class="field"><label>Original price</label><input type="number" step="0.01" min="0" name="original_price" placeholder="Only if real discount"></div>
        <div class="field"><label>Availability</label>
          <select name="availability">
            <option value="in_stock">In stock</option>
            <option value="out_of_stock">Out of stock</option>
            <option value="preorder">Pre-order</option>
            <option value="unknown" selected>Unknown</option>
          </select>
        </div>
      </div>
      <div style="margin-top:12px"><button class="btn btn-primary btn-sm">＋ Save offer</button></div>
    </form>
  </div>
</div>

<div class="admin-section" id="tab-media">
  <div class="pane">
    <div class="card-head"><h3>Media gallery</h3><span class="text-meta">{{ $product->images->count() }} images • drag ★ to set primary</span></div>

    @if($product->images->isEmpty())
      <div class="alert" style="background:var(--warn-light);border:1px solid #fde68a;color:#92400e">No images yet — the first image you add becomes the primary automatically.</div>
    @else
      <div class="img-grid" style="margin-bottom:14px">
        @foreach($product->images->sortBy('sort_order') as $img)
          <div class="img-card">
            <div class="img-thumb" style="position:relative">
              <img src="{{ $img->path }}" alt="{{ $img->alt_text }}">
              @if($img->is_main)<span style="position:absolute;top:8px;left:8px" class="badge badge-pick">★ Primary</span>@endif
            </div>
            <div style="padding:10px">
              <form method="POST" action="{{ route('admin.images.update', $img) }}" style="display:flex;gap:6px">
                @csrf @method('PUT')
                <input type="text" name="alt_text" value="{{ $img->alt_text }}" placeholder="Alt text (SEO)" style="flex:1;padding:7px 10px;border:1px solid var(--line);border-radius:8px;font-size:13px">
                <button class="btn btn-outline btn-sm">Save</button>
              </form>
            </div>
            <div class="img-actions">
              @if(!$img->is_main)
                <form method="POST" action="{{ route('admin.images.main', $img) }}">@csrf<button class="btn btn-outline btn-sm">★ Make primary</button></form>
              @endif
              <form method="POST" action="{{ route('admin.images.move', $img) }}?dir=up">@csrf<button class="btn btn-outline btn-sm" title="Move up">↑</button></form>
              <form method="POST" action="{{ route('admin.images.move', $img) }}?dir=down">@csrf<button class="btn btn-outline btn-sm" title="Move down">↓</button></form>
              <form method="POST" action="{{ route('admin.images.destroy', $img) }}" onsubmit="return confirm('Remove image?')" style="margin-left:auto">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">✕</button></form>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('admin.products.images.store', $product) }}" class="pane" style="background:var(--surface-2);border-style:dashed">
      @csrf
      <h4 style="font-size:14px;margin-bottom:10px">Add image</h4>
      <div class="field" style="margin-bottom:10px"><label>Image URL or storage path *</label>
        <input type="text" name="path" required placeholder="https://… or /storage/products/foo.jpg">
        <div class="field-hint">Remote URLs are kept as-is. For uploads, store in storage/app/public/products and use /storage/… path.</div>
      </div>
      <div class="field" style="margin-bottom:10px"><label>Alt text</label><input type="text" name="alt_text" placeholder="Describe the image for accessibility & SEO"></div>
      <button class="btn btn-primary btn-sm">＋ Add image</button>
    </form>
  </div>
</div>

@if($product->category?->attributeDefinitions->isNotEmpty())
<div class="admin-section" id="tab-specs">
  <form method="POST" action="{{ route('admin.products.attributes', $product) }}" class="pane">
    @csrf
    <div class="card-head"><h3>Specifications — {{ $product->category->name }}</h3><span class="text-meta">{{ $product->category->attributeDefinitions->count() }} fields</span></div>
    <p class="field-hint" style="margin-bottom:12px">Leave empty to clear a spec. Saved specs appear as “Key Specifications” on the product page and feed filtering.</p>
    <div class="form-grid-2">
      @foreach($product->category->attributeDefinitions->sortBy('sort_order') as $def)
        @php($existing = $product->attributes->firstWhere('attribute_definition_id', $def->id))
        <div class="field">
          <label>{{ $def->name }}{{ $def->unit ? " ({$def->unit})" : '' }}{{ $def->is_filterable ? ' • filterable' : '' }}</label>
          <input type="{{ $def->data_type === 'number' ? 'number' : 'text' }}" step="any"
                 name="attributes[{{ $def->id }}]" value="{{ old("attributes.{$def->id}", $existing?->value_text ?? $existing?->value_number ?? '') }}"
                 placeholder="{{ $def->data_type==='boolean' ? 'Yes / No' : ($def->unit ?: '') }}">
          @if($def->options)<div class="field-hint">Options: {{ is_array($def->options) ? implode(', ', $def->options) : $def->options }}</div>@endif
        </div>
      @endforeach
    </div>
    <button class="btn btn-outline" style="margin-top:14px">Save specifications</button>
  </form>
</div>
@endif
@endif {{-- exists --}}

@if(!$product->exists)
  <div class="pane" style="margin-top:16px;background:var(--brand-light);border-color:var(--brand-50)">
    <strong>Next steps after creating:</strong>
    <p class="field-hint">You’ll unlock Offers, Media and Specs tabs once the product exists — add at least one store offer so it appears in “Compare Stores”.</p>
  </div>
@endif

<script>
(function(){
  const tabs = document.querySelectorAll('.tabs-admin button');
  const sections = document.querySelectorAll('.admin-section');
  // restore from hash
  const hash = location.hash.replace('#','');
  if(hash && document.getElementById('tab-'+hash)){
    tabs.forEach(b=>b.classList.toggle('active', b.dataset.tab===hash));
    sections.forEach(s=>s.classList.toggle('active', s.id==='tab-'+hash));
  }
  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.dataset.tab;
      tabs.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      sections.forEach(s=>s.classList.toggle('active', s.id==='tab-'+id));
      history.replaceState(null,'','#'+id);
    });
  });
  // slug generator
  const nameEl = document.getElementById('field-name');
  const slugEl = document.getElementById('field-slug');
  const preview = document.getElementById('slug-preview');
  const genBtn = document.getElementById('btn-genslug');
  function slugify(s){ return s.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,120); }
  function updatePreview(){ if(preview) preview.textContent = slugEl.value || slugify(nameEl.value) || 'your-product-slug'; }
  if(nameEl && slugEl){
    nameEl.addEventListener('input', updatePreview);
    slugEl.addEventListener('input', updatePreview);
    if(genBtn) genBtn.addEventListener('click', ()=>{ slugEl.value = slugify(nameEl.value); updatePreview(); });
    updatePreview();
  }
  const sd = document.querySelector('input[name="short_description"]');
  const sdCount = document.getElementById('sd-count');
  if(sd && sdCount){ sd.addEventListener('input', ()=> sdCount.textContent = sd.value.length + '/500'); }
})();
</script>

@endsection
