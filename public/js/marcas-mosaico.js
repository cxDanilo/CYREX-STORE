window.addEventListener('DOMContentLoaded', function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    || document.body.classList.contains('motion-reduced');

  document.querySelectorAll('.cms-marcas-mosaico').forEach(function (root) {
    var track = root.querySelector('.cms-marcas-mosaico-track');
    var pages = root.querySelectorAll('.cms-marcas-mosaico-page');
    var dots = root.querySelectorAll('.cms-marcas-mosaico-dot');
    var prevBtn = root.querySelector('.cms-marcas-mosaico-arrow-left');
    var nextBtn = root.querySelector('.cms-marcas-mosaico-arrow-right');
    if (pages.length <= 1) return;

    var current = 0;
    var interval = parseInt(root.dataset.interval || '0', 10);
    var timer = null;

    function goTo(index) {
      current = (index + pages.length) % pages.length;
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
      dots.forEach(function (dot, i) { dot.classList.toggle('is-active', i === current); });
    }

    // Reinicia el timer en cada interacción manual, para que no
    // "pelee" con el visitante avanzando sola justo después de que
    // tocó una flecha o un punto.
    function resetTimer() {
      if (timer) clearInterval(timer);
      if (!reduced && interval > 0) {
        timer = setInterval(function () { goTo(current + 1); }, interval);
      }
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); resetTimer(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); resetTimer(); });
    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () { goTo(i); resetTimer(); });
    });

    resetTimer();
  });
});
