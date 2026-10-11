(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  const fetch = page?.fetch || globalThis.fetch?.bind(globalThis);

  const config = window.repotoolsLibrary;
  const get = id => document.getElementById(id);
  let libraries = [];
  let activeId = null;
  let editingCollection = false;
  let productLibraryId = null;
  let viewerId = null;
  let viewerIndex = 0;
  let scannerRevision = 0;
  let busy = false;
  let scannedLibraryId = null;
  let enteringEan = false;
  const icon = paths => {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor'); svg.setAttribute('stroke-width', '1.8');
    svg.setAttribute('stroke-linecap', 'round'); svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    svg.innerHTML = paths; return svg;
  };
  const barcodeIcon = () => icon('<path d="M3 5v14M6 5v14M10 5v14M13 5v14M18 5v14M21 5v14"/>');

  function message(text) {
    get('libraryStatus').textContent = text;
    const dialog = document.querySelector('.library-dialog[open]');
    if (dialog) {
      let status = dialog.querySelector('.library-form-status');
      if (!status) {
        status = document.createElement('p');
        status.className = 'library-form-status';
        status.setAttribute('role', 'status');
        dialog.append(status);
      }
      status.textContent = text;
    }
  }
  function openDialog(id) {
    get(id).querySelector('.library-form-status')?.remove();
    get(id).showModal();
  }
  const activeLibrary = () => libraries.find(l => Number(l.id) === activeId);
  const button = (text, className, handler) => {
    const element = document.createElement('button');
    element.type = 'button'; element.textContent = text; element.className = className;
    element.addEventListener('click', handler);
    return element;
  };
  async function request(data) {
    const response = await fetch('api/biblioteca.php', {
      credentials: 'same-origin',
      ...(data ? { method: 'POST', body: new URLSearchParams({ csrf: config.csrf, ...data }) } : {})
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'No pudimos cargar tu biblioteca.');
    return result.libraries;
  }
  async function mutate(data) {
    if (busy) throw new Error('Espera a que termine el cambio anterior.');
    busy = true;
    document.querySelectorAll('.library-app button, .library-dialog button[type="submit"]').forEach(b => b.disabled = true);
    try {
      libraries = await request(data);
      if (!libraries.some(l => Number(l.id) === activeId)) activeId = libraries.length ? Number(libraries[0].id) : null;
      render();
    } finally {
      busy = false;
      document.querySelectorAll('.library-app button, .library-dialog button[type="submit"]').forEach(b => b.disabled = false);
      get('shareLibrary').hidden = !activeLibrary() || activeLibrary().products.length < 1; get('shareLibrary').disabled = busy || get('shareLibrary').hidden;
    }
  }
  function render() {
    get('libraryEmpty').hidden = libraries.length > 0;
    get('collectionList').hidden = libraries.length === 0;
    get('collectionSettings').open = false;
    get('collectionList').replaceChildren();
    libraries.forEach(library => {
      const tab = button(`${library.name} (${library.products.length})`, 'library-secondary', () => {
        activeId = Number(library.id); render();
      });
      tab.setAttribute('aria-pressed', String(Number(library.id) === activeId));
      get('collectionList').append(tab);
    });
    const library = activeLibrary();
    get('collectionPanel').hidden = !library;
    get('libraryProducts').replaceChildren();
    if (!library) return;
    get('collectionTitle').textContent = library.name;
    get('collectionTitle').title = library.name;
    get('shareLibrary').hidden = library.products.length < 1; get('shareLibrary').disabled = busy || get('shareLibrary').hidden;
    get('shareLibrary').title = 'Compartir colección';
    if (!library.products.length) {
      const empty = document.createElement('p'); empty.textContent = 'Escanea tu primer producto.';
      get('libraryProducts').append(empty);
    }
    library.products.forEach((product, index) => {
      const card = document.createElement('article'); card.className = 'library-product';
      const title = document.createElement('h3'); title.textContent = product.name || 'Producto sin nombre';
      const code = document.createElement('p'); code.className = 'library-product-code';
      code.append(barcodeIcon(), document.createTextNode(product.ean));
      const description = document.createElement('div'); description.className = 'library-product-description'; description.append(title, code);
      const actions = document.createElement('div'); actions.className = 'library-product-actions';
      const view = button('', 'library-primary library-view-code', () => {
        viewerId = Number(library.id); viewerIndex = index;
        renderViewer(); openDialog('libraryEanDialog');
      });
      view.setAttribute('aria-label', 'Ver EAN'); view.title = 'Ver EAN';
      view.append(icon('<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>'), barcodeIcon());
      const remove = button('', 'library-danger library-remove-product', async () => {
        if (!await window.RepoConfirm({ title: 'Quitar producto', message: `¿Quitar «${product.name || product.ean}» de esta colección?`, confirmLabel: 'Quitar' })) return;
        try { await mutate({ action: 'delete_product', library_id: library.id, ean: product.ean }); message('Producto eliminado.'); }
        catch (error) { message(error.message); }
      });
      remove.setAttribute('aria-label', 'Quitar producto'); remove.title = 'Quitar producto';
      remove.append(icon('<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>'));
      actions.append(view, remove);
      card.append(description, actions); get('libraryProducts').append(card);
    });
  }
  function renderViewer() {
    if (get('libraryInfoDialog').open) get('libraryInfoDialog').close();
    window.RepoProductInfo.clear(get('libraryProductDetails'));
    const library = libraries.find(l => Number(l.id) === viewerId);
    if (!library?.products.length) return;
    viewerIndex = Math.max(0, Math.min(viewerIndex, library.products.length - 1));
    const product = library.products[viewerIndex];
    const selector = get('eanCollection'); selector.replaceChildren();
    libraries.filter(l => l.products.length).forEach(l => {
      const option = document.createElement('option'); option.value = l.id; option.textContent = l.name;
      option.selected = Number(l.id) === viewerId; selector.append(option);
    });
    get('eanProductName').textContent = product.name || 'Producto sin nombre';
    get('eanValue').textContent = product.ean;
    get('eanPosition').textContent = `${viewerIndex + 1} de ${library.products.length}`;
    get('librarySwipeHint').hidden = library.products.length < 2;
    get('previousLibraryEan').disabled = viewerIndex === 0;
    get('nextLibraryEan').disabled = viewerIndex === library.products.length - 1;
    get('libraryBarcode').replaceChildren();
    try {
      if (!window.JsBarcode) throw new Error('No se pudo cargar el código de barras.');
      JsBarcode(get('libraryBarcode'), product.ean, { format: product.ean.length === 8 ? 'EAN8' : 'EAN13', width: 3, height: 110, displayValue: false, margin: 16 });
    } catch {
      get('eanPosition').textContent += ' · No se pudo dibujar este EAN.';
    }
  }
  function productDialog(ean = '', libraryId = activeId) {
    enteringEan = !ean;
    productLibraryId = libraryId;
    get('libraryProductForm').reset(); get('libraryProductEan').value = ean;
    get('libraryProductEan').readOnly = Boolean(ean);
    get('libraryManualEan').hidden = Boolean(ean);
    get('libraryScannedEan').hidden = !ean;
    get('libraryScannedEan').textContent = ean ? `EAN ${ean}` : '';
    get('libraryProductReference').hidden = enteringEan;
    get('libraryProductSubmit').textContent = enteringEan ? 'Siguiente' : 'Guardar';
    get('libraryProductFormTitle').textContent = enteringEan ? 'Escribir código EAN' : 'Guardar producto';
    openDialog('libraryProductDialog');
    (ean ? get('libraryProductName') : get('libraryProductEan')).focus();
    window.RepoProductInfo.clear(get('libraryScanProductInfo'));
    if (ean) loadScannedProduct(ean);
  }
  async function loadScannedProduct(ean) {
    const product = await window.RepoProductInfo.showSummary(get('libraryScanProductInfo'), ean, { compact: true });
    if (product && get('libraryProductDialog').open && get('libraryProductEan').value === ean && !get('libraryProductName').value) get('libraryProductName').value = Array.from(window.RepoProductInfo.referenceName(product)).slice(0, 120).join('');
  }
  (page ? page.listen : window.addEventListener.bind(window))('repotools:product-updated', event => {
    const { ean } = event.detail;
    if (get('libraryProductDialog').open && get('libraryProductEan').value === ean) loadScannedProduct(ean);
    if (get('libraryInfoDialog').open && get('libraryInfoEan').dataset.ean === ean) window.RepoProductInfo.show(get('libraryProductDetails'), ean, { fresh: true });
  });
  get('newCollection').addEventListener('click', () => {
    editingCollection = false; get('collectionFormTitle').textContent = 'Nueva colección';
    get('collectionName').value = ''; openDialog('collectionDialog');
  });
  get('renameCollection').addEventListener('click', () => {
    get('collectionSettings').open = false;
    editingCollection = true; get('collectionFormTitle').textContent = 'Renombrar colección';
    get('collectionName').value = activeLibrary().name; openDialog('collectionDialog');
  });
  get('collectionForm').addEventListener('submit', async event => {
    event.preventDefault(); const name = get('collectionName').value.trim();
    if (!name) return message('Escribe un nombre.');
    try {
      await mutate({ action: editingCollection ? 'rename' : 'create', name, library_id: activeId || '' });
      if (!editingCollection) { activeId = Number(libraries.find(l => l.name === name).id); render(); }
      get('collectionDialog').close(); message('');
    } catch (error) { message(error.message); }
  });
  get('deleteCollection').addEventListener('click', async () => {
    get('collectionSettings').open = false;
    if (!await window.RepoConfirm({ title: 'Eliminar colección', message: `Se eliminará «${activeLibrary().name}» y todos sus productos. Esta acción no se puede deshacer.` })) return;
    try { await mutate({ action: 'delete', library_id: activeId }); message('Colección eliminada.'); }
    catch (error) { message(error.message); }
  });
  get('manualLibraryProduct').addEventListener('click', () => productDialog());
  document.addEventListener('click', event => {
    if (!get('collectionSettings').contains(event.target)) get('collectionSettings').open = false;
  });
  get('collectionSettings').addEventListener('keydown', event => {
    if (event.key === 'Escape') { get('collectionSettings').open = false; get('collectionSettings').querySelector('summary').focus(); }
  });
  get('collectionSettings').addEventListener('toggle', () => {
    if (get('collectionSettings').open) get('collectionSettings').querySelector('.library-settings-menu').scrollIntoView({ block: 'nearest' });
  });
  get('libraryProductForm').addEventListener('submit', async event => {
    event.preventDefault();
    const ean = get('libraryProductEan').value.replace(/[\s\u200B-\u200D\uFEFF-]/g, '');
    get('libraryProductEan').value = ean;
    if (!/^(?:\d{8}|\d{13})$/.test(ean)) return message('Escribe un código EAN de 8 o 13 números.');
    if (!window.RepoEANScanner.validEAN(ean)) return message('Revisa el código EAN: el dígito de control no coincide.');
    if (enteringEan) {
      productDialog(ean, productLibraryId);
      return;
    }
    try {
      await mutate({ action: 'add_product', library_id: productLibraryId, ean: get('libraryProductEan').value.trim(), name: get('libraryProductName').value.trim() });
      get('libraryProductDialog').close(); message('Producto guardado.');
    } catch (error) { message(error.message); }
  });
  get('eanCollection').addEventListener('change', () => {
    viewerId = Number(get('eanCollection').value); viewerIndex = 0;
    activeId = viewerId; render(); renderViewer();
  });
  function moveViewer(direction) {
    const library = libraries.find(l => Number(l.id) === viewerId);
    if (!library || viewerIndex + direction < 0 || viewerIndex + direction >= library.products.length) return;
    viewerIndex += direction; renderViewer();
  }
  get('previousLibraryEan').addEventListener('click', () => moveViewer(-1));
  get('nextLibraryEan').addEventListener('click', () => moveViewer(1));
  get('openLibraryProductInfo').addEventListener('click', () => {
    const ean = get('eanValue').textContent;
    get('libraryInfoEan').textContent = `EAN ${ean}`; get('libraryInfoEan').dataset.ean = ean;
    openDialog('libraryInfoDialog'); window.RepoProductInfo.show(get('libraryProductDetails'), ean, { fresh: true });
  });
  get('libraryInfoDialog').addEventListener('close', () => window.RepoProductInfo.clear(get('libraryProductDetails')));
  get('reportLibraryProduct').addEventListener('click', () => {
    const ean = get('eanValue').textContent;
    if (window.RepoEANScanner.validEAN(ean)) window.RepoProductReports.open(ean);
  });
  let swipeStart = null;
  const swipeArea = get('libraryEanSwipeArea');
  swipeArea.addEventListener('touchstart', event => {
    swipeStart = event.touches.length === 1 ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null;
  }, { passive: true });
  swipeArea.addEventListener('touchend', event => {
    const start = swipeStart; swipeStart = null;
    if (!start || event.changedTouches.length !== 1 || !get('libraryEanDialog').open || get('libraryInfoDialog').open) return;
    const dx = event.changedTouches[0].clientX - start.x, dy = event.changedTouches[0].clientY - start.y;
    if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) moveViewer(dx < 0 ? 1 : -1);
  }, { passive: true });
  swipeArea.addEventListener('touchcancel', () => { swipeStart = null; }, { passive: true });
  get('libraryEanDialog').addEventListener('close', () => { swipeStart = null; });
  (page ? page.listen : window.addEventListener.bind(window))('keydown', event => {
    if (!get('libraryEanDialog').open || get('libraryInfoDialog').open || /INPUT|TEXTAREA|SELECT/.test(event.target.tagName)) return;
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault(); moveViewer(event.key === 'ArrowLeft' ? -1 : 1);
    }
  });
  document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => get(b.dataset.close).close()));

  const scanner = window.RepoEANScanner.create('libraryReader', {
    onManual:()=>{const id=scannedLibraryId;closeScanner();productDialog('',id);},
    onStatus: text => { get('libraryScannerStatus').textContent = text; },
    onError: () => { get('libraryScannerStatus').textContent = 'No pudimos leer el código. Intenta nuevamente.'; },
    onCode: async ean => {
      const libraryId = scannedLibraryId;
      const revision = scannerRevision;
      get('libraryScannerDialog').close();
      productDialog(ean, libraryId);
      // Cada lectura confirmada cuenta una vez; escribir el código no suma.
      try {
        const response = await fetch('api/escaneos.php', { method: 'POST', body: new URLSearchParams({ csrf: config.csrf, code: ean, event: crypto.randomUUID() }) });
        if (!response.ok && revision === scannerRevision) message('El código se leyó, pero no sumó en TOPs.');
      } catch { if (revision === scannerRevision) message('El código se leyó, pero no sumó en TOPs.'); }
    }
  });
  function closeScanner() {
    scannerRevision++;
    get('libraryScannerDialog').close();
    scanner.stop().catch(() => {});
  }
  get('closeLibraryScanner').addEventListener('click', closeScanner);
  get('libraryScannerDialog').addEventListener('cancel', event => { event.preventDefault(); closeScanner(); });
  get('scanLibraryProduct').addEventListener('click', async () => {
    const revision = ++scannerRevision;
    scannedLibraryId = activeId;
    openDialog('libraryScannerDialog');
    try { await scanner.start(); }
    catch {
      if (revision !== scannerRevision) return;
      closeScanner(); message('No pudimos abrir la cámara. Revisa el permiso o escribe el código.');
    }
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && get('libraryScannerDialog').open) closeScanner();
  });
  (page ? page.listen : window.addEventListener.bind(window))('pagehide', closeScanner);
  async function shareRequest(data) {
    const response = await fetch('api/biblioteca.php', { method: 'POST', body: new URLSearchParams({ csrf: config.csrf, ...data }) });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'No pudimos compartir la colección.');
    return result;
  }
  get('shareLibrary').addEventListener('click', async () => {
    const library = activeLibrary();
    if (!library || library.products.length < 1 || busy) return;
    const control = get('shareLibrary'); control.disabled = true;
    try {
      const result = await shareRequest({ action: 'share', library_id: library.id });
      const url = new URL('biblioteca.php', location.href); url.searchParams.set('share', result.token);
      const showLink = () => { get('libraryShareUrl').value = url.href; openDialog('libraryShareDialog'); };
      if (navigator.share) {
        try { await navigator.share({ title: library.name, text: `${library.name}: ${library.products.length} productos`, url: url.href }); }
        catch (error) { if (error.name !== 'AbortError') showLink(); }
      } else showLink();
    } catch (error) { message(error.message); }
    finally { control.hidden = !activeLibrary() || activeLibrary().products.length < 1; control.disabled = control.hidden; }
  });
  get('copyLibraryShare').addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(get('libraryShareUrl').value); get('libraryShareDialog').close(); message('Enlace copiado.'); }
    catch { get('libraryShareUrl').select(); message('Copia este enlace para compartir.'); }
  });
  async function previewImport() {
    if (!config.share) return;
    openDialog('libraryImportDialog'); get('confirmLibraryImport').disabled = true;
    get('libraryImportStatus').textContent = 'Cargando colección…';
    try {
      const response = await fetch(`api/biblioteca.php?share=${encodeURIComponent(config.share)}`);
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'No pudimos cargar la colección.');
      get('libraryImportQuestion').textContent = `¿Agregar «${result.collection.name}» a tu biblioteca con estos ${result.collection.products.length} productos?`;
      get('libraryImportProducts').replaceChildren();
      result.collection.products.forEach(product => {
        const item = document.createElement('li'); item.textContent = product.name || 'Producto sin nombre';
        const code = document.createElement('small'); code.textContent = `EAN ${product.ean}`; item.append(code); get('libraryImportProducts').append(item);
      });
      get('libraryImportStatus').textContent = result.alreadyAdded ? 'Esta colección ya está en tu biblioteca.' : '';
      get('confirmLibraryImport').disabled = result.alreadyAdded;
    } catch (error) { get('libraryImportStatus').textContent = error.message; }
  }
  get('confirmLibraryImport').addEventListener('click', async () => {
    const control = get('confirmLibraryImport'); control.disabled = true;
    try {
      const result = await shareRequest({ action: 'import', token: config.share });
      libraries = result.libraries; activeId = Number(libraries.at(-1).id); render();
      get('libraryImportDialog').close(); message('Colección agregada.');
      const url = new URL(location.href); url.searchParams.delete('share'); history.replaceState(null, '', url);
    } catch (error) { get('libraryImportStatus').textContent = error.message; control.disabled = false; }
  });
  get('newCollection').disabled = true;
  message('Cargando colecciones…');
  request().then(result => {
    libraries = result; activeId = libraries.length ? Number(libraries[0].id) : null;
    render(); message(''); previewImport();
  }).catch(error => message(error.message)).finally(() => get('newCollection').disabled = false);
})();
