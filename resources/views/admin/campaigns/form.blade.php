@extends('admin._shell')
@section('page-title')
{{ $campaign->exists ? 'Edit: '.$campaign->name : 'New Campaign' }}
@endsection

@section('page')
<style>
  .product-picker{max-height:300px;overflow-y:auto;border:1px solid var(--line);border-radius:10px;padding:8px;background:var(--surface)}
  .product-picker label{display:flex;gap:8px;align-items:center;padding:6px 8px;border-radius:6px;cursor:pointer}
  .product-picker label:hover{background:var(--surface-2)}
  .product-picker input[type=checkbox]{width:16px;height:16px}
  .pick-info{font-size:12px;color:var(--ink-3);margin-bottom:8px}
</style>

<div class="prod-form-head">
  <a class="btn btn-outline btn-sm" href="{{ route('admin.campaigns.index') }}">← Campaigns</a>
  <div class="spacer"></div>
</div>

@if(isset($errors) && $errors->any())
  <div class="alert alert-err" style="margin-bottom:14px">
    <ul style="margin:6px 0 0 18px">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ $campaign->exists ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}" class="pane" style="max-width:820px">
  @csrf @if($campaign->exists)@method('PUT')@endif

  <div class="card-head"><h3>Campaign details</h3></div>

  <div class="field" style="margin-bottom:12px">
    <label>Name *</label>
    <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required placeholder="e.g. September Tech Deals">
  </div>

  <div class="field" style="margin-bottom:12px">
    <label>Slug <span style="font-weight:400;color:var(--ink-3)">— auto if empty</span></label>
    <input type="text" name="slug" value="{{ old('slug', $campaign->slug) }}" placeholder="september-tech-deals">
  </div>

  <div class="field" style="margin-bottom:12px">
    <label>Description</label>
    <textarea name="description" rows="2" placeholder="Optional campaign description">{{ old('description', $campaign->description) }}</textarea>
  </div>

  <div class="form-grid-2" style="margin-bottom:12px">
    <div class="field">
      <label>Theme</label>
      <select name="theme">
        @foreach(['default'=>'Default','flash'=>'Flash Deal','seasonal'=>'Seasonal','clearance'=>'Clearance'] as $k=>$v)
          <option value="{{ $k }}" {{ old('theme', $campaign->theme ?? 'default')===$k?'selected':'' }}>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Priority <span style="font-weight:400;color:var(--ink-3)">— higher shows first</span></label>
      <input type="number" name="priority" value="{{ old('priority', $campaign->priority ?? 0) }}" min="0">
    </div>
  </div>

  <div class="form-grid-2" style="margin-bottom:12px">
    <div class="field">
      <label>Start date *</label>
      <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d\TH:i')) }}" required>
    </div>
    <div class="field">
      <label>End date *</label>
      <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $campaign->ends_at?->format('Y-m-d\TH:i')) }}" required>
    </div>
  </div>

  <label class="check" style="margin-bottom:16px">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $campaign->exists ? $campaign->is_active : true) ? 'checked' : '' }}>
    <span style="font-size:13.5px;font-weight:600">Active</span>
  </label>

  @if(isset($products))
  <div class="card-head" style="margin-top:16px"><h3>Products in this campaign</h3></div>
  <p class="pick-info">Select published products to include in this campaign.</p>
  <div class="product-picker">
    @forelse($products as $p)
      <label>
        <input type="checkbox" name="product_ids[]" value="{{ $p->id }}" {{ in_array($p->id, $selectedProductIds ?? []) ? 'checked' : '' }}>
        <span>
          <strong>{{ $p->name }}</strong>
          @if($p->brand)<small style="color:var(--ink-3)"> — {{ $p->brand->name }}</small>@endif
        </span>
      </label>
    @empty
      <p style="text-align:center;padding:16px;color:var(--ink-3)">No published products available. Create products first.</p>
    @endforelse
  </div>
  @endif

  <div style="display:flex;gap:10px;margin-top:16px">
    <button class="btn btn-primary">💾 Save campaign</button>
    <a class="btn btn-outline" href="{{ route('admin.campaigns.index') }}">Cancel</a>
  </div>
</form>
@endsection
