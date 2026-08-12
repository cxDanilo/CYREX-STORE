window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(hover: none)').matches) return;

  // Delegado en document por la misma razón que product-tilt.js: la
  // galería del producto se reemplaza entera al navegar sin recarga
  // (public/js/page-nav.js), así que no puede depender de un listener
  // enganchado a un .gallery-main específico que puede dejar de existir.
  var activeGallery = null;

  document.addEventListener('mousemove', function (e) {
    var container = e.target.closest('.gallery-main');

    if (container !== activeGallery && activeGallery) {
      var prevImg = activeGallery.querySelector('img');
      if (prevImg) prevImg.style.transformOrigin = '50% 50%';
    }
    activeGallery = container;

    if (!container) return;

    var img = container.querySelector('img');
    if (!img) return;

    var rect = container.getBoundingClientRect();
    var x = ((e.clientX - rect.left) / rect.width) * 100;
    var y = ((e.clientY - rect.top) / rect.height) * 100;
    img.style.transformOrigin = x + '% ' + y + '%';
  });
});
