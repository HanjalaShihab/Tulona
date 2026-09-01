@extends('admin._shell')
@section('page-title')
Dashboard
@endsection

@section('page')
<div class="stat-cards">
  <div class="stat-card">
    <span class="sc-label">Products</span>
    <b>{{ number_format($products) }}</b>
    <span class="sc-trend up">Active catalog</span>
  </div>
  <div class="stat-card">
    <span class="sc-label">Active offers</span>
    <b>{{ number_format($offers) }}</b>
    <span class="sc-trend up">Across all stores</span>
  </div>
  <div class="stat-card">
    <span class="sc-label">Active merchants</span>
    <b>{{ $merchants }}</b>
    <span class="sc-trend">Connected stores</span>
  </div>
  <div class="stat-card">
    <span class="sc-label">Categories</span>
    <b>{{ $categories }}</b>
    <span class="sc-trend">Organized</span>
  </div>
  <div class="stat-card">
    <span class="sc-label">Published articles</span>
    <b>{{ $articles }}</b>
    <span class="sc-trend">Guides & reviews</span>
  </div>
  <div class="stat-card">
    <span class="sc-label">Total clicks</span>
    <b>{{ number_format($clicksTotal) }}</b>
    <span class="sc-trend up">Lifetime</span>
  </div>
  <div class="stat-card">
    <span class="sc-label">Clicks today</span>
    <b>{{ $clicksToday }}</b>
    <span class="sc-trend">Today's performance</span>
  </div>
  <div class="stat-card">
    <span class="sc-label">Failed imports</span>
    <b style="{{ $failedImports ? 'color:var(--danger)' : '' }}">{{ $failedImports }}</b>
    @if($failedImports)<span class="sc-trend down">Needs attention</span>@else<span class="sc-trend up">All clear</span>@endif
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px">
  <div class="panel">
    <div class="panel-head"><h2>Top products by clicks</h2></div>
    <div class="panel-body" style="padding:0">
    <table class="data-table">
      @forelse($topProducts as $p)
        <tr>
          <td>
            <strong>{{ $p->name }}</strong>
            <br><small style="color:var(--ink-3)">{{ $p->brand?->name }}</small>
          </td>
          <td class="num"><strong>{{ number_format($p->clicks_count ?? 0) }}</strong> clicks</td>
        </tr>
      @empty
        <tr><td colspan="2" style="text-align:center;color:var(--ink-3);padding:24px">No clicks recorded yet.</td></tr>
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
          <td>
            <strong>{{ \Illuminate\Support\Str::limit($b->filename, 30) }}</strong>
            <br><small style="color:var(--ink-3)">{{ $b->created_count }} created · {{ $b->updated_count }} updated · {{ $b->skipped_count }} skipped · {{ $b->failed_count }} failed</small>
          </td>
          <td><span class="status-pill status-{{ $b->status }}">{{ ucfirst($b->status) }}</span></td>
        </tr>
      @empty
        <tr><td colspan="2" style="text-align:center;color:var(--ink-3);padding:24px">No imports yet.</td></tr>
      @endforelse
    </table>
    </div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Sync health</h2></div>
    <div class="panel-body" style="padding:0">
    <table class="data-table">
      @forelse($syncHealth as $log)
        <tr>
          <td>
            <strong>{{ $log->merchant->name }}</strong>
            <br><small style="color:var(--ink-3)">{{ optional($log->finished_at ?? $log->started_at)->diffForHumans() }}</small>
          </td>
          <td><span class="status-pill status-{{ $log->status }}">{{ ucfirst($log->status) }}</span></td>
        </tr>
      @empty
        <tr><td colspan="2" style="text-align:center;color:var(--ink-3);padding:24px">No sync runs yet — use "Sync now" on a merchant.</td></tr>
      @endforelse
    </table>
    </div>
  </div>
</div>
@endsection
