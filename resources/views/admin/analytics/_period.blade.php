@php($base = request()->except(['period', 'from', 'to']))
<div class="ana-period">
    <nav class="ana-pills" aria-label="Time period">
        @foreach(['today' => 'Today', '7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days', '1y' => '1 Year'] as $key => $label)
            <a href="{{ url()->current().'?'.http_build_query(array_merge($base, ['period' => $key])) }}"
               class="ana-pill {{ $period['key'] === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <a href="#" class="ana-pill {{ $period['key'] === 'custom' ? 'active' : '' }}" data-ana-custom>Custom</a>
    </nav>
    <form class="ana-custom {{ $period['key'] === 'custom' ? 'show' : '' }}" method="GET" action="{{ url()->current() }}">
        <input type="hidden" name="period" value="custom">
        @foreach($base as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ is_array($value) ? implode(',', $value) : $value }}">
        @endforeach
        <label class="ana-date-field">From <input type="date" name="from" value="{{ request('from') }}"></label>
        <label class="ana-date-field">To <input type="date" name="to" value="{{ request('to') }}"></label>
        <button type="submit" class="btn btn-sm">Apply</button>
    </form>
    <span class="ana-window">{{ $period['label'] }} · {{ $period['numDays'] }} day(s)</span>
</div>