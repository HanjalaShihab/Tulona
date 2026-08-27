@extends('admin._shell')
@section('page-title')
Edit Affiliate Link: {{ $affiliateOffer->product->name ?? '#' . $affiliateOffer->id }}
@endsection
@section('page')
<form method="POST" action="{{ route('admin.affiliate.update', $affiliateOffer) }}" class="pane form-grid" style="max-width:820px">
  @csrf @method('PUT')

  <div class="field"><label>Product</label><input value="{{ $affiliateOffer->product->name }}" disabled></div>
  <div class="field"><label>Merchant</label><input value="{{ $affiliateOffer->merchant->name }}" disabled></div>

  <div class="field" style="grid-column:1/-1"><label>Normal product URL</label>
    <input type="url" name="normal_product_url" value="{{ old('normal_product_url', $affiliateOffer->normal_product_url) }}" placeholder="https://merchant.example/product/…">
    <small style="color:var(--ink-3)">The public product page (not the affiliate URL).</small>
  </div>

  <div class="field" style="grid-column:1/-1"><label>Affiliate URL</label>
    <input type="url" name="affiliate_url" value="{{ old('affiliate_url', $affiliateOffer->affiliate_url) }}" placeholder="Paste the URL from the merchant's affiliate generator">
    <small style="color:var(--ink-3)">
      §21 Workflow: <strong>Open merchant generator → paste product URL → generate → copy → paste here → Save</strong>.
      Leave blank to keep the current link. A value here creates a new generation-history entry.
    </small>
  </div>

  <div class="field"><label>Tracking identifier</label><input type="text" name="tracking_identifier" value="{{ old('tracking_identifier', $affiliateOffer->tracking_identifier) }}"></div>
  <div class="field"><label>Commission rate</label><input type="number" step="0.01" min="0" name="commission_rate" value="{{ old('commission_rate', $affiliateOffer->commission_rate) }}"></div>
  <div class="field"><label>Commission type</label>
    <select name="commission_type">
      <option value="">—</option>
      <option value="percent" {{ old('commission_type', $affiliateOffer->commission_type)==='percent'?'selected':'' }}>Percent (%)</option>
      <option value="fixed" {{ old('commission_type', $affiliateOffer->commission_type)==='fixed'?'selected':'' }}>Fixed (BDT)</option>
    </select>
  </div>
  <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="commission_eligible" value="1" style="width:auto" {{ old('commission_eligible', $affiliateOffer->commission_eligible) ? 'checked' : '' }}> Commission eligible</label></div>

  <div class="field"><label>Status</label>
    <select name="status">
      @foreach(['pending','manual','generated','failed','invalid','inactive'] as $s)
        <option value="{{ $s }}" {{ old('status', $affiliateOffer->status)===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
  </div>

  <div class="field" style="grid-column:1/-1">
    <button class="btn btn-primary">💾 Save affiliate URL</button>
    <a class="btn btn-outline" href="{{ route('admin.affiliate.show', $affiliateOffer) }}">Cancel</a>
  </div>
</form>

@php($generatorUrl = (($affiliateOffer->merchant?->configuration ?? [])['affiliate_generator_url'] ?? null))
@if($generatorUrl)
  <div class="alert alert-ok" style="max-width:820px;margin-top:16px">
    Configured generator: <a href="{{ $generatorUrl }}" target="_blank" rel="noopener">{{ $generatorUrl }}</a>
  </div>
@endif

<h2 style="font-size:16px;margin:22px 0 10px">Recent generation attempts</h2>
<table class="data-table" style="max-width:820px">
  <thead><tr><th>When</th><th>Method</th><th>Status</th><th>Output</th></tr></thead>
  @forelse($recentGenerations as $g)
    <tr>
      <td>{{ $g->processed_at?->diffForHumans() ?? $g->created_at->diffForHumans() }}</td>
      <td>{{ ucfirst($g->method) }}</td>
      <td><span class="status-pill status-{{ $g->status }}">{{ ucfirst($g->status) }}</span></td>
      <td>
        @if($g->generated_url)<a href="{{ $g->generated_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($g->generated_url, 60) }}</a>
        @elseif($g->error)<span style="color:var(--danger)">{{ $g->error }}</span>
        @else <span style="color:var(--ink-3)">—</span>@endif
      </td>
    </tr>
  @empty
    <tr><td colspan="4">No generation attempts yet.</td></tr>
  @endforelse
</table>

<p style="margin-top:18px"><a href="{{ route('admin.affiliate.index') }}">← Back to all affiliate offers</a></p>
@endsection