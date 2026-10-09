(() => {
  'use strict';
  const cache = new Map();
  const activeRequests = new WeakMap();
  const text = value => typeof value === 'string' ? value.trim() : '';
  async function get(ean) {
    if (!/^(?:\d{8}|\d{13})$/.test(ean)) throw new Error('Código no válido.');
    if (!cache.has(ean)) {
      const pending = (async () => {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
          const fields = 'product_name,product_name_es,brands,quantity,categories,packaging,image_front_small_url';
          const response = await fetch(`https://world.openfoodfacts.org/api/v2/product/${ean}.json?fields=${fields}`, { signal: controller.signal });
          if (!response.ok) throw new Error('No pudimos cargar el producto.');
          const result = await response.json();
          if (result.status !== 1 || !result.product) return null;
          const product = result.product;
          return {
            name: text(product.product_name_es) || text(product.product_name),
            brand: text(product.brands), quantity: text(product.quantity),
            category: text(product.categories), packaging: text(product.packaging),
            image: text(product.image_front_small_url)
          };
        } finally { clearTimeout(timeout); }
      })();
      cache.set(ean, pending);
      pending.catch(() => { if (cache.get(ean) === pending) cache.delete(ean); });
    }
    return cache.get(ean);
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
        status.textContent = 'No hay información para este producto.'; container.append(status); return;
      }
      const heading = document.createElement('h2'); heading.textContent = product.name || 'Producto sin nombre';
      container.append(heading);
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
      const source = document.createElement('a'); source.href = `https://world.openfoodfacts.org/product/${ean}`;
      source.target = '_blank'; source.rel = 'noopener noreferrer'; source.className = 'product-details__source';
      source.textContent = 'Datos de Open Food Facts ↗'; container.append(source);
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
  window.RepoProductInfo = { get, show, summary };
})();
