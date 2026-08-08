window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;

  document.querySelectorAll('.cms-social-rotator').forEach(function (rotator) {
    var pages = rotator.querySelectorAll('.cms-social-page');
    if (pages.length <= 1) return;

    var interval = parseInt(rotator.dataset.interval || '5000', 10);
    var current = 0;

    setInterval(function () {
      var next = (current + 1) % pages.length;
      pages[current].classList.remove('is-active');
      pages[next].classList.add('is-active');
      current = next;
    }, interval);
  });
});
