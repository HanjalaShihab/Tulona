@extends('admin.analytics._layout')

@section('analytics')

<section class="ana-kpis">
    @foreach([
        ['👤', 'Unique Visitors', 'Needs visitor event tracking'],
        ['✨', 'New Visitors', 'Needs visitor event tracking'],
        ['🔁', 'Returning Visitors', 'Needs visitor event tracking'],
        ['🕘', 'Sessions', 'Needs session tracking'],
        ['📄', 'Page Views', 'Needs page-view tracking'],
        ['⏱', 'Avg. Session Duration', 'Needs session tracking'],
        ['📑', 'Pages per Session', 'Needs session tracking'],
        ['📉', 'Bounce Rate', 'Needs session tracking'],
    ] as [$ico, $label, $note])
        <div class="ana-kpi">
            <span class="ana-kpi-label"><span class="ana-kpi-ico">{{ $ico }}</span>{{ $label }}</span>
            <b class="ana-await-value">—</b>
            <div class="ana-kpi-meta">
                <span class="ana-await-badge">awaits tracking</span>
                <span class="ana-kpi-note">{{ $note }}</span>
            </div>
        </div>
    @endforeach
</section>

<div class="ana-row">
    <div class="ana-col-2">
        @include('admin.analytics._trend-panel')
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">About visitor metrics</h2>
            @include('admin.analytics._empty', [
                'compact' => true,
                'icon' => '◌',
                'title' => 'Visitor tracking is not enabled',
                'body' => 'Unique visitors, sessions and page views are not recorded yet. The cards above stay empty on purpose rather than showing estimated figures.',
                'note' => 'Once the backend records these as aggregated events, the whole Visitors section fills in automatically.',
            ])
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Click-event signals</h2>
            <p class="ana-foot">The only visitor-adjacent signal currently recorded — fully anonymous (hashed IPs, no personal data).</p>
            <div class="ana-kpis mini">
                @foreach($clickerStats as $stat)
                    <div class="ana-kpi">
                        <span class="ana-kpi-label">@isset($stat->icon)<span class="ana-kpi-ico">{{ $stat->icon }}</span>@endisset{{ $stat->label }}</span>
                        @if($stat->real)
                            <b>{{ number_format($stat->value) }}</b>
                        @else
                            <b class="ana-await-value">—</b>
                            <span class="ana-await-badge">awaits tracking</span>
                        @endif
                        <span class="ana-kpi-note">{{ $stat->note }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Pages that drove affiliate clicks</h2>
            @include('admin.analytics._bar-list', ['rows' => $referrerMix, 'color' => 'accent'])
        </div>
    </div>
</div>

@endsection