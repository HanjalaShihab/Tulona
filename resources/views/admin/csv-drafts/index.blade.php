@extends('admin._shell')
@section('page-title')
Product Drafts
@endsection
@section('page')
@if(session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif
@if($errors->any())
  <div class="alert alert-err">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
@endif

<div class="steps">
  <span class="step-pill done">✓ 1. Fetch from URL / Upload CSV</span>
  <span class="step-pill now">2. Review &amp; edit each draft</span>
  <span class="step-pill">3. Post all or one-by-one</span>
</div>

@if($pendingCount > 0)
  <div class="pane" style="margin-bottom:18px">
    <form method="POST" action="{{ route('admin.csv-drafts.post-all') }}" onsubmit="return confirm('Post ALL pending drafts now? Existing products may be updated with a new offer.');">
      @csrf
      <button class="btn btn-primary">Post all pending drafts ({{ $pendingCount }})</button>
      <small style="color:var(--ink-3);display:block;margin-top:6px">Publishes every pending draft in one go. Each draft still needs a name, merchant, category and affiliate link — drafts missing those will be skipped and marked as errors to fix individually.</small>
    </form>
  </div>
@endif

<div class="pane" style="margin-bottom:18px">
  <h2 style="font-size:16px;margin-bottom:4px">Fetch from a URL</h2>
  <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
    Create editable drafts for <strong>every product</strong> on a merchant product-list page.
    Go to the <a href="{{ route('admin.imports.index') }}">Generator</a> to fetch from a URL.
  </p>
  <a class="btn btn-outline" href="{{ route('admin.imports.index') }}">Go to Generator (fetch from URL)</a>
</div>

<div class="pane" style="margin-bottom:18px">
  <h2 style="font-size:16px;margin-bottom:4px">Upload a products CSV</h2>
  <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
    Every row in the file becomes an <strong>editable draft</strong> below. Open each one,
    review/change the details, add the category and affiliate link, then <strong>post it</strong> —
    nothing is published automatically.
  </p>
  <form method="POST" action="{{ route('admin.csv-drafts.upload') }}" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    @csrf
    <input type="file" name="file" accept=".csv,.txt" required>
    <select name="default_merchant_id" style="padding:8px;border:1px solid var(--line);border-radius:6px;background:#fff">
      <option value="">Auto-detect merchant</option>
      @foreach($merchants as $m)
        <option value="{{ $m->id }}">{{ $m->name }}</option>
      @endforeach
    </select>
    <button class="btn btn-primary">Upload CSV → create drafts</button>
  </form>
  <small style="color:var(--ink-3);display:block;margin-top:8px">
    Pick a merchant above when your CSV has no <code>merchant</code> column — every row without a
    matching merchant is assigned to it (and affiliate links are built from its <code>base_affiliate_url</code>).
    Recognized columns: <code>name</code>/<code>title</code>/<code>product name</code>, <code>description</code>,
    <code>category</code>, <code>brand</code>, <code>merchant</code>/<code>seller</code>, <code>price</code>,
    <code>original price</code>/<code>mrp</code>/<code>old price</code>, <code>currency</code>,
    <code>affiliate_url</code>/<code>affiliate link</code>, <code>url</code>/<code>link</code>,
    <code>availability</code>, <code>sku</code>, <code>image</code>.
  </small>
</div>

<div class="pane">
  <h2 style="font-size:16px;margin-bottom:4px">Drafts to review</h2>
  @if($pendingCount > 0)
    <form method="POST" action="{{ route('admin.csv-drafts.destroy-all') }}" onsubmit="return confirm('Remove ALL pending drafts? Posted products are NOT touched.');" style="margin-bottom:8px">
      @csrf
      @method('DELETE')
      <button class="btn btn-outline" style="color:var(--danger)">Remove all pending drafts ({{ $pendingCount }})</button>
    </form>
  @endif
  @forelse($drafts as $draft)
    <div style="padding:10px 0;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:14px">
      <div style="min-width:0">
        <div style="font-weight:600">{{ $draft->productName() ?: '(untitled product)' }}</div>
        @if($draft->status === 'posted')
          <div style="color:#1a7f37;font-size:12.5px">✓ Posted</div>
        @elseif(! empty($draft->data['merchant_id']))
          <div style="color:var(--ink-3);font-size:12.5px">Price {{ $draft->data['current_price'] ?? '—' }} {{ $draft->data['currency'] ?? 'BDT' }}</div>
        @else
          <div style="color:var(--ink-3);font-size:12.5px">No merchant detected — pick one when you post.</div>
        @endif
      </div>
      <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
        @unless($draft->status === 'posted')
          <a class="btn btn-primary" href="{{ route('admin.csv-drafts.edit', $draft) }}">Review &amp; post</a>
        @else
          <span class="btn btn-outline" style="opacity:.6" disabled>Posted</span>
        @endunless
        <form method="POST" action="{{ route('admin.csv-drafts.destroy', $draft) }}" onsubmit="return confirm('Remove this draft?');">
          @csrf
          @method('DELETE')
          <button class="btn btn-outline" style="color:var(--danger)">✕</button>
        </form>
      </div>
    </div>
  @empty
    @include('partials.empty', ['icon' => '&#128196;', 'text' => 'No drafts yet — upload a CSV above to get started.'])
  @endforelse

  <div style="margin-top:14px">{{ $drafts->links() }}</div>
</div>

<p style="margin-top:18px"><a href="{{ route('admin.scrape-post.index') }}">← Back to Product Generator</a></p>
@endsection
