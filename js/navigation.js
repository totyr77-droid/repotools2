(() => {
  if (window.RepoNavigation) return;
  const routes = new Set(['index.php', 'index.html', 'biblioteca.php', 'stock.php', 'palet.php', 'stock-compartido.php', 'tops.php', 'reportes.php', 'cuenta.php', 'referidos.php', '']);
  const base = new URL('.', location.href);
  const loaded = new Set([...document.scripts].filter(script => script.src && new URL(script.src).origin !== location.origin).map(script => script.src));
  let page, version = 0, pending, commitQueue = Promise.resolve(), activeURL = new URL(location.href);
  function bounded(operation, milliseconds) {
    let timer;
    return Promise.race([operation,new Promise((_,reject)=>{timer=setTimeout(()=>reject(new Error('Navigation timeout')),milliseconds);})]).finally(()=>clearTimeout(timer));
  }
  function asset(node, append, signal) {
    return new Promise((resolve,reject)=>{
      let timer;
      const finish=(error)=>{clearTimeout(timer);signal?.removeEventListener('abort',abort);node.onload=node.onerror=null;if(error){node.remove();reject(error);}else resolve();};
      const abort=()=>finish(new DOMException('Navigation canceled','AbortError'));
      if(signal?.aborted){abort();return;}
      signal?.addEventListener('abort',abort,{once:true});
      node.onload=()=>finish();node.onerror=()=>finish(new Error('Resource unavailable'));
      timer=setTimeout(()=>finish(new Error('Resource timeout')),8000);append(node);
    });
  }
  function createPage() {
    const body = document.body, controller = new AbortController(), cleanups = [];
    const listen = (target, type, callback, options = {}) => {
      target.addEventListener(type, callback, { ...(typeof options === 'boolean' ? { capture: options } : options), signal: controller.signal });
    };
    const scopedDocument = new Proxy(document, { get(target, key) {
      if (key === 'body') return body;
      if (key === 'getElementById') return id => body.querySelector(`#${CSS.escape(id)}`);
      if (key === 'querySelector' || key === 'querySelectorAll') return selector => body[key](selector);
      if (key === 'addEventListener') return (...args) => listen(target, ...args);
      const value = Reflect.get(target, key, target); return typeof value === 'function' ? value.bind(target) : value;
    } });
    return {
      document: scopedDocument,
      listen: (...args) => listen(window, ...args),
      fetch: (url, options = {}) => {
        if (!options.signal) return fetch(url, { ...options, signal: controller.signal });
        if (typeof AbortSignal.any === 'function') return fetch(url, { ...options, signal: AbortSignal.any([controller.signal, options.signal]) });
        const request = new AbortController();
        for (const signal of [controller.signal, options.signal].filter(Boolean)) {
          if (signal.aborted) request.abort();
          else {
            const abort = () => request.abort();
            signal.addEventListener('abort', abort, { once: true });
            cleanups.push(() => { request.abort(); signal.removeEventListener('abort', abort); });
          }
        }
        return fetch(url, { ...options, signal: request.signal });
      },
      interval: (...args) => { const id = setInterval(...args); cleanups.push(() => clearInterval(id)); return id; },
      onLeave: callback => cleanups.push(callback),
      async dispose() {
        controller.abort();
        await bounded(Promise.allSettled(cleanups.map(callback => Promise.resolve().then(callback))),3000).catch(()=>{});
        body.querySelectorAll('video').forEach(video => video.srcObject?.getTracks?.().forEach(track => track.stop()));
        body.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
      }
    };
  }
  function resetConfig() {
    for (const key of ['repotoolsStats', 'repotoolsLibrary', 'repotoolsStock', 'repotoolsUserId', 'repotoolsNotifications', 'repotoolsReports', 'repotoolsReportConfig', 'alertaGeneral', 'cerrarAlerta', 'quitarOverlay', 'RepoProductReports', 'RepoReports']) delete window[key];
  }
  async function styles(next, signal) {
    const current = [...document.head.querySelectorAll('link[rel="stylesheet"]')];
    const nextLinks = [...next.head.querySelectorAll('link[rel="stylesheet"]')];
    const wanted = new Set(nextLinks.map(link => new URL(link.getAttribute('href'), base).href));
    await Promise.all(nextLinks.map(link => {
      const href = new URL(link.getAttribute('href'), base).href;
      if (current.some(item => item.href === href)) return;
      const copy = document.createElement('link'); copy.rel = 'stylesheet'; copy.href = href;
      return asset(copy,node=>document.head.append(node),signal);
    }));
    return () => current.filter(link => !wanted.has(link.href)).forEach(link => link.remove());
  }
  async function execute(scripts, signal) {
    for (const source of scripts) {
      if(signal?.aborted)throw new DOMException('Navigation canceled','AbortError');
      const src = source.getAttribute('src');
      if (src && new URL(src, base).pathname.endsWith('/js/navigation.js')) continue;
      const script = document.createElement('script');
      if (!src) { script.textContent = source.textContent; document.body.append(script); continue; }
      const url = new URL(src, base);
      if (url.origin !== location.origin && loaded.has(url.href)) continue;
      script.src = url.href; script.async = false;
      await asset(script,node=>document.body.append(node),signal);
      if (url.origin !== location.origin) loaded.add(url.href);
    }
  }
  async function navigate(href, { historyMode = 'push', scroll = [0, 0] } = {}) {
    href = new URL(href, location.href).href;
    if (new URL(href).origin !== location.origin) { location.assign(href); return; }
      if (navigator.onLine === false) { location.assign(href); return; }
    window.RepotoolsPageLoader?.mark('NAVIGATION_CLICK_MS');
    const loaderTicket = window.RepotoolsPageLoader?.show(true);
    const token = ++version;
    pending?.abort(); pending = new AbortController();
    const navigation=pending;
    document.documentElement.classList.add('app-navigating');
    try {
      const response = await bounded(fetch(href, { credentials: 'same-origin', signal: navigation.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' }),15000);
      if (new URL(response.url).origin !== location.origin) throw new Error('Unsupported redirect');
      if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) throw new Error('Page unavailable');
      const next = new DOMParser().parseFromString(await bounded(response.text(),15000), 'text/html');
      if (!next.querySelector('main') || !next.querySelector('script[src*="js/navigation.js"]')) throw new Error('Unsupported page');
      if (token !== version) return;
      commitQueue = commitQueue.catch(() => {}).then(async () => {
        if (token !== version) return;
        const finishStyles = await styles(next,navigation.signal);
        if (token !== version) return;
        await page.dispose(); if(token!==version)return; window.RepotoolsLoader?.clear(); resetConfig();
        const scripts = [...next.body.querySelectorAll('script')]; scripts.forEach(script => script.remove());
        const destination = new URL(response.url);
        const sections = ['index.php', 'biblioteca.php', 'stock.php', 'tops.php', 'reportes.php', 'cuenta.php'];
        const sectionIndex = url => sections.indexOf((url.pathname.split('/').pop() || 'index.php').replace('index.html', 'index.php'));
        const direction = sectionIndex(destination) < sectionIndex(activeURL) ? -1 : 1;
        const main = next.querySelector('main');
        main.style.setProperty('--page-shift', `${direction * 28}px`);
        if (new URL(href).hash) destination.hash = new URL(href).hash;
        if (historyMode === 'push') history.replaceState({ ...history.state, appScroll: [scrollX, scrollY] }, '', location.href);
        if (historyMode === 'push') history.pushState({ appScroll: scroll }, '', destination.href);
        else history.replaceState({ ...history.state, appScroll: scroll }, '', destination.href);
        document.title = next.title;
        activeURL = destination;
        try{next.body.dataset.oneHand=localStorage.getItem('repotools_one_hand_v1_user_'+Number(next.body.dataset.uiUser||0))==='1'?'1':'0';}catch{next.body.dataset.oneHand='0';}
        document.body.replaceWith(document.importNode(next.body, true));
        finishStyles(); page = createPage();
        // Reset before section scripts can anchor layout to the previous scroll position.
        window.scrollTo({ left: scroll[0], top: scroll[1], behavior: 'instant' });
        window.repotoolsOfflineUser=Number(document.body.dataset.uiUser||0);
        window.RepoOneHand?.refresh(); window.RepoMobileNav?.refresh();
        await execute(scripts,navigation.signal);
        if(token!==version)return;
        await window.RepotoolsPageLoader?.ready(loaderTicket);
        window.scrollTo({ left: scroll[0], top: scroll[1], behavior: 'instant' });
        if (destination.hash) document.getElementById(decodeURIComponent(destination.hash.slice(1)))?.scrollIntoView();
        const heading = document.querySelector('main h1'); if (heading) { heading.tabIndex = -1; heading.focus({ preventScroll: true }); }
        window.dispatchEvent(new CustomEvent('repotools:navigated'));
      });
      await commitQueue;
    } catch (error) {
      if (token === version && (error.name !== 'AbortError' || navigator.onLine === false)) { navigation.abort(); location.assign(href); }
    } finally { if (token === version) { pending = null; document.documentElement.classList.remove('app-navigating'); } }
  }
  page = createPage();
  document.addEventListener('keydown', event => {
    document.documentElement.dataset.keyboardFocus = 'true';
  });
  document.addEventListener('pointerdown', () => { document.documentElement.dataset.keyboardFocus = 'false'; }, { passive: true });
  window.RepoNavigation = { get page() { return page; }, navigate };
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest('a[href]');
    if (!link || link.download || (link.target && link.target !== '_self')) return;
    const url = new URL(link.href);
    if (url.origin !== location.origin || new URL('.', url).pathname !== base.pathname || !routes.has(url.pathname.split('/').pop())) return;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
    if (link.hasAttribute('data-full-navigation')) { window.RepotoolsPageLoader?.show(true); return; }
    event.preventDefault(); navigate(url.href);
  });
  window.addEventListener('popstate', event => navigate(location.href, { historyMode: 'replace', scroll: event.state?.appScroll || [0, 0] }));
})();
