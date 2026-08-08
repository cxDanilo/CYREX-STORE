window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;
  if (window.matchMedia('(hover: none)').matches) return;

  document.querySelectorAll('.card').forEach(function (card) {
    card.addEventListener('mousemove', function (e) {
      var rect = card.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width;
      var y = (e.clientY - rect.top) / rect.height;
      var rx = (0.5 - y) * 10;
      var ry = (x - 0.5) * 10;

      card.classList.add('is-tilting');
      card.style.transform = 'perspective(900px) translateY(-2px) rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
    });

    card.addEventListener('mouseleave', function () {
      card.classList.remove('is-tilting');
      card.style.transform = '';
    });
  });
});
