@extends('admin._shell')
@section('page-title')
Product Generator
@endsection
@section('page')
@if(session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif
@if($errors->any())
  <div class="alert alert-err">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
@endif

<div class="pane" style="max-width:720px;margin-bottom:24px">
  <h2 style="font-size:16px;margin-bottom:8px">Fetch multiple products from a URL (like Scrape &amp; Post, but for a whole page)</h2>
  <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
    Give a merchant product-list page (JSON feed, JSON-LD, or an HTML category page) and every product on it
    is fetched with its details + images into <strong>editable drafts</strong>. Open each draft to review,
    adjust the category/subcategory and affiliate link, then <strong>post them all together or one by one</strong>.
    Nothing is published automatically.
  </p>
  <form method="POST" action="{{ route('admin.csv-drafts.generate') }}">
    @csrf
    <div class="field">
      <label>Merchant (optional — auto-detected from the URL if left blank)</label>
      <select name="merchant_id">
        <option value="">— auto-detect —</option>
        @foreach($merchants as $m)
          <option value="{{ $m->id }}">{{ $m->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Category (applied to generated drafts)</label>
      <select name="category_id">
        <option value="">— none —</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
      <small style="color:var(--ink-3)">Pick a top-level category to pre-fill the generated drafts; you can still change it per draft.</small>
    </div>
    <div class="field"><label>Product-list URL (JSON feed / JSON-LD / HTML page)</label><input type="url" name="source_url" placeholder="https://merchant.example/products" required></div>
    <button class="btn btn-primary btn-sm" style="margin-top:10px">Fetch products → create drafts</button>
  </form>
</div>

<div class="pane" style="max-width:720px;margin-bottom:24px">
  <h2 style="font-size:16px;margin-bottom:8px">…or upload a products CSV</h2>
  <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
    Every row also becomes an editable draft in the same review/post list.
  </p>
  <form method="POST" action="{{ route('admin.csv-drafts.upload') }}" enctype="multipart/form-data">
    @csrf
    <div class="field"><label>CSV file</label><input type="file" name="file" accept=".csv,.txt" required></div>
    <button class="btn btn-primary btn-sm" style="margin-top:10px">Upload CSV → create drafts</button>
  </form>
</div>

<div class="pane">
  <h2 style="font-size:16px;margin-bottom:10px">Review &amp; post generated drafts</h2>
  <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
    Open the drafts list to review and edit each product, then post them all or individually.
  </p>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn btn-primary" href="{{ route('admin.csv-drafts.index') }}">Open drafts ({{ $draftCount }})</a>
    <a class="btn btn-outline" href="{{ route('admin.csv-drafts.export') }}" title="Download every generated product draft as a CSV spreadsheet">⬇ Download all products (CSV)</a>
  </div>
</div>
@endsection
