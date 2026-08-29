@extends('admin._shell')

@section('page-title')
    Analytics
@endsection

@section('page')
    <div class="ana-hero">
        <div>
            <p class="ana-hero-eyebrow">Admin Insights</p>
            <h2 class="ana-hero-title">Analytics <span>Dashboard</span></h2>
            <p class="ana-hero-sub">Real, privacy-friendly signals — every metric is backed by tracked data or honestly marked as awaiting tracking.</p>
        </div>
        <div class="ana-hero-actions">
            @if(! empty($period))
                @include('admin.analytics._period')
            @endif
        </div>
    </div>
    @include('admin.analytics._tabs')
    <div class="ana-body">
        @yield('analytics')
    </div>
@endsection

@section('scripts')
<script>
(function () {
    var toggles = document.querySelectorAll('[data-ana-chart-toggle]');
    toggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group = btn.getAttribute('data-ana-chart-toggle').split(',');
            toggles.forEach(function (b) {
                b.classList.toggle('active', b.getAttribute('data-ana-chart-toggle') === btn.getAttribute('data-ana-chart-toggle'));
            });
            group.forEach(function (id) {
                var panel = document.getElementById(id.replace('#', ''));
                if (panel) panel.classList.toggle('hidden', false);
            });
            document.querySelectorAll('[id^="ana-chart-"]').forEach(function (el) {
                var keep = group.some(function (id) { return id.replace('#', '') === el.id; });
                if (!keep) el.classList.add('hidden');
            });
        });
    });

    var customToggle = document.querySelector('[data-ana-custom]');
    if (customToggle) {
        customToggle.addEventListener('click', function (e) {
            e.preventDefault();
            var form = document.querySelector('.ana-custom');
            customToggle.classList.add('active');
            if (form) form.classList.toggle('show', true);
        });
    }
})();
</script>
@endsection