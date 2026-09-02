/* Deferred font stylesheet swap — matches the media="print" link in <head>.
   Kept out of an inline onload= attribute so it works under a strict
   Content-Security-Policy (script-src 'self', no inline event handlers). */
(function(){
  var link = document.querySelector('link[data-font-swap]');
  if (!link) return;
  if (link.media === 'all') return;
  link.addEventListener('load', function(){ link.media = 'all'; });
})();

/* Intro reveal — plays once per page load, then removes itself. */
(function(){
  var el = document.getElementById('introReveal');
  if (!el) return;
  var finished = false;
  function finish(){
    if (finished) return;
    finished = true;
    el.classList.add('done');
  }
  var reduceMotion = false;
  try{ reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches; }catch(e){}
  if (reduceMotion){
    finish();
    return;
  }
  setTimeout(function(){
    el.classList.add('leaving');
    el.addEventListener('animationend', function handler(e){
      if (e.target !== el) return;
      el.removeEventListener('animationend', handler);
      finish();
    });
    /* Safety net: if animationend never fires for any reason (throttled
       background tab, a blocked/overridden animation, an odd browser),
       the curtain must never be able to block the site permanently. */
    setTimeout(finish, 2500);
  }, 1300);
})();

/* Hero panel tilt — reacts to cursor like a label catching light.
   Skipped entirely on touch devices and reduced-motion. */
(function(){
  var panel = document.getElementById('heroVisual');
  if (!panel) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (window.matchMedia('(hover: none)').matches) return;

  panel.addEventListener('mousemove', function(e){
    var r = panel.getBoundingClientRect();
    var x = (e.clientX - r.left) / r.width - 0.5;
    var y = (e.clientY - r.top) / r.height - 0.5;
    panel.style.transform =
      'rotateY(' + (x * 8) + 'deg) rotateX(' + (y * -8) + 'deg) scale(1.015)';
  });
  panel.addEventListener('mouseleave', function(){
    panel.style.transform = 'rotateY(0) rotateX(0) scale(1)';
  });
})();

/* Mobile burger menu toggle */
(function(){
  var burger = document.querySelector('.burger');
  var nav = document.querySelector('.nav');

  if (!burger || !nav) return;

  burger.addEventListener('click', function(){
    var isOpen = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isOpen);
    nav.classList.toggle('nav-open');
  });
})();

/* Theme toggle — manual choice overrides system preference and persists.
   The <head> script already applied any stored choice before first paint;
   this just wires up the click and keeps aria-pressed/label in sync. */
(function(){
  var btn = document.getElementById('themeToggle');
  if (!btn) return;
  var root = document.documentElement;
  var mql = window.matchMedia('(prefers-color-scheme: dark)');

  function isDark(){
    var explicit = root.getAttribute('data-theme');
    if (explicit === 'dark') return true;
    if (explicit === 'light') return false;
    return mql.matches;
  }
  function sync(){
    var dark = isDark();
    btn.setAttribute('aria-pressed', String(dark));
    btn.setAttribute('aria-label', dark ? 'Switch to light theme' : 'Switch to dark theme');
  }
  sync();

  btn.addEventListener('click', function(){
    var next = isDark() ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try{ localStorage.setItem('elysian-theme', next); }catch(e){}
    sync();
  });
})();

/* Quote handoff — no backend is claimed. Build a clear email draft instead of
   leaving the form looking live while posting to a dead hash target. */
(function(){
  var form = document.querySelector('[data-quote-form]');
  var status = document.getElementById('quote-status');
  if (!form || !status) return;

  form.addEventListener('submit', function(e){
    e.preventDefault();
    if (!form.checkValidity()){
      form.reportValidity();
      status.textContent = 'Please complete the required fields before continuing.';
      return;
    }

    var data = new FormData(form);
    var subject = 'Quote request from ' + data.get('company');
    var body = [
      'Name: ' + data.get('name'),
      'Company: ' + data.get('company'),
      'Email: ' + data.get('email'),
      'Phone: ' + (data.get('phone') || 'Not supplied'),
      'Industry: ' + data.get('industry'),
      'Label type: ' + data.get('product'),
      '',
      'Size, volume and conditions:',
      data.get('detail') || 'Not supplied',
      '',
      'POPIA consent: Yes'
    ].join('\n');

    status.textContent = 'Opening your email app…';
    window.location.href = 'mailto:orders@elysianlabels.co.za?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
  });
})();

/* Scroll reveal + year stamp. Motion respects prefers-reduced-motion. */
(function(){
  document.getElementById('yr').textContent = new Date().getFullYear();

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var items = document.querySelectorAll('.rv');

  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(function(el){ el.classList.add('in'); });
    return;
  }

  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

  items.forEach(function(el, i){
    el.style.transitionDelay = (Math.min(i % 4, 3) * 70) + 'ms';
    io.observe(el);
  });
})();
