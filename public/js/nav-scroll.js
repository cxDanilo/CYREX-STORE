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

  // El handler de scroll puede dispararse muchas veces por segundo
  // (sobre todo con scroll de mouse/trackpad con inercia) — sin esto,
  // cada evento recalculaba en el momento un backdrop-filter (blur), que
  // es de las propiedades más caras de repintar que hay. El navegador no
  // llega a mantener el ritmo y el header se siente trabado, más notorio
  // al scrollear rápido y volver. rAF agrupa todo eso a como máximo una
  // vez por frame (~60/s), que es todo lo que la pantalla puede mostrar
  // igual — no cambia la sensación de "atado al scroll", solo evita
  // trabajo repetido de más.
  var ticking = false;

  function update() {
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

    ticking = false;
  }

  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(update);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  update();
});
