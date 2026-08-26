/**
 * Efecto ambiente de fondo mientras hay una promo ACTIVA (nunca en fase
 * de expectativa) — elegido por Danilo desde Admin → Promociones
 * (columna "effect"). Un solo motor de partículas en canvas, sin
 * librería externa — pocas partículas, sin bloquear clics, y respeta
 * animaciones reducidas igual que el resto del sitio (ver
 * social-rotator.js).
 *
 * Cada fecha recurrente tiene, además de las partículas de fondo, un
 * detalle propio que aparece de vez en cuando (nunca en loop constante,
 * para no ser molesto):
 *   - Navidad (snow): el trineo de Santa cruza la pantalla cada tanto.
 *   - Año Nuevo (confetti): fuegos artificiales periódicos.
 *   - Reyes / fechas en general (sparkle): una estrella fugaz ocasional.
 *   - Halloween (spooky): telarañas fijas en las esquinas + una araña
 *     que baja de su hilo cada tanto.
 *   - Día de la Madre / Amor y Amistad (hearts): corazones flotando.
 */
window.addEventListener('DOMContentLoaded', function () {
  var root = document.querySelector('[data-promo-effect]');
  if (!root) return;

  var effect = root.dataset.promoEffect;
  if (!effect || effect === 'none') return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (document.body.classList.contains('motion-reduced')) return;

  var PRESETS = {
    snow: { count: 46, color: '255,255,255', speedY: [0.4, 1.1], speedX: [-0.3, 0.3], size: [1.6, 3.6], shape: 'circle', spawnTop: true, interactive: true, shake: true, flyby: 'santa' },
    confetti: { count: 45, colors: ['255,217,0', '255,255,255'], speedY: [1.2, 2.4], speedX: [-1, 1], size: [4, 7], shape: 'rect', spawnTop: true, rotate: true, fireworks: true },
    sparkle: { count: 26, color: '255,217,0', speedY: [-0.25, -0.08], speedX: [-0.15, 0.15], size: [1.3, 2.8], shape: 'circle', twinkle: true, flyby: 'shootingstar' },
    spooky: { count: 24, color: '190,140,255', speedY: [-0.3, -0.1], speedX: [-0.2, 0.2], size: [1.5, 3], shape: 'circle', twinkle: true, bats: true, corners: true, spiders: true },
    hearts: { count: 20, color: '255,105,140', speedY: [-0.3, -0.1], speedX: [-0.2, 0.2], size: [4.5, 8], shape: 'heart', twinkle: true },
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

  // === Murciélagos (Halloween) =============================================
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
    // Morado claro, no negro — el fondo del sitio ya es casi negro
    // (#080808), una silueta oscura se pierde por completo ahí.
    ctx.fillStyle = 'rgba(165,120,225,0.75)';
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.quadraticCurveTo(x - 9, y - 5 - flap, x - 15, y + 2);
    ctx.quadraticCurveTo(x - 7, y + 1, x, y + 4);
    ctx.quadraticCurveTo(x + 7, y + 1, x + 15, y + 2);
    ctx.quadraticCurveTo(x + 9, y - 5 - flap, x, y);
    ctx.fill();
  }

  // === Telarañas fijas en las esquinas (Halloween) ==========================
  function drawCornerWeb(cx, flipX) {
    ctx.save();
    ctx.translate(cx, 0);
    if (flipX) ctx.scale(-1, 1);
    ctx.strokeStyle = 'rgba(210,210,220,0.16)';
    ctx.lineWidth = 1;
    var reach = 72;
    var spokes = 5;
    for (var s = 0; s < spokes; s++) {
      var angle = (Math.PI / 2) * (s / (spokes - 1));
      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.lineTo(Math.cos(angle) * reach, Math.sin(angle) * reach);
      ctx.stroke();
    }
    [22, 42, 62].forEach(function (r) {
      ctx.beginPath();
      ctx.arc(0, 0, r, 0, Math.PI / 2);
      ctx.stroke();
    });
    ctx.restore();
  }

  // === Araña que baja de su hilo cada tanto (Halloween) =====================
  var spider = null;
  function scheduleSpider() {
    setTimeout(function () {
      spider = { x: rand(canvas.width * 0.15, canvas.width * 0.85), startTime: performance.now(), dropDepth: rand(90, 170) };
      scheduleSpider();
    }, rand(18000, 38000));
  }
  if (config.spiders) scheduleSpider();

  function drawSpider(x, y) {
    ctx.strokeStyle = 'rgba(220,220,230,0.5)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x, 0);
    ctx.lineTo(x, y);
    ctx.stroke();

    ctx.save();
    ctx.translate(x, y);
    // Igual que el murciélago: morado claro en vez de casi-negro, para
    // que se note contra el fondo oscuro del sitio.
    ctx.fillStyle = 'rgba(185,150,220,0.9)';
    ctx.beginPath();
    ctx.ellipse(0, 0, 5, 6, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = 'rgba(185,150,220,0.9)';
    for (var i = 0; i < 4; i++) {
      var lx = 5 + i * 1.3;
      var ly = -5 + i * 3.2;
      ctx.beginPath(); ctx.moveTo(0, -1 + i); ctx.lineTo(lx, ly); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(0, -1 + i); ctx.lineTo(-lx, ly); ctx.stroke();
    }
    ctx.restore();
  }

  function drawSpiderIfActive() {
    if (!spider) return;
    var t = performance.now() - spider.startTime;
    var DOWN = 2600, HOLD = 1600, UP = 2200;
    var y;
    if (t < DOWN) {
      y = (t / DOWN) * spider.dropDepth;
    } else if (t < DOWN + HOLD) {
      y = spider.dropDepth;
    } else if (t < DOWN + HOLD + UP) {
      y = spider.dropDepth * (1 - (t - DOWN - HOLD) / UP);
    } else {
      spider = null;
      return;
    }
    drawSpider(spider.x, y);
  }

  // === Corazón (Día de la Madre / Amor y Amistad) ===========================
  function drawHeart(x, y, size, colorStr, opacity) {
    ctx.fillStyle = 'rgba(' + colorStr + ',' + opacity + ')';
    ctx.beginPath();
    var top = size * 0.3;
    ctx.moveTo(x, y + top);
    ctx.bezierCurveTo(x, y, x - size / 2, y, x - size / 2, y + top);
    ctx.bezierCurveTo(x - size / 2, y + (size + top) / 2, x, y + (size + top) / 2, x, y + size);
    ctx.bezierCurveTo(x, y + (size + top) / 2, x + size / 2, y + (size + top) / 2, x + size / 2, y + top);
    ctx.bezierCurveTo(x + size / 2, y, x, y, x, y + top);
    ctx.fill();
  }

  // === Trineo de Santa cruzando cada tanto (Navidad) ========================
  // === Estrella fugaz cada tanto (Reyes / fechas en general) ================
  var flyby = null;
  var FLYBY_CONFIG = {
    santa: { minGap: 45000, maxGap: 95000, duration: 8000, yRange: [40, 100] },
    shootingstar: { minGap: 20000, maxGap: 48000, duration: 1300, yRange: [30, 260] },
  };

  function scheduleFlyby(type) {
    var cfg = FLYBY_CONFIG[type];
    setTimeout(function () {
      flyby = {
        type: type,
        y: rand(cfg.yRange[0], Math.min(cfg.yRange[1], canvas.height * 0.4)),
        dir: Math.random() < 0.5 ? 1 : -1,
        startTime: performance.now(),
        duration: cfg.duration,
      };
      scheduleFlyby(type);
    }, rand(cfg.minGap, cfg.maxGap));
  }
  if (config.flyby) scheduleFlyby(config.flyby);

  function drawSanta(x, y, dir) {
    ctx.save();
    ctx.translate(x, y);
    if (dir < 0) ctx.scale(-1, 1);
    // Dorado/rojo, no marrón oscuro — el fondo del sitio ya es casi
    // negro (#080808), una silueta oscura se pierde por completo ahí.
    ctx.fillStyle = 'rgba(198,145,80,0.92)';
    ctx.beginPath();
    ctx.ellipse(0, 0, 24, 9, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.beginPath();
    ctx.arc(-2, -13, 8, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = 'rgba(216,48,48,0.95)';
    ctx.beginPath();
    ctx.moveTo(-9, -17);
    ctx.lineTo(-1, -24);
    ctx.lineTo(4, -17);
    ctx.closePath();
    ctx.fill();
    ctx.beginPath();
    ctx.arc(-1, -24, 2, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(255,255,255,0.9)';
    ctx.fill();
    ctx.fillStyle = 'rgba(172,124,68,0.85)';
    for (var i = 0; i < 3; i++) {
      var rx = 36 + i * 15;
      ctx.beginPath();
      ctx.ellipse(rx, -1, 6, 3.5, 0, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.arc(rx + 7, -5, 2.6, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.restore();
  }

  function drawShootingStar(x, y, dir, t) {
    var fade = t < 0.15 ? t / 0.15 : (t > 0.85 ? (1 - t) / 0.15 : 1);
    ctx.save();
    ctx.globalAlpha = Math.max(0, fade);
    var tailLen = 58 * dir * -1;
    var grad = ctx.createLinearGradient(x, y, x + tailLen, y + tailLen * 0.3);
    grad.addColorStop(0, 'rgba(255,255,255,0.9)');
    grad.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.strokeStyle = grad;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.lineTo(x + tailLen, y + tailLen * 0.3);
    ctx.stroke();
    ctx.fillStyle = '#fff';
    ctx.beginPath();
    ctx.arc(x, y, 2.2, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  }

  function drawFlybyIfActive() {
    if (!flyby) return;
    var elapsed = performance.now() - flyby.startTime;
    var t = elapsed / flyby.duration;
    if (t >= 1) {
      flyby = null;
      return;
    }
    var margin = flyby.type === 'santa' ? 120 : 60;
    var startX = flyby.dir === 1 ? -margin : canvas.width + margin;
    var endX = flyby.dir === 1 ? canvas.width + margin : -margin;
    var x = startX + (endX - startX) * t;
    if (flyby.type === 'santa') drawSanta(x, flyby.y, flyby.dir);
    else drawShootingStar(x, flyby.y, flyby.dir, t);
  }

  // === Fuegos artificiales periódicos (Año Nuevo) ===========================
  var fireworkBursts = [];
  function scheduleFirework() {
    setTimeout(function () {
      spawnFirework();
      scheduleFirework();
    }, rand(9000, 18000));
  }
  function spawnFirework() {
    var cx = rand(canvas.width * 0.2, canvas.width * 0.8);
    var cy = rand(canvas.height * 0.15, canvas.height * 0.45);
    var color = Math.random() < 0.5 ? '255,217,0' : '255,255,255';
    for (var i = 0; i < 26; i++) {
      var angle = (Math.PI * 2 * i) / 26;
      var speed = rand(1.2, 3);
      fireworkBursts.push({ x: cx, y: cy, vx: Math.cos(angle) * speed, vy: Math.sin(angle) * speed, life: 1, color: color });
    }
  }
  if (config.fireworks) scheduleFirework();

  function drawFireworks() {
    for (var i = fireworkBursts.length - 1; i >= 0; i--) {
      var fb = fireworkBursts[i];
      fb.x += fb.vx;
      fb.y += fb.vy;
      fb.vy += 0.02;
      fb.life -= 0.012;
      if (fb.life <= 0) {
        fireworkBursts.splice(i, 1);
        continue;
      }
      ctx.fillStyle = 'rgba(' + fb.color + ',' + Math.max(0, fb.life) + ')';
      ctx.beginPath();
      ctx.arc(fb.x, fb.y, 2, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  // === Interacción con el mouse (nieve): empuja los copos cercanos, como
  // si el cursor abriera paso. Vuelve a su curso normal apenas se aleja.
  var mouse = { x: -9999, y: -9999, active: false };
  if (config.interactive) {
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

  // === Agitar el celular = más nieve, como un snow globe ====================
  // En iOS 13+ hace falta permiso explícito (solo se puede pedir en
  // respuesta a un toque), así que lo pedimos en el primer touch de la
  // página; en Android/iOS viejo el evento ya está disponible directo.
  // No agrega partículas nuevas: reubica de golpe algunas de las que ya
  // existen, más rápido y ya dentro de la pantalla.
  if (config.shake && window.DeviceMotionEvent) {
    var lastAccel = null;
    var lastShakeAt = 0;

    function onMotion(e) {
      var acc = e.accelerationIncludingGravity || e.acceleration;
      if (!acc) return;
      var cur = { x: acc.x || 0, y: acc.y || 0, z: acc.z || 0 };
      if (lastAccel) {
        var delta = Math.abs(cur.x - lastAccel.x) + Math.abs(cur.y - lastAccel.y) + Math.abs(cur.z - lastAccel.z);
        var now = Date.now();
        if (delta > 18 && now - lastShakeAt > 500) {
          lastShakeAt = now;
          var count = Math.min(particles.length, 22);
          for (var i = 0; i < count; i++) {
            var p = particles[Math.floor(Math.random() * particles.length)];
            p.y = rand(-80, canvas.height * 0.3);
            p.vy = rand(config.speedY[1], config.speedY[1] * 2);
            p.vx = rand(-1.2, 1.2);
          }
        }
      }
      lastAccel = cur;
    }

    function enableMotion() {
      document.removeEventListener('touchstart', enableMotion);
      if (typeof DeviceMotionEvent.requestPermission === 'function') {
        DeviceMotionEvent.requestPermission().then(function (state) {
          if (state === 'granted') window.addEventListener('devicemotion', onMotion);
        }).catch(function () {});
      } else {
        window.addEventListener('devicemotion', onMotion);
      }
    }
    document.addEventListener('touchstart', enableMotion, { once: true });
  }

  // El propio navegador ya pausa/retoma requestAnimationFrame solo al
  // ocultar/mostrar la pestaña — "running" acá es solo para saltear el
  // trabajo pesado mientras está oculta, NUNCA para volver a arrancar
  // el loop a mano (hacerlo a mano sumaba una cadena de rAF nueva cada
  // vez, en paralelo a la que el navegador ya retomaba solo).
  var running = true;
  document.addEventListener('visibilitychange', function () {
    running = !document.hidden;
  });

  function tick() {
    if (running) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      if (config.corners) {
        drawCornerWeb(0, false);
        drawCornerWeb(canvas.width, true);
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

        var opacity = Math.max(0, config.twinkle ? p.opacity * (0.4 + 0.6 * Math.sin(p.phase)) : p.opacity);

        if (config.shape === 'rect') {
          ctx.fillStyle = 'rgba(' + p.color + ',' + opacity + ')';
          ctx.save();
          ctx.translate(p.x, p.y);
          ctx.rotate(p.rotation);
          ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
          ctx.restore();
          p.rotation += p.vr;
        } else if (config.shape === 'heart') {
          ctx.beginPath();
          ctx.arc(p.x, p.y + p.size * 0.3, p.size * 1.3, 0, Math.PI * 2);
          ctx.fillStyle = 'rgba(' + p.color + ',' + (opacity * 0.18) + ')';
          ctx.fill();
          drawHeart(p.x, p.y, p.size, p.color, opacity);
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

      bats.forEach(function (bat) {
        bat.x += bat.vx;
        bat.t += 0.05;
        if (bat.x < -25) bat.x = canvas.width + 25;
        if (bat.x > canvas.width + 25) bat.x = -25;
        drawBat(bat.x, bat.y + Math.sin(bat.t) * 8, bat.t);
      });

      if (config.spiders) drawSpiderIfActive();
      if (config.flyby) drawFlybyIfActive();
      if (config.fireworks) drawFireworks();
    }
    requestAnimationFrame(tick);
  }

  requestAnimationFrame(tick);
});
