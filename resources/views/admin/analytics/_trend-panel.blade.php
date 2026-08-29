<div class="pane">
    <div class="ana-pane-head">
        <h2 class="ana-pane-title">Visitor trend</h2>
        <div class="ana-chart-tabs" role="tablist" aria-label="Chart metric">
            <button type="button" class="ana-chart-toggle active" data-ana-chart-toggle="pageviews">Page Views</button>
            <button type="button" class="ana-chart-toggle" data-ana-chart-toggle="visitors">Visitors</button>
            <button type="button" class="ana-chart-toggle" data-ana-chart-toggle="clicks">Clicks</button>
            <button type="button" class="ana-chart-toggle" data-ana-chart-toggle="sessions">Sessions</button>
        </div>
    </div>

    <div id="ana-chart-pageviews" class="ana-chart-panel">
        @include('admin.analytics._trend-series', [
            'series' => $pageTrend,
            'valueKey' => 'views',
            'metricLabel' => 'Page views',
        ])
    </div>

    <div id="ana-chart-visitors" class="ana-chart-panel hidden">
        @include('admin.analytics._trend-series', [
            'series' => $pageTrend,
            'valueKey' => 'visitors',
            'metricLabel' => 'Unique visitors',
        ])
    </div>

    <div id="ana-chart-clicks" class="ana-chart-panel hidden">
        @include('admin.analytics._trend')
        <p class="ana-foot">Affiliate clicks recorded when visitors tap a tracked outbound link.</p>
    </div>

    <div id="ana-chart-sessions" class="ana-chart-panel hidden">
        @include('admin.analytics._chart-await', [
            'title' => 'Sessions',
            'body' => 'Session boundaries are not tracked yet. This chart will populate automatically when session tracking is enabled.',
        ])
    </div>
</div>
