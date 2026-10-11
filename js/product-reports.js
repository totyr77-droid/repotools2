(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  const fetch = page?.fetch || globalThis.fetch?.bind(globalThis);

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
  const camera = document.getElementById('reportCameraDialog'), video = document.getElementById('reportCameraVideo');
  const cameraStatus = document.getElementById('reportCameraStatus'), capture = document.getElementById('captureReportPhoto'), retryCamera = document.getElementById('retryReportCamera');
  const flash=document.getElementById('reportCameraFlash');let lightOn=false;
  const capturedPhotos = new Map(), previews = new Map();
  let cameraVersion = 0, cameraKind, stream;
  let revision = 0, active, busy = false;
  function stopCamera() {
    ++cameraVersion; capture.disabled = true;lightOn=false;flash.hidden=true;flash.disabled=false;flash.setAttribute('aria-pressed','false');
    stream?.getTracks().forEach(track => track.stop()); stream = null; video.srcObject = null;
  }
  function removePhoto(kind) {
    if (previews.has(kind)) URL.revokeObjectURL(previews.get(kind)); previews.delete(kind); capturedPhotos.delete(kind);
    const preview = document.getElementById(kind === 'ean' ? 'reportPreviewEan' : 'reportPreviewProduct'); preview.removeAttribute('src'); preview.hidden = true;
    form.querySelector(`[data-report-remove="${kind}"]`).hidden = true;
    form.querySelector(`[data-report-camera="${kind}"]`).textContent = kind === 'ean' ? 'Tomar foto del EAN' : 'Tomar foto del producto';
  }
  async function openCamera(kind) {
    if (busy || !active || document.getElementById('reportPhotos').disabled || !['ean', 'product'].includes(kind)) return;
    stopCamera(); cameraKind = kind; const attempt = cameraVersion, reportVersion = revision;
    document.getElementById('reportCameraTitle').textContent = kind === 'ean' ? 'Foto del EAN' : 'Foto del producto';
    camera.dataset.photoKind = kind;
    document.getElementById('reportEanGuide').hidden = kind !== 'ean';
    cameraStatus.textContent = 'Abriendo cámara…'; retryCamera.hidden = true; if (!camera.open) camera.showModal();
    try {
      if (!navigator.mediaDevices?.getUserMedia) throw new Error('unsupported');
      const next = await navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: { ideal: 'environment' }, zoom: { ideal: 1 }, width: { ideal: 1280 }, height: { ideal: 960 } } });
      if (attempt !== cameraVersion || reportVersion !== revision || !camera.open || !dialog.open) { next.getTracks().forEach(track => track.stop()); return; }
      stream = next;
      const track = next.getVideoTracks()[0], capabilities = track?.getCapabilities?.();
      if (capabilities?.zoom) {
        const zoom = Math.min(capabilities.zoom.max, Math.max(1, capabilities.zoom.min));
        try { await track.applyConstraints({ advanced: [{ zoom }] }); } catch {}
      }
      if (attempt !== cameraVersion || !camera.open) return;
      video.srcObject = next; await video.play();
      if (attempt !== cameraVersion || !camera.open) return;
      flash.hidden=stream.getVideoTracks()[0]?.getCapabilities?.().torch!==true;flash.setAttribute('aria-label','Encender luz');flash.title='Encender luz';
      capture.disabled = !video.videoWidth || !video.videoHeight;
      cameraStatus.textContent = kind === 'ean' ? 'Alinea el código horizontal dentro del marco, incluidos los números.' : 'Enfoca el frente del producto.';
    } catch (error) {
      if (attempt !== cameraVersion || !camera.open) return;
      stopCamera(); retryCamera.hidden = false;
      cameraStatus.textContent = error.name === 'NotAllowedError' ? 'Permite usar la cámara en tu navegador y vuelve a intentar.' : error.name === 'NotReadableError' ? 'La cámara está ocupada. Cierra otras aplicaciones que la usen y vuelve a intentar.' : error.message === 'unsupported' ? 'Abre la web con HTTPS en Safari o Chrome para usar la cámara.' : 'No pudimos abrir la cámara. Revisa el permiso y vuelve a intentar.';
    }
  }
  form.querySelectorAll('[data-report-camera]').forEach(button => button.addEventListener('click', () => openCamera(button.dataset.reportCamera)));
  form.querySelectorAll('[data-report-remove]').forEach(button => button.addEventListener('click', () => { if (!busy) removePhoto(button.dataset.reportRemove); }));
  document.getElementById('closeReportCamera').addEventListener('click', () => { stopCamera(); camera.close(); });
  camera.addEventListener('cancel', stopCamera);
  camera.addEventListener('close', () => { if (!camera.open) stopCamera(); });
  flash.addEventListener('click',async()=>{const track=stream?.getVideoTracks()[0],attempt=cameraVersion;if(!track||flash.disabled)return;flash.disabled=true;try{const enabled=!lightOn,constraints=track.getConstraints?.()||{};await track.applyConstraints({...constraints,advanced:[{...Object.assign({},...(constraints.advanced||[])),torch:enabled}]});if(attempt!==cameraVersion||!camera.open)return;lightOn=track.getSettings?.().torch??enabled;flash.setAttribute('aria-pressed',String(lightOn));const label=lightOn?'Apagar luz':'Encender luz';flash.setAttribute('aria-label',label);flash.title=label;}catch{if(attempt===cameraVersion)cameraStatus.textContent='No pudimos cambiar la luz. Puedes tomar la foto igualmente.';}finally{if(attempt===cameraVersion)flash.disabled=false;}});
  retryCamera.addEventListener('click', () => openCamera(cameraKind));
  function cameraReady() {
    if (stream && camera.open && video.videoWidth && video.videoHeight) capture.disabled = false;
  }
  video.addEventListener('loadeddata', cameraReady);
  video.addEventListener('resize', cameraReady);
  capture.addEventListener('click', async () => {
    if (!stream || capture.disabled || !video.videoWidth || !video.videoHeight) return;
    const attempt = cameraVersion, reportVersion = revision, kind = cameraKind; capture.disabled = true;
    try {
      const frame = video.getBoundingClientRect(), ratio = frame.width / frame.height;
      let sourceWidth = Math.min(video.videoWidth, video.videoHeight * ratio), sourceHeight = sourceWidth / ratio;
      let sourceX = (video.videoWidth - sourceWidth) / 2, sourceY = (video.videoHeight - sourceHeight) / 2;
      if (kind === 'ean') {
        const guide = document.getElementById('reportEanGuide').getBoundingClientRect();
        sourceX += (guide.left - frame.left) / frame.width * sourceWidth;
        sourceY += (guide.top - frame.top) / frame.height * sourceHeight;
        sourceWidth *= guide.width / frame.width; sourceHeight *= guide.height / frame.height;
      }
      const scale = Math.min(1, 1280 / Math.max(sourceWidth, sourceHeight)); const canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(sourceWidth * scale)); canvas.height = Math.max(1, Math.round(sourceHeight * scale));
      canvas.getContext('2d').drawImage(video, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, canvas.width, canvas.height);
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.8));
      if (attempt !== cameraVersion || reportVersion !== revision || !camera.open || !dialog.open) return;
      if (!blob || blob.size > 1024 * 1024) throw new Error('No pudimos guardar la foto. Intenta otra vez.');
      removePhoto(kind); capturedPhotos.set(kind, blob); const url = URL.createObjectURL(blob); previews.set(kind, url);
      const preview = document.getElementById(kind === 'ean' ? 'reportPreviewEan' : 'reportPreviewProduct'); preview.src = url; preview.hidden = false;
      form.querySelector(`[data-report-remove="${kind}"]`).hidden = false; form.querySelector(`[data-report-camera="${kind}"]`).textContent = 'Volver a tomar';
      stopCamera(); camera.close();
    } catch (error) { if (attempt === cameraVersion && camera.open) { cameraStatus.textContent = error.message; capture.disabled = false; } }
  });
  document.addEventListener('visibilitychange', () => { if (document.hidden) { stopCamera(); if (camera.open) camera.close(); } });
  (page ? page.listen : window.addEventListener.bind(window))('pagehide', stopCamera);
  page?.onLeave(stopCamera);
  page?.onLeave(() => { revision++; previews.forEach(url => URL.revokeObjectURL(url)); previews.clear(); capturedPhotos.clear(); });
  async function open(ean) {
    if (busy) return;
    const photoPending = window.RepoEANScanner?.takeScanPhoto?.(ean);
    stopCamera(); if (camera.open) camera.close(); ['ean', 'product'].forEach(removePhoto);
    const version = ++revision; active = null; form.reset(); form.hidden = true; status.textContent = 'Cargando…';
    if (!dialog.open) dialog.showModal();
    try {
      const [config, lookup] = await Promise.all([request(), window.RepoProductInfo.get(ean).then(product => ({ product, missing: product === null })).catch(() => ({ product: null, missing: false }))]);
      const { product, missing } = lookup;
      const scanPhoto = await photoPending;
      if (version !== revision || !dialog.open) return;
      if (config.can_submit === false) { status.textContent = 'Tu cuenta tiene bloqueado el envío de reportes. Puedes seguir usando las demás funciones.'; return; }
      active = { ean, csrf: config.csrf, key: crypto.randomUUID() };
      document.getElementById('productReportEan').textContent = `EAN ${ean}`;
      document.getElementById('productReportTitle').textContent = missing ? 'Agregar producto' : 'Reportar producto';
      document.getElementById('reportProblem').hidden = missing;
      form.elements.description.disabled = missing; form.elements.description.required = false;
      form.elements.name.required = missing;
      document.getElementById('reportInstructions').textContent = missing ? 'Completa los datos del producto. Un moderador los revisará.' : 'Propón los datos correctos. Un moderador los revisará.';
      for (const field of ['name', 'quantity', 'brand', 'category', 'packaging']) form.elements[field].value = product?.[field] || '';
      document.getElementById('reportPhotos').disabled = !config.photos_enabled;
      if (config.photos_enabled && scanPhoto) {
        capturedPhotos.set('ean', scanPhoto); const url = URL.createObjectURL(scanPhoto); previews.set('ean', url);
        const preview = document.getElementById('reportPreviewEan'); preview.src = url; preview.hidden = false;
        form.querySelector('[data-report-remove="ean"]').hidden = false;
        form.querySelector('[data-report-camera="ean"]').textContent = 'Volver a tomar foto del EAN';
      }
      document.getElementById('reportPhotosNote').textContent = config.photos_enabled ? '' : 'Las fotos no están disponibles porque falta configurar su almacenamiento. Puedes enviar el reporte sin fotos.';
      status.textContent = ''; form.hidden = false;
    } catch (error) { if (version === revision) status.textContent = error.message; }
  }
  close.addEventListener('click', () => { if (!busy) dialog.close(); });
  dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
  dialog.addEventListener('close', () => { if (!dialog.open) { ++revision; active = null; stopCamera(); if (camera.open) camera.close(); ['ean', 'product'].forEach(removePhoto); } });
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (!active || busy) return;
    busy = true; send.disabled = true; close.disabled = true; status.textContent = 'Enviando…';
    try {
      const body = new FormData(form); body.set('csrf', active.csrf); body.set('ean', active.ean); body.set('submission_key', active.key); body.set('owner_token', ownerToken());
      for (const kind of ['ean', 'product']) {
        const file = capturedPhotos.get(kind); body.delete('photo_' + kind);
        if (file && !document.getElementById('reportPhotos').disabled) body.set('photo_' + kind, await compress(file), kind + '.jpg');
      }
      const result = await request('', body); form.hidden = true; ['ean', 'product'].forEach(removePhoto);
      status.replaceChildren(document.createTextNode(`Reporte #${result.report.id} enviado. `));
      const link = document.createElement('a'); link.href = 'reportes.php'; link.textContent = 'Ver mis reportes'; status.append(link);
    } catch (error) { status.textContent = error.message; }
    finally { busy = false; send.disabled = false; close.disabled = false; }
  });
  window.RepoProductReports = { open };
})();
