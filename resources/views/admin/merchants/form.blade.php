@extends('admin._shell')
@section('page-title')
{{ $merchant->exists ? 'Edit merchant' : 'New merchant' }}
@endsection
@section('page')
<form method="POST" action="{{ $merchant->exists ? route('admin.merchants.update', $merchant) : route('admin.merchants.store') }}" class="pane form-grid" style="max-width:900px">
  @csrf @if($merchant->exists)@method('PUT')@endif
  <div class="field"><label>Name *</label><input type="text" name="name" value="{{ old('name', $merchant->name) }}" required></div>
  <div class="field"><label>Slug (auto if empty)</label><input type="text" name="slug" value="{{ old('slug', $merchant->slug) }}"></div>
  <div class="field"><label>Affiliate network</label>
    <select name="affiliate_network_id"><option value="">—</option>@foreach($networks as $n)<option value="{{ $n->id }}" {{ old('affiliate_network_id', $merchant->affiliate_network_id)==$n->id?'selected':'' }}>{{ $n->name }}</option>@endforeach</select></div>
  <div class="field"><label>Status</label><select name="status">@foreach(['active','inactive'] as $s)<option {{ old('status',$merchant->status ?? 'active')===$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div>
  <div class="field"><label>Connector type</label>
    <select name="connector_type"><option value="">auto (generic)</option>@foreach(['generic','url'] as $c)<option value="{{ $c }}" {{ old('connector_type',$merchant->connector_type)===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select></div>
  <div class="field"><label>Product import method</label>
    <select name="product_import_method"><option value="">—</option>@foreach(['csv','feed','api','url','scrape','html'] as $m)<option value="{{ $m }}" {{ old('product_import_method',$merchant->product_import_method)===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select></div>
  <div class="field"><label>Affiliate link method</label>
    <select name="affiliate_link_method"><option value="">—</option>@foreach(['manual','automated'] as $m)<option value="{{ $m }}" {{ old('affiliate_link_method',$merchant->affiliate_link_method)===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select></div>
  <div class="field"><label>Affiliate enabled</label>
    <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="affiliate_enabled" value="1" {{ old('affiliate_enabled',$merchant->affiliate_enabled)?'checked':'' }}> enable affiliate links</label></div>
  <div class="field"><label>Website</label><input type="url" name="website_url" value="{{ old('website_url', $merchant->website_url) }}"></div>
  <div class="field"><label>Logo URL</label><input type="url" name="logo_path" value="{{ old('logo_path', $merchant->logo_path) }}"></div>
  <div class="field"><label>Country (ISO code)</label><input type="text" name="country" value="{{ old('country', $merchant->country ?? 'BD') }}" maxlength="2"></div>
  <div class="field"><label>Region</label><input type="text" name="region" value="{{ old('region', $merchant->region) }}"></div>
  <div class="field"><label>Currencies</label><input type="text" name="currencies[]" value="{{ old('currencies.0', ($merchant->currencies[0] ?? 'BDT')) }}" placeholder="BDT"><small style="color:var(--ink-3)">comma-separated: BDT, USD…</small></div>
  <div class="field"><label>Currency #2</label><input type="text" name="currencies[]" value="{{ old('currencies.1') }}" placeholder="USD"></div>
  <div class="field" style="grid-column:1/-1"><label>Affiliate base URL</label><input type="url" name="base_affiliate_url" value="{{ old('base_affiliate_url', $merchant->base_affiliate_url) }}"></div>
  <div class="field" style="grid-column:1/-1"><label>Tracking template ({affiliate_url} / {click_id})</label><input type="text" name="tracking_template" value="{{ old('tracking_template', $merchant->tracking_template) }}"></div>
  <div class="field"><label>Commission note</label><textarea name="commission_note" rows="2">{{ old('commission_note', $merchant->commission_note) }}</textarea></div>
  <div class="field"><label>Terms / notes</label><textarea name="terms_notes" rows="2">{{ old('terms_notes', $merchant->terms_notes) }}</textarea></div>
  <div class="field" style="grid-column:1/-1"><label>Feed config JSON (API keys go in .env as FEED_SLUG_KEY / FEED_SLUG_SECRET — never here)</label>
    <textarea name="feed_config" rows="3" placeholder='{"endpoint":"https://api.example.com/products"}'>{{ old('feed_config', json_encode($merchant->feed_config)) }}</textarea></div>
  <div class="field" style="grid-column:1/-1"><label>Configuration JSON (connector/generator options; e.g. {"affiliate_generator_url":"https://..."})</label>
    <textarea name="configuration" rows="3" placeholder='{"affiliate_generator_url":"https://merchant.example.com/affiliate"}'>{{ old('configuration', $merchant->configuration ? json_encode($merchant->configuration) : '') }}</textarea></div>
  <div class="field"><label>SEO title</label><input type="text" name="seo_title" value="{{ old('seo_title', $merchant->seo_title) }}"></div>
  <div class="field"><label>SEO description</label><textarea name="seo_description" rows="2">{{ old('seo_description', $merchant->seo_description) }}</textarea></div>
  <button class="btn btn-primary">&#128190; Save merchant</button>
</form>
<p style="margin-top:14px"><a href="{{ route('admin.merchants.index') }}">← Back</a></p>
@endsection
