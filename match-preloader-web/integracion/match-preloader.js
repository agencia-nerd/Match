/*!
 * MATCH · Preloader — vanilla JS, sin dependencias.
 *
 * Atributos opcionales en [data-match-preloader]:
 *   data-speed="1"        multiplicador de velocidad (1 = valor aprobado)
 *   data-once="session"   mostrarlo solo una vez por sesión
 *
 * Al terminar:
 *   - elimina el preloader del DOM
 *   - <html> pasa de .match-preloading a .match-loaded
 *   - dispara window 'match:preloader-done'
 *
 * El porcentaje refleja la carga real (imágenes, fuentes, window.load) y nunca
 * avanza más rápido que la animación. Si la web tarda, el logo se queda en
 * pantalla hasta que todo cargue y recién ahí pasa al Hero.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-match-preloader]');
  var html = document.documentElement;
  if (!root) { html.classList.remove('match-preloading'); return; }

  var SEEN_KEY = 'matchPreloaderSeen';
  var once = root.getAttribute('data-once') === 'session';

  function finish() {
    if (root.parentNode) root.parentNode.removeChild(root);
    html.classList.remove('match-preloading');
    html.classList.add('match-loaded');
    try { if (once) sessionStorage.setItem(SEEN_KEY, '1'); } catch (e) {}
    window.dispatchEvent(new CustomEvent('match:preloader-done'));
    window.removeEventListener('resize', onResize);
  }

  try { if (once && sessionStorage.getItem(SEEN_KEY)) { finish(); return; } } catch (e) {}
  if (!root.animate) { window.addEventListener('load', finish); return; }

  /* ---------- Línea de tiempo (ms a velocidad 1) ---------- */
  var T = 6400;          // duración total hasta cubrir con el Hero
  var HOLD = 5300;       // punto donde espera si la web aún no terminó de cargar
  var RATE = (parseFloat(root.getAttribute('data-speed')) || 1) * 1.4;
  var E = 'cubic-bezier(.77,0,.18,1)';
  var OUT = 'cubic-bezier(.16,1,.3,1)';
  var IO = 'cubic-bezier(.83,0,.17,1)';
  var HW = 'cubic-bezier(.76,0,.24,1)';
  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var q = function (s) { return root.querySelector(s); };
  var qa = function (s) { return Array.prototype.slice.call(root.querySelectorAll(s)); };
  var o = function (ms) { return ms / T; };

  var anims = [];
  var main = null;
  var grain = null;

  function add(el, kf) {
    if (!el) return null;
    var a = el.animate(kf, { duration: T, iterations: 1, fill: 'both' });
    a.playbackRate = RATE;
    anims.push(a);
    return a;
  }

  function build(atTime, paused) {
    anims.forEach(function (a) { a.cancel(); });
    anims = [];

    var S = function (op, y, sc, bl) {
      return { opacity: op, transform: 'translateY(' + y + '%) scale(' + sc + ')', filter: 'blur(' + bl + 'px)' };
    };
    main = add(q('[data-stage]'), [
      Object.assign({ offset: 0, easing: OUT }, S(0, 0, .55, 14)),
      Object.assign({ offset: o(900) }, S(1, 0, 1, 0)),
      Object.assign({ offset: o(5450), easing: 'cubic-bezier(.7,0,.84,0)' }, S(1, 0, 1, 0)),
      Object.assign({ offset: o(6150) }, S(0, -6, .94, 6)),
      Object.assign({ offset: 1 }, S(0, -6, .94, 6))
    ]);

    add(q('[data-hero]'), [
      { offset: 0, clipPath: 'inset(100% 0% 0% 0%)' },
      { offset: o(5500), clipPath: 'inset(100% 0% 0% 0%)', easing: HW },
      { offset: o(6400), clipPath: 'inset(0% 0% 0% 0%)' },
      { offset: 1, clipPath: 'inset(0% 0% 0% 0%)' }
    ]);

    add(q('[data-progress]'), [
      { offset: 0, opacity: 0 }, { offset: o(500), opacity: 1 },
      { offset: o(5300), opacity: 1 }, { offset: o(5700), opacity: 0 }, { offset: 1, opacity: 0 }
    ]);

    // Cubo: cada cara gira sobre el borde compartido (profundidad = mitad del lado)
    var cube = q('[data-cube]');
    var u = cube ? (cube.offsetWidth / 36 || 3) : 3;
    var F = { c: [0, 0], right: [0, 90], left: [0, -90], top: [90, 0], bottom: [-90, 0] };
    var dep = function (dir) { return (dir === 'top' || dir === 'bottom' ? 11 : 18) * u; };
    var r2 = function (rot, d) {
      return 'translateZ(' + (-d) + 'px) rotateX(' + rot[0] + 'deg) rotateY(' + rot[1] + 'deg) translateZ(' + d + 'px)';
    };
    function face(name, from, tin, to, tout) {
      var el = q('[data-face="' + name + '"]');
      var di = from ? dep(from) : dep(to), dO = to ? dep(to) : di, kf = [];
      if (from) kf.push({ offset: 0, transform: r2(F[from], di) }, { offset: o(tin), transform: r2(F[from], di), easing: E }, { offset: o(tin + 600), transform: r2(F.c, di) });
      else kf.push({ offset: 0, transform: r2(F.c, di) });
      if (to) kf.push({ offset: o(tout), transform: r2(F.c, dO), easing: E }, { offset: o(tout + 600), transform: r2(F[to], dO) }, { offset: 1, transform: r2(F[to], dO) });
      else kf.push({ offset: 1, transform: r2(F.c, di) });
      add(el, kf);
      // Visibilidad explícita: cada cara solo se ve cuando está al frente
      // (no depende de backface-visibility, que falla en algunos equipos/GPU)
      if (el) {
        var ok = [];
        if (from) ok.push({ offset: 0, opacity: 0 }, { offset: o(tin + 150), opacity: 0, easing: 'cubic-bezier(.3,.6,.6,1)' }, { offset: o(tin + 480), opacity: 1 });
        else ok.push({ offset: 0, opacity: 1 });
        if (to) ok.push({ offset: o(tout), opacity: 1, easing: 'cubic-bezier(.4,0,.7,.4)' }, { offset: o(tout + 380), opacity: 0 }, { offset: 1, opacity: 0 });
        else ok.push({ offset: 1, opacity: 1 });
        add(el.firstElementChild, ok);
      }
    }
    face('empty', null, 0, 'right', 700);      // vacío
    face('navy', 'left', 700, 'bottom', 1500);  // azul oscuro · entra por la izquierda
    face('purple', 'top', 1500, 'left', 2300);  // morado · entra por arriba
    face('gray', 'right', 2300, 'top', 3100);   // gris · entra por la derecha
    face('celeste', 'bottom', 3100, null, 0);   // original · entra por abajo

    add(q('[data-lockup]'), [
      { offset: 0, transform: 'translateX(37.142857%)' },
      { offset: o(3900), transform: 'translateX(37.142857%)', easing: IO },
      { offset: o(4950), transform: 'translateX(0%)' },
      { offset: 1, transform: 'translateX(0%)' }
    ]);
    add(q('[data-word]'), [
      { offset: 0, clipPath: 'inset(0% 100% 0% 0%)' },
      { offset: o(3950), clipPath: 'inset(0% 100% 0% 0%)', easing: IO },
      { offset: o(5000), clipPath: 'inset(0% 0% 0% 0%)' },
      { offset: 1, clipPath: 'inset(0% 0% 0% 0%)' }
    ]);
    qa('[data-letter]').forEach(function (el, i) {
      var s = 4050 + i * 80;
      add(el, [
        { offset: 0, opacity: 0, transform: 'translateX(-26px)' },
        { offset: o(s), opacity: 0, transform: 'translateX(-26px)', easing: OUT },
        { offset: o(s + 950), opacity: 1, transform: 'translateX(0px)' },
        { offset: 1, opacity: 1, transform: 'translateX(0px)' }
      ]);
    });

    if (atTime != null) anims.forEach(function (a) { a.currentTime = atTime; });
    if (paused) anims.forEach(function (a) { a.pause(); });
    if (main) main.finished.then(finish).catch(function () {});
  }

  /* ---------- Grano animado ---------- */
  var g = q('.mp-grain');
  if (g && !reduced) {
    var pos = [[0, 0], [-8, 4], [5, -6], [-3, 9], [7, 2], [-6, -4], [2, 6], [0, 0]];
    grain = g.animate(pos.map(function (p) {
      return { transform: 'translate(' + p[0] + '%, ' + p[1] + '%)', easing: 'steps(1, end)' };
    }), { duration: 480, iterations: Infinity });
  }

  /* ---------- Progreso real de carga ---------- */
  var total = 1, done = 0, pageLoaded = document.readyState === 'complete';
  var tick1 = function () { done = Math.min(total, done + 1); };
  Array.prototype.forEach.call(document.images, function (img) {
    if (root.contains(img)) return;
    total++;
    if (img.complete) tick1();
    else { img.addEventListener('load', tick1, { once: true }); img.addEventListener('error', tick1, { once: true }); }
  });
  if (document.fonts && document.fonts.ready) { total++; document.fonts.ready.then(tick1, tick1); }
  function onLoad() { pageLoaded = true; done = total; }
  if (pageLoaded) onLoad(); else window.addEventListener('load', onLoad, { once: true });
  setTimeout(onLoad, 12000); // seguro: nunca bloquear más de 12 s

  var num = q('[data-pct]'), bar = q('[data-bar]'), shown = 0, holding = false;

  function frame() {
    if (!root.parentNode) { if (grain) grain.cancel(); return; }
    var ms = main ? (main.effect.getComputedTiming().progress || 0) * T : 0;
    if (main && main.playState === 'finished') ms = T;

    // Esperar en el logo completo si la web aún no cargó
    if (!pageLoaded && ms >= HOLD && !holding) { holding = true; anims.forEach(function (a) { a.pause(); }); }
    if (pageLoaded && holding) { holding = false; anims.forEach(function (a) { a.play(); }); }

    var p = Math.max(0, Math.min(1, (ms - 400) / 4400));
    var timeP = 1 - Math.pow(1 - p, 2.2);
    var realP = pageLoaded ? 1 : Math.min(0.99, done / total);
    var target = Math.min(timeP, realP);
    shown += (target - shown) * 0.18;
    if (target >= 1 && shown > 0.995) shown = 1;
    if (num) num.textContent = Math.round(shown * 100) + '%';
    if (bar) bar.style.transform = 'scaleX(' + shown + ')';
    requestAnimationFrame(frame);
  }

  /* ---------- Resize: recalcula la profundidad del cubo ---------- */
  var rt;
  function onResize() {
    clearTimeout(rt);
    rt = setTimeout(function () {
      if (!main || !root.parentNode) return;
      var t = main.currentTime, wasPaused = main.playState === 'paused';
      build(t, wasPaused);
    }, 150);
  }
  window.addEventListener('resize', onResize);

  build(reduced ? 5000 : null, false);
  requestAnimationFrame(frame);
})();
