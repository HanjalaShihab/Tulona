@php($max = $trend->max('c') ?? 0)
@php($total = $trend->sum('c') ?? 0)
@php($step = (int) max(1, ceil($trend->count() / 14)))
<div class="ana-chart" role="img" aria-label="Clicks per day, {{ $period['label'] }}">
    <div class="ana-chart-bars">
        @forelse($trend as $i => $row)
            <div class="ana-chart-col" style="height: {{ max(round($row->c / max($max, 1) * 150), 2) }}px"
                 title="{{ $row->day }}: {{ $row->c }}">
                <i></i>
            </div>
        @empty
            <div class="ana-empty compact" style="width:100%">
                <div class="ana-empty-ico">◷</div>
                <h3>No clicks in this period</h3>
                <p>Affiliate clicks are recorded when visitors tap a tracked outbound link.</p>
            </div>
        @endforelse
    </div>
    @if($max > 0)
    <div class="bar-labels">
        @foreach($trend as $i => $row)
            @if($i % $step === 0)
                <span class="bar-label">{{ $row->label }}</span>
            @endif
        @endforeach
    </div>
    @endif
</div>