// Parallax sutil del hero con video (bloque CMS "Hero con video"): el
// fondo se mueve más lento que el scroll real, dando sensación de
// profundidad -- el mismo tipo de efecto que el fondo del hero en
// /quienes-somos, adaptado acá a un video/imagen en vez de texto.
//
// Sin scale() acá a propósito -- antes escalaba .cms-hero-video-media
// (que contiene el iframe/video) además de moverlo, pero un scale()
// sobre esa capa le mostraba a Chrome una costura fina horizontal (un
// bug de compositing por GPU al escalar video/iframe). El zoom que
// tapa la marca de YouTube ahora está fijo en el tamaño del iframe
// (ver .cms-hero-video-iframe en app.css) en vez de un transform.
(function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.body.classList.contains('motion-reduced');
  if (reduced) return;

  var heroes = document.querySelectorAll('.cms-hero-video');
  if (!heroes.length) return;

  var raf = null;

  function update() {
    raf = null;
    heroes.forEach(function (hero) {
      var media = hero.querySelector('.cms-hero-video-media');
      if (!media) return;
      var rect = hero.getBoundingClientRect();
      if (rect.bottom < 0 || rect.top > window.innerHeight) return;
      var shift = Math.max(0, -rect.top) * 0.15;
      media.style.transform = 'translateY(' + shift.toFixed(1) + 'px)';
    });
  }

  document.addEventListener('scroll', function () {
    if (!raf) raf = requestAnimationFrame(update);
  }, { passive: true });

  update();
})();
