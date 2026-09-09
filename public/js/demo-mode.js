// Modo "kiosco" para pantallas físicas del local — se activa visitando
// cualquier URL con ?demo=1 (?demo=0 lo apaga) y de ahí en más el sitio
// se recorre solo: home -> tienda -> un producto al azar -> arma tu pc
// -> vuelve a empezar, con scroll lento en cada parada. Cada paso es
// una navegación de página completa (no page-nav.js), así que el
// estado "estoy en modo demo" se guarda en localStorage PARA ESTE
// script, y en una cookie aparte (ver App\Support\DemoMode) para que
// el servidor excluya ese tráfico de Analítica/GA4.
(function () {
  var STORAGE_KEY = 'cyrexDemoMode';
  var PAUSE_UNTIL_KEY = 'cyrexDemoPausedUntil';
  var DWELL_MS = 16000;
  // Si alguien se acerca y toca la pantalla, se frena el recorrido este
  // rato para no arrancarle la página de las manos mientras la usa.
  var PAUSE_MS = 3 * 60 * 1000;

  var params = new URLSearchParams(location.search);
  if (params.has('demo')) {
    if (params.get('demo') === '0') {
      localStorage.removeItem(STORAGE_KEY);
      localStorage.removeItem(PAUSE_UNTIL_KEY);
    } else {
      localStorage.setItem(STORAGE_KEY, '1');
    }
    params.delete('demo');
    var clean = location.pathname + (params.toString() ? '?' + params.toString() : '') + location.hash;
    history.replaceState(null, '', clean);
  }

  if (localStorage.getItem(STORAGE_KEY) !== '1') return;

  function isPaused() {
    var until = parseInt(localStorage.getItem(PAUSE_UNTIL_KEY) || '0', 10);
    return Date.now() < until;
  }

  ['pointerdown', 'keydown', 'wheel'].forEach(function (evt) {
    window.addEventListener(evt, function () {
      localStorage.setItem(PAUSE_UNTIL_KEY, String(Date.now() + PAUSE_MS));
    }, { passive: true });
  });

  function nextUrl() {
    var path = location.pathname;

    if (path === '/tienda') {
      var cards = document.querySelectorAll('.product-grid a.card:not(.card-help)');
      if (cards.length) {
        return cards[Math.floor(Math.random() * cards.length)].getAttribute('href');
      }
      return '/arma-tu-pc';
    }

    if (path.indexOf('/producto/') === 0) return '/arma-tu-pc';
    if (path === '/arma-tu-pc') return '/';

    return '/tienda';
  }

  function autoScroll(durationMs) {
    var start = window.scrollY;
    var end = Math.max(document.body.scrollHeight - window.innerHeight, 0);
    if (end <= start) return;

    var startTime = performance.now();

    function step(now) {
      if (isPaused()) return;
      var t = Math.min((now - startTime) / durationMs, 1);
      window.scrollTo(0, start + (end - start) * t);
      if (t < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
  }

  function goToNext() {
    if (isPaused()) {
      setTimeout(goToNext, 5000);
      return;
    }
    location.href = nextUrl();
  }

  function waitOutPause() {
    var check = setInterval(function () {
      if (!isPaused()) {
        clearInterval(check);
        location.href = '/';
      }
    }, 5000);
  }

  window.addEventListener('DOMContentLoaded', function () {
    if (isPaused()) {
      waitOutPause();
      return;
    }
    autoScroll(DWELL_MS * 0.7);
    setTimeout(goToNext, DWELL_MS);
  });
})();
