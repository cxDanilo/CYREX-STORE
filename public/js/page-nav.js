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

  // "page-nav-enter" queda pegada en <main> entre una navegación y la
  // siguiente (recién se saca justo antes de agregarla de nuevo, ver
  // applyPage) — mientras tanto, su animation:...forwards sobre el
  // transform de cada card de la grilla deja ese transform trabado
  // para siempre, pisando el que pone product-tilt.js al pasar el
  // mouse. Por eso el tilt se perdía después de la primera navegación
  // suave y no volvía ni pasando a otra página. Se saca sola apenas
  // termina la entrada en cascada, para devolverle el transform al
  // hover normal.
  var enterCleanupTimer = null;

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

  // El banner de categoría (.page-head.has-banner) se pinta con
  // background-image, no con un <img> — sin esto, el fade-in de
  // page-nav-section-in arrancaba apenas se insertaba el HTML, y si esa
  // foto puntual todavía no estaba en caché del navegador se veía
  // aparecer de golpe un instante después de que el resto del encabezado
  // ya había terminado de entrar ("se ve muy seco" fue el reporte
  // exacto). Precargarla ANTES de aplicar la página nueva hace que el
  // fade-in del encabezado entero arranque recién cuando la foto ya está
  // lista — como máximo demora 500ms extra, para no colgar la
  // navegación entera si esa imagen puntual fallara en cargar.
  function extractBannerUrl(html) {
    var match = html.match(/--shop-banner-image:\s*url\(['"]?([^'")]+)['"]?\)/);

    return match ? match[1] : null;
  }

  function preloadImage(url, timeoutMs) {
    return new Promise(function (resolve) {
      if (!url) { resolve(); return; }
      var settled = false;
      var finish = function () { if (!settled) { settled = true; resolve(); } };
      var img = new Image();
      img.onload = finish;
      img.onerror = finish;
      img.src = url;
      setTimeout(finish, timeoutMs);
    });
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

  // Guardamos el HTML de cada página ya visitada en esta sesión — el
  // "volver" (popstate) lo restaura DE ESTA CACHÉ, sin esperar red. Esto
  // no es solo optimización: Safari en iOS, con el gesto de deslizar
  // desde el borde para volver atrás, espera una respuesta casi
  // instantánea — si el popstate se queda esperando un fetch (aunque
  // sea rápido), a veces se rinde y termina recargando la página de
  // verdad en vez de dejar que este script la restaure. Sirviendo la
  // versión ya conocida al toque (sin red de por medio) le damos esa
  // respuesta inmediata. El avance (pushState) SIEMPRE pide una versión
  // fresca — el precio/stock puede haber cambiado.
  var pageCache = {};

  // La página con la que arrancó esta pestaña (carga real del servidor,
  // nunca pasó por loadPage()) también hay que dejarla en la caché —
  // si no, el PRIMER "volver" de la sesión (al punto de partida) siempre
  // cae al fetch, justo el caso más común de todos.
  pageCache[location.href] = { title: document.title, mainHTML: main.innerHTML };

  function applyPage(entry, opts) {
    document.title = entry.title;
    main.innerHTML = entry.mainHTML;
    document.body.classList.remove('page-nav-loading');
    clearTimeout(enterCleanupTimer);
    main.classList.remove('page-nav-enter');
    void main.offsetWidth;
    main.classList.add('page-nav-enter');
    enterCleanupTimer = setTimeout(function () {
      main.classList.remove('page-nav-enter');
    }, 1150);
    fadeInImages(main);

    // El menú flotante de categorías vive en partials/nav.blade.php,
    // FUERA de <main> — su visibilidad ("solo en /tienda" o "en todo
    // el sitio") se decide una sola vez, en el request real que
    // renderizó el <nav>, y ese <nav> nunca se vuelve a pedir acá. Sin
    // esto, llegar a /tienda navegando (en vez de con una carga real)
    // lo dejaba invisible para siempre, aunque el destino fuera
    // /tienda — solo un F5 lo hacía reaparecer.
    var catFloat = document.querySelector('.cat-float');
    if (catFloat && catFloat.dataset.categoryMenuScope !== 'all') {
      catFloat.style.display = (location.pathname === '/tienda') ? '' : 'none';
    }

    // Igual que en shop-ajax.js: el HTML inyectado con innerHTML no lo
    // detecta Alpine solo.
    window.Alpine.initTree(main);

    if (opts.pushState) {
      window.scrollTo({ top: 0, behavior: 'instant' });
    } else {
      var scrollY = (window.history.state && typeof window.history.state.scrollY === 'number') ? window.history.state.scrollY : 0;
      window.scrollTo(0, scrollY);
    }

    // Sin esto, Analytics solo cuenta una vista de página por carga
    // real — cualquier producto al que se llega por navegación suave
    // quedaría invisible para las métricas del negocio.
    if (window.gtag) {
      window.gtag('event', 'page_view', { page_title: document.title, page_location: location.href });
    }
  }

  function loadPage(url, opts) {
    var cached = pageCache[url];
    if (cached && !opts.pushState) {
      applyPage(cached, opts);
      return;
    }

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

        var entry = { title: doc.title, mainHTML: newMain.innerHTML };
        pageCache[url] = entry;

        if (opts.pushState) {
          history.replaceState(Object.assign({}, history.state, { scrollY: window.scrollY }), '', location.href);
          history.pushState({ scrollY: 0 }, '', url);
        }

        preloadImage(extractBannerUrl(entry.mainHTML), 500).then(function () {
          if (signal.aborted) return;
          applyPage(entry, opts);
        });
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
