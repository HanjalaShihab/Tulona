@extends('admin._shell')
@section('page-title')
{{ $page->exists ? 'Edit: '.$page->title : 'New landing page' }}
@endsection

@section('page')
<form method="POST" action="{{ $page->exists ? route('admin.landing-pages.update', $page) : route('admin.landing-pages.store') }}">
  @csrf @if($page->exists)@method('PUT')@endif

  <div class="pane form-grid" style="max-width:760px">
    <div class="field" style="grid-column:1/-1"><label>Title *</label><input type="text" name="title" value="{{ old('title', $page->title) }}" required></div>
    <div class="field"><label>Slug * (lowercase, hyphens)</label><input type="text" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="best-gaming-mouse-under-3000" required></div>
    <div class="field"><label>Status</label>
      <select name="status">@foreach(['draft','published'] as $s)<option value="{{ $s }}" {{ old('status',$page->status ?? 'draft')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div class="field" style="grid-column:1/-1"><label>Excerpt</label><textarea name="excerpt" rows="2">{{ old('excerpt', $page->excerpt) }}</textarea></div>
    <div class="field" style="grid-column:1/-1"><label>SEO title</label><input type="text" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}"></div>
    <div class="field" style="grid-column:1/-1"><label>SEO description</label><textarea name="seo_description" rows="2">{{ old('seo_description', $page->seo_description) }}</textarea></div>
    <div class="field"><label>Canonical URL (optional)</label><input type="url" name="canonical_url" value="{{ old('canonical_url', $page->canonical_url) }}"></div>
    <div class="field"><label>Published at</label><input type="datetime-local" name="published_at" value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}"></div>
  </div>

  {{-- Sections builder (§38) --}}
  <h2 style="font-size:16px;margin:24px 0 10px">Page sections (order as displayed)</h2>
  <p style="color:var(--ink-3);margin-bottom:12px">Leave a section's fields blank to remove it. Types: <em>hero, text, products, comparisons, faq, cta</em>.</p>

  @php
    $blocks = old('sections', $page->exists && $page->sections ? $page->sections : []);
    $blocks = is_array($blocks) ? $blocks : [];
    $blocks[] = []; // one empty block to append a new section
  @endphp

  @foreach($blocks as $i => $block)
    @php($block = is_array($block) ? $block : [])
    @php($type = $block['type'] ?? '')
    <div class="pane" style="margin-bottom:14px">
      <div class="field" style="grid-column:1/-1">
        <label>Section type</label>
        <select name="sections[{{ $i }}][type]">
          <option value="">— remove —</option>
          @foreach(['hero','text','products','comparisons','faq','cta'] as $t)
            <option value="{{ $t }}" {{ $type===$t?'selected':'' }}>{{ ucfirst($t) }}</option>
          @endforeach
        </select>
      </div>

      @if(in_array($type, ['hero','text','faq','cta'], true))
      <div class="field"><label>Heading</label><input type="text" name="sections[{{ $i }}][heading]" value="{{ $block['heading'] ?? '' }}"></div>
      @endif

      @if($type === 'hero')
      <div class="field"><label>Subheading</label><input type="text" name="sections[{{ $i }}][subheading]" value="{{ $block['subheading'] ?? '' }}"></div>
      <div class="field"><label>CTA text</label><input type="text" name="sections[{{ $i }}][cta_text]" value="{{ $block['cta_text'] ?? '' }}"></div>
      <div class="field"><label>CTA URL</label><input type="url" name="sections[{{ $i }}][cta_url]" value="{{ $block['cta_url'] ?? '' }}"></div>
      <div class="field"><label>Image URL</label><input type="url" name="sections[{{ $i }}][image_url]" value="{{ $block['image_url'] ?? '' }}"></div>
      @endif

      @if($type === 'text')
      <div class="field" style="grid-column:1/-1"><label>Body (paragraph text)</label><textarea name="sections[{{ $i }}][body]" rows="4">{{ $block['body'] ?? '' }}</textarea></div>
      @endif

      @if(in_array($type, ['products','comparisons'], true))
      <div class="field"><label>Title</label><input type="text" name="sections[{{ $i }}][title]" value="{{ $block['title'] ?? '' }}"></div>
      <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="sections[{{ $i }}][description]" rows="2">{{ $block['description'] ?? '' }}</textarea></div>
      @endif

      @if($type === 'faq')
      <div class="field" style="grid-column:1/-1"><label>FAQ items (JSON array of {question, answer})</label>
        <textarea name="sections[{{ $i }}][faq_json]" rows="4" placeholder='[{"question":"Is it in stock?","answer":"Yes."}]'>{{ is_array($block['items'] ?? null) ? json_encode($block['items']) : ($block['faq_json'] ?? '') }}</textarea></div>
      @endif

      @if($type === 'cta')
      <div class="field"><label>Text</label><input type="text" name="sections[{{ $i }}][text]" value="{{ $block['text'] ?? '' }}"></div>
      <div class="field"><label>Button text</label><input type="text" name="sections[{{ $i }}][button_text]" value="{{ $block['button_text'] ?? '' }}"></div>
      <div class="field"><label>Button URL</label><input type="url" name="sections[{{ $i }}][button_url]" value="{{ $block['button_url'] ?? '' }}"></div>
      @endif
    </div>
  @endforeach

  <div class="pane form-grid" style="max-width:760px;margin-top:10px">
    <div class="field"><label>Featured products (shown by "products" sections)</label>
      <select name="products[]" multiple size="8" style="width:100%">@foreach($products as $p)<option value="{{ $p->id }}" {{ in_array($p->id, old('products', $page->products?->pluck('id')->all() ?? []), true)?'selected':'' }}>{{ $p->name }} ({{ $p->brand?->name }})</option>@endforeach</select>
      <small style="color:var(--ink-3)">Hold Ctrl/Cmd to select multiple.</small></div>
    <div class="field"><label>Featured comparisons (shown by "comparisons" sections)</label>
      <select name="comparisons[]" multiple size="8" style="width:100%">@foreach($comparisons as $c)<option value="{{ $c->id }}" {{ in_array($c->id, old('comparisons', $page->comparisons?->pluck('id')->all() ?? []), true)?'selected':'' }}>{{ $c->title }}</option>@endforeach</select>
      <small style="color:var(--ink-3)">Hold Ctrl/Cmd to select multiple.</small></div>
  </div>

  <button class="btn btn-primary" style="margin-top:14px">💾 Save landing page</button>
</form>
<p style="margin-top:14px"><a href="{{ route('admin.landing-pages.index') }}">← Back to landing pages</a></p>
@endsection
