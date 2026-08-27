<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Admin') — Tulona Admin</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="admin-shell" id="admin-shell">
  <div class="scrim" id="admin-scrim" hidden></div>
  <aside class="admin-side" id="admin-side">
    <div class="side-brand"><span class="side-brand-text">Tulona</span></div>

    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="side-ico">▤</span><span>Dashboard</span></a>

    <div class="nav-group-title">Catalog</div>
    <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><span class="side-ico">▦</span><span>Products</span></a>
    <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><span class="side-ico">❏</span><span>Categories</span></a>
    <a href="{{ route('admin.brands.index') }}" class="{{ request()->routeIs('admin.brands.*') ? 'active' : '' }}"><span class="side-ico">◎</span><span>Brands</span></a>

    <div class="nav-group-title">Sources</div>
    <a href="{{ route('admin.merchants.index') }}" class="{{ request()->routeIs('admin.merchants.*') ? 'active' : '' }}"><span class="side-ico">⌂</span><span>Merchants &amp; Sync</span></a>
    <a href="{{ route('admin.imports.index') }}" class="{{ request()->routeIs('admin.imports.*') ? 'active' : '' }}"><span class="side-ico">⚙</span><span>Generator</span></a>

    <div class="nav-group-title">Monetization</div>
    <a href="{{ route('admin.affiliate.index') }}" class="{{ request()->routeIs('admin.affiliate.*') ? 'active' : '' }}"><span class="side-ico">⛓</span><span>Affiliate</span></a>

    <div class="nav-group-title">Content</div>
    <a href="{{ route('admin.articles.index') }}" class="{{ request()->routeIs('admin.articles.*') ? 'active' : '' }}"><span class="side-ico">✎</span><span>Articles</span></a>
    <a href="{{ route('admin.comparisons.index') }}" class="{{ request()->routeIs('admin.comparisons.*') ? 'active' : '' }}"><span class="side-ico">⚖</span><span>Comparisons</span></a>

    <div class="nav-group-title">Insights</div>
    <a href="{{ route('admin.analytics') }}" class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}"><span class="side-ico">◔</span><span>Analytics</span></a>
    @can('manage-users')
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="side-ico">◍</span><span>Users</span></a>
    @endcan

    <div class="side-sep"></div>
    <a href="{{ url('/') }}" target="_blank" rel="noopener"><span class="side-ico">↗</span><span>View site</span></a>
    <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit()"><span class="side-ico">⏻</span><span>Logout — {{ auth()->user()->name }}</span></a>
  </aside>

  <div>
    <div class="admin-topbar">
      <button class="burger" id="admin-burger" aria-label="Toggle menu" aria-expanded="false">☰</button>
      <h1>@yield('page-title')</h1>
      <div class="spacer"></div>
      <span class="badge badge-pick">Admin</span>
    </div>

    <main class="admin-main">
      @if(session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif
      @if($errors->any())<div class="alert alert-err">{{ implode(' · ', $errors->all()) }}</div>@endif
      @yield('page')
    </main>
  </div>
</div>
<form id="logout-form" method="POST" action="{{ route('admin.logout') }}" class="visually-hidden">@csrf</form>
<script>
(function () {
  var shell = document.getElementById('admin-shell'),
      side = document.getElementById('admin-side'),
      scrim = document.getElementById('admin-scrim'),
      burger = document.getElementById('admin-burger');
  if (localStorage.getItem('tulona_admin_collapsed') === '1') shell.classList.add('collapsed');
  burger.addEventListener('click', function () {
    if (window.innerWidth <= 960) {
      var open = side.classList.toggle('open');
      scrim.hidden = !open;
      scrim.classList.toggle('show', open);
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    } else {
      shell.classList.toggle('collapsed');
      localStorage.setItem('tulona_admin_collapsed', shell.classList.contains('collapsed') ? '1' : '0');
    }
  });
  scrim.addEventListener('click', function () {
    side.classList.remove('open'); scrim.hidden = true; scrim.classList.remove('show');
  });
})();
</script>
</body>
</html>
