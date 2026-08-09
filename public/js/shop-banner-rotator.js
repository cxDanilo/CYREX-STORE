window.addEventListener('DOMContentLoaded', function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    || document.body.classList.contains('motion-reduced');

  document.querySelectorAll('.shop-banner-rotator').forEach(function (rotator) {
    var slides = rotator.querySelectorAll('.shop-banner-slide');
    var dots = rotator.querySelectorAll('.shop-banner-dot');
    if (slides.length <= 1) return;

    var interval = parseInt(rotator.dataset.interval || '4500', 10);
    var current = 0;
    var timer = null;

    function goTo(index) {
      slides[current].classList.remove('is-active');
      if (dots[current]) dots[current].classList.remove('is-active');
      current = index;
      slides[current].classList.add('is-active');
      if (dots[current]) dots[current].classList.add('is-active');
    }

    function start() {
      if (reduced) return;
      timer = setInterval(function () {
        goTo((current + 1) % slides.length);
      }, interval);
    }

    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () {
        clearInterval(timer);
        goTo(i);
        start();
      });
    });

    start();
  });
});
