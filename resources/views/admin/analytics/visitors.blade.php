@extends('admin.analytics._layout')

@section('analytics')

<section class="ana-kpis">
    @foreach($visitorStats as $stat)
        <div class="ana-kpi">
            <span class="ana-kpi-label">@isset($stat->icon)<span class="ana-kpi-ico">{{ $stat->icon }}</span>@endisset{{ $stat->label }}</span>
            @if($stat->real && $stat->value !== null)
                <b>{{ is_float($stat->value) ? number_format($stat->value, 1) : number_format($stat->value) }}</b>
            @else
                <b class="ana-await-value">—</b>
                <span class="ana-await-badge">awaits tracking</span>
            @endif
            <span class="ana-kpi-note">{{ $stat->note }}</span>
        </div>
    @endforeach
</section>

<div class="ana-row">
    <div class="ana-col-2">
        @include('admin.analytics._trend-panel')
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <div class="ana-pane-head">
                <h2 class="ana-pane-title">Real-time</h2>
                @if($realtime['active'] > 0)
                    <span class="ana-live on">● {{ number_format($realtime['active']) }} active now</span>
                @else
                    <span class="ana-live off">LIVE · idle</span>
                @endif
            </div>
            @include('admin.analytics._realtime', ['realtime' => $realtime])
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Click-event signals</h2>
            <p class="ana-foot">Affiliate clicks remain a separate, fully anonymous signal (hashed IPs, no personal data).</p>
            <div class="ana-kpis mini">
                @foreach($clickerStats as $stat)
                    <div class="ana-kpi">
                        <span class="ana-kpi-label">@isset($stat->icon)<span class="ana-kpi-ico">{{ $stat->icon }}</span>@endisset{{ $stat->label }}</span>
                        @if($stat->real && $stat->value !== null)
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
