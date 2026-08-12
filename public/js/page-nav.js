window.addEventListener('DOMContentLoaded', function () {
  // <main> nunca se destruye (layouts/app.blade.php lo envuelve una
  // sola vez) — solo se le reemplaza el contenido en cada navegación
  // "suave", así que es seguro guardarlo una sola vez acá.
  var main = document.querySelector('main');
  if (!main) return;

  // La restauración automática de scroll del navegador no sirve acá:
  // corre en el momento de una navegación REAL, pero nuestro swap es
  // async (llega después de un fetch) — para cuando el contenido está
  // listo, el navegador ya intentó (y falló) restaurar. Se apaga y se
  // maneja a mano guardando/restaurando scrollY en el estado de cada
  // entrada del historial (ver más abajo y en shop-ajax.js).
  history.scrollRestoration = 'manual';

  var currentController = null;

  // Solo tienda↔producto (nunca home, admin, contacto, etc. — esas
  // páginas no comparten este mismo "molde" y no tiene sentido meterlas
  // acá sin pensarlo por separado). Esto SÍ incluye links a /tienda que
  // vienen de fuera de <main> — el mega-menú, la barra lateral flotante
  // de categorías y el footer viven en partials/nav.blade.php, antes
  // del <main> en el layout, así que un link ahí nunca se pierde por un
  // reemplazo de contenido (el propio <nav> nunca se destruye).
  //
  // La única exclusión real es "adentro de .shop-main" — eso ya lo
  // maneja shop-ajax.js con su propio swap más chico y más rápido (solo
  // el fragmento, no la página entera). Sin esa exclusión, un click en
  // "ordenar" dispararía los dos scripts a la vez sobre el mismo link.
  function isEligible(link) {
    if (link.origin !== location.origin) return false;

    // Mismo lugar exacto (mismo path Y mismos parámetros) — no hay
    // nada que navegar, esto sería un ancla o un link a la página tal
    // cual está.
    if (link.pathname === location.pathname && link.search === location.search) return false;

    // Un link DENTRO de .shop-main que sigue apuntando al MISMO
    // pathname (/tienda) es orden/filtro/paginación — eso ya lo maneja
    // shop-ajax.js con su propio swap más chico y más rápido (solo el
    // fragmento, no la página entera). Ojo: esto NO debe excluir las
    // cards de producto de la grilla (aunque también viven dentro de
    // .shop-main) — esas apuntan a OTRO pathname (/producto/...), así
    // que el chequeo de pathname de abajo ya las deja pasar solas. Solo
    // "Ver todo el catálogo ×" necesita el escape explícito con
    // data-page-nav, porque cambia de categoría manteniendo el mismo
    // pathname /tienda (afecta el encabezado, que shop-ajax.js no toca).
    var shopMain = document.querySelector('.shop-main');
    if (shopMain && shopMain.contains(link) && link.pathname === location.pathname && !link.hasAttribute('data-page-nav')) {
      return false;
    }

    return link.pathname === '/tienda' || link.pathname.indexOf('/producto/') === 0;
  }

  // Ver shop-ajax.js: mismo motivo — la card entra antes de que la
  // imagen en sí termine de bajar de la red, así que la foto necesita
  // su propio fade-in disparado por 'load', no por la inserción del
  // HTML.
  function fadeInImages(container) {
    container.querySelectorAll('.card-media img').forEach(function (img) {
      if (img.complete) {
        img.classList.add('is-loaded');
      } else {
        img.addEventListener('load', function () { img.classList.add('is-loaded'); });
        img.addEventListener('error', function () { img.classList.add('is-loaded'); });
      }
    });
  }

  function loadPage(url, opts) {
    if (currentController) currentController.abort();
    currentController = new AbortController();
    var signal = currentController.signal;

    document.body.classList.add('page-nav-loading');

    fetch(url, { signal: signal })
      .then(function (res) {
        if (!res.ok) throw new Error('bad status');
        return res.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var newMain = doc.querySelector('main');
        if (!newMain) throw new Error('sin <main> en la respuesta');

        if (opts.pushState) {
          history.replaceState(Object.assign({}, history.state, { scrollY: window.scrollY }), '', location.href);
          history.pushState({ scrollY: 0 }, '', url);
        }

        document.title = doc.title;
        main.innerHTML = newMain.innerHTML;
        document.body.classList.remove('page-nav-loading');
        main.classList.remove('page-nav-enter');
        void main.offsetWidth;
        main.classList.add('page-nav-enter');
        fadeInImages(main);

        // Igual que en shop-ajax.js: el HTML inyectado con innerHTML
        // no lo detecta Alpine solo.
        window.Alpine.initTree(main);

        if (opts.pushState) {
          window.scrollTo({ top: 0, behavior: 'instant' });
        } else {
          var scrollY = (window.history.state && typeof window.history.state.scrollY === 'number') ? window.history.state.scrollY : 0;
          window.scrollTo(0, scrollY);
        }

        // Sin esto, Analytics solo cuenta una vista de página por carga
        // real — cualquier producto al que se llega por navegación
        // suave quedaría invisible para las métricas del negocio.
        if (window.gtag) {
          window.gtag('event', 'page_view', { page_title: document.title, page_location: location.href });
        }
      })
      .catch(function (err) {
        if (err.name === 'AbortError') return;
        document.body.classList.remove('page-nav-loading');
        window.location.href = url;
      });
  }

  document.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (!link || !isEligible(link)) return;

    event.preventDefault();
    loadPage(link.href, { pushState: true });
  });

  // Dueño único del popstate de todo el sitio (tienda y producto por
  // igual) — shop-ajax.js NO tiene el suyo propio, a propósito: si lo
  // tuviera, un back que caiga en /producto/{slug} le pediría un
  // fragmento a una ruta que no sabe devolver fragmentos.
  window.addEventListener('popstate', function () {
    loadPage(location.href, { pushState: false });
  });
});
