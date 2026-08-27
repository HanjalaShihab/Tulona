// Tulona — tiny progressive-enhancement layer (< 2 KB). The site is fully
// functional without JavaScript (§39: no unnecessary JS).
document.addEventListener('DOMContentLoaded', function () {
  // Mobile navigation toggle
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.querySelector('.main-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { nav.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); }
    });
  }

  // Filters bottom-sheet on mobile
  var filterBtn = document.querySelector('[data-toggle=filters]');
  var filters = document.querySelector('.filters');
  if (filterBtn && filters) {
    filterBtn.addEventListener('click', function () {
      filters.classList.toggle('open');
      filters.setAttribute('aria-expanded', filters.classList.contains('open') ? 'true' : 'false');
    });
  }

  // Live search suggestions (uses public endpoint, degrades silently)
  var searchInput = document.querySelector('.search-input[data-suggest]');
  var list = document.querySelector('.suggest');
  if (searchInput && list) {
    var timer = null;
    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      var q = searchInput.value.trim();
      if (q.length < 2) { list.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch('/suggest?q=' + encodeURIComponent(q))
          .then(function (r) { return r.ok ? r.json() : []; })
          .then(function (items) {
            list.innerHTML = (items || []).map(function (p) {
              return '<li><a href="' + p.url + '">' + (p.brand ? p.brand + ' ' : '') + p.name + '</a></li>';
            }).join('');
          })
          .catch(function () { /* offline is fine — form submit still works */ });
      }, 220);
    });
    document.addEventListener('click', function (e) {
      if (!list.contains(e.target) && e.target !== searchInput) { list.innerHTML = ''; }
    });
  }
});
