// nav vive fuera de <main> (partials/nav.blade.php, incluido una sola
// vez en el layout) — la navegación suave tienda↔producto↔home
// (page-nav.js) nunca lo vuelve a tocar, solo reemplaza <main>. La
// clase nav-hero-mode que el servidor le pone al <nav> en la carga
// real (según si esa página tenía el hero-video) se quedaría pegada
// para siempre si algo no la vuelve a evaluar tras cada swap — por
// eso esta función se re-consulta el DOM de cero cada vez que corre
// (mismo criterio que el parallax del banner, más abajo) y además
// queda expuesta en window para que page-nav.js la llame de nuevo
// después de cada navegación suave, sin depender de que el scroll
// por sí solo dispare un evento (si ya estabas en scrollY 0 en las
// dos páginas, scrollTo(0,0) no dispara 'scroll').
window.updateNavHeroMode = function () {
  var nav = document.querySelector('nav');
  var hero = document.querySelector('.cms-hero-video');
  var hint = document.querySelector('.cms-hero-scroll-hint');

  if (nav) nav.classList.toggle('nav-hero-mode', !!hero);
  if (!hero) return;

  // No cambia nada hasta que se scrolleó el 75% del alto del video —
  // recién ahí empieza a pasar de transparente a sólido, y termina de
  // asentarse al llegar al final del video. Todo atado al scroll real,
  // sin transición de tiempo — por eso se siente "al toque" con cada
  // movimiento de la rueda, no una animación que se dispara aparte.
  var heroHeight = hero.offsetHeight;
  var startAt = heroHeight * 0.75;
  var endAt = heroHeight;
  var span = Math.max(endAt - startAt, 1);

  if (nav) {
    var progress = (window.scrollY - startAt) / span;
    progress = Math.min(1, Math.max(0, progress));
    // El blur del header (backdrop-filter) es carísimo de recalcular, y
    // cada valor de --nav-progress distinto dispara un recálculo nuevo.
    // Con progress "crudo" (precisión de punto flotante atada 1:1 al
    // scroll en píxeles) eso son decenas de recálculos por segundo al
    // scrollear rápido — de ahí el trabón. Redondeando a pasos de 5% se
    // ve igual de fluido (20 pasos en ~200px de scroll es imperceptible)
    // pero corta esa cantidad de recálculos a una fracción.
    progress = Math.round(progress * 20) / 20;
    nav.style.setProperty('--nav-progress', progress);
  }

  // El aviso de "seguí scrolleando" solo tiene sentido antes de que el
  // visitante toque la rueda — se apaga rápido, no espera al mismo
  // punto que el header (eso sería demasiado tarde).
  if (hint) {
    hint.style.opacity = Math.max(0, 1 - window.scrollY / 150);
  }
};

window.addEventListener('DOMContentLoaded', function () {
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

  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      ticking = false;
      window.updateNavHeroMode();
    });
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.updateNavHeroMode();
});

// Parallax sutil del banner de categoría (tienda) y del armador — la
// imagen se mueve un poco más lento que el resto al scrollear, da
// sensación de profundidad. .page-head mide poco (~150-250px), así que
// el recorrido es corto a propósito: más que eso se ve forzado en un
// banner tan bajo. Se busca el elemento DE NUEVO en cada frame (no se
// guarda una sola vez) porque la navegación suave de la tienda
// (page-nav.js) reemplaza el <main> entero al cambiar de categoría.
window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;

  var ticking = false;

  function update() {
    ticking = false;
    var pageHead = document.querySelector('.page-head.has-banner');
    if (!pageHead) return;

    var rect = pageHead.getBoundingClientRect();
    var mid = window.innerHeight / 2;
    // -1 = el banner todavía está bien abajo en pantalla, 1 = ya se fue
    // bien arriba, 0 = centrado en el viewport.
    var progress = (mid - (rect.top + rect.height / 2)) / (mid + rect.height / 2);
    progress = Math.min(1, Math.max(-1, progress));
    pageHead.style.setProperty('--banner-parallax', (progress * 40).toFixed(1) + 'px');
    pageHead.style.setProperty('--banner-parallax-scale', (1 + Math.abs(progress) * 0.06).toFixed(3));
  }

  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(update);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  update();
});
