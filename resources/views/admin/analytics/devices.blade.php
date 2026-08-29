@extends('admin.analytics._layout')

@section('analytics')

<div class="ana-kpis">
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">📱</span>Device split of affiliate clicks</span>
        <b>{{ number_format($clickCount) }}</b>
        <span class="ana-kpi-note">From hashed click events only</span>
    </div>
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">🖥</span>Page views by device</span>
        <b class="ana-await-value">—</b>
        <span class="ana-await-badge">awaits tracking</span>
        <span class="ana-kpi-note">Requires page-view tracking to split by device</span>
    </div>
</div>

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Device split (clicks)</h2>
            @include('admin.analytics._bar-list', ['rows' => $deviceRows, 'color' => 'dev'])
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Device breakdown</h2>
            @if($deviceRows->isEmpty())
                @include('admin.analytics._empty', ['compact' => true, 'title' => 'No device data yet', 'body' => 'Click events include a mobile/desktop family tag once any clicks are recorded.'])
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr><th>Device</th><th class="num">Clicks</th><th class="num">Share</th><th class="num">Visitors</th><th class="num">Sessions</th><th class="num">CTR</th></tr>
                        </thead>
                        @foreach($deviceRows as $row)
                            <tr>
                                <td>{{ $row->device }}</td>
                                <td class="num"><b>{{ number_format($row->clicks) }}</b></td>
                                <td class="num">{{ number_format($row->share, 1) }}%</td>
                                <td class="num">@include('admin.analytics._await', ['title' => 'Requires visitor tracking'])</td>
                                <td class="num">@include('admin.analytics._await', ['title' => 'Requires session tracking'])</td>
                                <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <p class="ana-foot">Visitors, sessions and CTR columns wait for visitor page-view tracking.</p>
            @endif
        </div>
    </div>
</div>

@endsection