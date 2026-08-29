@php($keep = request()->only(['period', 'from', 'to', 'sort', 'dir']))
<nav class="ana-tabs" aria-label="Analytics sections">
    <a href="{{ route('admin.analytics', $keep) }}" class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}">Overview</a>
    @foreach([
        ['admin.analytics.visitors', 'Visitors'],
        ['admin.analytics.products', 'Products'],
        ['admin.analytics.clicks', 'Affiliate Clicks'],
        ['admin.analytics.search', 'Search'],
        ['admin.analytics.comparisons', 'Comparisons'],
        ['admin.analytics.categories', 'Categories'],
        ['admin.analytics.sources', 'Traffic Sources'],
        ['admin.analytics.devices', 'Devices'],
        ['admin.analytics.landing-pages', 'Landing Pages'],
    ] as [$name, $label])
        <a href="{{ route($name, $keep) }}" class="{{ request()->routeIs($name) ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>

@if(! empty($period))
    @include('admin.analytics._period')
@endif