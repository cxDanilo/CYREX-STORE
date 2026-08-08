window.addEventListener('DOMContentLoaded', function () {
  var nav = document.querySelector('nav.nav-hero-mode');
  var hint = document.querySelector('.cms-hero-scroll-hint');
  if (!nav && !hint) return;

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
    if (nav) {
      var progress = (window.scrollY - startAt) / span;
      progress = Math.min(1, Math.max(0, progress));
      nav.style.setProperty('--nav-progress', progress);
    }

    // El aviso de "seguí scrolleando" solo tiene sentido antes de que el
    // visitante toque la rueda — se apaga rápido, no espera al mismo
    // punto que el header (eso sería demasiado tarde).
    if (hint) {
      hint.style.opacity = Math.max(0, 1 - window.scrollY / 150);
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});
