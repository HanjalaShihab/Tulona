@extends('admin._shell')
@section('page-title')
{{ $category->exists ? 'Edit category' : 'New category' }}
@endsection
@section('page')
<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="pane form-grid" style="max-width:820px">
  @csrf @if($category->exists)@method('PUT')@endif
  <div class="field"><label>Name *</label><input type="text" name="name" value="{{ old('name', $category->name) }}" required></div>
  <div class="field"><label>Slug (auto if empty)</label><input type="text" name="slug" value="{{ old('slug', $category->slug) }}"></div>
  <div class="field"><label>Parent category</label>
    <select name="parent_id"><option value="">None (top level)</option>
      @foreach($categories as $c)<option value="{{ $c->id }}" {{ old('parent_id', $category->parent_id)==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
  <div class="field"><label>Icon (emoji)</label><input type="text" name="icon" value="{{ old('icon', $category->icon) }}" maxlength="4"></div>
  <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
  <label class="check"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->exists ? $category->is_active : true)?'checked':'' }}> Active</label>
  <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="description" rows="2">{{ old('description', $category->description) }}</textarea></div>
  <div class="field"><label>SEO title</label><input type="text" name="seo_title" value="{{ old('seo_title', $category->seo_title) }}"></div>
  <div class="field"><label>SEO description</label><textarea name="seo_description" rows="2">{{ old('seo_description', $category->seo_description) }}</textarea></div>
  <button class="btn btn-primary">&#128190; Save</button>
</form>
<p style="margin-top:14px"><a href="{{ route('admin.categories.index') }}">← Back</a></p>
@endsection
