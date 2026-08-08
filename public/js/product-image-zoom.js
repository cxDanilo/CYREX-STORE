window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(hover: none)').matches) return;

  document.querySelectorAll('.gallery-main').forEach(function (container) {
    var img = container.querySelector('img');
    if (!img) return;

    container.addEventListener('mousemove', function (e) {
      var rect = container.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;
      img.style.transformOrigin = x + '% ' + y + '%';
    });

    container.addEventListener('mouseleave', function () {
      img.style.transformOrigin = '50% 50%';
    });
  });
});
