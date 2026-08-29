@if($realtime['active'] > 0)
    <div class="ana-kpis mini">
        <div class="ana-kpi">
            <span class="ana-kpi-label"><span class="ana-kpi-ico">🟢</span>Active visitors</span>
            <b>{{ number_format($realtime['active']) }}</b>
            <span class="ana-kpi-note">Distinct visitors in the last {{ $realtime['ttl'] }} minutes</span>
        </div>
    </div>
    <div class="realtime-feed">
        <h3 class="ana-recent-title">Latest activity</h3>
        <ul class="realtime-list">
            @foreach($realtime['recent'] as $v)
                <li>
                    <span class="rt-dot"></span>
                    <span class="rt-path" title="{{ $v->path }}">{{ $v->path }}</span>
                    <span class="rt-family">{{ $v->family ?? '—' }}</span>
                    <span class="rt-time">{{ $v->viewed_at->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@else
    @include('admin.analytics._empty', [
        'compact' => true,
        'icon' => '◷',
        'title' => 'No visitors right now',
        'body' => 'No page-view beacon has fired in the last few minutes. This updates live as visitors browse.',
        'note' => 'Data comes from the anonymous page-view beacon — never fabricated.',
    ])
@endif
