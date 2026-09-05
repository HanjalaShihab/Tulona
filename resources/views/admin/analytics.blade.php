@extends('admin._shell')
@section('page-title')
Analytics
@endsection
@section('page')
<div class="stat-cards">
  <div class="stat-card"><b>{{ number_format($totals['clicks_total']) }}</b><span>Total clicks</span></div>
  <div class="stat-card"><b>{{ number_format($totals['clicks_today']) }}</b><span>Clicks today</span></div>
  <div class="stat-card"><b>{{ number_format($totals['clicks_week']) }}</b><span>This week</span></div>
  <div class="stat-card"><b>{{ number_format($totals['clicks_month']) }}</b><span>This month</span></div>
  <div class="stat-card"><b>{{ number_format($totals['products']) }}</b><span>Products</span></div>
  <div class="stat-card"><b>{{ number_format($totals['offers']) }}</b><span>Active offers</span></div>
  <div class="stat-card"><b>{{ number_format($totals['price_drops']) }}</b><span>Price drops recorded</span></div>
  <div class="stat-card"><b title="Only real, imported commission data">{{ \App\Support\Currency::format($totals['revenue_approved'], 'USD') }}</b><span>Approved commission (imported)</span></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px">
  <div class="pane">
    <h2 style="font-size:15px;margin-bottom:12px">Clicks — last 30 days</h2>
    @if($clicksByDay->isEmpty())
      <p style="color:var(--ink-3)">No clicks yet.</p>
    @else
      @php($maxC = $clicksByDay->max('c'))
      <div class="bar-chart" role="img" aria-label="Clicks per day, last 30 days">
        @foreach($clicksByDay as $row)<div style="height:{{ max(round($row->c / max($maxC,1) * 150), 2) }}px" title="{{ $row->day }}: {{ $row->c }}"></div>@endforeach
      </div>
    @endif
  </div>

  <div class="pane"><h2 style="font-size:15px;margin-bottom:10px">Top products</h2>
    <table class="data-table">@forelse($topProducts as $r)<tr><td>{{ $r->name }}</td><td style="text-align:right">{{ number_format($r->c) }}</td></tr>@empty<tr><td>No data.</td></tr>@endforelse</table>
  </div>

  <div class="pane"><h2 style="font-size:15px;margin-bottom:10px">Top merchants</h2>
    <table class="data-table">@forelse($topMerchants as $r)<tr><td>{{ $r->name }}</td><td style="text-align:right">{{ number_format($r->c) }}</td></tr>@empty<tr><td>No data.</td></tr>@endforelse</table>
  </div>

  <div class="pane"><h2 style="font-size:15px;margin-bottom:10px">Top categories</h2>
    <table class="data-table">@forelse($topCategories as $r)<tr><td>{{ $r->name }}</td><td style="text-align:right">{{ number_format($r->c) }}</td></tr>@empty<tr><td>No data.</td></tr>@endforelse</table>
  </div>

  <div class="pane"><h2 style="font-size:15px;margin-bottom:10px">Top landing pages</h2>
    <table class="data-table">@forelse($topLandingPages as $r)<tr><td>{{ $r->page === '(direct)' ? '(direct)' : str_replace('/product/','…',$r->page) }}</td><td style="text-align:right">{{ number_format($r->c) }}</td></tr>@empty<tr><td>No data.</td></tr>@endforelse</table>
  </div>

  <div class="pane">
    <h2 style="font-size:15px;margin-bottom:8px">Affiliate conversions (imported)</h2>
    <p style="font-size:12.5px;color:var(--ink-3);margin-bottom:8px">Revenue is only shown from real network imports — clicks are never counted as revenue.</p>
    <table class="data-table">
      <thead><tr><th>Merchant</th><th>Status</th><th>Commission</th><th>Date</th></tr></thead>
      @forelse($conversionRows as $cv)
        <tr><td>{{ $cv->merchant?->name ?? '—' }}</td>
          <td><span class="status-pill status-{{ $cv->status }}">{{ ucfirst($cv->status) }}</span></td>
          <td>{{ \App\Support\Currency::format((float)$cv->commission_amount, $cv->currency) }}</td>
          <td>{{ optional($cv->converted_at)->format('M j, Y') ?? '—' }}</td></tr>
      @empty
        <tr><td colspan="4">No conversion imports yet. Feed providers can import these via official APIs.</td></tr>
      @endforelse
    </table>
  </div>
</div>
@endsection
