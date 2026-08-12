window.addEventListener('DOMContentLoaded', function () {
  var shopMain = document.querySelector('.shop-main');
  if (!shopMain) return;

  // Pedido en vuelo — si el visitante clickea "Siguiente" dos veces
  // rápido, se cancela el primer pedido en vez de dejar que una
  // respuesta vieja llegue después de la nueva y la pise.
  var currentController = null;

  // Solo se intercepta un link si: es del mismo sitio, apunta a esta
  // MISMA página (/tienda), y no está marcado data-no-ajax — así
  // "Ver todo el catálogo ×" (que cambia de categoría, y por lo tanto
  // debería actualizar también el título/breadcrumb de arriba, que
  // este intercambio no toca) sigue siendo una navegación normal.
  function isAjaxEligible(link) {
    return link.origin === location.origin
      && link.pathname === location.pathname
      && !link.hasAttribute('data-no-ajax');
  }

  function loadUrl(url, opts) {
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
        shopMain.innerHTML = html;
        shopMain.classList.remove('shop-main-loading');
        shopMain.classList.add('shop-main-enter');

        // El HTML recién insertado con innerHTML no lo detecta Alpine
        // solo — sin esto, el desplegable de orden y los botones de
        // "agregar rápido" de cada card quedan muertos después del
        // primer cambio de página/orden/filtro.
        window.Alpine.initTree(shopMain);

        if (opts.pushState) history.pushState({ shopAjax: true }, '', url);

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

  shopMain.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (!link || !shopMain.contains(link) || !isAjaxEligible(link)) return;

    event.preventDefault();
    loadUrl(link.href, { pushState: true, scrollIntoView: true });
  });

  // El botón atrás/adelante del navegador no dispara 'click' — sin
  // esto, romperían history.pushState (quedarían mostrando el
  // contenido viejo con la URL ya cambiada). No se fuerza el scroll
  // acá: al volver atrás, el navegador ya restaura la posición de
  // scroll que tenía esa página — forzar otro scroll encima se siente
  // como que "te empuja" de vuelta arriba de la nada.
  window.addEventListener('popstate', function () {
    loadUrl(location.href, { pushState: false, scrollIntoView: false });
  });
});
