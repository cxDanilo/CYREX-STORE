// El widget flotante de "Tasa del día" es position:fixed en la esquina
// inferior derecha -- en mobile, con la grilla de productos a 2
// columnas, tapaba el título/categoría del producto que caía justo
// ahí al hacer scroll. Se lo oculta con un fade mientras el usuario
// scrollea activamente y se lo vuelve a mostrar apenas se detiene, en
// vez de sacarlo del todo (sigue disponible en reposo).
(function () {
  var widget = document.querySelector('.exchange-rate-float');
  if (!widget) return;

  var hideTimer = null;

  document.addEventListener('scroll', function () {
    widget.classList.add('is-hidden');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(function () {
      widget.classList.remove('is-hidden');
    }, 500);
  }, { passive: true });
})();
