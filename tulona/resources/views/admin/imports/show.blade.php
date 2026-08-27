@extends('admin._shell')
@section('page-title')
Generator: {{ $batch->source_type === 'url' ? 'URL scrape #'.$batch->id : $batch->filename }}
@endsection
@section('page')
@if(session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif
@if($errors->any())
  <div class="alert alert-err">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
@endif
@if($batch->source_type === 'url')
  {{-- URL scrape flow (§14/§16) --}}
  <p style="margin-bottom:14px">
    Source: <strong>{{ $batch->source_url }}</strong> ·
    Merchant: <strong>{{ $batch->merchant?->name ?? '—' }}</strong> ·
    @if($batch->category_slug)<span>Category: <strong>{{ \App\Models\Category::where('slug', $batch->category_slug)->value('name') ?? $batch->category_slug }}</strong></span> ·@endif
    Status: <span class="status-pill status-{{ $batch->status }}">{{ ucfirst($batch->status) }}</span>
  </p>

  @if($batch->status === 'queued')
    <div class="alert alert-ok">Scraper fetching the source — refresh this page to see results.</div>
  @elseif($batch->status === 'preview')
    @php
      $n = fn ($s) => $batch->items->where('status', $s)->count();
      $total = max($batch->items->count(), 1);
    @endphp
    <div class="stat-cards" style="margin-top:0;max-width:840px">
      <div class="stat-card"><b>{{ number_format($batch->items->count()) }}</b><span>Total</span></div>
      <div class="stat-card"><b style="color:var(--accent)">{{ number_format($n('new')) }}</b><span>New</span></div>
      <div class="stat-card"><b>{{ number_format($n('matched')) }}</b><span>Existing</span></div>
      <div class="stat-card"><b style="color:var(--warn)">{{ number_format($n('duplicate')) }}</b><span>Potential dup.</span></div>
      <div class="stat-card"><b style="color:var(--danger)">{{ number_format($n('error')) }}</b><span>Errors</span></div>
    </div>

    <div style="margin-top:16px;display:flex;gap:8px">
      <form method="POST" action="{{ route('admin.imports.confirm', $batch) }}">
        @csrf
        <button class="btn btn-primary">Import All ({{ number_format($batch->items->whereNotIn('status', ['error','skipped'])->count()) }})</button>
      </form>
      <form method="POST" action="{{ route('admin.imports.cancel', $batch) }}">
        @csrf
        <button class="btn btn-outline" style="color:var(--danger)">Cancel</button>
      </form>
    </div>

    <form id="selected-form" method="POST" action="{{ route('admin.imports.selected', $batch) }}" style="margin-top:16px">
      @csrf
      <table class="data-table">
        <thead><tr>
          <th style="width:28px"><input type="checkbox" onclick="document.querySelectorAll('[data-item]').forEach(c=>c.checked=this.checked)"></th>
          <th>Product</th><th>Merchant SKU</th><th>Price</th><th>Match</th><th>Status</th><th>Error</th>
        </tr></thead>
        <tbody>
        @foreach($items as $item)
          <tr>
            <td>
              @if(! in_array($item->status, ['error', 'skipped']))
                <input type="checkbox" name="items[]" value="{{ $item->id }}" data-item checked>
              @endif
            </td>
            <td>
              {{ $item->normalized_data['name'] ?? '—' }}
              @if($item->product_id)
                <div class="note">→ {{ ($item->product->name ?? 'existing product') }} (id #{{ $item->product_id }})</div>
              @endif
            </td>
            <td>{{ $item->normalized_data['sku'] ?? $item->normalized_data['model_number'] ?? '—' }}</td>
            <td>{{ $item->normalized_data['price'] ?? '—' }}</td>
            <td><span class="badge {{ $item->match_type === 'name' ? 'badge-stale' : 'badge-deal' }}">{{ $item->match_type ?? '—' }}</span></td>
            <td><span class="status-pill status-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
            <td style="font-size:12.5px;color:var(--danger)">{{ $item->error ?? '' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div style="margin-top:10px">{{ $items->links() }}</div>
      <button class="btn btn-primary btn-sm" style="margin-top:10px">Import selected items</button>
    </form>
  @elseif($batch->status === 'processing')
    <div class="alert alert-ok">
      Import is running in the background ({{ number_format($batch->imported_count + $batch->failed_count) }} / {{ number_format($batch->total_rows) }} rows done). Refresh for progress.
    </div>
  @elseif($batch->status === 'completed')
    <div class="stat-cards" style="max-width:840px">
      <div class="stat-card"><b>{{ number_format($batch->imported_count) }}</b><span>Imported</span></div>
      <div class="stat-card"><b>{{ number_format($batch->created_count) }}</b><span>Created</span></div>
      <div class="stat-card"><b>{{ number_format($batch->updated_count) }}</b><span>Updated</span></div>
      <div class="stat-card"><b style="color:var(--danger)">{{ number_format($batch->failed_count) }}</b><span>Failed</span></div>
    </div>
  @elseif(in_array($batch->status, ['failed', 'cancelled']))
    <div class="alert alert-err">{{ $batch->status === 'cancelled' ? 'This import was cancelled.' : 'This scrape failed — see the reasons below.' }}</div>
    @if($batch->items->where('status','error')->count())
      <div class="error-preview">
        <h2 style="font-size:15px;margin-bottom:10px">Row errors ({{ $batch->items->where('status','error')->count() }})</h2>
        <table class="data-table">
          <thead><tr><th>Product</th><th>Error</th></tr></thead>
          @foreach($batch->items->where('status','error')->take(100) as $item)
            <tr><td>{{ $item->normalized_data['name'] ?? '—' }}</td><td>{{ $item->error }}</td></tr>
          @endforeach
        </table>
      </div>
    @endif
  @endif

@else

  {{-- Legacy CSV/file flow (§67) --}}
  <div class="steps">
    <span class="step-pill done">1 · Upload ✓</span>
    <span class="step-pill done">2 · Validate ✓</span>
    <span class="step-pill {{ in_array($batch->status, ['processing','completed']) ? 'done' : 'now' }}">3 · Process</span>
    <span class="step-pill {{ $batch->status === 'completed' ? 'done now' : '' }}">4 · Results</span>
  </div>

  <p style="margin-bottom:14px">
    File: <strong>{{ $batch->filename }}</strong> · Rows: <strong>{{ number_format($batch->total_rows) }}</strong> ·
    Status: <span class="status-pill status-{{ $batch->status }}">{{ ucfirst($batch->status) }}</span>
  </p>

  @unless($batch->status==='failed')
    @if($preview && $batch->status === 'validated')
      <div class="alert alert-ok">
        Validation passed. Ready to import {{ number_format($batch->total_rows) }} rows.
        Processing runs in the background via the queue worker (php artisan queue:work).
      </div>
      <form method="POST" action="{{ route('admin.imports.confirm', $batch) }}">
        @csrf
        <button class="btn btn-primary">▶ Confirm & start import</button>
      </form>
    @elseif($batch->status === 'processing')
      <div class="alert alert-ok">Import is running in the background. Refresh this page for results.</div>
    @endif
  @endunless

  @if($batch->status === 'failed')
    <div class="alert alert-err">Validation found blocking errors below. Fix them and re-upload.</div>
  @endif

  @if($batch->status === 'completed')
    <div class="stat-cards" style="margin-top:16px;max-width:720px">
      <div class="stat-card"><b>{{ number_format($batch->imported_count) }}</b><span>Imported</span></div>
      <div class="stat-card"><b>{{ number_format($batch->created_count) }}</b><span>Created</span></div>
      <div class="stat-card"><b>{{ number_format($batch->updated_count) }}</b><span>Updated</span></div>
      <div class="stat-card"><b>{{ number_format($batch->skipped_count) }}</b><span>Skipped</span></div>
      <div class="stat-card"><b style="{{ $batch->failed_count ? 'color:var(--danger)' : '' }}">{{ number_format($batch->failed_count) }}</b><span>Failed</span></div>
    </div>
  @endif

  @if($batch->errors->isNotEmpty())
    <div class="error-preview">
      <h2 style="font-size:15px;margin-bottom:10px">Validation errors & warnings ({{ $batch->errors->count() }})</h2>
      <table class="data-table">
        <thead><tr><th>Row</th><th>Field</th><th>Message</th><th>Severity</th></tr></thead>
        @foreach($batch->errors->take(200) as $err)
          <tr>
            <td>{{ $err->row_number ?? '—' }}</td>
            <td>{{ $err->field ?? '—' }}</td>
            <td>{{ $err->message }}</td>
            <td><span class="badge {{ $err->severity === 'error' ? 'badge-deal' : 'badge-stale' }}">{{ strtoupper($err->severity) }}</span></td>
          </tr>
        @endforeach
      </table>
      @if($batch->errors->count() > 200)<p class="note">Showing first 200 of {{ $batch->errors->count() }}.</p>@endif
    </div>
  @endif

@endif

<p style="margin-top:18px"><a href="{{ route('admin.imports.index') }}">← Back to imports</a></p>
@endsection