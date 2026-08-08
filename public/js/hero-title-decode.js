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

    el.childNodes.forEach(function (node) {
      if (node.nodeType === Node.TEXT_NODE) {
        node.textContent.split('').forEach(function (ch) {
          var span = document.createElement('span');
          span.className = 'glyph is-scrambled';
          span.textContent = ch === ' ' ? ' ' : randomGlyph();
          span.setAttribute('aria-hidden', 'true');
          frag.appendChild(span);
          if (ch !== ' ') glyphs.push({ span: span, real: ch });
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
