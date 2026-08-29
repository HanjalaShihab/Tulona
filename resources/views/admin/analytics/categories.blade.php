@extends('admin.analytics._layout')

@section('analytics')

<div class="ana-kpis">
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">🏷</span>Affiliate clicks</span>
        <b>{{ number_format($clickCount) }}</b>
        <span class="ana-kpi-note">In period, grouped by product category</span>
    </div>
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">🗂</span>Category views</span>
        <b class="ana-await-value">—</b>
        <span class="ana-await-badge">awaits tracking</span>
        <span class="ana-kpi-note">Requires category page-view tracking</span>
    </div>
</div>

<div class="ana-row">
    <div class="pane full">
        <div class="ana-pane-head">
            <h2 class="ana-pane-title">Category performance</h2>
        </div>
        @if($categoryRows->isEmpty())
            @include('admin.analytics._empty', ['compact' => true, 'title' => 'No category click traffic yet', 'body' => 'Clicks from products grouped into their categories will appear here.'])
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr><th>Category</th><th class="num">Products</th><th class="num">Views</th><th class="num">Affiliate clicks</th><th class="num">CTR</th><th class="num">Share</th></tr>
                    </thead>
                    @foreach($categoryRows as $row)
                        <tr>
                            <td><a href="{{ route('admin.categories.edit', $row->id) }}">{{ $row->name }}</a></td>
                            <td class="num">{{ number_format($row->products) }}</td>
                            <td class="num">@include('admin.analytics._await', ['title' => 'Requires category page-view tracking'])</td>
                            <td class="num"><b>{{ number_format($row->clicks) }}</b></td>
                            <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                            <td class="num">
                                <div class="ana-track inline" aria-label="{{ number_format($row->share, 1) }}%"><i style="width:{{ min($row->share, 100) }}%" class="fill-brand"></i></div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
</div>

@endsection