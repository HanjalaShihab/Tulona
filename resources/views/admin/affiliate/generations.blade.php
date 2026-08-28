@extends('admin._shell')
@section('page-title')
Generation History — {{ $affiliateOffer->product->name ?? '#' . $affiliateOffer->id }}
@endsection
@section('page')
<p style="margin-bottom:14px">
  Product: <a href="{{ route('admin.affiliate.show', $affiliateOffer) }}"><strong>{{ $affiliateOffer->product->name ?? '—' }}</strong></a> ·
  Merchant: <strong>{{ $affiliateOffer->merchant->name ?? '—' }}</strong> ·
  Status: <span class="status-pill status-{{ $affiliateOffer->status }}">{{ ucfirst($affiliateOffer->status) }}</span>
</p>

<table class="data-table">
  <thead><tr><th>When</th><th>Method</th><th>Status</th><th>Input URL</th><th>Generated URL</th><th>By</th></tr></thead>
  @forelse($generations as $g)
    <tr>
      <td>{{ $g->processed_at?->diffForHumans() ?? $g->created_at->diffForHumans() }}</td>
      <td>{{ ucfirst($g->method) }}</td>
      <td><span class="status-pill status-{{ $g->status }}">{{ ucfirst($g->status) }}</span></td>
      <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $g->input_url ?? '—' }}</td>
      <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        @if($g->generated_url)<a href="{{ $g->generated_url }}" target="_blank" rel="noopener" title="{{ $g->generated_url }}">{{ \Illuminate\Support\Str::limit($g->generated_url, 44) }}</a>
        @elseif($g->error)<span style="color:var(--danger)" title="{{ $g->error }}">{{ $g->error }}</span>
        @else <span style="color:var(--ink-3)">—</span>@endif
      </td>
      <td>{{ $g->initiator?->name ?? 'system' }}</td>
    </tr>
  @empty
    <tr><td colspan="6">No generation attempts yet.</td></tr>
  @endforelse
</table>
{{ $generations->links('partials.pagination') }}
<p style="margin-top:18px"><a href="{{ route('admin.affiliate.show', $affiliateOffer) }}">← Back to affiliate offer</a></p>
@endsection