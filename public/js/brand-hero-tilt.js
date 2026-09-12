// Parallax muy sutil entre las capas de producto del hero de una
// página de marca (/marcas/{slug}) al mover el cursor -- cada capa se
// mueve una distancia distinta para dar sensación de profundidad,
// sin llegar a un tilt 3D como el de las product cards
// (product-tilt.js), que es un efecto más marcado pensado para una
// card chica, no para una composición grande de hero.
window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;
  if (window.matchMedia('(hover: none)').matches) return;

  document.querySelectorAll('.brand-hero-composition').forEach(function (el) {
    var layers = el.querySelectorAll('.brand-hero-product');
    if (!layers.length) return;

    el.addEventListener('mousemove', function (e) {
      var rect = el.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width - 0.5;
      var y = (e.clientY - rect.top) / rect.height - 0.5;

      layers.forEach(function (layer, i) {
        var depth = (i + 1) * 6;
        layer.style.transform = 'translate(' + (x * depth).toFixed(1) + 'px, ' + (y * depth).toFixed(1) + 'px)';
      });
    });

    el.addEventListener('mouseleave', function () {
      layers.forEach(function (layer) { layer.style.transform = ''; });
    });
  });
});
