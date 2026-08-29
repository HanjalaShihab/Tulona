@extends('admin.analytics._layout')

@section('analytics')

<div class="ana-row">
    <div class="ana-col-2">
        <div class="pane">
            <h2 class="ana-pane-title">Acquisition sources</h2>
            @include('admin.analytics._empty', [
                'icon' => '↗',
                'title' => 'External referrers are not recorded',
                'body' => 'For privacy, outbound and internal navigation intentionally drop external referrers, so Google / Facebook / YouTube / Direct splits are unavailable.',
                'note' => 'When acquisition tracking is enabled, this panel fills in automatically.',
            ])
        </div>
    </div>

    <div class="ana-col-1">
        <div class="pane">
            <h2 class="ana-pane-title">Pages that drove affiliate clicks</h2>
            @include('admin.analytics._bar-list', ['rows' => $referrerMix, 'color' => 'purple'])
            <p class="ana-foot">“Direct entry” means the click had no internal referrer (typed URL or referrer withheld by the browser).</p>
        </div>
    </div>
</div>

@endsection