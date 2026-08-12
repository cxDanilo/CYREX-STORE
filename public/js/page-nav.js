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

  // Solo tienda↔producto — nunca toca category=... del mega-menú/
  // footer/home ni ningún otro link del sitio, a propósito: esas
  // páginas no comparten este mismo "molde" y no tiene sentido meterlas
  // acá sin pensarlo por separado. pathname !== location.pathname es
  // clave: sin eso, este listener también capturaría los links de
  // orden/filtro/paginación DENTRO de /tienda, que ya maneja
  // shop-ajax.js — con los dos escuchando el mismo click a la vez.
  function isEligible(link) {
    return link.origin === location.origin
      && !link.hasAttribute('data-no-ajax')
      && main.contains(link)
      && link.pathname !== location.pathname
      && (link.pathname === '/tienda' || link.pathname.indexOf('/producto/') === 0);
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
