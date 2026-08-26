/**
 * Efecto ambiente de fondo mientras hay una promo ACTIVA (nunca en fase
 * de expectativa) — nieve, confeti, brillo o niebla+murciélagos, elegido
 * por Danilo desde Admin → Promociones (columna "effect"). Un solo motor
 * de partículas en canvas reusado para los 4, en vez de una librería
 * externa — pocas partículas, sin bloquear clics, y respeta animaciones
 * reducidas igual que el resto del sitio (ver social-rotator.js).
 */
window.addEventListener('DOMContentLoaded', function () {
  var root = document.querySelector('[data-promo-effect]');
  if (!root) return;

  var effect = root.dataset.promoEffect;
  if (!effect || effect === 'none') return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;

  var PRESETS = {
    snow: { count: 60, color: '255,255,255', speedY: [0.4, 1.1], speedX: [-0.3, 0.3], size: [1.5, 3.5], shape: 'circle', spawnTop: true },
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

  var running = true;
  document.addEventListener('visibilitychange', function () {
    running = !document.hidden;
    if (running) requestAnimationFrame(tick);
  });

  function tick() {
    if (!running) return;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    particles.forEach(function (p) {
      p.x += p.vx;
      p.y += p.vy;
      p.phase += 0.02;

      var opacity = config.twinkle ? p.opacity * (0.4 + 0.6 * Math.sin(p.phase)) : p.opacity;
      ctx.globalAlpha = Math.max(0, opacity);
      ctx.fillStyle = 'rgb(' + p.color + ')';

      if (config.shape === 'rect') {
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.rotation);
        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
        ctx.restore();
        p.rotation += p.vr;
      } else {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fill();
      }

      if (p.y > canvas.height + 10 || p.y < -20 || p.x < -20 || p.x > canvas.width + 20) {
        Object.assign(p, spawn());
        if (config.spawnTop) p.y = -10;
      }
    });
    ctx.globalAlpha = 1;

    bats.forEach(function (bat) {
      bat.x += bat.vx;
      bat.t += 0.05;
      if (bat.x < -25) bat.x = canvas.width + 25;
      if (bat.x > canvas.width + 25) bat.x = -25;
      drawBat(bat.x, bat.y + Math.sin(bat.t) * 8, bat.t);
    });

    requestAnimationFrame(tick);
  }

  requestAnimationFrame(tick);
});
