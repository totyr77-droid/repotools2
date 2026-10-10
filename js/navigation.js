(() => {
  if (window.RepoNavigation) return;
  const routes = new Set(['index.php', 'index.html', 'biblioteca.php', 'stock.php', 'tops.php', 'reportes.php', 'cuenta.php', '']);
  const base = new URL('.', location.href);
  const loaded = new Set([...document.scripts].filter(script => script.src && new URL(script.src).origin !== location.origin).map(script => script.src));
  let page, version = 0, pending, commitQueue = Promise.resolve();
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
        await Promise.allSettled(cleanups.map(callback => Promise.resolve().then(callback)));
        body.querySelectorAll('video').forEach(video => video.srcObject?.getTracks?.().forEach(track => track.stop()));
        body.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
      }
    };
  }
  function resetConfig() {
    for (const key of ['repotoolsStats', 'repotoolsLibrary', 'repotoolsStock', 'repotoolsUserId', 'repotoolsNotifications', 'repotoolsReports', 'repotoolsReportConfig', 'alertaGeneral', 'cerrarAlerta', 'quitarOverlay', 'RepoProductReports', 'RepoReports']) delete window[key];
  }
  async function styles(next) {
    const current = [...document.head.querySelectorAll('link[rel="stylesheet"]')];
    const nextLinks = [...next.head.querySelectorAll('link[rel="stylesheet"]')];
    const wanted = new Set(nextLinks.map(link => new URL(link.getAttribute('href'), base).href));
    await Promise.all(nextLinks.map(link => {
      const href = new URL(link.getAttribute('href'), base).href;
      if (current.some(item => item.href === href)) return;
      return new Promise((resolve, reject) => {
        const copy = document.createElement('link'); copy.rel = 'stylesheet'; copy.href = href;
        copy.onload = resolve; copy.onerror = () => reject(new Error('Stylesheet unavailable'));
        document.head.append(copy);
      });
    }));
    return () => current.filter(link => !wanted.has(link.href)).forEach(link => link.remove());
  }
  async function execute(scripts) {
    for (const source of scripts) {
      const src = source.getAttribute('src');
      if (src && new URL(src, base).pathname.endsWith('/js/navigation.js')) continue;
      const script = document.createElement('script');
      if (!src) { script.textContent = source.textContent; document.body.append(script); continue; }
      const url = new URL(src, base);
      if (url.origin !== location.origin && loaded.has(url.href)) continue;
      await new Promise((resolve, reject) => {
        script.src = url.href; script.async = false;
        script.onload = () => { if (url.origin !== location.origin) loaded.add(url.href); resolve(); };
        script.onerror = () => reject(new Error('Script unavailable'));
        document.body.append(script);
      });
    }
  }
  async function navigate(href, { historyMode = 'push', scroll = [0, 0] } = {}) {
    href = new URL(href, location.href).href;
    if (new URL(href).origin !== location.origin) { location.assign(href); return; }
    const token = ++version;
    pending?.abort(); pending = new AbortController();
    document.documentElement.classList.add('app-navigating');
    try {
      const response = await fetch(href, { credentials: 'same-origin', signal: pending.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
      if (new URL(response.url).origin !== location.origin) throw new Error('Unsupported redirect');
      if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) throw new Error('Page unavailable');
      const next = new DOMParser().parseFromString(await response.text(), 'text/html');
      if (!next.querySelector('main') || !next.querySelector('script[src*="js/navigation.js"]')) throw new Error('Unsupported page');
      if (token !== version) return;
      commitQueue = commitQueue.catch(() => {}).then(async () => {
        if (token !== version) return;
        const finishStyles = await styles(next);
        if (token !== version) return;
        await page.dispose(); resetConfig();
        const scripts = [...next.body.querySelectorAll('script')]; scripts.forEach(script => script.remove());
        const destination = new URL(response.url);
        if (new URL(href).hash) destination.hash = new URL(href).hash;
        if (historyMode === 'push') history.replaceState({ ...history.state, appScroll: [scrollX, scrollY] }, '', location.href);
        if (historyMode === 'push') history.pushState({ appScroll: scroll }, '', destination.href);
        else history.replaceState({ ...history.state, appScroll: scroll }, '', destination.href);
        document.title = next.title;
        document.body.replaceWith(document.importNode(next.body, true));
        finishStyles(); page = createPage();
        await execute(scripts);
        window.scrollTo(...scroll);
        if (destination.hash) document.getElementById(decodeURIComponent(destination.hash.slice(1)))?.scrollIntoView();
        const heading = document.querySelector('main h1'); if (heading) { heading.tabIndex = -1; heading.focus({ preventScroll: true }); }
        window.dispatchEvent(new CustomEvent('repotools:navigated'));
      });
      await commitQueue;
    } catch (error) {
      if (token === version && error.name !== 'AbortError') location.assign(href);
    } finally { if (token === version) document.documentElement.classList.remove('app-navigating'); }
  }
  page = createPage();
  window.RepoNavigation = { get page() { return page; }, navigate };
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest('a[href]');
    if (!link || link.download || (link.target && link.target !== '_self') || link.hasAttribute('data-full-navigation')) return;
    const url = new URL(link.href);
    if (url.origin !== location.origin || new URL('.', url).pathname !== base.pathname || !routes.has(url.pathname.split('/').pop())) return;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
    event.preventDefault(); navigate(url.href);
  });
  window.addEventListener('popstate', event => navigate(location.href, { historyMode: 'replace', scroll: event.state?.appScroll || [0, 0] }));
})();
