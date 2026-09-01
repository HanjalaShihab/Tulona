/* Tulona — animations & interactions. Zero dependencies, tiny, respects prefers-reduced-motion. */
(function () {
  'use strict';

  var doc = document;
  var body = document.body;
  var reduced = false;
  if (window.matchMedia) reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Header scroll state + back-to-top */
  var header = doc.querySelector('.site-header');
  var toTop = doc.querySelector('[data-totop]');
  function onScroll() {
    var y = window.scrollY || 0;
    if (header) header.classList.toggle('scrolled', y > 10);
    if (toTop) toTop.classList.toggle('show', y > 600);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  if (toTop) {
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
    });
  }

  /* Mobile drawer */
  var burger = doc.querySelector('[data-drawer-open]');
  var drawer = doc.querySelector('[data-drawer]');
  var scrim = doc.querySelector('[data-scrim]');
  var drawerClosers = doc.querySelectorAll('[data-drawer-close]');
  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('open', open);
    if (scrim) {
      scrim.classList.toggle('show', open);
      scrim.hidden = !open;
    }
    if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    body.classList.toggle('nav-lock', open);
  }
  if (burger) {
    burger.addEventListener('click', function () {
      setDrawer(!drawer.classList.contains('open'));
    });
  }
  if (scrim) scrim.addEventListener('click', function () { setDrawer(false); });
  drawerClosers.forEach(function (el) {
    el.addEventListener('click', function () { setDrawer(false); });
  });

  /* Reveal-on-scroll */
  var revealEls = doc.querySelectorAll('[data-reveal]');
  var canObserve = 'IntersectionObserver' in window;
  function revealAll() {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }
  if (reduced || !canObserve) {
    revealAll();
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          el.classList.add('in');
          io.unobserve(el);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    revealEls.forEach(function (el) {
      var d = parseInt(el.getAttribute('data-delay'), 10) || 0;
      if (d) el.style.transitionDelay = d + 'ms';
      io.observe(el);
    });
  }

  /* Animated counters */
  var counters = doc.querySelectorAll('[data-count]');
  if (!reduced && canObserve) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        var target = parseFloat(el.getAttribute('data-count'));
        var suffix = el.getAttribute('data-suffix') || '';
        var dur = 1500;
        var startTs = null;
        function frame(ts) {
          if (startTs === null) startTs = ts;
          var p = Math.min((ts - startTs) / dur, 1);
          var eased = 1 - Math.pow(1 - p, 3);
          el.textContent = Math.round(target * eased).toLocaleString() + suffix;
          if (p < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
        cio.unobserve(el);
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { cio.observe(el); });
  }

  /* Header search suggestions via /api/search */
  var suggestInput = doc.querySelector('[data-suggest]');
  var suggestList = doc.querySelector('.suggest');
  var suggestTimer = null;
  var suggestAbort = null;
  if (suggestInput && suggestList) {
    suggestInput.addEventListener('input', function () {
      var q = suggestInput.value.trim();
      clearTimeout(suggestTimer);
      if (q.length < 2) {
        suggestList.classList.remove('open');
        return;
      }
      suggestTimer = setTimeout(function () {
        if (suggestAbort) suggestAbort.abort();
        suggestAbort = new AbortController();
        var url = '/api/search?q=' + encodeURIComponent(q);
        fetch(url, { signal: suggestAbort.signal })
          .then(function (r) { return r.ok ? r.json() : null; })
          .then(function (data) {
            if (!data) return;
            var items = (data.products || []).slice(0, 6);
            suggestList.innerHTML = '';
            items.forEach(function (p) {
              var li = doc.createElement('li');
              var a = doc.createElement('a');
              a.href = '/product/' + p.slug;
              a.textContent = p.name;
              li.appendChild(a);
              suggestList.appendChild(li);
            });
            suggestList.classList.toggle('open', items.length > 0);
          })
          .catch(function () { });
      }, 250);
    });
    doc.addEventListener('click', function (e) {
      if (suggestList.classList.contains('open') && !suggestInput.contains(e.target) && !suggestList.contains(e.target)) {
        suggestList.classList.remove('open');
      }
    });
  }

  /* Marquee duplication for seamless loops */
  doc.querySelectorAll('[data-marquee]').forEach(function (marquee) {
    var track = marquee.querySelector('.marquee-track');
    var group = marquee.querySelector('.marquee-group');
    if (!track || !group) return;
    var clone = group.cloneNode(true);
    clone.setAttribute('aria-hidden', 'true');
    track.appendChild(clone);
  });

  /* Gentle 3D tilt on hero cards (fine pointers only) */
  if (!reduced && window.matchMedia && window.matchMedia('(pointer: fine)').matches) {
    doc.querySelectorAll('[data-tilt]').forEach(function (card) {
      var raf = null;
      card.addEventListener('mousemove', function (e) {
        if (raf) return;
        raf = requestAnimationFrame(function () {
          var r = card.getBoundingClientRect();
          var px = (e.clientX - r.left) / r.width -  0.5;
          var py = (e.clientY - r.top) / r.height -  0.5;
          var rx = (px * 7).toFixed(2);
          var ry = ((-(py * 9)).toFixed(2));
          card.style.transform = 'perspective(900px) rotateY(' + rx + 'deg) rotateX(' + ry + 'deg) translateY(-6px)';
          raf = null;
        });
      });
      card.addEventListener('mouseleave', function () {
        card.style.transform = '';
      });
    });
  }
})();
