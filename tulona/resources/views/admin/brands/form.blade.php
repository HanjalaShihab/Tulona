@extends('admin._shell')
@section('page-title')
{{ $brand->exists ? 'Edit brand' : 'New brand' }}
@endsection
@section('page')
<form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" class="pane form-grid" style="max-width:760px">
  @csrf @if($brand->exists)@method('PUT')@endif
  <div class="field"><label>Name *</label><input type="text" name="name" value="{{ old('name', $brand->name) }}" required></div>
  <div class="field"><label>Slug (auto if empty)</label><input type="text" name="slug" value="{{ old('slug', $brand->slug) }}"></div>
  <div class="field"><label>Logo URL</label><input type="url" name="logo_path" value="{{ old('logo_path', $brand->logo_path) }}"></div>
  <div class="field"><label>Website</label><input type="url" name="website_url" value="{{ old('website_url', $brand->website_url) }}"></div>
  <div class="field" style="grid-column:1/-1"><label>Description</label><textarea name="description" rows="3">{{ old('description', $brand->description) }}</textarea></div>
  <div class="field"><label>SEO title</label><input type="text" name="seo_title" value="{{ old('seo_title', $brand->seo_title) }}"></div>
  <div class="field"><label>SEO description</label><textarea name="seo_description" rows="2">{{ old('seo_description', $brand->seo_description) }}</textarea></div>
  <button class="btn btn-primary">💾 Save</button>
</form>
<p style="margin-top:14px"><a href="{{ route('admin.brands.index') }}">← Back</a></p>
@endsection
