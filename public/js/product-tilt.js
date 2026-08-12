window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;
  if (window.matchMedia('(hover: none)').matches) return;

  // Delegado en document (no un listener por .card) porque las cards
  // de la tienda/relacionados se reemplazan enteras vía innerHTML al
  // ordenar/filtrar/paginar (public/js/shop-ajax.js) o al navegar sin
  // recarga a otra página (public/js/page-nav.js) — un listener
  // enganchado directo a una card vieja se pierde con ella. Con
  // delegación, cualquier card nueva funciona sola, sin re-inicializar
  // nada a mano.
  var activeCard = null;

  function resetCard(card) {
    card.classList.remove('is-tilting');
    card.style.transform = '';
  }

  document.addEventListener('mousemove', function (e) {
    var card = e.target.closest('.card');

    if (card !== activeCard && activeCard) {
      resetCard(activeCard);
    }
    activeCard = card;

    if (!card) return;

    var rect = card.getBoundingClientRect();
    var x = (e.clientX - rect.left) / rect.width;
    var y = (e.clientY - rect.top) / rect.height;
    var rx = (0.5 - y) * 10;
    var ry = (x - 0.5) * 10;

    card.classList.add('is-tilting');
    card.style.transform = 'perspective(900px) translateY(-2px) rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
  });
});
