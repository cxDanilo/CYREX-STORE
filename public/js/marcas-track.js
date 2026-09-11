// Frenado/retomada progresiva del carrusel de marcas (bloque CMS
// "Marcas") al pasar el mouse por encima. Antes esto era un simple
// animation-play-state:paused por CSS -- funcionaba, pero cortaba el
// movimiento en seco de un frame al otro. Acá se le baja la velocidad
// real a la misma animación CSS (Animation.playbackRate) de a poco en
// vez de pausarla de golpe, y se vuelve a subir igual de progresivo al
// sacar el mouse.
(function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.body.classList.contains('motion-reduced');
  if (reduced) return;

  document.querySelectorAll('.cms-marcas-wrap').forEach(function (wrap) {
    var track = wrap.querySelector('.cms-marcas-track');
    if (!track) return;

    var anim = track.getAnimations()[0];
    if (!anim) return;

    var raf = null;
    var target = 1;

    function step() {
      var current = anim.playbackRate;
      var next = current + (target - current) * 0.18;
      if (Math.abs(next - target) < 0.01) next = target;
      anim.playbackRate = next;
      if (next !== target) {
        raf = requestAnimationFrame(step);
      } else {
        raf = null;
      }
    }

    function rampTo(value) {
      target = value;
      if (!raf) raf = requestAnimationFrame(step);
    }

    wrap.addEventListener('mouseenter', function () { rampTo(0); });
    wrap.addEventListener('mouseleave', function () { rampTo(1); });
  });
})();
