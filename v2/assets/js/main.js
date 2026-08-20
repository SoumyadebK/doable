// Nav shrink on scroll, mobile menu, smooth in-page scroll, reveal-on-scroll.
(function () {
  var nav = document.getElementById('site-nav');
  if (nav) {
    var onScroll = function () {
      if (window.scrollY > 20) nav.classList.add('glass', 'shadow-sm');
      else nav.classList.remove('glass', 'shadow-sm');
    };
    window.addEventListener('scroll', onScroll);
    onScroll();
  }

  var burger = document.getElementById('menu-toggle');
  var mobile = document.getElementById('mobile-menu');
  if (burger && mobile) {
    burger.addEventListener('click', function () { mobile.classList.toggle('hidden'); });
    mobile.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { mobile.classList.add('hidden'); });
    });
  }

  // Smooth scroll for same-page #anchors
  document.querySelectorAll('a[href*="#"]').forEach(function (a) {
    a.addEventListener('click', function (ev) {
      var href = a.getAttribute('href');
      var hash = href.indexOf('#') > -1 ? href.substring(href.indexOf('#')) : '';
      if (!hash || hash === '#') return;
      var onSamePage = href.indexOf('#') === 0 || href.indexOf(location.pathname + '#') > -1 || href.indexOf('/#') === href.indexOf('#') - 1;
      var target = document.querySelector(hash);
      if (target && (href.charAt(0) === '#' || onSamePage)) {
        ev.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // Reveal on scroll
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { if (en.isIntersecting) en.target.classList.add('in'); });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
})();
