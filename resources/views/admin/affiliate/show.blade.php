@extends('admin._shell')
@section('page-title')
Affiliate Offer: {{ $affiliateOffer->product->name ?? '#' . $affiliateOffer->id }}
@endsection
@section('page')
<div class="pane" style="max-width:820px;margin-bottom:18px">
  <p><strong>Merchant:</strong> {{ $affiliateOffer->merchant->name ?? '—' }}</p>
  <p><strong>Status:</strong> <span class="status-pill status-{{ $affiliateOffer->status }}">{{ ucfirst($affiliateOffer->status) }}</span></p>
  <p><strong>Normal product URL:</strong>
    @if($affiliateOffer->normal_product_url)<a href="{{ $affiliateOffer->normal_product_url }}" target="_blank" rel="noopener">{{ $affiliateOffer->normal_product_url }}</a>
    @else <span style="color:var(--ink-3)">—</span>@endif
  </p>
  <p><strong>Affiliate URL:</strong>
    @if($affiliateOffer->affiliate_url)<a href="{{ $affiliateOffer->affiliate_url }}" target="_blank" rel="noopener">{{ $affiliateOffer->affiliate_url }}</a>
    @else <span style="color:var(--danger)">no affiliate link yet</span>@endif
  </p>
  <p><strong>Commission:</strong> {{ $affiliateOffer->commission_rate !== null ? $affiliateOffer->commission_rate.($affiliateOffer->commission_type==='fixed'?' BDT':'%') : '—' }} · eligible {{ $affiliateOffer->commission_eligible ? 'Yes' : 'No' }}</p>

  <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
    <a class="btn btn-primary btn-sm" href="{{ route('admin.affiliate.edit', $affiliateOffer) }}">✏️ Edit / paste affiliate URL</a>
    <form method="POST" action="{{ route('admin.affiliate.open-generator', $affiliateOffer) }}">@csrf
      <button class="btn btn-outline btn-sm" title="{{ (($affiliateOffer->merchant->configuration ?? [])['affiliate_generator_url'] ?? null) ?: 'No generator configured' }}">🔗 Open merchant generator</button>
    </form>
    <form method="POST" action="{{ route('admin.affiliate.verify', $affiliateOffer) }}">@csrf
      <button class="btn btn-outline btn-sm">✓ Mark verified</button>
    </form>
    <a class="btn btn-outline btn-sm" href="{{ route('admin.affiliate.generations', $affiliateOffer) }}">🕓 Generation history ({{ $affiliateOffer->generations->count() }})</a>
  </div>

  @if($affiliateOffer->last_error)
    <div class="alert alert-err" style="margin-top:14px">Last error: {{ $affiliateOffer->last_error }}</div>
  @endif
</div>

<h2 style="font-size:16px;margin-bottom:10px">Recent generation attempts</h2>
<table class="data-table" style="max-width:820px">
  <thead><tr><th>When</th><th>Method</th><th>Status</th><th>Input / Generated</th><th>By</th></tr></thead>
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
      <td>{{ $g->initiator?->name ?? 'system' }}</td>
    </tr>
  @empty
    <tr><td colspan="5">No generation attempts yet.</td></tr>
  @endforelse
</table>

<p style="margin-top:18px"><a href="{{ route('admin.affiliate.index') }}">← Back to all affiliate offers</a></p>
@endsection