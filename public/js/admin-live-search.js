// Busqueda en vivo para listados del admin (por ahora, Productos): el
// <form> lleva data-live-search, el contenedor a reemplazar lleva
// data-live-search-results. En vez del submit normal (que recarga la
// pagina entera), pide el mismo listado por fetch con el header
// X-Requested-With -- el controller detecta $request->ajax() y
// devuelve solo la tabla/paginacion en vez de la vista completa.
(function () {
  const form = document.querySelector('[data-live-search]');
  const results = document.querySelector('[data-live-search-results]');
  if (!form || !results) return;

  const input = form.querySelector('input[name="q"]');
  let debounceTimer = null;
  let inFlight = null;

  function load(url, { pushState = true } = {}) {
    if (inFlight) inFlight.abort();
    const controller = new AbortController();
    inFlight = controller;

    results.setAttribute('aria-busy', 'true');
    fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      signal: controller.signal,
    })
      .then((r) => r.text())
      .then((html) => {
        results.innerHTML = html;
        results.removeAttribute('aria-busy');
        if (pushState) history.replaceState({}, '', url);
        if (inFlight === controller) inFlight = null;
      })
      .catch((err) => {
        if (inFlight === controller) inFlight = null;
        if (err.name !== 'AbortError') results.removeAttribute('aria-busy');
      });
  }

  function currentSearchUrl(q) {
    const params = new URLSearchParams(window.location.search);
    if (q) params.set('q', q);
    else params.delete('q');
    params.delete('page');
    const qs = params.toString();
    return window.location.pathname + (qs ? '?' + qs : '');
  }

  if (input) {
    input.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => load(currentSearchUrl(input.value.trim())), 350);
    });
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(debounceTimer);
    load(currentSearchUrl(input ? input.value.trim() : ''));
  });

  // Los links de paginacion quedan dentro de data-live-search-results
  // y apuntan a la misma URL (?page=N&q=...) -- se interceptan para
  // que tampoco recarguen la pagina. Cualquier otro link (Editar, Ver
  // sitio, etc.) apunta a otra ruta y se deja pasar normal.
  results.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link) return;
    const href = link.getAttribute('href');
    if (!href) return;
    const linkUrl = new URL(href, window.location.origin);
    if (linkUrl.pathname !== window.location.pathname) return;
    e.preventDefault();
    load(linkUrl.pathname + linkUrl.search);
    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  window.addEventListener('popstate', () => {
    load(window.location.pathname + window.location.search, { pushState: false });
  });
})();
