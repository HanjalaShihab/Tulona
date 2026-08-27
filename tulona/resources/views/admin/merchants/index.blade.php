@extends('admin._shell')
@section('page-title')
Merchants
@endsection
@section('page')
<table class="data-table">
  <thead><tr><th>Name</th><th>Network</th><th>Products</th><th>Offers</th><th>Country</th><th>Last sync</th><th>Status</th><th style="width:180px"></th></tr></thead>
  @forelse($merchants as $m)
    <tr>
      <td><a href="{{ route('admin.merchants.edit', $m) }}"><strong>{{ $m->name }}</strong></a></td>
      <td>{{ $m->network?->name ?? '—' }}</td>
      <td>{{ $m->product_count }}</td>
      <td>{{ $m->offers_count }}</td>
      <td>{{ $m->country }}</td>
      <td>{{ $m->last_synced_at?->diffForHumans() ?? 'never' }} @if($m->sync_status === 'failed')<span class="badge badge-out">failed</span>@endif</td>
      <td><span class="status-pill status-{{ $m->status }}">{{ ucfirst($m->status) }}</span></td>
      <td style="text-align:right">
        <form method="POST" action="{{ route('admin.merchants.sync', $m) }}" style="display:inline">@csrf
          <button class="btn btn-outline btn-sm">Sync now</button></form>
        @if($m->status === 'active')
        <form method="POST" action="{{ route('admin.merchants.destroy', $m) }}" style="display:inline" onsubmit="return confirm('Disable merchant and all its offers?')">@csrf @method('DELETE')
          <button class="btn btn-danger btn-sm">Disable</button></form>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="8">No merchants yet.</td></tr>
  @endforelse
</table>
{{ $merchants->links('partials.pagination') }}
<p style="margin-top:14px"><a class="btn btn-primary" href="{{ route('admin.merchants.create') }}">＋ New merchant</a>
<span style="color:var(--ink-3);font-size:13px"> — no code changes required; scheduled sync runs every 6 hours (php artisan schedule:work).</span></p>
@endsection
