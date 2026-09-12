// El widget flotante de "Tasa del día" es position:fixed en la esquina
// inferior derecha -- en mobile, con la grilla de productos a 2
// columnas, tapaba el título/categoría del producto que caía justo
// ahí al hacer scroll. Se lo oculta con un fade mientras el usuario
// scrollea activamente y se lo vuelve a mostrar apenas se detiene, en
// vez de sacarlo del todo (sigue disponible en reposo).
//
// Además se puede cerrar con la X -- ocupa bastante espacio en mobile
// y no todos lo necesitan. Se guarda en sessionStorage (no
// localStorage a propósito): dura mientras esa pestaña/sesión sigue
// abierta, pero vuelve a aparecer la próxima vez que entren al sitio,
// en vez de quedar cerrado para siempre.
(function () {
  var widget = document.querySelector('.exchange-rate-float');
  if (!widget) return;

  var DISMISS_KEY = 'cyrex_exchange_rate_dismissed';

  function wasDismissed() {
    try { return sessionStorage.getItem(DISMISS_KEY) === '1'; } catch (e) { return false; }
  }

  if (wasDismissed()) {
    widget.remove();
    return;
  }

  var closeBtn = widget.querySelector('.exchange-rate-float-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      widget.remove();
      try { sessionStorage.setItem(DISMISS_KEY, '1'); } catch (e) {}
    });
  }

  var hideTimer = null;

  document.addEventListener('scroll', function () {
    widget.classList.add('is-hidden');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(function () {
      widget.classList.remove('is-hidden');
    }, 500);
  }, { passive: true });
})();
