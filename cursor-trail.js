/* Glowing cursor trail with particles.
   Add to any page, just before </body>:  <script src="cursor-trail.js"></script>
   Change the look in OPT below. */
(function () {
  if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var OPT = {
    hue: 170,          // base color (0-360): 170 teal, 210 blue, 280 purple, 330 pink
    hueRange: 60,      // how far the color drifts over time (0 = fixed color)
    trailLife: 520,    // how long the trail lasts (ms)
    trailWidth: 9,     // thickness of the glowing trail
    maxParticles: 220
  };

  var c = document.createElement('canvas');
  c.setAttribute('aria-hidden', 'true');
  c.style.cssText = 'position:fixed;left:0;top:0;width:100%;height:100%;pointer-events:none;z-index:9999';
  document.body.appendChild(c);
  var ctx = c.getContext('2d');
  var dpr = Math.min(window.devicePixelRatio || 1, 2), W, H;

  function resize() {
    W = window.innerWidth; H = window.innerHeight;
    c.width = W * dpr; c.height = H * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.lineCap = 'round'; ctx.lineJoin = 'round';
  }
  resize();
  window.addEventListener('resize', resize);

  var mouse = { x: 0, y: 0, seen: false }, head = { x: 0, y: 0 };
  var pts = [], parts = [], lastMove = 0, hue = OPT.hue;

  window.addEventListener('pointermove', function (e) {
    mouse.x = e.clientX; mouse.y = e.clientY;
    if (!mouse.seen) { head.x = mouse.x; head.y = mouse.y; mouse.seen = true; pts = []; }
    lastMove = performance.now();
  });
  window.addEventListener('pointerdown', function (e) { burst(e.clientX, e.clientY); });
  document.addEventListener('pointerleave', function () { mouse.seen = false; });

  function spawn(x, y, speed, h) {
    var ang = Math.random() * 6.283, v = speed * (0.4 + Math.random());
    parts.push({ x: x, y: y, vx: Math.cos(ang) * v, vy: Math.sin(ang) * v,
                 born: performance.now(), max: 500 + Math.random() * 700,
                 size: 1 + Math.random() * 2.2, h: h + Math.random() * 40 - 20 });
    if (parts.length > OPT.maxParticles) parts.shift();
  }
  function burst(x, y) { for (var i = 0; i < 22; i++) spawn(x, y, 3, hue); }

  function line(a, b, w, col, alpha) {
    ctx.globalAlpha = alpha; ctx.lineWidth = w; ctx.strokeStyle = col;
    ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
  }

  function frame(t) {
    ctx.clearRect(0, 0, W, H);
    hue = OPT.hue + Math.sin(t / 1800) * OPT.hueRange;

    if (mouse.seen) {
      var ox = head.x, oy = head.y;
      head.x += (mouse.x - head.x) * 0.35;
      head.y += (mouse.y - head.y) * 0.35;
      var dist = Math.hypot(head.x - ox, head.y - oy);
      var last = pts[pts.length - 1];
      if (!last || Math.hypot(head.x - last.x, head.y - last.y) > 1.5) pts.push({ x: head.x, y: head.y, t: t });
      if (dist > 0.5 && t - lastMove < 120) {
        var n = Math.min(3, 1 + Math.floor(dist / 10));
        for (var i = 0; i < n; i++) spawn(head.x, head.y, 0.9, hue);
      }
    }
    while (pts.length && t - pts[0].t > OPT.trailLife) pts.shift();

    // glowing trail: three layers, wide and faint to thin and bright
    for (var j = 1; j < pts.length; j++) {
      var a = 1 - (t - pts[j].t) / OPT.trailLife, w = OPT.trailWidth * a;
      line(pts[j - 1], pts[j], w * 3, 'hsl(' + hue + ',100%,60%)', a * 0.12);
      line(pts[j - 1], pts[j], w * 1.8, 'hsl(' + hue + ',100%,62%)', a * 0.3);
      line(pts[j - 1], pts[j], w * 0.8, 'hsl(' + hue + ',100%,78%)', a * 0.95);
    }

    // soft glow at the pointer
    if (mouse.seen) {
      var g = ctx.createRadialGradient(head.x, head.y, 0, head.x, head.y, 20);
      g.addColorStop(0, 'hsla(' + hue + ',100%,70%,.45)');
      g.addColorStop(1, 'hsla(' + hue + ',100%,60%,0)');
      ctx.globalAlpha = 1; ctx.fillStyle = g;
      ctx.beginPath(); ctx.arc(head.x, head.y, 20, 0, 6.283); ctx.fill();
    }

    // particles
    for (var k = parts.length - 1; k >= 0; k--) {
      var p = parts[k], age = t - p.born;
      if (age > p.max) { parts.splice(k, 1); continue; }
      p.x += p.vx; p.y += p.vy; p.vx *= 0.985; p.vy = p.vy * 0.985 - 0.004;
      var al = 1 - age / p.max;
      ctx.globalAlpha = 1;
      ctx.fillStyle = 'hsla(' + p.h + ',100%,65%,' + (al * 0.25) + ')';
      ctx.beginPath(); ctx.arc(p.x, p.y, p.size * 3, 0, 6.283); ctx.fill();
      ctx.fillStyle = 'hsla(' + p.h + ',100%,78%,' + al + ')';
      ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, 6.283); ctx.fill();
    }
    ctx.globalAlpha = 1;
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
