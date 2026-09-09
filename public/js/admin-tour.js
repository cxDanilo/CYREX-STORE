// Recorrido guiado de bienvenida para el primer login de un usuario
// nuevo — ver Admin\TourController (marca tour_seen_at) y el div
// #admin-tour-root en admin/layout.blade.php (siempre presente,
// data-start="1" solo cuando DashboardController detecta que el
// usuario todavía no lo vio).
//
// A diferencia de la v1 (un solo tour acotado al Dashboard), este
// recorrido cruza varias páginas reales (Dashboard -> Productos ->
// Nuevo producto -> Editar un producto), así que el progreso se
// guarda en localStorage y se retoma en cada carga de página — no
// hay SPA de por medio, cada paso puede ser una navegación de página
// completa. Por eso este script se carga en TODAS las páginas del
// admin (ver admin/layout.blade.php), no solo en el Dashboard, y
// arranca sin hacer nada si no hay un recorrido en curso.
(function () {
  var root = document.getElementById('admin-tour-root');
  if (!root) return;

  var DISMISS_URL = root.dataset.dismissUrl;
  var CSRF = root.dataset.csrf;
  var STEP_KEY = 'cyrexTourStep';

  function isEditProductPath(path) {
    return /^\/admin\/productos\/[^/]+\/editar$/.test(path);
  }

  var steps = [
    {
      path: '/admin/dashboard',
      selector: '[data-tour="logo"]',
      placement: 'bottom',
      title: '¡Bienvenido a Cyrex Admin!',
      text: 'Este es el panel donde se maneja toda la tienda: catálogo, contenido del sitio y ajustes generales.',
    },
    {
      path: '/admin/dashboard',
      selector: '[data-tour="nav-catalogo"]',
      placement: 'right',
      title: 'Catálogo',
      text: 'Productos, categorías, combos, promociones y descuentos — todo lo que se vende.',
    },
    {
      path: '/admin/dashboard',
      selector: '[data-tour="nav-contenido"]',
      placement: 'right',
      title: 'Contenido',
      text: 'Páginas, plantillas, medios y menús del sitio público.',
    },
    {
      path: '/admin/dashboard',
      selector: '[data-tour="stats"]',
      placement: 'bottom',
      title: 'Resumen rápido',
      text: 'Productos activos, categorías, páginas publicadas y usuarios, de un vistazo.',
    },
    {
      path: '/admin/dashboard',
      selector: '[data-tour="actividad"]',
      placement: 'top',
      title: 'Actividad reciente',
      text: 'Los últimos cambios en el catálogo, y quién los hizo.',
    },
    {
      path: '/admin/dashboard',
      selector: '[data-tour="ver-sitio"]',
      placement: 'right',
      title: 'Ver el sitio',
      text: 'Abre la tienda tal cual la ve un cliente, en una pestaña nueva.',
    },
    {
      path: '/admin/productos',
      selector: '[data-tour="producto-nuevo-btn"]',
      placement: 'bottom',
      title: 'Cargar un producto',
      text: 'Este botón abre el formulario para crear un producto nuevo. Vamos a verlo por dentro.',
    },
    {
      path: '/admin/productos/nuevo',
      tabSelector: '[data-tour="tab-general"]',
      selector: '[data-tour="form-general"]',
      placement: 'bottom',
      title: 'Datos generales',
      text: 'Nombre, categoría y descripción. El slug (la URL) se arma solo a partir del nombre.',
    },
    {
      path: '/admin/productos/nuevo',
      tabSelector: '[data-tour="tab-imagenes"]',
      selector: '[data-tour="form-imagen"]',
      placement: 'bottom',
      title: 'La foto principal',
      text: 'Es obligatoria, salvo que ya le hayas puesto foto a alguna variante en la pestaña Variantes.',
    },
    {
      path: '/admin/productos/nuevo',
      tabSelector: '[data-tour="tab-precio"]',
      selector: '[data-tour="form-precio"]',
      placement: 'top',
      title: 'Precio y estado',
      text: 'El precio, si está Publicado o en Privado, y el switch para marcarlo Agotado.',
    },
    {
      path: '/admin/productos/nuevo',
      selector: '[data-tour="form-actions"]',
      placement: 'top',
      title: 'Guardar',
      text: 'Así se guarda — no hace falta completar todo ahora, se puede terminar después editándolo. Justo lo que vemos a continuación.',
    },
    {
      path: '/admin/productos',
      selector: '[data-tour="producto-editar-link"]',
      placement: 'bottom',
      title: 'Editar un producto existente',
      text: 'Cualquier fila tiene su botón "Editar". Entremos a uno para ver cómo es.',
      hrefFrom: true,
    },
    {
      matches: isEditProductPath,
      tabSelector: '[data-tour="tab-precio"]',
      selector: '[data-tour="form-precio"]',
      placement: 'top',
      title: 'Editando',
      text: 'Es el mismo formulario, ya con los datos cargados — cambiá lo que haga falta (por ejemplo, marcar Agotado acá) y guardá.',
    },
    {
      selector: null,
      title: '¡Listo!',
      text: 'Ya sabés cargar y editar productos. El resto del panel se explora solo — nada se rompe por curiosear.',
    },
  ];

  var current = -1;
  var backdrop, highlight, card;

  function stepMatchesPath(step, path) {
    if (!step) return false;
    if (step.matches) return step.matches(path);
    if (step.path) return step.path === path;
    return true;
  }

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
    localStorage.removeItem(STEP_KEY);
    teardown();
    fetch(DISMISS_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF },
    });
  }

  function abandon() {
    // El usuario se fue a otro lado por su cuenta (no usó los botones
    // del tour) — se deja de insistir en esta página, pero no se
    // marca tour_seen_at: si vuelve al Dashboard sin haberlo
    // terminado, arranca de nuevo desde el paso 1.
    localStorage.removeItem(STEP_KEY);
  }

  function closeMobileNav() {
    var closeBtn = document.querySelector('.admin-nav-close');
    if (closeBtn && closeBtn.offsetParent !== null) closeBtn.click();
  }

  function goToIndex(index) {
    current = index;
    localStorage.setItem(STEP_KEY, String(index));
    render();
  }

  function goNext() {
    var step = steps[current];
    var nextIndex = current + 1;

    if (nextIndex >= steps.length) return finish();

    if (step.hrefFrom) {
      var target = document.querySelector(step.selector);
      var href = target && target.getAttribute('href');
      if (href) {
        localStorage.setItem(STEP_KEY, String(nextIndex));
        location.href = href;
        return;
      }
    }

    var next = steps[nextIndex];
    if (stepMatchesPath(next, location.pathname)) {
      goToIndex(nextIndex);
    } else if (next.path) {
      localStorage.setItem(STEP_KEY, String(nextIndex));
      location.href = next.path;
    } else {
      goToIndex(nextIndex);
    }
  }

  function skipMissing() {
    if (current < steps.length - 1) {
      goToIndex(current + 1);
    } else {
      finish();
    }
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

    card.querySelector('.admin-tour-title').textContent = step.title;
    card.querySelector('.admin-tour-text').textContent = step.text;

    card.querySelectorAll('[data-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var action = btn.dataset.action;
        if (action === 'skip') return finish();
        if (action === 'prev') return goToIndex(current - 1);
        if (action === 'next') return goNext();
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

    if (step.tabSelector) {
      var tabBtn = document.querySelector(step.tabSelector);
      if (tabBtn) tabBtn.click();
    }

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

    // Contador de FRAMES, no de tiempo real — si la pestaña pierde el
    // foco justo acá (una notificación, cambiar de ventana), rAF deja
    // de dispararse hasta que vuelve a estar visible: con un plazo en
    // tiempo real (Date.now()) el conteo seguiría corriendo igual y el
    // recuadro podría quedar pegado en una medida vieja (ej. 0x0, de
    // justo cuando tabSelector recién cambió de pestaña y el layout
    // todavía no se había acomodado). Contando frames en cambio, el
    // seguimiento simplemente se pausa y retoma solo al volver.
    var framesLeft = 40;
    highlight.hidden = false;
    requestAnimationFrame(function track() {
      updateBoxes(target.getBoundingClientRect(), step.placement);
      framesLeft--;
      if (framesLeft > 0) requestAnimationFrame(track);
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
    if (!step || !step.selector) return;
    var target = document.querySelector(step.selector);
    if (target) updateBoxes(target.getBoundingClientRect(), step.placement);
  }

  window.addEventListener('DOMContentLoaded', function () {
    if (root.dataset.start === '1' && !localStorage.getItem(STEP_KEY)) {
      localStorage.setItem(STEP_KEY, '0');
    }

    var stored = localStorage.getItem(STEP_KEY);
    if (stored === null) return;

    var index = parseInt(stored, 10);
    var step = steps[index];
    if (!step) return localStorage.removeItem(STEP_KEY);

    if (!stepMatchesPath(step, location.pathname)) return abandon();

    build();
    goToIndex(index);
  });
})();
