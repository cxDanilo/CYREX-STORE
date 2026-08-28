window.addEventListener('DOMContentLoaded', function () {
  // El listener va en document (no en .shop-main) porque .shop-main se
  // puede destruir y volver a crear entera si se navega a un producto
  // y se vuelve (ver public/js/page-nav.js, que reemplaza el <main>
  // completo) — un listener enganchado directo al nodo viejo quedaría
  // muerto. Por eso .shop-main se vuelve a buscar en cada click, nunca
  // se guarda en una variable de afuera.

  // Pedido en vuelo — si el visitante clickea "Siguiente" dos veces
  // rápido, se cancela el primer pedido en vez de dejar que una
  // respuesta vieja llegue después de la nueva y la pise.
  var currentController = null;

  // "shop-main-enter" nunca se sacaba después de que la animación de
  // entrada terminaba — se quedaba pegada para siempre en .shop-main.
  // Como esa clase tiene animation:...forwards sobre el transform de
  // cada card (para la entrada en cascada), "forwards" deja ese
  // transform trabado incluso después de terminar la animación — y
  // eso pisaba para siempre el transform que pone product-tilt.js al
  // pasar el mouse (por eso el tilt solo andaba en la carga real de la
  // página, antes de la primera paginación/orden/filtro). Sacando la
  // clase apenas termina la animación, el transform vuelve a manos del
  // hover normal.
  var enterCleanupTimer = null;

  // Solo se intercepta un link si: es del mismo sitio, apunta a esta
  // MISMA página (/tienda), no está marcado data-no-ajax, y está
  // DENTRO de .shop-main — así un link del footer o del mega-menú que
  // también apunte a /tienda (pero cambiando de categoría) sigue
  // siendo una navegación normal: si se interceptara, la grilla se
  // actualizaría pero el título/breadcrumb de arriba (fuera de
  // .shop-main) se quedarían con el nombre de la categoría vieja.
  function isAjaxEligible(shopMain, link) {
    return link.origin === location.origin
      && link.pathname === location.pathname
      && !link.hasAttribute('data-no-ajax')
      && shopMain.contains(link);
  }

  // Marca cada <img> de card como "ya cargada" para que su fade-in (ver
  // app.css, .shop-main-enter .card-media img) dispare apenas la foto
  // en sí termine de bajar — no apenas se insertó el HTML, que es
  // bastante antes. img.complete cubre el caso de una imagen que ya
  // estaba en caché del navegador (ahí 'load' nunca dispararía porque
  // ya disparó antes de que este listener existiera).
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

  function loadUrl(shopMain, url, opts) {
    if (currentController) currentController.abort();
    currentController = new AbortController();
    var signal = currentController.signal;

    shopMain.classList.add('shop-main-loading');

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: signal })
      .then(function (res) {
        if (!res.ok) throw new Error('bad status');
        return res.text();
      })
      .then(function (html) {
        // Si mientras esperábamos la respuesta el visitante ya se fue
        // a otra página (public/js/page-nav.js reemplazó el <main>),
        // este .shop-main quedó desconectado del documento — aplicar
        // el swap igual no se ve (nada renderiza un nodo huérfano)
        // pero sí pushearía una entrada de historial con la URL vieja
        // encima de la página nueva. Cortar acá evita eso.
        if (!shopMain.isConnected) return;

        shopMain.innerHTML = html;
        shopMain.classList.remove('shop-main-loading');
        shopMain.classList.add('shop-main-enter');
        fadeInImages(shopMain);

        clearTimeout(enterCleanupTimer);
        enterCleanupTimer = setTimeout(function () {
          shopMain.classList.remove('shop-main-enter');
        }, 1050);

        // El HTML recién insertado con innerHTML no lo detecta Alpine
        // solo — sin esto, el desplegable de orden y los botones de
        // "agregar rápido" de cada card quedan muertos después del
        // primer cambio de página/orden/filtro.
        window.Alpine.initTree(shopMain);

        if (opts.pushState) {
          // Guarda dónde estaba scrolleada la entrada ACTUAL antes de
          // avanzar a la nueva — así, si el visitante vuelve acá con
          // el botón atrás, public/js/page-nav.js (dueño único del
          // popstate de todo el sitio) puede restaurar el scroll en
          // vez de arrancar arriba de todo.
          history.replaceState(Object.assign({}, history.state, { scrollY: window.scrollY }), '', location.href);
          history.pushState({ scrollY: 0 }, '', url);
        }

        if (opts.scrollIntoView && window.scrollY > shopMain.offsetTop) {
          shopMain.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      })
      .catch(function (err) {
        // Un abort real (lo cancelamos nosotros mismos arriba) no es un
        // error de verdad — solo se navega de vuelta a la página normal
        // si el pedido falló de verdad (sin internet, error del server).
        if (err.name === 'AbortError') return;
        shopMain.classList.remove('shop-main-loading');
        window.location.href = url;
      });
  }

  document.addEventListener('click', function (event) {
    var shopMain = document.querySelector('.shop-main');
    if (!shopMain) return;

    var link = event.target.closest('a');
    if (!link || !isAjaxEligible(shopMain, link)) return;

    event.preventDefault();
    loadUrl(shopMain, link.href, { pushState: true, scrollIntoView: true });
  });
});
