@extends('admin.analytics._layout')

@section('analytics')

<div class="ana-row">
    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Top searches</h2>
            @include('admin.analytics._empty', [
                'icon' => '⌕',
                'title' => 'Search queries are not logged yet',
                'body' => 'The site search runs live but no search events are recorded, so there is no query list to show.',
                'note' => 'When search-event tracking is enabled, top queries appear here automatically — never estimated.',
            ])
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Searches with no results</h2>
            @include('admin.analytics._empty', [
                'icon' => '⌕',
                'title' => 'Nothing to report yet',
                'body' => 'Zero-result queries are a great catalog signal, but they are not recorded yet.',
                'note' => 'This list will be populated from the same future search-event tracking.',
            ])
        </div>
    </div>
</div>

<div class="ana-row">
    <div class="pane full">
        @include('admin.analytics._empty', [
            'compact' => true,
            'icon' => 'i',
            'title' => 'Search analytics roadmap',
            'body' => 'Both panels are wired and render instantly once the backend stores aggregated search queries. No fake search counts are ever shown here.',
        ])
    </div>
</div>

@endsection