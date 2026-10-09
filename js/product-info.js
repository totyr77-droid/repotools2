(() => {
  'use strict';
  const cache = new Map();
  const access = new Map();
  const activeRequests = new WeakMap();
  async function get(ean, { fresh = false } = {}) {
    if (!/^(?:\d{8}|\d{13})$/.test(ean)) throw new Error('Código no válido.');
    if (fresh) cache.delete(ean);
    if (!cache.has(ean)) {
      const pending = (async () => {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        try {
          const response = await fetch(`api/productos.php?ean=${encodeURIComponent(ean)}`, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store' });
          const result = await response.json();
          if (!response.ok) throw new Error(result.error || 'No pudimos cargar el producto.');
          access.set(ean, { canEdit: result.can_edit === true, csrf: result.csrf });
          return result.product || null;
        } finally { clearTimeout(timeout); }
      })();
      cache.set(ean, pending);
      pending.catch(() => { if (cache.get(ean) === pending) cache.delete(ean); });
    }
    return cache.get(ean);
  }
  function editButton(container, ean) {
    if (!access.get(ean)?.canEdit) return;
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'product-edit-button';
    button.title = 'Editar información del producto'; button.setAttribute('aria-label', 'Editar información del producto');
    button.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/></svg>';
    button.addEventListener('click', () => window.RepoProductEditor?.open(ean));
    container.append(button);
  }
  function clear(container) {
    if (!container) return;
    activeRequests.set(container, Symbol()); container.replaceChildren();
    container.setAttribute('aria-busy', 'false');
  }
  async function showSummary(container, ean) {
    if (!container) return null;
    const token = Symbol(); activeRequests.set(container, token);
    container.textContent = 'Buscando producto…';
    try {
      const product = await get(ean);
      if (activeRequests.get(container) !== token) return null;
      container.textContent = summary(product);
      editButton(container, ean);
      return product;
    } catch (error) {
      if (activeRequests.get(container) === token) container.textContent = error.message || 'No pudimos cargar el producto.';
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
    access.set(ean, { canEdit: result.can_edit === true, csrf: result.csrf });
    window.dispatchEvent(new CustomEvent('repotools:product-updated', { detail: { ean, product: result.product } }));
    return result.product;
  }
  async function show(container, ean) {
    if (!container) return;
    const token = Symbol(); activeRequests.set(container, token);
    container.replaceChildren(); container.setAttribute('aria-busy', 'true');
    const status = document.createElement('p'); status.className = 'product-details__status';
    status.setAttribute('role', 'status'); status.textContent = 'Buscando producto…'; container.append(status);
    try {
      const product = await get(ean);
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
          const url = new URL(product.image);
          if (url.protocol === 'https:' && (url.hostname === 'openfoodfacts.org' || url.hostname.endsWith('.openfoodfacts.org'))) {
            const image = document.createElement('img'); image.src = url.href; image.alt = product.name || 'Foto del producto';
            image.className = 'product-details__image'; image.loading = 'lazy'; image.referrerPolicy = 'no-referrer';
            image.addEventListener('error', () => image.remove()); container.append(image);
          }
        } catch {}
      }
      const list = document.createElement('dl');
      for (const [label, value] of [['Marca', product.brand], ['Presentación', product.quantity], ['Categoría', product.category], ['Envase', product.packaging]]) {
        if (!value) continue;
        const row = document.createElement('div'); const term = document.createElement('dt'); const detail = document.createElement('dd');
        term.textContent = label; detail.textContent = value; row.append(term, detail); list.append(row);
      }
      container.append(list);
      if (!list.children.length && !product.name) { status.textContent = 'Este producto todavía no tiene características cargadas.'; container.append(status); }
      if (product.source === 'openfoodfacts') {
        const source = document.createElement('a'); source.href = `https://world.openfoodfacts.org/product/${ean}`;
        source.target = '_blank'; source.rel = 'noopener noreferrer'; source.className = 'product-details__source';
        source.textContent = 'Open Food Facts · ODbL ↗'; container.append(source);
      }
    } catch {
      if (activeRequests.get(container) !== token) return;
      status.textContent = 'No pudimos cargar el producto. El EAN sigue disponible.';
      const retry = document.createElement('button'); retry.type = 'button'; retry.textContent = 'Reintentar'; retry.className = 'product-details__retry';
      retry.addEventListener('click', () => show(container, ean)); container.replaceChildren(status, retry);
    } finally {
      if (activeRequests.get(container) === token) container.setAttribute('aria-busy', 'false');
    }
  }
  function summary(product) {
    if (!product) return 'Producto sin información';
    return `${product.name || 'Nombre no disponible'} | ${product.quantity || 'Presentación no disponible'}`;
  }
  window.RepoProductInfo = { get, show, summary, showSummary, clear, editButton, save, permission: ean => access.get(ean) };
})();
