@extends('admin.analytics._layout')

@section('analytics')

<section class="ana-kpis">
    @include('admin.analytics._kpis', ['kpis' => $kpis])
</section>

<div class="ana-row">
    <div class="ana-col-2">
        @include('admin.analytics._trend-panel')
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <div class="ana-pane-head">
                <h2 class="ana-pane-title">Real-time</h2>
                <span class="ana-live off">LIVE · inactive</span>
            </div>
            @include('admin.analytics._empty', [
                'compact' => true,
                'icon' => '◷',
                'title' => 'Real-time panel is off',
                'body' => 'The site does not stream visitor events yet, so there is nothing real-time to report.',
                'note' => 'This panel stays empty instead of showing invented numbers.',
            ])
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <div class="ana-pane-head">
                <h2 class="ana-pane-title">Top products</h2>
                <a class="ana-more" href="{{ route('admin.analytics.products', request(['period','from','to','sort','dir'])) }}">All products →</a>
            </div>
            @if($topProducts->isEmpty())
                @include('admin.analytics._empty', ['compact' => true, 'title' => 'No products clicked yet', 'body' => 'Once visitors click tracked outbound links, products appear here.'])
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Product</th><th class="num">Clicks</th><th class="num">Best price</th><th class="num">Merchants</th></tr></thead>
                        @foreach($topProducts as $p)
                            <tr>
                                <td><a href="{{ route('admin.products.edit', $p->id) }}">{{ $p->name }}</a></td>
                                <td class="num"><b>{{ number_format($p->clicks) }}</b></td>
                                <td class="num">{{ $p->price !== null ? \App\Support\Currency::format($p->price, 'BDT') : '—' }}</td>
                                <td class="num">{{ $p->merchants }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <div class="ana-pane-head">
                <h2 class="ana-pane-title">Affiliate performance</h2>
                <a class="ana-more" href="{{ route('admin.analytics.clicks', request(['period','from','to'])) }}">Details →</a>
            </div>
            @include('admin.analytics._bar-list', ['rows' => $merchantShare, 'color' => 'accent'])
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Top comparisons</h2>
            @if($topComparisons->isEmpty())
                @include('admin.analytics._empty', ['compact' => true, 'title' => 'No comparison traffic yet', 'body' => 'Clicks that happen on comparison pages will appear here.'])
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Comparison</th><th class="num">Clicks</th><th class="num">CTR</th></tr></thead>
                        @foreach($topComparisons as $c)
                            <tr>
                                <td>
                                    <a href="{{ $c->slug ? url('/'.$c->slug) : '#' }}" target="_blank" rel="noopener">{{ $c->title }}</a>
                                    @if($c->featured)<span class="badge badge-pick">Featured</span>@endif
                                </td>
                                <td class="num"><b>{{ number_format($c->clicks) }}</b></td>
                                <td class="num">@include('admin.analytics._await', ['title' => 'CTR needs page-view impressions']) </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Affiliate funnel</h2>
            <ol class="ana-funnel">
                @foreach($funnel as $i => $step)
                    <li class="ana-funnel-step {{ $step['real'] ? 'real' : 'await' }}">
                        <span class="ana-funnel-label">{{ $step['label'] }}</span>
                        @if($step['real'])
                            <b>{{ number_format((int) $step['value']) }}</b>
                        @else
                            <b class="ana-await-value">—</b>
                            <span class="ana-await-badge">awaits tracking</span>
                        @endif
                    </li>
                    @if(! $loop->last)<li class="ana-funnel-arrow" aria-hidden="true">▼</li>@endif
                @endforeach
            </ol>
            <p class="ana-foot">Purchases and revenue are shown only when the affiliate network imports verified conversions.</p>
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <div class="ana-pane-head">
                <h2 class="ana-pane-title">Traffic sources (internal)</h2>
                <a class="ana-more" href="{{ route('admin.analytics.sources', request(['period','from','to'])) }}">Details →</a>
            </div>
            @include('admin.analytics._bar-list', ['rows' => $referrerMix, 'color' => 'purple'])
            <p class="ana-foot">Which of our own pages drove outbound affiliate clicks. External acquisition sources are not recorded (privacy).</p>
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Affiliate conversions (imported)</h2>
            <p class="ana-foot">Only real, network-imported commission data — clicks are never counted as revenue.</p>
            @if($conversions['count'] === 0)
                @include('admin.analytics._empty', ['compact' => true, 'title' => 'No conversion imports yet', 'body' => 'Feed providers can import verified conversions via official APIs.'])
            @else
                <div class="ana-kpi mini">
                    <span class="ana-kpi-label">Approved commission</span>
                    <b>{{ \App\Support\Currency::format($conversions['approved'], 'USD') }}</b>
                </div>
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
            @endif
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="pane full">
        <div class="ana-pane-head">
            <h2 class="ana-pane-title">Opportunities</h2>
            <span class="ana-await-badge done">auto-computed</span>
        </div>
        @if(empty($opportunities))
            @include('admin.analytics._empty', ['compact' => true, 'title' => 'No gaps detected', 'body' => 'Catalog and tracking look healthy for this period.'])
        @else
            <ul class="ana-list">
                @foreach($opportunities as $op)
                    <li>{{ $op }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="ana-row">
    <div class="pane full ana-footwide">
        <p>
            <strong>Terminology note.</strong> This site has no user accounts, so analytics always use visitor/engagement terms:
            unique visitors, sessions, page views, product views, affiliate clicks and outbound clicks — never “users” or “registered users”.
            Only aggregated, privacy-friendly numbers are shown; no personal data is stored or displayed. Metrics without backend tracking
            are intentionally left empty rather than estimated.
        </p>
    </div>
</div>

@endsection