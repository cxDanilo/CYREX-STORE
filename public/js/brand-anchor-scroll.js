// Los links del subnav sticky y el "Descubrir {marca} ↓" del hero
// apuntan a #anclas dentro de la misma página de marca -- se maneja el
// scroll a mano en vez de confiar en el salto nativo del navegador al
// cambiar el hash, porque con un header sticky de por medio (nav
// principal + subnav) el resultado no siempre es consistente entre
// navegadores. scroll-margin-top (app.css) ya deja el offset correcto,
// esto solo dispara un scrollIntoView explícito.
//
// Llegar directo con el hash en la URL (un link compartido tipo
// /marcas/ajazz#productos) también pasa por acá -- el salto automático
// del navegador al cargar puede correr antes de que termine de
// asentarse el layout (imágenes, hero), dejando el scroll corto o nulo.
if (location.hash && document.querySelector('.brand-page')) {
  window.addEventListener('load', function () {
    var target = document.getElementById(location.hash.slice(1));
    if (target) target.scrollIntoView({ block: 'start' });
  });
}

document.addEventListener('click', function (event) {
  var link = event.target.closest('.brand-page a[href^="#"]');
  if (!link) return;

  var target = document.getElementById(link.getAttribute('href').slice(1));
  if (!target) return;

  event.preventDefault();
  target.scrollIntoView({ block: 'start' });
  history.replaceState(null, '', link.getAttribute('href'));
});
