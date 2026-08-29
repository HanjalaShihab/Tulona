@php($compact = $compact ?? false)
<div class="ana-empty{{ $compact ? ' compact' : '' }}">
    <div class="ana-empty-ico">{{ $icon ?? '◌' }}</div>
    <h3>{{ $title }}</h3>
    <p>{{ $body }}</p>
    @if(! empty($note))
        <p class="ana-empty-note">{{ $note }}</p>
    @endif
</div>