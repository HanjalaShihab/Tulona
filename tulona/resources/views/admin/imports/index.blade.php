@extends('admin._shell')
@section('page-title')
Product Generator
@endsection
@section('page')
<div class="pane" style="max-width:640px;margin-bottom:24px">
  <h2 style="font-size:16px;margin-bottom:8px">Upload a CSV</h2>
  <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
    Flow: upload → automatic validation → review errors → confirm → background processing → results.<br>
    Columns: <code>name, category_slug, brand_slug, merchant_slug, price, original_price, currency,
    availability, affiliate_url, description, gtin</code>. Duplicates by slug are matched to the existing product and updated with a new offer.
  </p>
  <form method="POST" action="{{ route('admin.imports.upload') }}" enctype="multipart/form-data">
    @csrf
    <div class="field"><label>CSV or JSON file (max 20 MB)</label><input type="file" name="file" accept=".csv,.txt,.json" required></div>
    <button class="btn btn-primary btn-sm" style="margin-top:10px">Upload & validate</button>
  </form>
</div>

<div class="pane" style="max-width:640px;margin-bottom:24px">
  <h2 style="font-size:16px;margin-bottom:8px">Generate products from a URL (live scrapes)</h2>
  <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
    Give a merchant a product-list URL (JSON feed, JSON-LD, or an HTML category page).
    The generator fetches it <strong>live</strong>, normalizes and matches each row, and stages an
    instant preview of <strong>New / Existing / Potential duplicates / Errors</strong> for review —
    none are imported until you confirm.
  </p>
  <form method="POST" action="{{ route('admin.imports.scrape') }}">
    @csrf
    <div class="field">
      <label>Merchant</label>
      <select name="merchant_id" required>
        <option value="">— select merchant —</option>
        @foreach($merchants as $m)
          <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->connector_type ?? 'generic' }})</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Category (applied to imported products)</label>
      <select name="category_id">
        <option value="">— no category (rows without one are skipped) —</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="field"><label>Product-list URL (JSON feed / JSON-LD / HTML page)</label><input type="url" name="source_url" placeholder="https://merchant.example/products" required></div>
    <button class="btn btn-primary btn-sm" style="margin-top:10px">Scrape & preview live</button>
  </form>
</div>

<h2 style="font-size:16px;margin-bottom:10px">History</h2>
<table class="data-table">
  <thead><tr><th>Source</th><th>Rows</th><th>Created / Updated / Skipped / Failed</th><th>Status</th><th>When</th><th></th></tr></thead>
  @forelse($batches as $b)
    <tr>
      <td>{{ $b->source_type === 'url' ? ($b->merchant?->name ?? 'URL') : \Illuminate\Support\Str::limit($b->filename, 34) }}</td>
      <td>{{ number_format($b->total_rows) }}</td>
      <td>{{ $b->created_count }} / {{ $b->updated_count }} / {{ $b->skipped_count }} / {{ $b->failed_count }}</td>
      <td><span class="status-pill status-{{ $b->status }}">{{ ucfirst($b->status) }}</span></td>
      <td>{{ $b->created_at->diffForHumans() }}</td>
      <td><a class="btn btn-outline btn-sm" href="{{ route('admin.imports.show', $b) }}">Details</a></td>
    </tr>
  @empty
    <tr><td colspan="6">No imports yet.</td></tr>
  @endforelse
</table>
{{ $batches->links('partials.pagination') }}
@endsection
