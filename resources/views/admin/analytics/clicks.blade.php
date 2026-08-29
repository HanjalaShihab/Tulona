@extends('admin.analytics._layout')

@section('analytics')

<section class="ana-kpis">
    @include('admin.analytics._kpis', ['kpis' => $kpis])
</section>

<div class="ana-row">
    <div class="ana-col-2">
        <div class="pane">
            <div class="ana-pane-head">
                <h2 class="ana-pane-title">Click volume</h2>
            </div>
            @include('admin.analytics._trend')
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Clicks by merchant</h2>
            @include('admin.analytics._bar-list', ['rows' => $merchantShare, 'color' => 'accent'])
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="pane full">
        <div class="ana-pane-head">
            <h2 class="ana-pane-title">Merchant breakdown</h2>
        </div>
        @if($merchantShare->isEmpty())
            @include('admin.analytics._empty', ['compact' => true, 'title' => 'No affiliate clicks recorded', 'body' => 'Outbound click tracking starts working as soon as visitors use tracked links.'])
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Merchant</th><th class="num">Clicks</th><th class="num">CTR</th><th class="num">Share</th></tr></thead>
                    @foreach($merchantShare as $row)
                        <tr>
                            <td><a href="{{ route('admin.merchants.edit', $row->id) }}">{{ $row->name }}</a></td>
                            <td class="num"><b>{{ number_format($row->clicks) }}</b></td>
                            <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                            <td class="num">{{ number_format($row->share, 1) }}%</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
        <p class="ana-foot">No purchase amounts or revenue are derived from clicks. Verified conversions come only from official network imports, shown below.</p>
    </div>
</div>

<div class="ana-row">
    <div class="pane full">
        <div class="ana-pane-head">
            <h2 class="ana-pane-title">Affiliate conversions (imported)</h2>
        </div>
        @if($conversions['count'] === 0)
            @include('admin.analytics._empty', ['compact' => true, 'title' => 'No conversion imports yet', 'body' => 'Feed providers can import verified conversions via official APIs.'])
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Merchant</th><th>Status</th><th class="num">Commission</th><th>Date</th></tr></thead>
                    @foreach($conversions['rows'] as $cv)
                        <tr>
                            <td>{{ $cv->merchant?->name ?? '—' }}</td>
                            <td><span class="status-pill status-{{ $cv->status }}">{{ ucfirst($cv->status) }}</span></td>
                            <td class="num">{{ \App\Support\Currency::format((float) $cv->commission_amount, $cv->currency) }}</td>
                            <td>{{ optional($cv->converted_at)->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <p class="ana-foot">Approved {{ \App\Support\Currency::format($conversions['approved'], 'USD') }} · Pending {{ \App\Support\Currency::format($conversions['pending'], 'USD') }}</p>
        @endif
    </div>
</div>

@endsection