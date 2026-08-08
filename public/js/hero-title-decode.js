window.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;

  var GLYPHS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#%&*';

  function randomGlyph() {
    return GLYPHS[Math.floor(Math.random() * GLYPHS.length)];
  }

  document.querySelectorAll('.cms-hero-video-title').forEach(function (el) {
    var originalLabel = el.textContent.replace(/\s+/g, ' ').trim();
    var frag = document.createDocumentFragment();
    var glyphs = [];

    // Cada letra va en su propio <span> para poder animarla, pero si se
    // dejan sueltas el navegador puede cortar línea en medio de una
    // palabra (pasaba en celular: "BUSCAS" se partía en "BUSC" / "AS").
    // Por eso las letras de una misma palabra se agrupan en un wrapper
    // con white-space:nowrap — el salto de línea solo puede pasar en los
    // espacios reales entre wrappers, nunca adentro de una palabra.
    el.childNodes.forEach(function (node) {
      if (node.nodeType === Node.TEXT_NODE) {
        node.textContent.split(/(\s+)/).forEach(function (segment) {
          if (segment === '') return;

          if (/^\s+$/.test(segment)) {
            frag.appendChild(document.createTextNode(segment));
            return;
          }

          var wordSpan = document.createElement('span');
          wordSpan.style.whiteSpace = 'nowrap';
          segment.split('').forEach(function (ch) {
            var span = document.createElement('span');
            span.className = 'glyph is-scrambled';
            span.textContent = randomGlyph();
            span.setAttribute('aria-hidden', 'true');
            wordSpan.appendChild(span);
            glyphs.push({ span: span, real: ch });
          });
          frag.appendChild(wordSpan);
        });
      } else if (node.nodeName === 'BR') {
        frag.appendChild(document.createElement('br'));
      }
    });

    el.setAttribute('aria-label', originalLabel);
    el.innerHTML = '';
    el.appendChild(frag);

    glyphs.forEach(function (g, i) {
      var base = i * 26;
      setTimeout(function () { g.span.textContent = randomGlyph(); }, base + 50);
      setTimeout(function () {
        g.span.textContent = g.real;
        g.span.classList.remove('is-scrambled');
        g.span.classList.add('is-settled');
      }, base + 130);
    });
  });
});
