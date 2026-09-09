window.addEventListener('DOMContentLoaded', function () {
  var bar = document.getElementById('scroll-progress-bar');
  if (!bar) return;

  var ticking = false;

  function update() {
    ticking = false;
    var scrollable = document.documentElement.scrollHeight - window.innerHeight;
    var progress = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
    bar.style.width = Math.min(100, Math.max(0, progress)) + '%';
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(update);
      ticking = true;
    }
  }, { passive: true });

  // Recalcula también al cambiar de tamaño — una imagen que carga
  // tarde puede alargar la página sin que haya scroll de por medio.
  window.addEventListener('resize', update);

  update();
});
