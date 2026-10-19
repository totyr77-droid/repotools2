(() => {
  if (window.RepotoolsLoader) return;
  const states = new WeakMap(), active = new Set();
  const noop = () => {};
  function start(target, { type = 'local', label = 'Cargando...', delay = 180, minimum = 300 } = {}) {
    if (!target) return noop;
    states.get(target)?.cancel();
    const busy = target.getAttribute('aria-busy'), disabled = target.disabled;
    const button = type === 'button', positioned = getComputedStyle(target).position === 'static';
    const position = target.style.position;
    let node, timer, removal, watchdog, shown = 0, ended = false;
    target.setAttribute('aria-busy', 'true');
    if (button) target.disabled = true;
    function restore() {
      if (states.get(target) !== state) return;
      if (busy === null) target.removeAttribute('aria-busy'); else target.setAttribute('aria-busy', busy);
      if (button) target.disabled = disabled;
    }
    function clean() {
      clearTimeout(timer); clearTimeout(removal); clearTimeout(watchdog); node?.remove();
      if (states.get(target) === state) {
        states.delete(target); target.classList.remove('repotools-loading-host');
        if (positioned) target.style.position = position;
      }
      active.delete(state);
    }
    const state = { cancel() { ended = true; restore(); clean(); } };
    states.set(target, state); active.add(state);
    timer = setTimeout(() => {
      if (ended || !target.isConnected) return;
      shown = performance.now();
      node = document.createElement('span'); node.className = 'repotools-loader repotools-loader--' + type;
      node.setAttribute('role', 'status'); node.setAttribute('aria-live', 'polite');
      const text = document.createElement('span'); text.className = 'repotools-loader-label'; text.textContent = label;
      if (type === 'fullscreen') {
        const logo = document.createElement('img'); logo.src = new URL('img/brand/repotools-isotipo-transparente.svg', document.baseURI).href; logo.alt = ''; logo.width = logo.height = 80; node.append(logo);
      }
      const mark = document.createElement('span'); mark.className = 'repotools-loader-mark'; mark.setAttribute('aria-hidden', 'true');
      for (let i = 0; i < (type === 'skeleton' ? 3 : 5); i++) { const bar = document.createElement('i'); bar.style.setProperty('--loader-index', i); mark.append(bar); }
      if(target.classList.contains('stock-pallet-lookup-notice')){const brand=document.createElement('span');brand.className='repotools-loader-brand';brand.setAttribute('aria-hidden','true');const box=document.createElement('span');box.className='repotools-loader-box';box.append(text,mark);node.append(brand,box);}else node.append(mark,text);
      if (positioned) target.style.position = 'relative';
      if (!button) target.classList.add('repotools-loading-host');
      (type === 'fullscreen' ? document.documentElement : target).append(node);
    }, delay);
    const stop = () => {
      if (ended) return; ended = true; clearTimeout(timer); restore();
      // Content and controls are available immediately; only the visual indicator has a minimum.
      removal = setTimeout(clean, shown ? Math.max(0, minimum - (performance.now() - shown)) : 0);
    };
    stop.cancel = state.cancel;
    // Visual safeguard only: requests and their existing error/offline handling are unchanged.
    watchdog = setTimeout(state.cancel, 20000);
    return stop;
  }
  function clear() { [...active].forEach(state => state.cancel()); }
  window.addEventListener('pagehide', clear);
  window.addEventListener('offline', clear);
  window.RepotoolsLoader = { start, clear };
})();
