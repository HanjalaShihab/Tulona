@php($color = $color ?? 'brand')
@php($maxShare = $rows->pluck('share')->max() ?? 0)
@php($totalClicks = $rows->sum('clicks') ?? 0)
@if($rows->isEmpty())
    @include('admin.analytics._empty', [
        'compact' => true,
        'title' => 'Nothing to show yet',
        'body' => 'No data recorded in this period.',
    ])
@else
    <div class="ana-bars-list">
        @foreach($rows as $row)
            <div class="ana-bar-row">
                <div class="ana-bar-head">
                    <span class="ana-bar-name">{{ $row->name }}</span>
                    <span class="ana-bar-nums">{{ number_format($row->clicks) }}
                        @isset($row->share) · {{ number_format($row->share, 1) }}% @endisset</span>
                </div>
                <div class="ana-track" aria-hidden="true">
                    <i style="width: {{ $maxShare > 0 ? max(round($row->share / $maxShare * 100), 2) : 0 }}%" class="fill-{{ $color }}"></i>
                </div>
            </div>
        @endforeach
        @if($totalClicks > 0)
            <div class="ana-bar-total">Total · {{ number_format($totalClicks) }} clicks</div>
        @endif
    </div>
@endif