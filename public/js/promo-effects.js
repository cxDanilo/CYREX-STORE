/**
 * Efecto ambiente de fondo mientras hay una promo ACTIVA (nunca en fase
 * de expectativa) — nieve, confeti, brillo o niebla+murciélagos, elegido
 * por Danilo desde Admin → Promociones (columna "effect"). Un solo motor
 * de partículas en canvas reusado para los 4, en vez de una librería
 * externa — pocas partículas, sin bloquear clics, y respeta animaciones
 * reducidas igual que el resto del sitio (ver social-rotator.js).
 *
 * La nieve además: reacciona al mouse (abre paso al pasar cerca) y se
 * acumula como un gorrito blanco sobre algunas cards de producto
 * visibles en pantalla (config.interactive / config.piles).
 */
window.addEventListener('DOMContentLoaded', function () {
  var root = document.querySelector('[data-promo-effect]');
  if (!root) return;

  var effect = root.dataset.promoEffect;
  if (!effect || effect === 'none') return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;

  var PRESETS = {
    snow: { count: 46, color: '255,255,255', speedY: [0.4, 1.1], speedX: [-0.3, 0.3], size: [1.6, 3.6], shape: 'circle', spawnTop: true, interactive: true, piles: true },
    confetti: { count: 45, colors: ['255,217,0', '255,255,255'], speedY: [1.2, 2.4], speedX: [-1, 1], size: [4, 7], shape: 'rect', spawnTop: true, rotate: true },
    sparkle: { count: 26, color: '255,217,0', speedY: [-0.25, -0.08], speedX: [-0.15, 0.15], size: [1.3, 2.8], shape: 'circle', twinkle: true },
    spooky: { count: 24, color: '190,140,255', speedY: [-0.3, -0.1], speedX: [-0.2, 0.2], size: [1.5, 3], shape: 'circle', twinkle: true, bats: true },
  };

  var config = PRESETS[effect];
  if (!config) return;

  var canvas = document.createElement('canvas');
  canvas.className = 'promo-effect-canvas';
  document.body.appendChild(canvas);
  var ctx = canvas.getContext('2d');

  function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);
  window.addEventListener('load', resize);

  function rand(min, max) {
    return Math.random() * (max - min) + min;
  }

  function spawn() {
    return {
      x: rand(0, canvas.width),
      y: config.spawnTop ? rand(-canvas.height, 0) : rand(0, canvas.height),
      vx: rand(config.speedX[0], config.speedX[1]),
      vy: rand(config.speedY[0], config.speedY[1]),
      size: rand(config.size[0], config.size[1]),
      opacity: rand(0.35, 0.85),
      color: config.colors ? config.colors[Math.floor(Math.random() * config.colors.length)] : config.color,
      rotation: rand(0, Math.PI * 2),
      vr: config.rotate ? rand(-0.05, 0.05) : 0,
      phase: rand(0, Math.PI * 2),
    };
  }

  var particles = [];
  for (var i = 0; i < config.count; i++) particles.push(spawn());

  var bats = [];
  if (config.bats) {
    for (var b = 0; b < 3; b++) {
      bats.push({
        x: rand(0, canvas.width),
        y: rand(30, canvas.height * 0.3),
        vx: rand(0.35, 0.7) * (Math.random() < 0.5 ? 1 : -1),
        t: rand(0, Math.PI * 2),
      });
    }
  }

  function drawBat(x, y, t) {
    var flap = Math.sin(t * 6) * 5;
    ctx.fillStyle = 'rgba(15,10,25,0.55)';
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.quadraticCurveTo(x - 9, y - 5 - flap, x - 15, y + 2);
    ctx.quadraticCurveTo(x - 7, y + 1, x, y + 4);
    ctx.quadraticCurveTo(x + 7, y + 1, x + 15, y + 2);
    ctx.quadraticCurveTo(x + 9, y - 5 - flap, x, y);
    ctx.fill();
  }

  // --- Interacción con el mouse: empuja las partículas cercanas, como
  // si el cursor abriera paso entre los copos. Se apaga sola en touch
  // (no hay "mouse" real que seguir) y vuelve a su curso normal apenas
  // el cursor se aleja, sin acumular fuerza permanente.
  var mouse = { x: -9999, y: -9999, active: false };
  if (config.interactive || config.piles) {
    window.addEventListener('mousemove', function (e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
      mouse.active = true;
    });
    window.addEventListener('mouseleave', function () {
      mouse.active = false;
    });
  }

  var MOUSE_RADIUS = 70;

  // --- Acumulación: la nieve que "aterriza" en el borde superior del
  // botón "Agregar al carrito" (si hay uno en la página) se suma a un
  // montoncito propio en vez de seguir cayendo, y se sacude/libera al
  // pasar el mouse por encima. Se probó también con las cards del
  // catálogo pero se veía cargado/desprolijo con varias acumulando a
  // la vez — se sacó, queda solo en el botón.
  var piles = [];
  var MAX_PILE_SNOW = 9;
  var EXPEL_PAD = 14;

  function refreshPiles() {
    if (!config.piles) return;
    var cta = document.querySelector('.btn-cta');
    if (!cta) {
      piles = [];
      return;
    }
    var rect = cta.getBoundingClientRect();
    if (rect.bottom < 0 || rect.top > canvas.height || rect.width < 60) {
      piles = [];
      return;
    }
    var existing = piles[0] && piles[0].el === cta ? piles[0] : null;
    piles = [{
      el: cta,
      rect: rect,
      snow: existing ? existing.snow : 0,
      bumps: existing ? existing.bumps : null,
      expel: true,
    }];
  }

  if (config.piles) {
    refreshPiles();
    setInterval(refreshPiles, 500);
    window.addEventListener('scroll', function () {
      // Solo actualiza posiciones ya conocidas — barato, no vuelve a
      // buscar elementos nuevos en cada scroll (eso lo hace el interval).
      piles.forEach(function (p) { p.rect = p.el.getBoundingClientRect(); });
    });
  }

  // Puñado de copos que salen disparados al "sacudir" el montoncito del
  // botón — puramente decorativo, se apagan solos en menos de un segundo.
  var burstFlakes = [];

  function expelPile(pile) {
    var released = Math.min(pile.snow, 0.55);
    pile.snow -= released;
    if (Math.random() < 0.7) {
      burstFlakes.push({
        x: rand(pile.rect.left + 8, pile.rect.right - 8),
        y: pile.rect.top - rand(0, 5),
        vx: rand(-1.6, 1.6),
        vy: rand(-2.2, -0.8),
        size: rand(1.5, 3),
        life: 1,
      });
    }
  }

  // Bumps fijos por montoncito (calculados una vez) para que la forma
  // no titile de frame a frame — solo cambia de tamaño con pile.snow.
  // Cada bulto tiene su propia "capacidad" — no crecen todos juntos
  // desde el primer copo, se van llenando de a uno (como un montoncito
  // real que arranca en un punto y se va esparciendo), y solo se
  // dibujan una vez que absorbieron algo de nieve de verdad.
  function ensureBumps(pile) {
    if (pile.bumps) return;
    var width = Math.max(pile.rect.width, 40);
    var n = Math.max(4, Math.min(9, Math.round(width / 24)));
    pile.bumps = [];
    for (var i = 0; i < n; i++) {
      pile.bumps.push({ xRatio: (i + 0.5) / n, jitter: rand(-3, 3), scale: rand(0.7, 1.15), capacity: rand(0.9, 1.6) });
    }
  }

  function drawPile(pile) {
    if (pile.snow <= 0.25) return;
    ensureBumps(pile);
    var r = pile.rect;
    var remaining = pile.snow;

    pile.bumps.forEach(function (b) {
      var amount = Math.max(0, Math.min(remaining, b.capacity));
      remaining -= amount;
      if (amount <= 0.18) return;

      var x = r.left + b.xRatio * r.width + b.jitter;
      var radius = 2.5 + amount * 2.4 * b.scale;
      var y = r.top - radius * 0.62;

      ctx.fillStyle = 'rgba(235,242,255,0.22)';
      ctx.beginPath();
      ctx.arc(x, y, radius * 1.4, 0, Math.PI * 2);
      ctx.fill();

      ctx.fillStyle = 'rgba(255,255,255,0.55)';
      ctx.beginPath();
      ctx.arc(x, y, radius, 0, Math.PI * 2);
      ctx.fill();

      ctx.fillStyle = 'rgba(255,255,255,0.92)';
      ctx.beginPath();
      ctx.arc(x, y - radius * 0.18, radius * 0.55, 0, Math.PI * 2);
      ctx.fill();
    });
  }

  function drawBurstFlakes() {
    for (var i = burstFlakes.length - 1; i >= 0; i--) {
      var f = burstFlakes[i];
      f.x += f.vx;
      f.y += f.vy;
      f.vy += 0.08;
      f.life -= 0.02;
      if (f.life <= 0) {
        burstFlakes.splice(i, 1);
        continue;
      }
      ctx.globalAlpha = Math.max(0, f.life);
      ctx.fillStyle = '#fff';
      ctx.beginPath();
      ctx.arc(f.x, f.y, f.size, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.globalAlpha = 1;
  }

  // El propio navegador ya pausa/retoma requestAnimationFrame solo al
  // ocultar/mostrar la pestaña — "running" acá es solo para saltear el
  // trabajo pesado mientras está oculta, NUNCA para volver a arrancar
  // el loop a mano. Hacerlo a mano (como se hacía antes) sumaba una
  // cadena de rAF nueva cada vez, en paralelo a la que el navegador
  // retomaba solo, y cada ida y vuelta a la pestaña hacía que la nieve
  // cayera más rápido (dos, tres, cuatro loops corriendo a la vez).
  var running = true;
  document.addEventListener('visibilitychange', function () {
    running = !document.hidden;
  });

  function tick() {
    if (running) {

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // La nieve acumulada se derrite despacio, para que no quede fija
    // para siempre en la misma card mientras la promo sigue activa. La
    // del botón, además, se sacude y libera si el mouse está encima.
    if (config.piles) {
      piles.forEach(function (p) {
        p.snow = Math.max(0, p.snow - 0.004);
        if (p.expel && p.snow > 0.3 && mouse.active) {
          var overButton = mouse.x >= p.rect.left - EXPEL_PAD && mouse.x <= p.rect.right + EXPEL_PAD &&
            mouse.y >= p.rect.top - EXPEL_PAD && mouse.y <= p.rect.bottom + EXPEL_PAD;
          if (overButton) expelPile(p);
        }
      });
    }

    particles.forEach(function (p) {
      if (mouse.active && config.interactive) {
        var dx = p.x - mouse.x;
        var dy = p.y - mouse.y;
        var dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < MOUSE_RADIUS && dist > 0.01) {
          var force = (1 - dist / MOUSE_RADIUS) * 2.2;
          p.x += (dx / dist) * force;
          p.y += (dy / dist) * force;
        }
      }

      p.x += p.vx;
      p.y += p.vy;
      p.phase += 0.02;

      if (config.piles) {
        for (var pi = 0; pi < piles.length; pi++) {
          var pile = piles[pi];
          if (pile.snow >= MAX_PILE_SNOW) continue;
          var landY = pile.rect.top - pile.snow * 0.4;
          if (p.vy > 0 && p.y >= landY - 3 && p.y <= landY + 6 && p.x >= pile.rect.left + 4 && p.x <= pile.rect.right - 4) {
            pile.snow = Math.min(MAX_PILE_SNOW, pile.snow + 0.35);
            Object.assign(p, spawn());
            p.y = -10;
            return;
          }
        }
      }

      var opacity = Math.max(0, config.twinkle ? p.opacity * (0.4 + 0.6 * Math.sin(p.phase)) : p.opacity);

      if (config.shape === 'rect') {
        ctx.fillStyle = 'rgba(' + p.color + ',' + opacity + ')';
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.rotation);
        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
        ctx.restore();
        p.rotation += p.vr;
      } else {
        // Glow suave detrás del núcleo, en 3 capas — un círculo duro y
        // chico se lee como una mota de polvo; con degradé se lee nieve.
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size * 2.6, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(' + p.color + ',' + (opacity * 0.12) + ')';
        ctx.fill();

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size * 1.7, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(' + p.color + ',' + (opacity * 0.3) + ')';
        ctx.fill();

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(' + p.color + ',' + opacity + ')';
        ctx.fill();
      }

      if (p.y > canvas.height + 10 || p.y < -20 || p.x < -20 || p.x > canvas.width + 20) {
        Object.assign(p, spawn());
        if (config.spawnTop) p.y = -10;
      }
    });

    if (config.piles) {
      piles.forEach(drawPile);
      drawBurstFlakes();
    }

    bats.forEach(function (bat) {
      bat.x += bat.vx;
      bat.t += 0.05;
      if (bat.x < -25) bat.x = canvas.width + 25;
      if (bat.x > canvas.width + 25) bat.x = -25;
      drawBat(bat.x, bat.y + Math.sin(bat.t) * 8, bat.t);
    });

    }
    requestAnimationFrame(tick);
  }

  requestAnimationFrame(tick);
});
