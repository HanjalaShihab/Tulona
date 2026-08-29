@foreach($kpis as $card)
    <div class="ana-kpi">
        <span class="ana-kpi-label">@isset($card['icon'])<span class="ana-kpi-ico">{{ $card['icon'] }}</span>@endisset{{ $card['label'] }}</span>
        @if($card['real'])
            <b>{{ is_int($card['value']) ? number_format($card['value']) : $card['value'] }}</b>
            <div class="ana-kpi-meta">
                @if(is_float($card['delta']))
                    <span class="ana-delta {{ $card['delta'] >= 0 ? 'up' : 'down' }}">
                        {{ $card['delta'] >= 0 ? '↑' : '↓' }} {{ number_format(abs($card['delta']), 1) }}%
                    </span>
                @else
                    <span class="ana-delta nada">no baseline</span>
                @endif
                @if(! empty($card['note']))
                    <span class="ana-kpi-note">{{ $card['note'] }}</span>
                @endif
            </div>
        @else
            <b class="ana-await-value" title="{{ $card['note'] ?? 'Metric requires tracking to be enabled' }}">—</b>
            <div class="ana-kpi-meta">
                <span class="ana-await-badge">awaits tracking</span>
                @if(! empty($card['note']))
                    <span class="ana-kpi-note">{{ $card['note'] }}</span>
                @endif
            </div>
        @endif
    </div>
@endforeach