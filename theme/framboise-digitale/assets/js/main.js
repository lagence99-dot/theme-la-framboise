/* La Framboise Digitale — animations au scroll. Sans dépendance. */
(function () {
  'use strict';
  var root = document.documentElement;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion:reduce)').matches;
  function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
  function cl(x) { return x < 0 ? 0 : x > 1 ? 1 : x; }

  /* --- cadres au format exact de l'image --- */
  function fit(im) {
    var f = im.closest('.fr[data-fit]');
    if (f && im.naturalWidth) { f.style.aspectRatio = im.naturalWidth + ' / ' + im.naturalHeight; relayout(); }
  }
  $$('.fr[data-fit] img').forEach(function (im) {
    if (im.complete && im.naturalWidth) fit(im); else im.addEventListener('load', function () { fit(im); });
  });

  $$('.fr[data-fit] video').forEach(function (v) {
    function f() { var fr = v.closest('.fr'); if (fr && v.videoWidth) { fr.style.aspectRatio = v.videoWidth + ' / ' + v.videoHeight; relayout(); } }
    if (v.readyState >= 1) f(); else v.addEventListener('loadedmetadata', f);
  });

  /* --- texte qui s'allume mot à mot --- */
  $$('[data-fill]').forEach(function (el) {
    var ws = el.textContent.trim().split(/\s+/); el.textContent = '';
    ws.forEach(function (w, i) {
      var s = document.createElement('span'); s.className = 'w'; s.textContent = w; el.appendChild(s);
      if (i < ws.length - 1) el.appendChild(document.createTextNode(' '));
    });
  });

  /* --- titres découpés : les mots montent ligne par ligne --- */
  function splitWords(node) {
    Array.prototype.slice.call(node.childNodes).forEach(function (n) {
      if (n.nodeType === 3) {
        var parts = n.textContent.split(/(\s+)/), frag = document.createDocumentFragment();
        parts.forEach(function (p) {
          if (!p) return;
          if (/^\s+$/.test(p)) { frag.appendChild(document.createTextNode(' ')); return; }
          var o = document.createElement('span'), i = document.createElement('span');
          o.className = 'wd'; i.className = 'wi'; i.textContent = p; o.appendChild(i); frag.appendChild(o);
        });
        node.replaceChild(frag, n);
      } else if (n.nodeType === 1 && n.tagName !== 'BR') { splitWords(n); }
    });
  }
  function lineIndex(el) {
    var top = null, li = -1;
    $$('.wd', el).forEach(function (w) {
      var t = w.offsetTop; if (top === null || Math.abs(t - top) > 6) { top = t; li++; }
      w.firstChild.style.setProperty('--li', Math.min(li, 8));
    });
  }
  var spl = $$('.spl');
  if (!reduce) spl.forEach(function (el) { splitWords(el); lineIndex(el); });

  /* --- apparitions --- */
  var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' }) : null;
  $$('.rv,.spl,.wl,.wr,.wu,.rule').forEach(function (el) { if (io) io.observe(el); else el.classList.add('in'); });

  /* --- vidéos : lecture seulement quand elles sont à l'écran --- */
  if (io && !reduce) {
    var vio = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        var v = e.target;
        if (e.isIntersecting) { if (v.getAttribute('data-paused')) return; var p = v.play(); if (p && p.catch) p.catch(function () {}); } else { v.pause(); }
      });
    }, { threshold: 0.1 });
    $$('video[data-auto]').forEach(function (v) { vio.observe(v); });
  }

  /* --- diaporama d'ouverture (images ou vidéos) --- */
  $$('.hero').forEach(function (hero) {
    var slides = $$('.hs', hero), bars = $$('.hs-bars button', hero);
    var nm = hero.querySelector('.hs-name'), sub = hero.querySelector('.hs-sub');
    if (slides.length < 2) return;
    var cur = 0, timer = 0;
    function media(i) { return slides[i].querySelector('video'); }
    function go(n) {
      if (n === cur) return;
      slides.forEach(function (s) { s.classList.remove('prev'); });
      slides[cur].classList.remove('on'); slides[cur].classList.add('prev');
      if (bars[cur]) bars[cur].classList.remove('on');
      var old = media(cur); if (old) setTimeout(function () { old.pause(); }, 1600);
      cur = n; slides[cur].classList.add('on');
      var v = media(cur); if (v) { try { v.currentTime = 0; } catch (e) {} var p = v.play(); if (p && p.catch) p.catch(function () {}); }
      if (bars[cur]) { void bars[cur].offsetWidth; bars[cur].classList.add('on'); }
      document.dispatchEvent(new CustomEvent('lfd:slide'));
      if (nm) { nm.firstChild.textContent = slides[cur].getAttribute('data-name') || ''; nm.classList.remove('sw'); void nm.offsetWidth; nm.classList.add('sw'); }
      if (sub) sub.textContent = slides[cur].getAttribute('data-sub') || '';
    }
    function auto() {
      clearInterval(timer);
      timer = setInterval(function () {
        if (document.hidden || window.scrollY > window.innerHeight * 1.4) return;
        go((cur + 1) % slides.length);
      }, 5600);
    }
    bars.forEach(function (b, i) { b.addEventListener('click', function () { go(i); auto(); }); });
    if (!reduce) auto();
  });

  /* --- légère parallaxe de l'ouverture à la souris --- */
  if (!reduce) {
    window.addEventListener('pointermove', function (e) {
      if (e.pointerType === 'touch' || window.scrollY > window.innerHeight) return;
      root.style.setProperty('--mx', (e.clientX / window.innerWidth - 0.5).toFixed(3));
      root.style.setProperty('--my', (e.clientY / window.innerHeight - 0.5).toFixed(3));
    }, { passive: true });
  }

  /* --- menu mobile --- */
  var burger = document.getElementById('burger');
  if (burger) {
    var setNav = function (open) {
      root.classList.toggle('nav-open', open);
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
    };
    burger.addEventListener('click', function () { setNav(!root.classList.contains('nav-open')); });
    $$('#nav a').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var h = a.getAttribute('href');
        if (!h || h === '#') { e.preventDefault(); return; }
        setNav(false);
      });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setNav(false); });
  }
  var totop = document.getElementById('totop');
  if (totop) totop.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });

  /* --- moteur de scroll --- */
  var hdr = document.getElementById('hdr'), pins = $$('[data-pin]'), panels = $$('.panel'), hpins = $$('[data-hpin]'), feats = $$('.feat'), fills = $$('[data-fill]'), pars = $$('[data-par]'), vbands = $$('[data-vband]');
  var lastY = 0, raf = 0;
  function size() {
    hpins.forEach(function (h) {
      var track = h.querySelector('.htrack'); if (!track) return;
      var w = track.scrollWidth;
      h.style.height = reduce ? '' : Math.max(window.innerHeight, w - window.innerWidth + window.innerHeight) + 'px';
    });
  }
  function tick() {
    raf = 0;
    var vh = window.innerHeight, vw = window.innerWidth, y = window.scrollY;
    if (hdr) {
      hdr.classList.toggle('scrolled', y > 40);
      hdr.classList.toggle('hide', y > lastY && y > vh * 0.9 && !root.classList.contains('nav-open'));
    }
    lastY = y;
    if (reduce) return;
    pins.forEach(function (el) {
      var r = el.getBoundingClientRect(); if (!r.height) return;
      el.style.setProperty('--hp', cl(-r.top / Math.max(1, r.height - vh)).toFixed(3));
    });
    panels.forEach(function (el, i) {
      var r = el.getBoundingClientRect(); if (!r.height || r.top > vh * 1.2 || r.bottom < -vh) return;
      var nx = panels[i + 1], same = nx && nx.parentNode === el.parentNode;
      el.style.setProperty('--q', (same ? cl(1 - nx.getBoundingClientRect().top / vh) : 0).toFixed(3));
      el.style.setProperty('--e', cl((vh - r.top) / vh).toFixed(3));
    });
    hpins.forEach(function (h) {
      var r = h.getBoundingClientRect(), track = h.querySelector('.htrack');
      if (!track || !r.height || r.top > vh || r.bottom < 0) return;
      var p = cl(-r.top / Math.max(1, r.height - vh)), dx = p * Math.max(0, track.scrollWidth - vw);
      track.style.transform = 'translate3d(' + (-dx).toFixed(1) + 'px,0,0)';
      h.style.setProperty('--hg', p.toFixed(3));
    });
    feats.forEach(function (el) {
      var r = el.getBoundingClientRect(); if (!r.height || r.bottom < -300 || r.top > vh + 300) return;
      var b = cl((vh * 0.96 - r.top) / (vh * 0.62));
      el.style.setProperty('--b', (b * b * (3 - 2 * b)).toFixed(3));
    });
    vbands.forEach(function (el) {
      var r = el.getBoundingClientRect(); if (!r.height || r.bottom < -200 || r.top > vh + 200) return;
      el.style.setProperty('--v', cl((vh - r.top) / (vh * 0.75)).toFixed(3));
    });
    pars.forEach(function (el) {
      var r = el.getBoundingClientRect(); if (!r.height || r.bottom < -200 || r.top > vh + 200) return;
      el.style.setProperty('--p', cl((vh - r.top) / (vh + r.height)).toFixed(3));
    });
    fills.forEach(function (el) {
      var r = el.getBoundingClientRect(); if (!r.height || r.bottom < -200 || r.top > vh + 200) return;
      var p = cl((vh * 0.9 - r.top) / (r.height + vh * 0.4)), w = el.children, n = w.length;
      for (var i = 0; i < n; i++) w[i].style.opacity = (0.16 + 0.84 * cl(p * n * 1.15 - i)).toFixed(2);
    });
  }
  function req() { if (!raf) raf = requestAnimationFrame(tick); }
  var rl = 0;
  function relayout() { if (rl) return; rl = setTimeout(function () { rl = 0; size(); req(); }, 60); }
  window.addEventListener('scroll', req, { passive: true });
  window.addEventListener('resize', relayout);
  window.addEventListener('load', relayout);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { spl.forEach(function (el) { if (!el.classList.contains('in')) lineIndex(el); }); relayout(); });
  /* --- bouton pause des vidéos --- */
  function syncBtn(v) {
    var b = v.parentNode && v.parentNode.querySelector('.vbtn');
    if (!b) { var c = v.closest('.cover'); b = c && c.querySelector('.vbtn'); }
    if (!b) return;
    b.classList.toggle('is-paused', v.paused);
    b.setAttribute('aria-label', v.paused ? 'Lire la vidéo' : 'Mettre la vidéo en pause');
  }
  $$('video[data-auto]').forEach(function (v) {
    v.addEventListener('play', function () { syncBtn(v); });
    v.addEventListener('pause', function () { syncBtn(v); });
    syncBtn(v);
  });
  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('.vbtn') : null;
    if (!b || b.classList.contains('sbtn')) return;
    e.preventDefault();
    var v = b.parentNode.querySelector('video');
    if (!v) return;
    if (v.paused) { v.removeAttribute('data-paused'); var p = v.play(); if (p && p.catch) p.catch(function () {}); }
    else { v.setAttribute('data-paused', '1'); v.pause(); }
  });

  /* --- vidéos côte à côte : flèches, compteur, glisser au doigt --- */
  $$('[data-vrow]').forEach(function (row) {
    var track = row.querySelector('.vrow-track'), cells = $$('.vcell', row);
    var prev = row.querySelector('.vprev'), next = row.querySelector('.vnext'), count = row.querySelector('.vcount b');
    if (!track || !cells.length) return;
    function step() { return cells[0].getBoundingClientRect().width || track.clientWidth; }
    function update() {
      var max = track.scrollWidth - track.clientWidth;
      row.classList.toggle('is-scrollable', max > 4);
      if (count) count.textContent = Math.min(cells.length, Math.round(track.scrollLeft / step()) + 1);
      if (prev) prev.disabled = track.scrollLeft < 4;
      if (next) next.disabled = track.scrollLeft > max - 4;
    }
    if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: reduce ? 'auto' : 'smooth' }); });
    if (next) next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: reduce ? 'auto' : 'smooth' }); });
    track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
    window.addEventListener('resize', update);
    update();
  });

  /* --- son : une seule vidéo sonore à la fois (la plus visible), comme sur Instagram ; ambiance de page --- */
  (function () {
    var sv = $$('video[data-sound]'), amb = document.getElementById('lfd-amb'), abtn = document.getElementById('lfd-abtn');
    if (!sv.length && !amb) return;
    var gestured = false, soundOn = true, ratios = [], ambWanted = true, ducked = false, actx = null, gain = null, fadeT = 0;
    // son au chargement : on essaie tout de suite ; si le navigateur refuse, le son démarre au premier geste du visiteur
    var auto = 'unknown', tryingV = null, ambAuto = 'unknown';
    function allowed() { return gestured || auto === 'allowed' || ambAuto === 'allowed'; }
    function tryAuto(v) {
      auto = 'trying'; tryingV = v;
      var done = false;
      function ok() { if (done) return; done = true; tryingV = null; if (!v.paused && !v.muted) { auto = 'allowed'; } else { auto = 'blocked'; v.muted = true; var q = v.play(); if (q && q.catch) q.catch(function () {}); } pick(); }
      function fail() { if (done) return; done = true; tryingV = null; auto = 'blocked'; v.muted = true; var q = v.play(); if (q && q.catch) q.catch(function () {}); pick(); }
      v.muted = false;
      var p = v.play();
      if (p && p.then) p.then(function () { setTimeout(ok, 200); }, fail); else setTimeout(ok, 200);
    }
    try { if (sessionStorage.getItem('lfd-amb') === 'off') ambWanted = false; } catch (x) {}
    function ratioOf(v) { for (var i = 0; i < ratios.length; i++) { if (ratios[i][0] === v) return ratios[i][1]; } return 0; }
    function setRatio(v, r) { for (var i = 0; i < ratios.length; i++) { if (ratios[i][0] === v) { ratios[i][1] = r; return; } } ratios.push([v, r]); }
    function eligible(v) { var hs = v.closest('.hs'); if (hs && !hs.classList.contains('on')) return false; return !v.paused; }
    function btnFor(v) { var h = v.closest('.hero'); if (h) return h.querySelector('.hero-sbtn'); return v.parentNode.querySelector('.sbtn'); }
    function pick() {
      var best = null, br = 0.5;
      sv.forEach(function (v) { var r = ratioOf(v); if (eligible(v) && r >= br) { best = v; br = r; } });
      var any = false;
      sv.forEach(function (v) {
        var want = soundOn && v === best, on = want && allowed();
        if (v === tryingV) { /* essai de son automatique en cours */ }
        else if (want && !on && auto === 'unknown') { tryAuto(v); }
        else { v.muted = !on; }
        if (on) any = true;
        var blocked = want && !on && auto === 'blocked';
        var b = btnFor(v);
        if (b && (!v.closest('.hs') || v.closest('.hs').classList.contains('on'))) {
          b.classList.toggle('is-on', on); b.classList.toggle('is-blocked', blocked); b.setAttribute('aria-pressed', on ? 'true' : 'false');
          b.setAttribute('aria-label', on ? 'Couper le son' : 'Activer le son');
        }
      });
      $$('.hero-sbtn').forEach(function (b) { var h = b.closest('.hero'); b.hidden = !h.querySelector('.hs.on video[data-sound]'); });
      ducked = any; ambSync();
    }
    // ambiance : volume réglé par Web Audio (le volume d'un lecteur est ignoré sur iPhone)
    function ambVolume(to, done) {
      if (!amb) return;
      var vol = to;
      if (gain && actx) {
        var t = actx.currentTime; gain.gain.cancelScheduledValues(t); gain.gain.setValueAtTime(gain.gain.value, t); gain.gain.linearRampToValueAtTime(vol, t + 0.8);
        clearTimeout(fadeT); if (done) fadeT = setTimeout(done, 850);
      } else {
        var from = amb.volume, t0 = performance.now();
        (function step(now) { var k = Math.min(1, (now - t0) / 800); amb.volume = Math.max(0, Math.min(1, from + (vol - from) * k)); if (k < 1) requestAnimationFrame(step); else if (done) done(); })(t0);
      }
    }
    function ambSync() {
      if (!amb) return;
      var vol = parseFloat(amb.getAttribute('data-vol')) || 0.2;
      if (ambWanted && !allowed() && !ducked && ambAuto === 'unknown') {
        ambAuto = 'trying'; amb.volume = 0;
        var t = amb.play();
        if (t && t.then) t.then(function () { ambAuto = 'allowed'; ambVolume(vol); ambSync(); pick(); }, function () { ambAuto = 'blocked'; ambSync(); });
        else ambAuto = 'blocked';
        return;
      }
      var play = ambWanted && allowed() && !ducked;
      if (play) {
        if (gestured && !actx && (window.AudioContext || window.webkitAudioContext)) {
          try { actx = new (window.AudioContext || window.webkitAudioContext)(); var node = actx.createMediaElementSource(amb); gain = actx.createGain(); gain.gain.value = 0; node.connect(gain); gain.connect(actx.destination); } catch (x) { actx = null; gain = null; }
        }
        if (actx && actx.state === 'suspended') actx.resume();
        if (!gain) amb.volume = amb.paused ? 0 : amb.volume;
        if (amb.paused) { var p = amb.play(); if (p && p.catch) p.catch(function () {}); }
        ambVolume(vol);
      } else if (!amb.paused) {
        ambVolume(0, function () { if (!(ambWanted && allowed() && !ducked)) amb.pause(); });
      }
      if (abtn) { var on = ambWanted && allowed(); abtn.classList.toggle('is-blocked', ambWanted && !on && ambAuto === 'blocked'); abtn.classList.toggle('is-on', on && !ducked); abtn.setAttribute('aria-pressed', on ? 'true' : 'false'); abtn.setAttribute('aria-label', on ? 'Couper l’ambiance sonore' : 'Activer l’ambiance sonore'); }
    }
    if (sv.length && 'IntersectionObserver' in window) {
      var sio = new IntersectionObserver(function (es) { es.forEach(function (e) { setRatio(e.target, e.isIntersecting ? e.intersectionRatio : 0); }); pick(); }, { threshold: [0, 0.25, 0.5, 0.75, 1] });
      sv.forEach(function (v) { sio.observe(v); v.addEventListener('play', pick); v.addEventListener('pause', pick); });
    }
    document.addEventListener('lfd:slide', function () { setTimeout(pick, 50); });
    // premier geste du visiteur : le navigateur autorise alors le son
    function gesture(e) {
      if (gestured || (e.target.closest && e.target.closest('.sbtn,.abtn'))) return;
      gestured = true; pick();
    }
    ['pointerdown', 'keydown', 'touchend'].forEach(function (t) { document.addEventListener(t, gesture, { passive: true }); });
    document.addEventListener('click', function (e) {
      var s = e.target.closest ? e.target.closest('.sbtn') : null;
      if (s) {
        e.preventDefault();
        var h = s.closest('.hero'), v = h ? h.querySelector('.hs.on video[data-sound]') : s.parentNode.querySelector('video[data-sound]');
        var wasOn = s.classList.contains('is-on');
        gestured = true; soundOn = !wasOn;
        if (v && soundOn) { setRatio(v, Math.max(ratioOf(v), 0.51)); if (v.paused) { v.removeAttribute('data-paused'); var p = v.play(); if (p && p.catch) p.catch(function () {}); } }
        pick(); return;
      }
      if (abtn && e.target.closest && e.target.closest('.abtn')) {
        e.preventDefault();
        ambWanted = !(ambWanted && gestured); gestured = true;
        try { sessionStorage.setItem('lfd-amb', ambWanted ? 'on' : 'off'); } catch (x) {}
        ambSync();
      }
    });
    pick();
  })();

  /* --- chiffres clés : défilement jusqu'à la valeur --- */
  $$('.stat-n .num[data-to]').forEach(function (el) {
    var to = parseFloat(el.getAttribute('data-to')), raw = el.getAttribute('data-raw') || el.textContent;
    if (!isFinite(to) || reduce || !io) return;
    var dec = (raw.split(/[.,]/)[1] || '').replace(/\D/g, '').length, sep = /\d[\s\u00a0\u202f]\d/.test(raw);
    function fmt(v) { var s = v.toFixed(dec); if (sep) s = s.replace(/\B(?=(\d{3})+(?!\d))/g, '\u202f'); return dec && raw.indexOf(',') > -1 ? s.replace('.', ',') : s; }
    el.textContent = fmt(0);
    var cio = new IntersectionObserver(function (es) {
      if (!es[0].isIntersecting) return;
      cio.disconnect();
      var t0 = performance.now(), dur = 1800;
      (function step(now) { var k = Math.min(1, (now - t0) / dur), e = 1 - Math.pow(1 - k, 4); el.textContent = fmt(to * e); if (k < 1) requestAnimationFrame(step); else el.textContent = raw; })(t0);
    }, { threshold: 0.4 });
    cio.observe(el);
  });

  /* --- carrousel de visuels : image active en avant, voisines estompées et floutées --- */
  $$('[data-cflow]').forEach(function (cf) {
    var items = $$('.cf-item', cf), n = items.length, cur = 0, name = cf.querySelector('.cf-name'), count = cf.querySelector('.cf-count'), moved = false, vis = false;
    function layout() {
      items.forEach(function (it, i) {
        var d = i - cur; if (d > n / 2) d -= n; if (d < -n / 2) d += n;
        var a = Math.abs(d);
        it.style.transform = 'translate(-50%,-50%) translateX(' + (d * 48) + '%) scale(' + (a === 0 ? 1 : (a === 1 ? 0.72 : 0.5)) + ')';
        it.style.opacity = a === 0 ? '1' : (a === 1 ? '0.5' : '0');
        it.style.filter = a === 0 ? 'none' : 'blur(' + (a === 1 ? 3 : 8) + 'px) saturate(.8)';
        it.style.zIndex = String(10 - a);
        it.style.pointerEvents = a > 1 ? 'none' : '';
        it.classList.toggle('is-on', a === 0);
        it.setAttribute('aria-hidden', a === 0 ? 'false' : 'true');
        var v = it.querySelector('video');
        if (v) { if (a === 0 && vis) { var p = v.play(); if (p && p.catch) p.catch(function () {}); } else { v.pause(); } }
      });
      var c = items[cur].getAttribute('data-cap') || '', h = items[cur].getAttribute('data-href') || '';
      name.textContent = '';
      if (c) { var el = document.createElement(h ? 'a' : 'span'); el.textContent = c; if (h) el.href = h; name.appendChild(el); }
      if (count) count.textContent = (cur + 1) + '/' + n;
    }
    function go(k) { cur = (k + n) % n; layout(); }
    cf.querySelector('.cf-prev').addEventListener('click', function () { go(cur - 1); });
    cf.querySelector('.cf-next').addEventListener('click', function () { go(cur + 1); });
    cf.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') go(cur - 1); if (e.key === 'ArrowRight') go(cur + 1); });
    var sx = null;
    cf.addEventListener('pointerdown', function (e) { sx = e.clientX; moved = false; });
    cf.addEventListener('pointerup', function (e) { if (sx === null) return; var dx = e.clientX - sx; sx = null; if (Math.abs(dx) > 40) { moved = true; go(cur + (dx < 0 ? 1 : -1)); } });
    items.forEach(function (it, i) {
      it.addEventListener('click', function () {
        if (moved) { moved = false; return; }
        if (i !== cur) { go(i); return; }
        var h = it.getAttribute('data-href'); if (h) window.location.href = h;
      });
    });
    if (io) new IntersectionObserver(function (es) { vis = es[0].isIntersecting; layout(); }, { threshold: 0.3 }).observe(cf);
    if (!reduce) setInterval(function () { if (vis && !document.hidden && !cf.matches(':hover') && !cf.contains(document.activeElement)) go(cur + 1); }, 4200);
    layout();
  });

  size(); tick();
})();
