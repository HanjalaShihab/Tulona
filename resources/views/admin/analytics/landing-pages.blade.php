@extends('admin.analytics._layout')

@section('analytics')

<div class="ana-kpis">
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">📄</span>Landing pages</span>
        <b>{{ number_format($landingRows->count()) }}</b>
        <span class="ana-kpi-note">Total pages in the landing-page library</span>
    </div>
    <div class="ana-kpi">
        <span class="ana-kpi-label"><span class="ana-kpi-ico">👆</span>Clicks from landing pages</span>
        <b>{{ number_format($landingRows->sum('clicks')) }}</b>
        <span class="ana-kpi-note">Outbound clicks where the referrer was a /landing/ page</span>
    </div>
</div>

<div class="ana-row">
    <div class="pane full">
        <div class="ana-pane-head">
            <h2 class="ana-pane-title">Landing page performance</h2>
        </div>
        @if($landingRows->isEmpty())
            @include('admin.analytics._empty', ['title' => 'No landing pages yet', 'body' => 'Create and publish landing pages to begin measuring their affiliate engagement.'])
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Landing page</th><th class="num">Views</th><th class="num">Affiliate clicks</th><th class="num">CTR</th><th>Status</th></tr></thead>
                    @foreach($landingRows as $row)
                        <tr>
                            <td><a href="{{ route('admin.landing-pages.edit', $row->id) }}">{{ $row->title }}</a> <span class="ana-dim">/landing/{{ $row->slug }}</span></td>
                            <td class="num">@include('admin.analytics._await', ['title' => 'Requires page-view tracking'])</td>
                            <td class="num"><b>{{ number_format($row->clicks) }}</b></td>
                            <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions'])</td>
                            <td><span class="status-pill status-{{ $row->status === 'published' ? 'active' : 'draft' }}">{{ ucfirst($row->status) }}</span></td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <p class="ana-foot">Views and CTR stay empty until page-view tracking is enabled. Clicks are real — counted when a /landing/ page sends a visitor to a merchant.</p>
        @endif
    </div>
</div>

@endsection