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