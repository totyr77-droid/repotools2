(() => {
  'use strict';
  const config = window.repotoolsLibrary;
  const get = id => document.getElementById(id);
  let libraries = [];
  let activeId = null;
  let editingCollection = false;
  let productLibraryId = null;
  let viewerId = null;
  let viewerIndex = 0;
  let reader = null;
  let startPromise = null;
  let processing = false;
  let busy = false;
  let scannedLibraryId = null;

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
    }
  }
  function render() {
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
    if (!library.products.length) {
      const empty = document.createElement('p'); empty.textContent = 'Escanea tu primer producto.';
      get('libraryProducts').append(empty);
    }
    library.products.forEach((product, index) => {
      const card = document.createElement('article'); card.className = 'library-product';
      const title = document.createElement('h3'); title.textContent = product.name || 'Producto sin nombre';
      const code = document.createElement('p'); code.textContent = product.ean;
      const actions = document.createElement('div'); actions.className = 'library-product-actions';
      actions.append(button('Ver EAN', 'library-primary', () => {
        viewerId = Number(library.id); viewerIndex = index;
        renderViewer(); openDialog('libraryEanDialog');
      }), button('Quitar', 'library-danger', async () => {
        if (!confirm('¿Quitar este producto de la colección?')) return;
        try { await mutate({ action: 'delete_product', library_id: library.id, ean: product.ean }); message('Producto eliminado.'); }
        catch (error) { message(error.message); }
      }));
      card.append(title, code, actions); get('libraryProducts').append(card);
    });
  }
  function renderViewer() {
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
    productLibraryId = libraryId;
    get('libraryProductForm').reset(); get('libraryProductEan').value = ean;
    openDialog('libraryProductDialog');
    (ean ? get('libraryProductName') : get('libraryProductEan')).focus();
  }
  get('newCollection').addEventListener('click', () => {
    editingCollection = false; get('collectionFormTitle').textContent = 'Nueva colección';
    get('collectionName').value = ''; openDialog('collectionDialog');
  });
  get('renameCollection').addEventListener('click', () => {
    editingCollection = true; get('collectionFormTitle').textContent = 'Renombrar colección';
    get('collectionName').value = activeLibrary().name; openDialog('collectionDialog');
  });
  get('collectionForm').addEventListener('submit', async event => {
    event.preventDefault(); const name = get('collectionName').value.trim();
    if (!name) return message('Escribe un nombre.');
    try {
      await mutate({ action: editingCollection ? 'rename' : 'create', name, library_id: activeId || '' });
      if (!editingCollection) { activeId = Number(libraries.find(l => l.name === name).id); render(); }
      get('collectionDialog').close(); message('Colección guardada.');
    } catch (error) { message(error.message); }
  });
  get('deleteCollection').addEventListener('click', async () => {
    if (!confirm('¿Eliminar esta colección y todos sus productos?')) return;
    try { await mutate({ action: 'delete', library_id: activeId }); message('Colección eliminada.'); }
    catch (error) { message(error.message); }
  });
  get('manualLibraryProduct').addEventListener('click', () => productDialog());
  get('libraryProductForm').addEventListener('submit', async event => {
    event.preventDefault();
    try {
      await mutate({ action: 'add_product', library_id: productLibraryId, ean: get('libraryProductEan').value.trim(), name: get('libraryProductName').value.trim() });
      get('libraryProductDialog').close(); message('Producto guardado.');
    } catch (error) { message(error.message); }
  });
  get('eanCollection').addEventListener('change', () => {
    viewerId = Number(get('eanCollection').value); viewerIndex = 0;
    activeId = viewerId; render(); renderViewer();
  });
  get('previousLibraryEan').addEventListener('click', () => { viewerIndex--; renderViewer(); });
  get('nextLibraryEan').addEventListener('click', () => { viewerIndex++; renderViewer(); });
  document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => get(b.dataset.close).close()));

  async function closeScanner() {
    const current = reader;
    if (current) {
      try { await startPromise; if (current.isScanning) await current.stop(); await current.clear(); } catch {}
      if (reader === current) reader = null;
    }
    get('libraryScannerDialog').close();
  }
  get('closeLibraryScanner').addEventListener('click', closeScanner);
  get('libraryScannerDialog').addEventListener('cancel', event => { event.preventDefault(); closeScanner(); });
  get('scanLibraryProduct').addEventListener('click', async () => {
    if (!window.Html5Qrcode) return message('No se pudo cargar la cámara. Puedes escribir el código.');
    processing = false; get('libraryScannerStatus').textContent = 'Apunta al código de barras.';
    scannedLibraryId = activeId;
    openDialog('libraryScannerDialog');
    reader = new Html5Qrcode('libraryReader', { formatsToSupport: [Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8] });
    try {
      startPromise = reader.start({ facingMode: 'environment' }, {
        fps: 10, qrbox: (width, height) => ({ width: Math.min(280, Math.floor(width * .85)), height: Math.min(140, Math.floor(height * .6)) })
      }, async ean => {
        if (processing || !/^(?:\d{8}|\d{13})$/.test(ean)) return;
        processing = true; await closeScanner();
        // Solo una lectura de cámara cuenta para TOPs; escribir o importar no suma.
        try {
          const response = await fetch('api/escaneos.php', { method: 'POST', body: new URLSearchParams({ csrf: config.csrf, code: ean, event: crypto.randomUUID() }) });
          if (!response.ok) message('El código se leyó, pero no sumó en TOPs.');
        } catch { message('El código se leyó, pero no sumó en TOPs.'); }
        productDialog(ean, scannedLibraryId);
      }, () => {});
      await startPromise;
    } catch {
      await closeScanner(); message('No pudimos abrir la cámara. Revisa el permiso o escribe el código.');
    }
  });
  window.addEventListener('pagehide', () => { if (reader) closeScanner(); });
  get('newCollection').disabled = true;
  message('Cargando colecciones…');
  request().then(result => {
    libraries = result; activeId = libraries.length ? Number(libraries[0].id) : null;
    render(); message(libraries.length ? '' : 'Crea tu primera colección.');
  }).catch(error => message(error.message)).finally(() => get('newCollection').disabled = false);
})();
