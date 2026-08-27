@extends('admin._shell')
@section('page-title')
Dashboard
@endsection

@section('page')
<div class="stat-cards">
  <div class="stat-card"><span class="sc-label">Products</span><b>{{ number_format($products) }}</b></div>
  <div class="stat-card"><span class="sc-label">Active offers</span><b>{{ number_format($offers) }}</b></div>
  <div class="stat-card"><span class="sc-label">Active merchants</span><b>{{ $merchants }}</b></div>
  <div class="stat-card"><span class="sc-label">Categories</span><b>{{ $categories }}</b></div>
  <div class="stat-card"><span class="sc-label">Published articles</span><b>{{ $articles }}</b></div>
  <div class="stat-card"><span class="sc-label">Total clicks</span><b>{{ number_format($clicksTotal) }}</b></div>
  <div class="stat-card"><span class="sc-label">Clicks today</span><b>{{ $clicksToday }}</b></div>
  <div class="stat-card"><span class="sc-label">Failed imports</span><b style="{{ $failedImports ? 'color:var(--danger)' : '' }}">{{ $failedImports }}</b>@if($failedImports)<span class="sc-trend trend-down">!</span>@endif</div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px">
  <div class="panel">
    <div class="panel-head"><h2>Top products by clicks</h2></div>
    <div class="panel-body" style="padding:0">
    <table class="data-table">
      @forelse($topProducts as $p)
        <tr><td>{{ $p->name }}<br><small style="color:var(--ink-3)">{{ $p->brand?->name }}</small></td><td style="text-align:right"><strong>{{ number_format($p->clicks_count ?? 0) }}</strong> clicks</td></tr>
      @empty
        <tr><td>No clicks recorded yet.</td></tr>
      @endforelse
    </table>
    </div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Recent imports</h2><a class="btn btn-outline btn-sm" href="{{ route('admin.imports.index') }}">View all</a></div>
    <div class="panel-body" style="padding:0">
    <table class="data-table">
      @forelse($recentImports as $b)
        <tr>
          <td>{{ \Illuminate\Support\Str::limit($b->filename, 30) }}<br>
            <small style="color:var(--ink-3)">{{ $b->created_count }} created · {{ $b->updated_count }} updated · {{ $b->skipped_count }} skipped · {{ $b->failed_count }} failed</small></td>
          <td><span class="status-pill status-{{ $b->status }}">{{ ucfirst($b->status) }}</span></td>
        </tr>
      @empty
        <tr><td>No imports yet.</td></tr>
      @endforelse
    </table>
    </div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Sync health</h2></div>
    <div class="panel-body" style="padding:0">
    <table class="data-table">
      @forelse($syncHealth as $log)
        <tr><td>{{ $log->merchant->name }}<br><small style="color:var(--ink-3)">{{ optional($log->finished_at ?? $log->started_at)->diffForHumans() }}</small></td>
          <td><span class="status-pill status-{{ $log->status }}">{{ ucfirst($log->status) }}</span></td></tr>
      @empty
        <tr><td>No sync runs yet — use “Sync now” on a merchant.</td></tr>
      @endforelse
    </table>
    </div>
  </div>
</div>
@endsection
