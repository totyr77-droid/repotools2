(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  const fetch = page?.fetch || globalThis.fetch?.bind(globalThis);

  const cache = new Map();
  const access = new Map();
  const activeRequests = new WeakMap();
  async function get(ean, { fresh = false, image = false } = {}) {
    if (!/^(?:\d{8}|\d{13})$/.test(ean)) throw new Error('Código no válido.');
    if (fresh) { cache.delete(ean); cache.delete(ean + ':image'); }
    const key = image ? ean + ':image' : ean;
    if (!cache.has(key)) {
      const pending = (async () => {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        try {
          const response = await fetch(`api/productos.php?ean=${encodeURIComponent(ean)}${image ? '&image=1' : ''}`, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store' });
          const result = await response.json();
          if (!response.ok) throw new Error(result.error || 'No pudimos cargar el producto.');
          access.set(ean, { canEdit: result.can_edit === true, csrf: result.csrf });
          return result.product || null;
        } finally { clearTimeout(timeout); }
      })();
      cache.set(key, pending);
      pending.catch(() => { if (cache.get(key) === pending) cache.delete(key); });
    }
    return cache.get(key);
  }
  function editButton(container, ean) {
    if (!container) return;
    if (window.RepoProductReports) {
      const report = document.createElement('button'); report.type = 'button'; report.className = 'product-report-button';
      report.title = 'Reportar un error'; report.setAttribute('aria-label', 'Reportar un error');
      report.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 21V3m0 1c4-3 8 3 14 0v10c-6 3-10-3-14 0"/></svg>';
      if (container.id === 'libraryScanProductInfo' || container.id === 'scanProductActions') {
        const label = document.createElement('span'); label.textContent = container.id === 'scanProductActions' ? 'Reportar' : 'Reportar producto'; report.append(label);
        report.title = 'Reportar producto'; report.setAttribute('aria-label', 'Reportar producto');
      }
      report.addEventListener('click', () => window.RepoProductReports.open(ean)); container.append(report);
    }
    if (!access.get(ean)?.canEdit) return;
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'product-edit-button';
    button.title = 'Editar información del producto'; button.setAttribute('aria-label', 'Editar información del producto');
    button.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/></svg>';
    if (container.id === 'scanProductActions') { const label = document.createElement('span'); label.textContent = 'Editar'; button.append(label); }
    button.addEventListener('click', () => window.RepoProductEditor?.open(ean));
    container.append(button);
  }
  function clear(container) {
    if (!container) return;
    activeRequests.set(container, Symbol()); container.replaceChildren();
    container.setAttribute('aria-busy', 'false');
  }
  async function showSummary(container, ean, { compact = false } = {}) {
    if (!container) return null;
    const token = Symbol(); activeRequests.set(container, token);
    container.textContent = 'Buscando producto…';
    try {
      const product = await get(ean);
      if (activeRequests.get(container) !== token) return null;
      if (compact) container.textContent = product ? 'Nombre sugerido. Puedes editarlo.' : 'No tenemos información de este producto. Escribe un nombre para recordarlo.';
      else renderSummary(container, product);
      editButton(container, ean);
      return product;
    } catch (error) {
      if (activeRequests.get(container) === token) { container.textContent = compact ? 'No pudimos cargar los datos del producto. Puedes guardar el EAN con un nombre propio.' : (error.message || 'No pudimos cargar el producto.'); editButton(container, ean); }
      return null;
    }
  }
  async function save(ean, fields, revision) {
    const permission = access.get(ean);
    if (!permission?.canEdit) throw new Error('Solo administradores y moderadores pueden editar productos.');
    const response = await fetch('api/productos.php', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ ...fields, ean, revision, csrf: permission.csrf }) });
    const result = await response.json();
    if (!response.ok) {
      if (response.status === 409) cache.delete(ean);
      const error = new Error(result.error || 'No pudimos guardar el producto.'); error.status = response.status; throw error;
    }
    cache.set(ean, Promise.resolve(result.product));
    cache.delete(ean + ':image');
    access.set(ean, { canEdit: result.can_edit === true, csrf: result.csrf });
    window.dispatchEvent(new CustomEvent('repotools:product-updated', { detail: { ean, product: result.product } }));
    return result.product;
  }
  async function show(container, ean, { fresh = false } = {}) {
    if (!container) return;
    const token = Symbol(); activeRequests.set(container, token);
    container.replaceChildren(); container.setAttribute('aria-busy', 'true');
    const status = document.createElement('p'); status.className = 'product-details__status';
    status.setAttribute('role', 'status'); status.textContent = 'Buscando producto…'; container.append(status);
    try {
      const product = await get(ean, { image: true, fresh });
      if (activeRequests.get(container) !== token) return;
      container.replaceChildren();
      if (!product) {
        status.textContent = 'No hay información para este producto.'; container.append(status); editButton(container, ean); return;
      }
      const heading = document.createElement('h2'); heading.textContent = product.name || 'Producto sin nombre';
      container.append(heading);
      editButton(container, ean);
      if (product.image) {
        try {
          const url = new URL(product.image, location.href);
          const ownPhoto = url.origin === location.origin && url.pathname.endsWith('/api/productos.php') && url.searchParams.get('ean') === ean && url.searchParams.get('photo') === 'product';
          if (ownPhoto || (url.protocol === 'https:' && (url.hostname === 'openfoodfacts.org' || url.hostname.endsWith('.openfoodfacts.org')))) {
            const image = document.createElement('img'); image.src = url.href; image.alt = product.name || 'Foto del producto';
            image.className = 'product-details__image'; image.loading = 'lazy'; image.referrerPolicy = 'no-referrer';
            image.addEventListener('error', () => image.remove()); container.append(image);
          }
        } catch {}
      }
      const list = document.createElement('dl');
      for (const [label, value] of [['Marca', product.brand], ['Contenido neto', product.quantity], ['Categoría', product.category], ['Envase', product.packaging]]) {
        if (!value) continue;
        const row = document.createElement('div'); const term = document.createElement('dt'); const detail = document.createElement('dd');
        term.textContent = label; detail.textContent = value; row.append(term, detail); list.append(row);
      }
      container.append(list);
      if (!list.children.length && !product.name) { status.textContent = 'Este producto todavía no tiene características cargadas.'; container.append(status); }
    } catch {
      if (activeRequests.get(container) !== token) return;
      status.textContent = 'No pudimos cargar el producto. El EAN sigue disponible.';
      const retry = document.createElement('button'); retry.type = 'button'; retry.textContent = 'Reintentar'; retry.className = 'product-details__retry';
      retry.addEventListener('click', () => show(container, ean)); container.replaceChildren(status, retry);
      editButton(container, ean);
    } finally {
      if (activeRequests.get(container) === token) container.setAttribute('aria-busy', 'false');
    }
  }
  function displayName(product) {
    const name = (product?.name || '').trim(), brand = (product?.brand || '').trim();
    if (!name) return brand;
    return brand && !name.toLocaleLowerCase('es').includes(brand.toLocaleLowerCase('es')) ? `${brand} ${name}` : name;
  }
  function summary(product) {
    if (!product) return 'No tenemos información de este producto';
    return `${displayName(product) || 'Producto sin nombre'} · ${product.quantity || 'Contenido no disponible'}`;
  }
  function renderSummary(container, product) {
    container.replaceChildren();
    if (typeof product === 'string' || !product) { container.textContent = typeof product === 'string' ? product : summary(null); return; }
    const name = document.createElement('span'); name.className = 'product-summary-name'; name.textContent = displayName(product) || 'Producto sin nombre';
    const quantity = document.createElement('span'); quantity.className = 'product-summary-quantity'; quantity.textContent = product.quantity || 'Contenido no disponible';
    container.append(name, quantity);
  }
  function referenceName(product) {
    return [displayName(product), (product?.quantity || '').trim()].filter(Boolean).join(' ');
  }
  window.RepoProductInfo = { get, show, summary, renderSummary, displayName, referenceName, showSummary, clear, editButton, save, permission: ean => access.get(ean) };
})();
