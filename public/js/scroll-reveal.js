// Navegar entre tienda↔producto no siempre es una carga real de página
// (ver page-nav.js: reemplaza el <main> con fetch, sin recargar) — un
// simple DOMContentLoaded acá dejaría el próximo producto visitado con
// sus secciones en opacity:0 para siempre, sin nadie observándolas.
// Por eso queda expuesta como función reusable (mismo patrón que
// fadeInImages()/Alpine.initTree() en page-nav.js), para que se pueda
// volver a llamar después de cada swap.
window.initScrollReveal = function (root) {
  root = root || document;

  // [data-reveal-group] arma el stagger sola: cada hijo directo recibe
  // data-reveal + un pequeño delay creciente (tope en el 6to, para que
  // una grilla larga no tarde una eternidad en terminar de aparecer).
  root.querySelectorAll('[data-reveal-group]').forEach(function (group) {
    Array.prototype.forEach.call(group.children, function (child, i) {
      child.setAttribute('data-reveal', '');
      child.style.transitionDelay = (Math.min(i, 5) * 70) + 'ms';
    });
  });

  var targets = root.querySelectorAll('[data-reveal]:not(.is-revealed)');
  if (!targets.length) return;

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    || document.body.classList.contains('motion-reduced');

  if (reduceMotion || !('IntersectionObserver' in window)) {
    targets.forEach(function (el) { el.classList.add('is-revealed'); });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-revealed');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

  targets.forEach(function (el) { observer.observe(el); });
};

window.addEventListener('DOMContentLoaded', function () {
  window.initScrollReveal(document);
});
