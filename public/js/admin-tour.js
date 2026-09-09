// Recorrido guiado de bienvenida para el primer login de un usuario
// nuevo — ver Admin\TourController (marca tour_seen_at) y
// partials/admin-tour.blade.php (solo se incluye este script cuando
// corresponde). Vanilla JS a propósito: es un overlay con posiciones
// absolutas que se recalculan todo el tiempo, más simple de controlar
// sin pelear con la reactividad de Alpine para esto puntual.
(function () {
  var root = document.getElementById('admin-tour-root');
  if (!root) return;

  var DISMISS_URL = root.dataset.dismissUrl;
  var CSRF = root.dataset.csrf;

  var steps = [
    {
      selector: '[data-tour="logo"]',
      placement: 'bottom',
      title: '¡Bienvenido a Cyrex Admin!',
      text: 'Este es el panel donde se maneja toda la tienda: catálogo, contenido del sitio y ajustes generales.',
    },
    {
      selector: '[data-tour="nav-catalogo"]',
      placement: 'right',
      title: 'Catálogo',
      text: 'Productos, categorías, combos, promociones y descuentos — todo lo que se vende.',
    },
    {
      selector: '[data-tour="nav-contenido"]',
      placement: 'right',
      title: 'Contenido',
      text: 'Páginas, plantillas, medios y menús del sitio público.',
    },
    {
      selector: '[data-tour="stats"]',
      placement: 'bottom',
      title: 'Resumen rápido',
      text: 'Productos activos, categorías, páginas publicadas y usuarios, de un vistazo.',
    },
    {
      selector: '[data-tour="actividad"]',
      placement: 'top',
      title: 'Actividad reciente',
      text: 'Los últimos cambios en el catálogo, y quién los hizo.',
    },
    {
      selector: '[data-tour="ver-sitio"]',
      placement: 'right',
      title: 'Ver el sitio',
      text: 'Abre la tienda tal cual la ve un cliente, en una pestaña nueva.',
    },
    {
      selector: null,
      title: '¡Listo!',
      text: 'Ya conocés lo básico del panel. El resto se explora solo — nada se rompe por curiosear.',
    },
  ];

  var current = 0;
  var backdrop, highlight, card;
  var trackingUntil = 0;

  function build() {
    backdrop = document.createElement('div');
    backdrop.className = 'admin-tour-backdrop';

    highlight = document.createElement('div');
    highlight.className = 'admin-tour-highlight';

    card = document.createElement('div');
    card.className = 'admin-tour-card';

    document.body.appendChild(backdrop);
    document.body.appendChild(highlight);
    document.body.appendChild(card);

    window.addEventListener('resize', onResize);
  }

  function teardown() {
    window.removeEventListener('resize', onResize);
    closeMobileNav();
    [backdrop, highlight, card].forEach(function (el) {
      if (el && el.parentNode) el.parentNode.removeChild(el);
    });
  }

  function finish() {
    teardown();
    fetch(DISMISS_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF },
    });
  }

  function goTo(index) {
    current = index;
    render();
  }

  function skipMissing() {
    if (current < steps.length - 1) {
      goTo(current + 1);
    } else {
      finish();
    }
  }

  function closeMobileNav() {
    var closeBtn = document.querySelector('.admin-nav-close');
    if (closeBtn && closeBtn.offsetParent !== null) closeBtn.click();
  }

  function render() {
    var step = steps[current];

    var buttons = '';
    if (current > 0) {
      buttons += '<button type="button" class="btn btn-sm" data-action="prev">Anterior</button>';
    }
    buttons += '<button type="button" class="btn btn-sm admin-tour-skip" data-action="skip">Saltar</button>';
    buttons += '<button type="button" class="btn btn-primary btn-sm" data-action="next">'
      + (current === steps.length - 1 ? 'Entendido' : 'Siguiente') + '</button>';

    card.innerHTML =
      '<div class="admin-tour-step">Paso ' + (current + 1) + ' de ' + steps.length + '</div>' +
      '<div class="admin-tour-title"></div>' +
      '<div class="admin-tour-text"></div>' +
      '<div class="admin-tour-actions">' + buttons + '</div>';

    // textContent, no innerHTML, para el título/texto (son fijos acá
    // adentro, pero mejor no acostumbrarse a interpolar HTML sin pensar).
    card.querySelector('.admin-tour-title').textContent = step.title;
    card.querySelector('.admin-tour-text').textContent = step.text;

    card.querySelectorAll('[data-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var action = btn.dataset.action;
        if (action === 'skip') return finish();
        if (action === 'prev') return goTo(current - 1);
        if (action === 'next') return current === steps.length - 1 ? finish() : goTo(current + 1);
      });
    });

    positionFor(step);
  }

  function positionFor(step) {
    if (!step.selector) {
      card.classList.add('is-centered');
      highlight.hidden = false;
      highlight.style.width = '0px';
      highlight.style.height = '0px';
      highlight.style.top = '50%';
      highlight.style.left = '50%';
      return;
    }

    card.classList.remove('is-centered');

    var target = document.querySelector(step.selector);
    if (!target) return skipMissing();

    var sidebar = document.querySelector('.admin-sidebar');
    var hamburger = document.querySelector('.admin-hamburger');
    var isMobile = hamburger && hamburger.offsetParent !== null;

    if (isMobile && sidebar) {
      var isInSidebar = sidebar.contains(target);
      var sidebarOpen = sidebar.classList.contains('is-open');
      if (isInSidebar && !sidebarOpen) hamburger.click();
      if (!isInSidebar && sidebarOpen) closeMobileNav();
    }

    target.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Sigue el rect del target ~500ms (dura el scroll suave) para que el
    // recuadro se mueva CON la página en vez de quedar pegado al lugar
    // viejo hasta que el scroll termina.
    trackingUntil = Date.now() + 500;
    highlight.hidden = false;
    requestAnimationFrame(function track() {
      updateBoxes(target.getBoundingClientRect(), step.placement);
      if (Date.now() < trackingUntil) requestAnimationFrame(track);
    });
  }

  function updateBoxes(rect, placement) {
    var pad = 6;
    highlight.style.top = (rect.top - pad) + 'px';
    highlight.style.left = (rect.left - pad) + 'px';
    highlight.style.width = (rect.width + pad * 2) + 'px';
    highlight.style.height = (rect.height + pad * 2) + 'px';

    var gap = 16;
    var cardRect = card.getBoundingClientRect();
    var top, left;

    if (placement === 'right') {
      top = rect.top;
      left = rect.right + gap;
      if (left + cardRect.width > window.innerWidth - 12) {
        left = rect.left;
        top = rect.bottom + gap;
      }
    } else if (placement === 'top') {
      top = rect.top - cardRect.height - gap;
      left = rect.left;
    } else {
      top = rect.bottom + gap;
      left = rect.left;
    }

    top = Math.max(12, Math.min(top, window.innerHeight - cardRect.height - 12));
    left = Math.max(12, Math.min(left, window.innerWidth - cardRect.width - 12));

    card.style.top = top + 'px';
    card.style.left = left + 'px';
  }

  function onResize() {
    var step = steps[current];
    if (!step.selector) return;
    var target = document.querySelector(step.selector);
    if (target) updateBoxes(target.getBoundingClientRect(), step.placement);
  }

  window.addEventListener('DOMContentLoaded', function () {
    build();
    render();
  });
})();
