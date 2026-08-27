@extends('admin._shell')
@section('page-title')
Affiliate Offers
@endsection
@section('page')
<div class="stat-cards" style="max-width:840px;margin-bottom:20px">
  <div class="stat-card"><b>{{ number_format($counts['total']) }}</b><span>Total</span></div>
  <div class="stat-card"><b>{{ number_format($counts['with_url']) }}</b><span>Have affiliate URL</span></div>
  <div class="stat-card"><b style="color:var(--accent)">{{ number_format($counts['manual']) }}</b><span>Manual</span></div>
  <div class="stat-card"><b style="color:var(--warn)">{{ number_format($counts['pending']) }}</b><span>Pending / needs link</span></div>
</div>

<div class="pane" style="max-width:840px;margin-bottom:16px">
  <form method="POST" action="{{ route('admin.affiliate.bulk-generate') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    @csrf
    <strong style="font-size:13.5px">Bulk link generation (§23):</strong>
    <select name="merchant_id" style="max-width:220px">
      <option value="">All merchants</option>
      @foreach($merchants as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
    </select>
    <button class="btn btn-primary btn-sm">⚡ Generate pending links (queue)</button>
    <span style="font-size:12px;color:var(--ink-3)">requires: php artisan queue:work --queue=imports</span>
  </form>
</div>

@if($runs->isNotEmpty())
  <div class="pane" style="max-width:840px;margin-bottom:16px;padding:16px">
    <h3 style="font-size:14px;margin:0 0 12px">Recent bulk-generation runs (§23)</h3>
    @foreach($runs as $run)
      @php($pct = $run->total > 0 ? (int) floor(($run->processed / $run->total) * 100) : 0)
      <div class="generation-run" data-run-id="{{ $run->id }}"
           data-status="{{ $run->status }}"
           data-total="{{ $run->total }}" data-processed="{{ $run->processed }}"
           data-generated="{{ $run->generated }}" data-failed="{{ $run->failed }}">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
          <strong>{{ $run->merchant?->name ?? 'All merchants' }}</strong>
          <span class="status-pill status-{{ $run->status }}">{{ ucfirst($run->status) }}</span>
        </div>
        <div style="height:8px;background:var(--line);border-radius:4px;overflow:hidden">
          <div class="progress-fill" style="height:100%;width:{{ $pct }}%;background:var(--accent);transition:width .4s"></div>
        </div>
        <div class="run-stats" style="font-size:12px;color:var(--ink-2);margin-top:6px">
          {{ $run->processed }} / {{ $run->total }} · {{ $run->generated }} generated · <span style="color:var(--danger)">{{ $run->failed }} failed</span>
        </div>
      </div>
    @endforeach
  </div>
@endif

<script>
function pollRuns() {
  document.querySelectorAll('.generation-run[data-status="queued"], .generation-run[data-status="processing"]').forEach(function (el) {
    fetch('{{ route('admin.affiliate.generation-progress', ['run' => '__ID__']) }}'.replace('__ID__', el.dataset.runId))
      .then(r => r.json())
      .then(d => {
        el.dataset.status = d.status;
        const pct = d.total > 0 ? Math.floor((d.processed / d.total) * 100) : 0;
        el.querySelector('.progress-fill').style.width = pct + '%';
        el.querySelector('.run-stats').textContent = d.processed + ' / ' + d.total + ' · ' + d.generated + ' generated · ' + d.failed + ' failed';
        const pill = el.querySelector('.status-pill');
        pill.className = 'status-pill status-' + d.status;
        pill.textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
      });
  });
}
setInterval(pollRuns, 3000);
</script>

<form method="GET" class="filter-bar" style="max-width:840px;margin-bottom:16px">
  <select name="merchant_id"><option value="">All merchants</option>@foreach($merchants as $m)<option value="{{ $m->id }}" {{ request('merchant_id')==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach</select>
  <select name="status"><option value="">All statuses</option>@foreach($statuses as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select>
  <input type="text" name="q" placeholder="Search product…" value="{{ request('q') }}">
  <button class="btn btn-primary btn-sm">Filter</button>
  @if(request()->has('merchant_id')||request()->has('status')||request()->has('q'))<a class="btn btn-outline btn-sm" href="{{ route('admin.affiliate.index') }}">Reset</a>@endif
</form>

<table class="data-table">
  <thead><tr><th>Product</th><th>Merchant</th><th>Affiliate URL</th><th>Commission</th><th>Status</th><th>Verified</th><th></th></tr></thead>
  @forelse($offers as $o)
    <tr>
      <td><a href="{{ route('admin.affiliate.show', $o) }}"><strong>{{ $o->product->name ?? '—' }}</strong></a></td>
      <td>{{ $o->merchant->name ?? '—' }}</td>
      <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        @if($o->affiliate_url)
          <a href="{{ $o->affiliate_url }}" target="_blank" rel="noopener" title="{{ $o->affiliate_url }}">{{ \Illuminate\Support\Str::limit($o->affiliate_url, 44) }}</a>
        @else
          <span style="color:var(--ink-3)">none</span>
        @endif
      </td>
      <td>{{ $o->commission_rate !== null ? $o->commission_rate.($o->commission_type==='fixed' ? ' BDT' : '%') : '—' }}</td>
      <td><span class="status-pill status-{{ $o->status }}">{{ ucfirst($o->status) }}</span></td>
      <td>{{ $o->last_verified_at?->diffForHumans() ?? '—' }}</td>
      <td><a class="btn btn-outline btn-sm" href="{{ route('admin.affiliate.edit', $o) }}">Edit</a></td>
    </tr>
  @empty
    <tr><td colspan="7">No affiliate offers found.</td></tr>
  @endforelse
</table>
{{ $offers->links('partials.pagination') }}
@endsection