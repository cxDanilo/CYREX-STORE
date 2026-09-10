window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;

  document.querySelectorAll('.cms-social-rotator').forEach(function (rotator) {
    var pages = rotator.querySelectorAll('.cms-social-page');
    if (pages.length <= 1) return;

    var interval = parseInt(rotator.dataset.interval || '5000', 10);
    var current = 0;

    // Si esta página se abandona por una navegación suave (page-nav.js
    // reemplaza <main> con innerHTML), este setInterval nunca se
    // enteraba y seguía corriendo para siempre sobre nodos ya
    // desconectados del documento. Se corta solo apenas nota que
    // "rotator" ya no está en el documento.
    var timerId = setInterval(function () {
      if (!rotator.isConnected) { clearInterval(timerId); return; }
      var next = (current + 1) % pages.length;
      pages[current].classList.remove('is-active');
      pages[next].classList.add('is-active');
      current = next;
    }, interval);
  });
});
