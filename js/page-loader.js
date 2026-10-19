(() => {
  if (window.RepotoolsPageLoader) return;
  const root = document.documentElement;
  let state = 'hidden', generation = 0, shown = 0, watchdog, fade;
  const timings = {};
  const mark = name => { timings[name] = Math.round(performance.now()); };
  function node() {
    let overlay = document.getElementById('repotoolsPageLoader');
    if (!overlay) {
      overlay = document.createElement('div'); overlay.id = 'repotoolsPageLoader';
      overlay.setAttribute('role', 'status'); overlay.setAttribute('aria-label', 'Cargando');
      const image = document.createElement('img'); image.src = window.repotoolsLoaderLogo; image.alt = ''; image.width = image.height = 80;
      const label = document.createElement('span'); label.textContent = 'Cargando...'; const progress=document.createElement('span');progress.className='page-loader-progress';progress.setAttribute('aria-hidden','true');overlay.append(image,label,progress);
      root.append(overlay);
    }
    return overlay;
  }
  function show(restart = false) {
    if (state === 'visible' && !restart) return generation;
    clearTimeout(fade); clearTimeout(watchdog); generation++; shown = performance.now(); state = 'visible';
    for (const key of ['FONTS_READY_MS','UI_READY_MS','LOADER_HIDDEN_MS','TIMEOUT_MS']) delete timings[key];
    mark('LOADER_SHOWN_MS');
    const overlay = node(); root.classList.add('is-loading');
    if (typeof overlay.showPopover === 'function') {
      overlay.setAttribute('popover', 'manual');
      if (!overlay.matches(':popover-open')) overlay.showPopover();
    }
    watchdog = setTimeout(() => { mark('TIMEOUT_MS'); hide(generation, true); }, 5000);
    return generation;
  }
  function hide(ticket = generation, immediate = false) {
    if (ticket !== generation || state !== 'visible') return Promise.resolve();
    state = 'hiding'; clearTimeout(watchdog);
    return new Promise(resolve => {
      setTimeout(() => {
        if (ticket !== generation) { resolve(); return; }
        root.classList.remove('is-loading'); mark('LOADER_HIDDEN_MS');
        fade = setTimeout(() => { if (ticket === generation) { state = 'hidden'; node().hidePopover?.(); window.dispatchEvent(new Event('repotools:page-ready')); } }, immediate ? 0 : 220);
        resolve(); // Navigation may continue while the visual fade finishes.
      }, immediate ? 0 : Math.max(0, 250 - (performance.now() - shown)));
    });
  }
  async function ready(ticket, initial = false) {
    const waits = [document.fonts?.ready?.then(() => { if (ticket === generation) mark('FONTS_READY_MS'); })];
    if (initial && document.readyState !== 'complete') waits.push(new Promise(resolve => window.addEventListener('load', resolve, {once:true})));
    // Explicitly request the local solid icon face, even if the current section has few icons.
    if (document.fonts) waits.push(document.fonts.load('900 16px "Font Awesome 6 Free"'));
    let timer;
    await Promise.race([Promise.allSettled(waits), new Promise(resolve => { timer = setTimeout(resolve, Math.max(0, 4800 - (performance.now() - shown))); })]); clearTimeout(timer);
    await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    if (ticket !== generation) return;
    mark('UI_READY_MS'); await hide(ticket);
  }
  window.RepotoolsPageLoader = {show, hide, ready, mark, timings, get visible(){return state === 'visible';}};
  mark('NEW_DOCUMENT_MS'); const initial = show();
  document.addEventListener('DOMContentLoaded', () => ready(initial, true), {once:true});
  window.addEventListener('pageshow', event => { if (event.persisted) { clearTimeout(watchdog); clearTimeout(fade); generation++; state='hidden';root.classList.remove('is-loading');node().hidePopover?.(); } });
})();
