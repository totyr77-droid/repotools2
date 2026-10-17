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
          access.set(ean, { canEdit: result.can_edit === true, photosEnabled:result.photos_enabled===true, csrf: result.csrf, missing:!result.product, checkedAt:Date.now() });
          if(result.product)window.RepoOfflineCatalog?.put(Number(window.repotoolsOfflineUser||0),ean,result.product).catch(()=>{});return result.product || null;
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
  async function save(ean, fields, revision, photo) {
    const permission = access.get(ean);
    if (!permission?.canEdit) throw new Error('Solo administradores y moderadores pueden editar productos.');
    const body=new FormData();for(const [key,value]of Object.entries({...fields,ean,revision,csrf:permission.csrf}))body.set(key,value);if(photo)body.set('photo_product',photo,'product.jpg');
    const response = await fetch('api/productos.php', { method: 'POST', credentials: 'same-origin', body });
    const result = await response.json();
    if (!response.ok) {
      if (response.status === 409) cache.delete(ean);
      const error = new Error(result.error || 'No pudimos guardar el producto.'); error.status = response.status; throw error;
    }
    cache.set(ean, Promise.resolve(result.product));
    cache.delete(ean + ':image');
    access.set(ean, { canEdit: result.can_edit === true, photosEnabled:result.photos_enabled===true, csrf: result.csrf });
    window.dispatchEvent(new CustomEvent('repotools:product-updated', { detail: { ean, product: result.product } }));
    return result.product;
  }
  async function show(container, ean, { fresh = false } = {}) {
    if (!container) return;
    const token = Symbol(); activeRequests.set(container, token);
    container.replaceChildren();
    const finishLoading = window.RepotoolsLoader?.start(container);
    container.setAttribute('aria-busy', 'true');
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
      if (product.source === 'openfoodfacts' || product.image_source === 'openfoodfacts') {
        const credit = document.createElement('small'); credit.className = 'product-source-credit';
        const source = document.createElement('a'); source.href = 'https://world.openfoodfacts.org/product/' + encodeURIComponent(ean);
        source.target = '_blank'; source.rel = 'noopener'; source.textContent = 'Open Food Facts';
        credit.append('Fuente: ', source, ' · ');
        const license = document.createElement('a'); license.href = 'https://world.openfoodfacts.org/data';
        license.target = '_blank'; license.rel = 'noopener'; license.textContent = product.image_source === 'openfoodfacts' ? 'ODbL · Foto CC BY-SA' : 'ODbL';
        credit.append(license); container.append(credit);
      }
      if (!list.children.length && !product.name) { status.textContent = 'Este producto todavía no tiene características cargadas.'; container.append(status); }
    } catch {
      if (activeRequests.get(container) !== token) return;
      status.textContent = 'No pudimos cargar el producto. El EAN sigue disponible.';
      const retry = document.createElement('button'); retry.type = 'button'; retry.textContent = 'Reintentar'; retry.className = 'product-details__retry';
      retry.addEventListener('click', () => show(container, ean)); container.replaceChildren(status, retry);
      editButton(container, ean);
    } finally {
      finishLoading?.();
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
    const name = document.createElement('span'); name.className = 'product-summary-name'; name.textContent = displayName(product) || 'Producto sin nombre'; name.title=name.textContent;
    const quantity = document.createElement('span'); quantity.className = 'product-summary-quantity'; quantity.textContent = product.quantity || 'Contenido no disponible';
    container.append(name, quantity);
  }
  function referenceName(product) {
    return [displayName(product), (product?.quantity || '').trim()].filter(Boolean).join(' ');
  }
  const referenceJobs=new Map(),referenceControls=new Set();let referenceLeft=false;
  const networkIsSlow=()=>{const connection=navigator.connection;return navigator.onLine===false||connection?.saveData===true||['slow-2g','2g','3g'].includes(connection?.effectiveType)||(Number(connection?.rtt)>800);};
  function stageReference(key,data,csrf){const lookup=access.get(data.ean);if(referenceLeft||data.action!=='save_product'||Number(data.product_id)!==0||!data.name?.trim()||!lookup?.missing||Date.now()-lookup.checkedAt>600000||networkIsSlow())return;
    const job={data:{ean:data.ean,name:data.name,code:data.code},created:Date.now(),promise:null};referenceJobs.set(key,job);
    job.promise=(async()=>{let controller;try{const health=await Promise.race([window.RepoConnectivity?.check({force:true,timeoutMs:1200}),new Promise(resolve=>setTimeout(()=>resolve(null),1250))]);if(referenceLeft||!health||health.kind!=='online'||health.latencyMs===null||health.latencyMs>1000||networkIsSlow())return '';controller=new AbortController();referenceControls.add(controller);const timer=setTimeout(()=>controller.abort(),1200);try{const response=await fetch('api/productos.php',{method:'POST',credentials:'same-origin',cache:'no-store',signal:controller.signal,body:new URLSearchParams({action:'stock_reference_prepare',operation_key:key,csrf,client_at:job.created,...job.data})});const result=await response.json();return response.ok&&typeof result.permit==='string'?result.permit:'';}finally{clearTimeout(timer);}}catch{return '';}finally{if(controller)referenceControls.delete(controller);}})();setTimeout(()=>{if(referenceJobs.get(key)===job)referenceJobs.delete(key);},10000);
  }
  async function takeReference(row){const job=referenceJobs.get(row.key);referenceJobs.delete(row.key);if(!job||referenceLeft||row.deferred||row.offline_wait||row.attempts||row.data?.action!=='save_product'||networkIsSlow())return '';for(const field of ['ean','name','code'])if(job.data[field]!==row.data[field])return '';const permit=await job.promise;return referenceLeft||networkIsSlow()?'':permit;}
  page?.onLeave(()=>{referenceLeft=true;referenceJobs.clear();for(const control of referenceControls)control.abort();});
  window.RepoCatalogReference={stage:stageReference,take:takeReference};
  window.RepoProductInfo = { get, show, summary, renderSummary, displayName, referenceName, showSummary, clear, editButton, save, permission: ean => access.get(ean) };
})();
