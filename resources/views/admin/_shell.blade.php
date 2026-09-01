<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Admin') &#8212; Tulona Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="admin-shell" id="admin-shell">
  <div class="scrim admin-scrim" id="admin-scrim" hidden></div>

  <aside class="admin-side" id="admin-side" aria-label="Admin navigation">
    <div class="side-brand">
      <a href="{{ route('admin.dashboard') }}" class="side-brand-link" aria-label="Tulona admin home">
        <span class="side-brand-text">Tulona</span>
      </a>
    </div>

    @php($dashOpen = request()->routeIs('admin.dashboard') || request()->routeIs('admin.analytics*'))
    <div class="nav-group {{ $dashOpen ? 'open' : '' }}">
      <a href="{{ route('admin.dashboard') }}" class="{{ $dashOpen ? 'active' : '' }}" aria-expanded="{{ $dashOpen ? 'true' : 'false' }}">
        <span class="side-ico">&#9638;</span>
        <span>Dashboard</span>
        <span class="caret">&#9662;</span>
      </a>
      <div class="nav-sub">
        <a href="{{ route('admin.analytics') }}" class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}">Overview</a>
        <a href="{{ route('admin.analytics.visitors') }}" class="{{ request()->routeIs('admin.analytics.visitors') ? 'active' : '' }}">Visitors</a>
        <a href="{{ route('admin.analytics.products') }}" class="{{ request()->routeIs('admin.analytics.products') ? 'active' : '' }}">Products</a>
        <a href="{{ route('admin.analytics.clicks') }}" class="{{ request()->routeIs('admin.analytics.clicks') ? 'active' : '' }}">Affiliate Clicks</a>
        <a href="{{ route('admin.analytics.search') }}" class="{{ request()->routeIs('admin.analytics.search') ? 'active' : '' }}">Search</a>
        <a href="{{ route('admin.analytics.categories') }}" class="{{ request()->routeIs('admin.analytics.categories') ? 'active' : '' }}">Categories</a>
        <a href="{{ route('admin.analytics.sources') }}" class="{{ request()->routeIs('admin.analytics.sources') ? 'active' : '' }}">Traffic Sources</a>
        <a href="{{ route('admin.analytics.devices') }}" class="{{ request()->routeIs('admin.analytics.devices') ? 'active' : '' }}">Devices</a>
        <a href="{{ route('admin.analytics.landing-pages') }}" class="{{ request()->routeIs('admin.analytics.landing-pages') ? 'active' : '' }}">Landing Pages</a>
      </div>
    </div>

    <div class="nav-group-title">Catalog</div>
    <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
      <span class="side-ico">&#9638;</span><span>Products</span>
    </a>
    <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
      <span class="side-ico">&#9675;</span><span>Categories</span>
    </a>
    <a href="{{ route('admin.brands.index') }}" class="{{ request()->routeIs('admin.brands.*') ? 'active' : '' }}">
      <span class="side-ico">&#9673;</span><span>Brands</span>
    </a>

    <div class="nav-group-title">Sources</div>
    <a href="{{ route('admin.merchants.index') }}" class="{{ request()->routeIs('admin.merchants.*') ? 'active' : '' }}">
      <span class="side-ico">&#8962;</span><span>Merchants &amp; Sync</span>
    </a>
    <a href="{{ route('admin.imports.index') }}" class="{{ request()->routeIs('admin.imports.*') ? 'active' : '' }}">
      <span class="side-ico">&#9881;</span><span>Generator</span>
    </a>
    <a href="{{ route('admin.scrape-post.index') }}" class="{{ request()->routeIs('admin.scrape-post.*') || request()->routeIs('admin.csv-drafts.*') ? 'active' : '' }}">
      <span class="side-ico">&#8707;</span><span>Scrape &amp; Post</span>
    </a>

    <div class="nav-group-title">Monetization</div>
    <a href="{{ route('admin.affiliate.index') }}" class="{{ request()->routeIs('admin.affiliate.*') ? 'active' : '' }}">
      <span class="side-ico">&#9741;</span><span>Affiliate</span>
    </a>

    <div class="nav-group-title">Content</div>
    <a href="{{ route('admin.articles.index') }}" class="{{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
      <span class="side-ico">&#9998;</span><span>Articles</span>
    </a>
    <a href="{{ route('admin.landing-pages.index') }}" class="{{ request()->routeIs('admin.landing-pages.*') ? 'active' : '' }}">
      <span class="side-ico">&#9783;</span><span>Landing Pages</span>
    </a>

    @can('manage-users')
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
      <span class="side-ico">&#9673;</span><span>Users</span>
    </a>
    @endcan

    <div class="side-sep"></div>

    <a href="{{ url('/') }}" target="_blank" rel="noopener">
      <span class="side-ico">&#8599;</span><span>View site</span>
    </a>
    <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit()">
      <span class="side-ico">&#9211;</span><span>Logout &#8212; {{ auth()->user()->name }}</span>
    </a>
  </aside>

  <div class="admin-content" id="admin-content">
    <div class="admin-topbar">
      <button class="burger" id="admin-burger" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="admin-side">&#9776;</button>
      <h1>@yield('page-title')</h1>
      <div class="spacer"></div>
      <span class="badge badge-pick">Admin</span>
    </div>

    <main class="admin-main">
      @if(session('status'))
        <div class="alert alert-ok">{{ session('status') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-err">{{ implode(' · ', $errors->all()) }}</div>
      @endif
      @yield('page')
    </main>
  </div>
</div>

<form id="logout-form" method="POST" action="{{ route('admin.logout') }}" class="visually-hidden">@csrf</form>

@yield('scripts')

<script>
(function () {
  var shell  = document.getElementById('admin-shell'),
      side   = document.getElementById('admin-side'),
      scrim  = document.getElementById('admin-scrim'),
      burger = document.getElementById('admin-burger');

  var MOBILE_BP = 960;
  var isMobile = function () { return window.innerWidth <= MOBILE_BP; };

  if (!isMobile() && localStorage.getItem('tulona_admin_collapsed') === '1') {
    shell.classList.add('collapsed');
  }

  function setBurgerExpanded(open) {
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function openDrawer() {
    side.classList.add('open');
    scrim.hidden = false;
    requestAnimationFrame(function () { scrim.classList.add('show'); });
    document.body.classList.add('nav-lock');
    setBurgerExpanded(true);
  }

  function closeDrawer() {
    side.classList.remove('open');
    scrim.classList.remove('show');
    document.body.classList.remove('nav-lock');
    setBurgerExpanded(false);
    setTimeout(function () { if (!side.classList.contains('open')) scrim.hidden = true; }, 180);
  }

  burger.addEventListener('click', function () {
    if (isMobile()) {
      if (side.classList.contains('open')) closeDrawer(); else openDrawer();
    } else {
      var collapsed = shell.classList.toggle('collapsed');
      localStorage.setItem('tulona_admin_collapsed', collapsed ? '1' : '0');
      setBurgerExpanded(false);
    }
  });

  scrim.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && side.classList.contains('open')) closeDrawer();
  });

  window.addEventListener('resize', function () {
    if (!isMobile() && side.classList.contains('open')) closeDrawer();
  });

  document.querySelectorAll('.nav-group').forEach(function (group) {
    var toggle = group.querySelector(':scope > a');
    if (!toggle) return;
    toggle.setAttribute('role', 'button');
    toggle.setAttribute('aria-controls', 'nav-sub-dashboard');
    group.querySelector('.nav-sub')?.setAttribute('id', 'nav-sub-dashboard');

    toggle.addEventListener('click', function (e) {
      if (toggle.classList.contains('active')) {
        e.preventDefault();
        var open = group.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    });

    toggle.addEventListener('keydown', function (e) {
      if ((e.key === 'Enter' || e.key === ' ') && toggle.classList.contains('active')) {
        e.preventDefault();
        toggle.click();
      }
    });
  });
})();
</script>
</body>
</html>
