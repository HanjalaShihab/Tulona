@php($max = $series->max($valueKey) ?? 0)
@php($total = $series->sum($valueKey) ?? 0)
@php($step = (int) max(1, ceil($series->count() / 14)))
<div class="ana-chart" role="img" aria-label="{{ $metricLabel }} per day, {{ $period['label'] }}">
    <div class="ana-chart-bars">
        @forelse($series as $i => $row)
            <div class="ana-chart-col" style="height: {{ max(round($row->{$valueKey} / max($max, 1) * 150), 2) }}px"
                 title="{{ $row->day }}: {{ number_format($row->{$valueKey}) }}">
                <i></i>
            </div>
        @empty
            <div class="ana-empty compact" style="width:100%">
                <div class="ana-empty-ico">◷</div>
                <h3>No {{ strtolower($metricLabel) }} in this period</h3>
                <p>The anonymous beacon has not recorded any views on the site yet.</p>
            </div>
        @endforelse
    </div>
    @if($max > 0)
    <div class="bar-labels">
        @foreach($series as $i => $row)
            @if($i % $step === 0)
                <span class="bar-label">{{ $row->label }}</span>
            @endif
        @endforeach
    </div>
    @endif
    @if($total > 0)
    <p class="ana-foot">{{ $metricLabel }} this period: <b>{{ number_format($total) }}</b></p>
    @endif
</div>
