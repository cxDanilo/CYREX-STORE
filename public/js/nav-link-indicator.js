// Barra líquida que se desliza y rebota bajo el link del header que
// estás mirando (Inicio/Tienda/lo que haya en el menú). Ver el CSS de
// .nav-link-indicator en app.css para la animación en sí -- esto solo
// mide la posición real de cada link y se la pasa a la barra.
(function () {
  var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.body.classList.contains('motion-reduced');
  if (!canHover || reduced) return;

  document.querySelectorAll('.nav-links-wrap').forEach(function (wrap) {
    var indicator = wrap.querySelector('.nav-link-indicator');
    var links = Array.prototype.slice.call(wrap.querySelectorAll('.nav-home-link'));
    if (!indicator || !links.length) return;

    links.forEach(function (link) {
      link.addEventListener('mouseenter', function () {
        indicator.style.left = link.offsetLeft + 'px';
        indicator.style.width = link.offsetWidth + 'px';
      });
    });
    // El show/hide de la barra lo maneja solo el CSS (:hover en
    // .nav-links-wrap) -- si se apagara la opacidad acá con JS
    // quedaría un estilo inline que le gana para siempre a esa regla
    // y la barra no volvería a aparecer en el siguiente hover.
  });
})();
