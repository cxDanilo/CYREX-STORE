// Toasts del admin — reemplaza los antiguos carteles fijos .admin-flash
// por avisos que aparecen, se leen y se van solos. El servidor sigue
// mandando exactamente lo mismo de siempre (session('status')/session
// ('error') vía #admin-toast-data, ver admin/layout.blade.php), esto
// solo cambia cómo se muestran.
(function () {
  var AUTO_DISMISS_MS = 4500;

  function stack() {
    return document.getElementById('admin-toast-stack');
  }

  function iconFor(type) {
    if (type === 'error') {
      return '<svg class="toast-icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    }
    return '<svg class="toast-icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M8 12.5l2.5 2.5L16 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }

  function show(message, type) {
    var el = stack();
    if (!el || !message) return;

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (type === 'error' ? 'error' : 'success');
    toast.innerHTML = iconFor(type) + '<span>' + message + '</span><button type="button" class="toast-close" aria-label="Cerrar">&times;</button>';
    el.appendChild(toast);

    // Un frame después de insertarlo, para que la transición de
    // opacidad/transform realmente se dispare (si arranca ya con la
    // clase puesta, el navegador no anima el cambio).
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { toast.classList.add('is-visible'); });
    });

    var timer = setTimeout(function () { dismiss(toast); }, AUTO_DISMISS_MS);
    toast.querySelector('.toast-close').addEventListener('click', function () {
      clearTimeout(timer);
      dismiss(toast);
    });
  }

  function dismiss(toast) {
    toast.classList.remove('is-visible');
    setTimeout(function () { toast.remove(); }, 250);
  }

  window.AdminToast = { show: show };

  document.addEventListener('DOMContentLoaded', function () {
    var data = document.getElementById('admin-toast-data');
    if (!data) return;

    if (data.dataset.status) show(data.dataset.status, 'success');
    if (data.dataset.error) show(data.dataset.error, 'error');
  });
})();
