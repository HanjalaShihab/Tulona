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

<div class="steps">
  <span class="step-pill now">1. Scrape</span>
  <span class="step-pill">2. Review &amp; categorize</span>
  <span class="step-pill">3. Post</span>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;align-items:start">
  <div class="pane">
    <h2 style="font-size:16px;margin-bottom:6px">Scrape a single product</h2>
    <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
      Paste a product detail page link (e.g. a Rokomari book page). Tulona fetches the
      details for that <strong>single page</strong>, then you choose the category and add
      your affiliate link before posting.
    </p>
    <form method="POST" action="{{ route('admin.scrape-post.scrape') }}">
      @csrf
      <div class="field">
        <label>Product detail URL (single page)</label>
        <input type="url" name="source_url" value="{{ old('source_url') }}" placeholder="https://www.rokomari.com/book/12345" required>
        <small style="color:var(--ink-3)">Merchant is auto-detected from the link — category and affiliate link are chosen on the next step.</small>
      </div>
      <button class="btn btn-primary" style="margin-top:10px">Scrape product details</button>
    </form>

    <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--line)">
      <small style="color:var(--ink-2);line-height:1.7;display:block">
        💡 <strong>Posting the same product again from another store?</strong> It gets matched
        automatically and all stores appear on one product page under <em>Compare Stores</em> —
        no duplicates.
      </small>
    </div>
  </div>

  <div class="pane">
    <h2 style="font-size:16px;margin-bottom:6px">Upload a CSV (many products)</h2>
    <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
      Got a spreadsheet/feed of products (e.g. an export)? Upload it here and every row becomes an
      <strong>editable draft</strong> you review and post one-by-one.
    </p>
    <a class="btn btn-primary" href="{{ route('admin.csv-drafts.index') }}">Upload CSV → review &amp; post</a>
  </div>

  <div class="pane">
    <h2 style="font-size:16px;margin-bottom:10px">Landing-page categories</h2>
    <p style="font-size:13.5px;color:var(--ink-2);margin-bottom:12px">
      Products posted into these categories appear in the <strong>Popular Categories</strong>
      section at the top of the landing page — choose one of these in step 2 to fill the
      homepage first.
    </p>
    @forelse($categories as $c)
      <div style="padding:8px 0;border-bottom:1px solid var(--line-2);display:flex;align-items:center;gap:10px">
        <span style="font-size:16px">{{ $c->icon ?? '🛍️' }}</span>
        <a href="{{ route('admin.categories.edit', $c) }}" style="font-weight:600">{{ $c->name }}</a>
        <span style="margin-left:auto;color:var(--ink-3);font-size:12.5px">{{ $c->children_count }} sub</span>
      </div>
    @empty
      @include('partials.empty', ['icon' => '&#128450;', 'text' => 'No categories yet — add some in Categories first.'])
    @endforelse
    <p style="font-size:12.5px;color:var(--ink-3);margin-top:12px">Not listed? Use “➕ Other / new category…” in step 2 to create one on the fly.</p>
  </div>
</div>

<p style="margin-top:18px"><a href="{{ route('admin.dashboard') }}">← Back to dashboard</a></p>
@endsection