(function () {
  var isl = document.getElementById('isl'), nav = document.getElementById('links'), mb = document.getElementById('mb');
  if (!isl || !nav) return;
  var links = [].slice.call(nav.querySelectorAll('a'));
  var ind = document.createElement('span');
  ind.className = 'ind';
  nav.appendChild(ind);
  var clean = function (p) { return p.replace(/index\.php$/, ''); };
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  function move(a) {
    if (!a || innerWidth <= 760) { ind.style.opacity = 0; return; }
    ind.style.opacity = 1;
    ind.style.width = a.offsetWidth + 'px';
    ind.style.transform = 'translateX(' + a.offsetLeft + 'px)';
  }
  function current() {
    var path = clean(location.pathname), best = null;
    links.forEach(function (a) { if (!a.hash && clean(a.pathname) === path) best = a; });
    if (path === clean(links[0].pathname)) {
      ['tips', 'artikel'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el && el.getBoundingClientRect().top < innerHeight * 0.4) {
          best = links.filter(function (a) { return a.hash === '#' + id; })[0] || best;
        }
      });
    }
    return best;
  }
  function update() {
    isl.classList.toggle('sm', scrollY > 80);
    var c = current();
    links.forEach(function (a) { a.classList.toggle('on', a === c); });
    move(c);
    var v = document.querySelector('.vis');
    if (v && !reduce && scrollY < innerHeight) v.style.transform = 'translateY(' + scrollY * 0.06 + 'px)';
  }
  addEventListener('scroll', update, { passive: true });
  addEventListener('resize', update);
  update();
  setTimeout(update, 300);

  if (mb) {
    mb.addEventListener('click', function () {
      var o = isl.classList.toggle('open');
      mb.setAttribute('aria-expanded', o);
      mb.textContent = o ? '\u2715' : '\u2630';
    });
    links.forEach(function (a) {
      a.addEventListener('click', function () { isl.classList.remove('open'); mb.textContent = '\u2630'; });
    });
  }

  var items = [].slice.call(document.querySelectorAll('.rv'));
  if (!('IntersectionObserver' in window)) { items.forEach(function (e) { e.classList.add('in'); }); return; }
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) {
      if (!e.isIntersecting) return;
      e.target.classList.add('in');
      io.unobserve(e.target);
      // after the reveal, drop the helper classes so normal hover effects work again
      setTimeout(function () { e.target.classList.remove('rv', 'in'); e.target.style.transitionDelay = ''; }, 1200);
    });
  }, { threshold: 0.12 });
  items.forEach(function (e, i) { e.style.transitionDelay = (i % 3) * 90 + 'ms'; io.observe(e); });
})();

/* Optional UI sound effects (synthesised in the browser, no audio files). Off by default. */
(function () {
  var on = false, ctx = null, last = null;
  try { on = localStorage.getItem('sfx') === '1'; } catch (e) {}
  var b = document.createElement('button');
  b.type = 'button'; b.className = 'sfx';
  function label() { b.textContent = 'SFX: ' + (on ? 'ON' : 'OFF'); b.setAttribute('aria-pressed', on); }
  function tone(f, d, t, v) {
    if (!on) return;
    try {
      ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.type = t; o.frequency.value = f;
      g.gain.setValueAtTime(v, ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + d);
      o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + d);
    } catch (e) {}
  }
  b.addEventListener('click', function () {
    on = !on;
    try { localStorage.setItem('sfx', on ? '1' : '0'); } catch (e) {}
    label(); tone(660, 0.12, 'square', 0.08);
  });
  label();
  document.body.appendChild(b);
  var sel = '.btn,.pmenu a,.tip,.isl nav a,a.card,.dlg label';
  document.addEventListener('mouseover', function (e) {
    var t = e.target.closest && e.target.closest(sel);
    if (t && t !== last) { last = t; tone(880, 0.06, 'square', 0.04); }
  });
  document.addEventListener('mouseout', function (e) {
    var t = e.target.closest && e.target.closest(sel);
    if (t && !t.contains(e.relatedTarget)) last = null;
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest(sel)) tone(180, 0.15, 'sawtooth', 0.08);
  });
})();

/* Draggable profile card: drag, throw (inertia + bounce), tilt, click or Enter to flip */
(function () {
  var c = document.querySelector('.pcard');
  if (!c) return;
  var host = c.offsetParent || c.parentElement;
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var x = 0, y = 0, vx = 0, vy = 0, rot = 0, drag = false, lx = 0, ly = 0, lt = 0, moved = 0, raf = 0;
  function lim() {
    return { a: -c.offsetLeft, b: host.clientWidth - c.offsetLeft - c.offsetWidth,
             c: -c.offsetTop, d: host.clientHeight - c.offsetTop - c.offsetHeight };
  }
  function draw() { c.style.transform = 'translate(' + x + 'px,' + y + 'px) rotate(' + rot + 'deg)'; }
  function tilt(v) { return Math.max(-16, Math.min(16, v)); }
  function loop() {
    if (!drag) {
      var L = lim();
      x += vx; y += vy; vx *= 0.95; vy *= 0.95;
      if (x < L.a) { x = L.a; vx = -vx * 0.6; }
      if (x > L.b) { x = L.b; vx = -vx * 0.6; }
      if (y < L.c) { y = L.c; vy = -vy * 0.6; }
      if (y > L.d) { y = L.d; vy = -vy * 0.6; }
      rot += (tilt(vx * 1.2) - rot) * 0.15;
      if (Math.abs(vx) < 0.15 && Math.abs(vy) < 0.15 && Math.abs(rot) < 0.2) { rot = 0; draw(); raf = 0; return; }
    }
    draw();
    raf = requestAnimationFrame(loop);
  }
  function kick() { if (!raf) raf = requestAnimationFrame(loop); }

  c.addEventListener('pointerdown', function (e) {
    if (e.target.closest('a')) return;
    drag = true; c.setPointerCapture(e.pointerId); c.classList.add('drag');
    lx = e.clientX; ly = e.clientY; lt = Date.now(); moved = 0; vx = vy = 0; kick();
  });
  c.addEventListener('pointermove', function (e) {
    if (!drag) return;
    var dx = e.clientX - lx, dy = e.clientY - ly, L = lim();
    lx = e.clientX; ly = e.clientY; lt = Date.now(); moved += Math.abs(dx) + Math.abs(dy);
    x = Math.max(L.a, Math.min(L.b, x + dx));
    y = Math.max(L.c, Math.min(L.d, y + dy));
    vx = dx; vy = dy;
    rot = reduce ? 0 : tilt(rot * 0.6 + dx * 0.9);
  });
  function end() {
    if (!drag) return;
    drag = false; c.classList.remove('drag');
    if (reduce || Date.now() - lt > 80) { vx = vy = 0; }   // held still before release: no throw
    if (moved < 6) { c.classList.toggle('flip'); vx = vy = 0; }  // a tap flips the card
    kick();
  }
  c.addEventListener('pointerup', end);
  c.addEventListener('pointercancel', end);
  c.addEventListener('keydown', function (e) {
    var s = 24, L = lim();
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); c.classList.toggle('flip'); return; }
    var m = { ArrowLeft: [-s, 0], ArrowRight: [s, 0], ArrowUp: [0, -s], ArrowDown: [0, s] }[e.key];
    if (!m) return;
    e.preventDefault();
    x = Math.max(L.a, Math.min(L.b, x + m[0])); y = Math.max(L.c, Math.min(L.d, y + m[1])); draw();
  });
  addEventListener('resize', function () {
    var L = lim(); x = Math.max(L.a, Math.min(L.b, x)); y = Math.max(L.c, Math.min(L.d, y)); draw();
  });
})();