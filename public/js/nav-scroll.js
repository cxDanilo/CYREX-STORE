window.addEventListener('DOMContentLoaded', function () {
  var nav = document.querySelector('nav.nav-hero-mode');
  if (!nav) return;

  var hero = document.querySelector('.cms-hero-video');
  var heroHeight = hero ? hero.offsetHeight : window.innerHeight;

  // No cambia nada hasta que se scrolleó el 75% del alto del video —
  // recién ahí empieza a pasar de transparente a sólido, y termina de
  // asentarse al llegar al final del video. Todo atado al scroll real,
  // sin transición de tiempo — por eso se siente "al toque" con cada
  // movimiento de la rueda, no una animación que se dispara aparte.
  var startAt = heroHeight * 0.75;
  var endAt = heroHeight;
  var span = Math.max(endAt - startAt, 1);

  function onScroll() {
    var progress = (window.scrollY - startAt) / span;
    progress = Math.min(1, Math.max(0, progress));
    nav.style.setProperty('--nav-progress', progress);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});
