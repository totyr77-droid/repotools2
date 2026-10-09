(() => {
  'use strict';
  let guestToken;
  function ownerToken() {
    if (guestToken) return guestToken;
    try { guestToken = localStorage.getItem('repotools-report-owner'); } catch {}
    if (!/^[a-f0-9]{64}$/.test(guestToken || '')) {
      guestToken = Array.from(crypto.getRandomValues(new Uint8Array(32)), value => value.toString(16).padStart(2, '0')).join('');
      try { localStorage.setItem('repotools-report-owner', guestToken); } catch {}
    }
    return guestToken;
  }
  async function request(query = '', body) {
    const response = await fetch('api/reportes.php' + query, { method: body ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'X-Report-Owner': ownerToken() }, body });
    const result = await response.json();
    if (!response.ok) { const error = new Error(result.error || 'No pudimos acceder a los reportes.'); error.status = response.status; throw error; }
    return result;
  }
  async function photo(id, kind) {
    const response = await fetch(`api/reportes.php?id=${id}&photo=${kind}`, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Report-Owner': ownerToken() } });
    if (!response.ok) throw new Error('No pudimos cargar la foto.');
    return URL.createObjectURL(await response.blob());
  }
  async function compress(file) {
    if (file.size > 20 * 1024 * 1024) throw new Error('La foto es demasiado grande. Usa otra imagen.');
    const url = URL.createObjectURL(file);
    try {
      const image = new Image(); image.src = url; await image.decode();
      const ratio = Math.min(1, 1280 / Math.max(image.naturalWidth, image.naturalHeight));
      const canvas = document.createElement('canvas'); canvas.width = Math.max(1, Math.round(image.naturalWidth * ratio)); canvas.height = Math.max(1, Math.round(image.naturalHeight * ratio));
      const context = canvas.getContext('2d'); context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height); context.drawImage(image, 0, 0, canvas.width, canvas.height);
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.8));
      if (!blob || blob.size > 1024 * 1024) throw new Error('Usa una foto más pequeña.');
      return blob;
    } catch (error) { throw new Error(error.message === 'Usa una foto más pequeña.' ? error.message : 'No pudimos leer la foto. Prueba con JPG o PNG.'); }
    finally { URL.revokeObjectURL(url); }
  }
  window.RepoReports = { request, photo, ownerToken };
  const dialog = document.getElementById('productReportDialog'); if (!dialog) return;
  const form = document.getElementById('productReportForm'); const status = document.getElementById('productReportStatus');
  const send = document.getElementById('sendProductReport'); const close = document.getElementById('closeProductReport');
  let revision = 0, active, busy = false;
  async function open(ean) {
    if (busy) return;
    const version = ++revision; active = null; form.reset(); form.hidden = true; status.textContent = 'Cargando…';
    if (!dialog.open) dialog.showModal();
    try {
      const [config, product] = await Promise.all([request(), window.RepoProductInfo.get(ean).catch(() => null)]);
      if (version !== revision || !dialog.open) return;
      active = { ean, csrf: config.csrf, key: crypto.randomUUID() };
      document.getElementById('productReportEan').textContent = `EAN ${ean}`;
      for (const field of ['name', 'quantity', 'brand', 'category', 'packaging']) form.elements[field].value = product?.[field] || '';
      document.getElementById('reportPhotos').disabled = !config.photos_enabled;
      document.getElementById('reportPhotosNote').textContent = config.photos_enabled ? '' : 'Las fotos todavía no están habilitadas. Puedes reportar igual.';
      status.textContent = ''; form.hidden = false;
    } catch (error) { if (version === revision) status.textContent = error.message; }
  }
  close.addEventListener('click', () => { if (!busy) dialog.close(); });
  dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
  dialog.addEventListener('close', () => { ++revision; active = null; });
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (!active || busy) return;
    busy = true; send.disabled = true; close.disabled = true; status.textContent = 'Enviando…';
    try {
      const body = new FormData(form); body.set('csrf', active.csrf); body.set('ean', active.ean); body.set('submission_key', active.key); body.set('owner_token', ownerToken());
      for (const kind of ['ean', 'product']) {
        const file = form.elements['photo_' + kind].files[0]; body.delete('photo_' + kind);
        if (file && !document.getElementById('reportPhotos').disabled) body.set('photo_' + kind, await compress(file), kind + '.jpg');
      }
      const result = await request('', body); form.hidden = true;
      status.replaceChildren(document.createTextNode(`Reporte #${result.report.id} enviado. `));
      const link = document.createElement('a'); link.href = 'reportes.php'; link.textContent = 'Ver mis reportes'; status.append(link);
    } catch (error) { status.textContent = error.message; }
    finally { busy = false; send.disabled = false; close.disabled = false; }
  });
  window.RepoProductReports = { open };
})();
