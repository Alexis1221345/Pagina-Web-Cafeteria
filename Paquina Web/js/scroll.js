/* =============================================
   SCROLL REVEAL + NAV ACTIVE STATE
   Espera a que loader.js inyecte las secciones.
============================================= */
document.addEventListener('sectionsLoaded', function () {

  /* ── Scroll reveal ──────────────────────── */
  var revealObs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, { threshold: 0.10, rootMargin: '0px 0px -36px 0px' });

  document.querySelectorAll('.reveal').forEach(function (el) {
    revealObs.observe(el);
  });

  window.revealObs = revealObs;

  /* ── Nav active state ───────────────────── */
  var navAnchors  = document.querySelectorAll('.nav-links a');
  var allSections = document.querySelectorAll('section[id]');

  var sectionObs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var id = entry.target.id;
        navAnchors.forEach(function (a) {
          a.classList.toggle('active', a.getAttribute('href') === '#' + id);
        });
      }
    });
  }, { threshold: 0.28 });

  allSections.forEach(function (s) { sectionObs.observe(s); });
});
