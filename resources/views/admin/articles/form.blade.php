@extends('admin._shell')
@section('page-title')
{{ $article->exists ? 'Edit: '.$article->title : 'New '.$article->type }}
@endsection
@section('page')
<form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" style="max-width:960px">
  @csrf @if($article->exists)@method('PUT')@endif
  <div class="pane form-grid">
    <div class="field"><label>Title *</label><input type="text" name="title" value="{{ old('title', $article->title) }}" required></div>
    <div class="field"><label>Slug (auto if empty)</label><input type="text" name="slug" value="{{ old('slug', $article->slug) }}"></div>
    <div class="field"><label>Type</label><select name="type">
      @foreach(['guide','review'] as $t)<option {{ old('type',$article->type)===$t?'selected':'' }}>{{ $t }}</option>@endforeach</select></div>
    <div class="field"><label>Category</label><select name="category_id"><option value="">—</option>
      @foreach($categories as $c)<option value="{{ $c->id }}" {{ old('category_id',$article->category_id)==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
    <div class="field"><label>Author</label><input type="text" name="author" value="{{ old('author', $article->author ?? 'Editorial Team') }}"></div>
    <div class="field"><label>Status</label><select name="status">@foreach(['draft','published'] as $s)<option {{ old('status',$article->status ?? 'draft')===$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div>
    <div class="field" style="grid-column:1/-1"><label>Excerpt</label><textarea name="excerpt" rows="2">{{ old('excerpt', $article->excerpt) }}</textarea></div>
    <div class="field" style="grid-column:1/-1"><label>Content (HTML — write well, no SEO spam)</label><textarea name="content" rows="12">{{ old('content', $article->content) }}</textarea></div>
    <div class="field"><label>Featured image URL</label><input type="url" name="featured_image" value="{{ old('featured_image', $article->featured_image) }}"></div>
    <div class="field"><label>SEO title</label><input type="text" name="seo_title" value="{{ old('seo_title', $article->seo_title) }}"></div>
    <div class="field"><label>SEO description</label><textarea name="seo_description" rows="2">{{ old('seo_description', $article->seo_description) }}</textarea></div>
    <div class="field"><label>Canonical URL (syndication)</label><input type="url" name="canonical_url" value="{{ old('canonical_url', $article->canonical_url) }}"></div>
    @foreach(old('selection_criteria', $article->selection_criteria ?? []) as $i => $crit)
      <div class="field"><label>Selection criterion #{{ $i+1 }}</label><input type="text" name="selection_criteria[{{ $i }}]" value="{{ $crit }}"></div>
    @endforeach
    <div class="field"><label>+ Selection criterion</label><input type="text" name="selection_criteria[]" placeholder="e.g. Real-world battery life"></div>
  </div>

  <h2 style="font-size:16px;margin:22px 0 10px">Recommended products in this article</h2>
  <div id="picks">
    @foreach(old('picks', $article->products->map(fn($p)=>['product_id'=>$p->id,'blurb'=>$p->pivot->blurb,'pick_label'=>$p->pivot->pick_label])->values()->all() ?: [[]]) as $i => $pick)
      <div class="pane form-grid" style="margin-bottom:12px">
        <div class="field"><label>Product</label>
          <select name="picks[{{ $i }}][product_id]"><option value="">— none —</option>
            @foreach($products as $p)<option value="{{ $p->id }}" {{ ($pick['product_id'] ?? '')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
        <div class="field"><label>Pick label</label><input type="text" name="picks[{{ $i }}][pick_label]" value="{{ $pick['pick_label'] ?? '' }}" placeholder="Best Overall / Budget Pick…"></div>
        <div class="field" style="grid-column:1/-1"><label>Why we picked it</label><input type="text" name="picks[{{ $i }}][blurb]" value="{{ $pick['blurb'] ?? '' }}"></div>
      </div>
    @endforeach
  </div>

  <button class="btn btn-primary">💾 Save article</button>
</form>
<p style="margin-top:14px"><a href="{{ route('admin.articles.index') }}">← Back</a></p>
@endsection
