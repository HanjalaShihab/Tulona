@extends('admin.analytics._layout')

@section('analytics')

<div class="ana-kpis">
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">👆</span>Clicks from comparison pages</span>
        <b>{{ number_format($clickCount) }}</b>
        <span class="ana-kpi-note">Outbound clicks whose referrer was a comparison page</span>
    </div>
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">👁</span>Comparison views</span>
        <b class="ana-await-value">—</b>
        <span class="ana-await-badge">awaits tracking</span>
        <span class="ana-kpi-note">Requires page-view tracking</span>
    </div>
</div>

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Top performing comparisons</h2>
            @php($top = $comparisonRows->filter(fn ($r) => $r->clicks > 0)->values())
            @if($top->isEmpty())
                @include('admin.analytics._empty', ['compact' => true, 'title' => 'No comparison click traffic yet', 'body' => 'Clicks recorded on comparison pages will rank here.'])
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Comparison</th><th class="num">Clicks</th><th class="num">CTR</th></tr></thead>
                        @foreach($top->take(10) as $c)
                            <tr>
                                <td><a href="{{ url('/'.$c->slug) }}" target="_blank" rel="noopener">{{ $c->title }}</a>
                                    @if($c->featured)<span class="badge badge-pick">Featured</span>@endif
                                    @if($c->status !== 'published')<span class="badge badge-stale">{{ ucfirst($c->status) }}</span>@endif
                                </td>
                                <td class="num"><b>{{ number_format($c->clicks) }}</b></td>
                                <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Lowest performing comparisons</h2>
            @php($lowest = $comparisonRows->filter(fn ($r) => $r->clicks > 0)->sortBy('clicks')->values())
            @if($lowest->isEmpty())
                @include('admin.analytics._empty', ['compact' => true, 'title' => 'No comparison click traffic yet', 'body' => 'Comparisons with the fewest clicks will appear here.'])
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Comparison</th><th class="num">Clicks</th><th class="num">CTR</th></tr></thead>
                        @foreach($lowest->take(10) as $c)
                            <tr>
                                <td><a href="{{ url('/'.$c->slug) }}" target="_blank" rel="noopener">{{ $c->title }}</a></td>
                                <td class="num"><b>{{ number_format($c->clicks) }}</b></td>
                                <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="pane full">
        <div class="ana-pane-head">
            <h2 class="ana-pane-title">All comparisons</h2>
        </div>
        @if($comparisonRows->isEmpty())
            @include('admin.analytics._empty', ['compact' => true, 'title' => 'No comparisons yet', 'body' => 'Create and publish comparisons to start measuring their affiliate engagement.'])
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr><th>Comparison</th><th class="num">Views</th><th class="num">Affiliate clicks</th><th class="num">CTR</th><th>Status</th></tr>
                    </thead>
                    @foreach($comparisonRows as $c)
                        <tr>
                            <td>{{ $c->title }}</td>
                            <td class="num">@include('admin.analytics._await', ['title' => 'Requires page-view tracking'])</td>
                            <td class="num"><b>{{ number_format($c->clicks) }}</b></td>
                            <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                            <td><span class="status-pill status-{{ $c->status === 'published' ? 'active' : 'draft' }}">{{ ucfirst($c->status) }}</span></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
        <p class="ana-foot">“Views” and “CTR” remain empty until page-view tracking is enabled — clicks are counted from the comparison URL only.</p>
    </div>
</div>

@endsection