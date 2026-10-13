(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  let scanPhoto = null, photoTimer, photoVersion = 0;
  function clearScanPhoto() { ++photoVersion; scanPhoto = null; clearTimeout(photoTimer); }
  function takeScanPhoto(ean) {
    const matching = scanPhoto?.ean === String(ean) && scanPhoto.expires > Date.now();
    if (matching) scanPhoto.taken = true;
    const photo = matching ? scanPhoto.blob : null;
    clearScanPhoto(); return photo;
  }
  function rememberScanPhoto(ean, element) {
    const version = photoVersion;
    try {
      const video = element.querySelector('video');
      if (!video?.videoWidth || !video.videoHeight) return;
      const frame = visibleFrame(video);
      const scale = Math.min(1, 1280 / Math.max(frame.width, frame.height));
      const canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(frame.width * scale)); canvas.height = Math.max(1, Math.round(frame.height * scale));
      canvas.getContext('2d').drawImage(video, frame.x, frame.y, frame.width, frame.height, 0, 0, canvas.width, canvas.height);
      let resolvePhoto;
      const pending = new Promise(resolve => { resolvePhoto = resolve; });
      const record = { ean: String(ean), blob: pending, expires: Date.now() + 300000, taken: false };
      scanPhoto = record;
      photoTimer = setTimeout(clearScanPhoto, 300000);
      canvas.toBlob(blob => {
        if ((version !== photoVersion && !record.taken) || record.expires <= Date.now() || !blob || blob.size > 1024 * 1024) { canvas.width = canvas.height = 0; resolvePhoto(null); return; }
        const photo = window.RepoScanPhoto ? window.RepoScanPhoto.normalize(canvas, String(ean), blob).finally(() => { canvas.width = canvas.height = 0; }) : blob;
        if (!window.RepoScanPhoto) canvas.width = canvas.height = 0;
        record.blob = photo; resolvePhoto(photo);
      }, 'image/jpeg', .8);
    } catch { /* Una foto opcional nunca bloquea el escaneo. */ }
  }
  page?.onLeave(clearScanPhoto);
  if (page) page.listen('pagehide', clearScanPhoto);
  else window.addEventListener?.('pagehide', clearScanPhoto);

  let audioContext = null, audioPrimed = false;
  function prepareSound() {
    try {
      if (window.navigator?.audioSession) window.navigator.audioSession.type = 'ambient';
      const Context = window.AudioContext || window.webkitAudioContext;
      if (!Context) return;
      audioContext ??= new Context();
      if (audioContext.state !== 'running') audioContext.resume().catch(() => {});
      if (!audioPrimed && audioContext.createBufferSource) {
        const source = audioContext.createBufferSource(); source.buffer = audioContext.createBuffer(1, 1, 22050);
        source.connect(audioContext.destination); source.onended = () => source.disconnect(); source.start(0); audioPrimed = true;
      }
    } catch {}
  }
  async function beep() {
    try { window.navigator?.vibrate?.(60); } catch {}
    if (!audioContext) return;
    let restoreSession = () => {}, scheduled = false;
    try {
      // Ambient obeys the iPhone Silent switch. Request playback only for
      // the confirmed tone, then restore the previous mixing policy.
      const session = window.navigator?.audioSession;
      if (session) {
        const previous = session.type;
        let restored = false;
        session.type = 'playback';
        restoreSession = () => {
          if (restored) return; restored = true;
          try { if (session.type === 'playback') session.type = previous; } catch {}
        };
      }
      if (audioContext.state !== 'running') await audioContext.resume();
      if (audioContext.state !== 'running') return;
      const oscillator = audioContext.createOscillator(), gain = audioContext.createGain(), now = audioContext.currentTime;
      oscillator.type = 'sine'; oscillator.frequency.setValueAtTime(1100, now);
      gain.gain.setValueAtTime(0, now); gain.gain.linearRampToValueAtTime(.45, now + .008); gain.gain.exponentialRampToValueAtTime(.0001, now + .14);
      oscillator.connect(gain); gain.connect(audioContext.destination);
      const restore = restoreSession;
      oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); restore(); };
      oscillator.start(now); oscillator.stop(now + .15);
      scheduled = true;
      // Release the exclusive policy even if iOS interrupts before onended.
      setTimeout(restore, 500);
    } catch {} finally { if (!scheduled) restoreSession(); }
  }
  function visibleFrame(video) {
    const width = video.videoWidth, height = video.videoHeight;
    const ratio = video.clientWidth && video.clientHeight ? video.clientWidth / video.clientHeight : width / height;
    const frameWidth = Math.min(width, Math.round(height * ratio)), frameHeight = Math.min(height, Math.round(width / ratio));
    return { x: Math.round((width - frameWidth) / 2), y: Math.round((height - frameHeight) / 2), width: frameWidth, height: frameHeight };
  }
  function validEAN(code) {
    if (typeof code !== 'string' || !/^(?:\d{8}|\d{13})$/.test(code) || /^0+$/.test(code)) return false;
    let sum = 0;
    for (let i = code.length - 2, weight = 3; i >= 0; i--, weight = 4 - weight) sum += Number(code[i]) * weight;
    return (10 - sum % 10) % 10 === Number(code.at(-1));
  }

  // Confirma el mismo EAN en fotogramas separados y dentro de una ventana corta.
  function confirmation(validate = validEAN) {
    let code = '', count = 0, first = 0, last = 0;
    return (value, now = performance.now()) => {
      if (!validate(value)) { code = ''; count = 0; return false; }
      if (value !== code || now - last > 650 || now - first > 1500) {
        code = value; count = 1; first = last = now; return false;
      }
      if (now - last < 60) return false;
      last = now;
      return ++count >= 3;
    };
  }

  function create(elementId, { onCode, onError = () => {}, onStatus = () => {}, onManual, scanContext, canAccept = () => true, keepOpen = () => false, repeatCooldown = 900, rearmRepeated = false, relayBypass = false }) {
    const element = document.getElementById(elementId);
    let revision = 0, session = null, queue = Promise.resolve();
    const enqueue = task => {
      const operation = queue.then(task);
      queue = operation.catch(() => {});
      return operation;
    };
    function cameraControls(current) {
      const track = element.querySelector?.('video')?.srcObject?.getVideoTracks?.()[0];
      if (!document.createElement) return;
      const capabilities = track?.getCapabilities?.() ?? {}, settings = track?.getSettings?.() ?? {};
      const zoom = capabilities.zoom;
      const minZoom = Math.max(1, zoom?.min ?? 1);
      const hasZoom = Number.isFinite(zoom?.min) && Number.isFinite(zoom?.max) && zoom.max > minZoom;
      if (!hasZoom && capabilities.torch !== true && !onManual) return;
      const controls = document.createElement('div'); controls.className = 'ean-camera-controls';
      controls.setAttribute('role', 'group'); controls.setAttribute('aria-label', 'Controles de cámara');
      const active = () => current.accepting && session === current && current.token === revision;
      let updates = current.cameraUpdates ?? Promise.resolve();
      function apply(name, value) {
        const operation = updates.catch(() => {}).then(async () => {
          if (!active()) return false;
          const constraints = track.getConstraints();
          const advanced = Object.assign({}, ...(constraints.advanced ?? []), { [name]: value });
          await track.applyConstraints({ ...constraints, advanced: [advanced] });
          if (!active()) return false;
          const actual = track.getSettings()[name];
          if (name === 'torch' ? actual !== value : !Number.isFinite(actual) || Math.abs(actual - value) > .1) throw new Error('Control no disponible');
          return true;
        });
        updates = operation;
        return operation;
      }
      if (hasZoom) {
        const label = document.createElement('label'); label.className = 'ean-camera-zoom';
        const text = document.createElement('span'); text.className = 'ean-camera-zoom-icon'; text.setAttribute('aria-hidden', 'true');
        text.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10" cy="10" r="6"/><path d="m15 15 5 5M7 10h6m-3-3v6"/></svg>';
        const input = document.createElement('input'); input.type = 'range'; input.setAttribute('aria-label', 'Zoom de cámara');
        input.setAttribute('aria-orientation', 'vertical');
        input.min = minZoom; input.max = current.isQR ? zoom.max : Math.max(minZoom, Math.min(zoom.max, Math.max(3, minZoom + (zoom.step || .1)))); input.step = zoom.step > 0 ? zoom.step : .1;
        let applied = Math.max(Number(input.min), Math.min(Number(input.max), settings.zoom ?? zoom.min));
        input.value = applied;
        const output = document.createElement('span'); output.className = 'ean-camera-zoom-value'; output.textContent = `${applied.toFixed(1)}×`;
        const updateZoom = async () => {
          if (!active()) return;
          input.disabled = true;
          try { if (await apply('zoom', Math.max(minZoom, Number(input.value)))) { applied = track.getSettings().zoom; output.textContent = `${applied.toFixed(1)}×`; } }
          catch { if (active()) { input.value = applied; onStatus('No pudimos ajustar el zoom. Puedes seguir escaneando.'); } }
          finally { input.disabled = false; }
        };
        input.addEventListener('change', updateZoom);
        if (Number.isFinite(settings.zoom) && settings.zoom < minZoom) void updateZoom();
        label.append(text, input, output); controls.append(label);
      }
      if (capabilities.torch === true) {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'ean-camera-light';
        let enabled = settings.torch === true;
        button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-12h-7Z"/></svg>';
        const render = () => { const label = enabled ? 'Apagar luz' : 'Encender luz'; button.setAttribute('aria-label', label); button.title = label; button.setAttribute('aria-pressed', String(enabled)); };
        render();
        button.addEventListener('click', async () => {
          if (!active()) return;
          button.disabled = true;
          try { if (await apply('torch', !enabled)) { enabled = !enabled; render(); } }
          catch { if (active()) onStatus('No pudimos encender o apagar la luz. Puedes seguir escaneando.'); }
          finally { button.disabled = false; }
        });
        controls.append(button);
      }
      if(onManual){const manual=document.createElement('button');manual.type='button';manual.className='ean-camera-manual';if(capabilities.torch!==true)manual.classList.add('ean-camera-manual-only');manual.setAttribute('aria-label',current.isQR?'Ingresar código del palet':'Ingresar EAN');manual.title=manual.getAttribute('aria-label');manual.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M6 9h.01M10 9h.01M14 9h.01M18 9h.01M6 13h.01M10 13h.01M14 13h.01M18 13h.01M7 16h10"/></svg>';manual.addEventListener('click',()=>{if(active())Promise.resolve(onManual()).catch(onError);});controls.append(manual);}
      current.disposeControls = () => controls.remove();
      element.append(controls);
    }
    function background(current, accept) {
      if (!document.createElement || !element.querySelector) return;
      const canvas = document.createElement('canvas'), context = canvas.getContext('2d');
      // Adaptador para html5-qrcode 2.3.8: reutiliza su decodificador sobre el canvas,
      // sin convertir cada fotograma en archivos ni crear URLs de imágenes.
      const decoder = current.reader.qrcode;
      if (!context || typeof decoder?.decodeRobustlyAsync !== 'function') return;
      const profiles = [{ angle: 0, crop: .7 }, { angle: -45, crop: .7 }, { angle: 45, crop: .7 }, { angle: 90, crop: .7 }, { angle: 0, crop: 1 }, { angle: -30, crop: .7 }, { angle: 30, crop: .7 }, { angle: -60, crop: .7 }, { angle: 60, crop: .7 }, { angle: 90, crop: 1 }, { angle: -18, crop: .7 }, { angle: 18, crop: .7 }];
      const probe = document.createElement('canvas'), probeContext = probe.getContext('2d', { willReadFrequently: true });
      function locate(video, frame) {
        if (!probeContext) return null;
        const scale = Math.min(1, 640 / Math.max(frame.width, frame.height));
        probe.width = Math.round(frame.width * scale); probe.height = Math.round(frame.height * scale);
        probeContext.drawImage(video, frame.x, frame.y, frame.width, frame.height, 0, 0, probe.width, probe.height);
        const { data } = probeContext.getImageData(0, 0, probe.width, probe.height);
        let xx = 0, yy = 0, xy = 0, mass = 0, xMass = 0, yMass = 0;
        for (let y = 1; y < probe.height - 1; y += 2) for (let x = 1; x < probe.width - 1; x += 2) {
          const i = (y * probe.width + x) * 4;
          const gx = data[i + 4] - data[i - 4], gy = data[i + probe.width * 4] - data[i - probe.width * 4];
          const energy = gx * gx + gy * gy;
          if (energy < 1600) continue;
          xx += gx * gx; yy += gy * gy; xy += gx * gy;
          mass += energy; xMass += x * energy; yMass += y * energy;
        }
        if (!mass || Math.hypot(xx - yy, 2 * xy) / mass < .35) return null;
        let angle = -Math.atan2(2 * xy, xx - yy) * 90 / Math.PI;
        const axis = Math.round(angle / 90) * 90;
        if (Math.abs(angle - axis) < 7) angle = axis;
        return { angle, crop: .7, centerX: xMass / mass / probe.width, centerY: yMass / mass / probe.height };
      }
      let variant = 0, probeAttempt = 0, lastProfile = null, running = false, disposed = false;
      const dispose = () => { if (disposed) return; disposed = true; canvas.width = canvas.height = probe.width = probe.height = 0; };
      current.disposeBackground = () => { clearTimeout(current.backgroundTimer); if (!running) dispose(); };
      async function analyze() {
        if (!current.accepting || session !== current || current.token !== revision) { dispose(); return; }
        const video = element.querySelector('video');
        const started = performance.now();
        if (video?.videoWidth && video.videoHeight && started - (current.lastPrimaryDetection ?? -Infinity) > 180) {
          running = true;
          try {
            const frame = visibleFrame(video);
            const adaptive = current.isQR ? { angle: 0, crop: 1 } : lastProfile ?? (probeAttempt++ % 2 === 0 ? locate(video, frame) : null);
            if (!current.isQR && window.Quagga?.decodeSingle && !adaptive) {
              // Localiza barras y su orientación en una sola imagen. El canvas
              // y las cajas del localizador permanecen fuera de la interfaz.
              const scale = Math.min(1, 1920 / Math.max(frame.width, frame.height));
              canvas.width = Math.round(frame.width * scale); canvas.height = Math.round(frame.height * scale);
              context.drawImage(video, frame.x, frame.y, frame.width, frame.height, 0, 0, canvas.width, canvas.height);
              let result;
              try {
                result = await window.Quagga.decodeSingle({ src: canvas.toDataURL('image/jpeg', .92), inputStream: { size: 0 }, locate: true, locator: { halfSample: false, patchSize: 'small' }, decoder: { readers: ['ean_reader', 'ean_8_reader'] }, numOfWorkers: 0 });
              } catch { /* El decodificador alternativo sigue disponible. */ }
              if (validEAN(result?.codeResult?.code)) {
                accept(result.codeResult.code, 'background');
                return;
              }
            }
            // Recorre orientaciones fuera de la vista del usuario; una sola tarea a la vez.
            const profile = adaptive ?? profiles[variant++ % profiles.length], radians = profile.angle * Math.PI / 180;
            // Conserva los píxeles originales de las barras pequeñas. El recorte
            // central reduce trabajo sin reducir resolución; la vista completa
            // también busca códigos que estén fuera del centro.
            const sourceWidth = Math.round(frame.width * profile.crop), sourceHeight = Math.round(frame.height * profile.crop);
            const scale = Math.min(1, 1920 / Math.max(sourceWidth, sourceHeight));
            const width = sourceWidth * scale, height = sourceHeight * scale;
            // drawImage recorta lo que queda fuera del video; el fondo blanco
            // permite mantener centradas también las barras cercanas a un borde.
            const sourceX = frame.x + Math.round(frame.width * (profile.centerX ?? .5) - sourceWidth / 2);
            const sourceY = frame.y + Math.round(frame.height * (profile.centerY ?? .5) - sourceHeight / 2);
            canvas.width = Math.ceil(Math.abs(width * Math.cos(radians)) + Math.abs(height * Math.sin(radians)) - 1e-6);
            canvas.height = Math.ceil(Math.abs(width * Math.sin(radians)) + Math.abs(height * Math.cos(radians)) - 1e-6);
            const clipX = Math.max(frame.x, sourceX), clipY = Math.max(frame.y, sourceY);
            const clipWidth = Math.min(frame.x + frame.width, sourceX + sourceWidth) - clipX;
            const clipHeight = Math.min(frame.y + frame.height, sourceY + sourceHeight) - clipY;
            context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height); context.save(); context.translate(canvas.width / 2, canvas.height / 2); context.rotate(radians); context.drawImage(video, clipX, clipY, clipWidth, clipHeight, -width / 2 + (clipX - sourceX) * scale, -height / 2 + (clipY - sourceY) * scale, clipWidth * scale, clipHeight * scale); context.restore();
            if (current.accepting && current.token === revision) {
              const result = await decoder.decodeRobustlyAsync(canvas);
              const code = result.text;
              if (current.validate(code)) { lastProfile = profile; accept(code, 'background'); } else lastProfile = null;
            }
          } catch { lastProfile = null; }
          finally {
            running = false;
            if (!current.accepting || session !== current || current.token !== revision) dispose();
            else current.backgroundTimer = setTimeout(analyze, Math.max(30, 100 - (performance.now() - started)));
          }
          return;
        }
        if (!current.accepting || session !== current || current.token !== revision) { dispose(); return; }
        current.backgroundTimer = setTimeout(analyze, 100);
      }
      current.backgroundTimer = setTimeout(analyze, 100);
    }
    async function release(current) {
      if (!current) return;
      if (!current.cleanup) current.cleanup = (async () => {
        current.accepting = false; current.disposeBackground?.(); current.disposeControls?.(); clearTimeout(current.progressTimer);
        try { await current.starting; } catch {}
        if (current.reader.isScanning) await current.reader.stop();
        current.reader.clear();
        element.classList.remove('ean-confirmed');
        element.classList.remove('ean-detecting');
        if (session === current) session = null;
      })();
      await current.cleanup;
    }
    function stop() {
      ++revision;
      if (session) session.accepting = false;
      return enqueue(() => release(session));
    }
    function start(format = 'ean') {
      clearScanPhoto();
      // Se habilita el audio en el toque que abre la cámara, antes de esperar
      // permisos o fotogramas, para que el primer escaneo también tenga sonido.
      prepareSound();
      const token = ++revision;
      if (session) session.accepting = false;
      return enqueue(async () => {
        await release(session);
        if (token !== revision) return false;
        element.classList.remove('ean-confirmed');
        element.classList.remove('ean-detecting');
        if (!window.Html5Qrcode || !window.Html5QrcodeSupportedFormats) throw new Error('No se pudo cargar el lector. Intenta nuevamente.');
        const formats = window.Html5QrcodeSupportedFormats;
        const isQR = format === 'qr';
        const validate = isQR ? code => typeof code === 'string' && code.trim().length > 0 && code.length <= 2048 : validEAN;
        const reader = new window.Html5Qrcode(elementId, { formatsToSupport: isQR ? [formats.QR_CODE] : [formats.EAN_13, formats.EAN_8], verbose: false });
        const current = { reader, accepting: true, token, isQR, validate, confirm: confirmation(validate) };
        session = current;
        const accept = (code, source = 'camera') => {
          if(current.lastDelivered===code){const elapsed=Date.now()-current.lastDeliveredSeen;current.lastDeliveredSeen=Date.now();if(elapsed<repeatCooldown)return;if(rearmRepeated)current.lastDelivered='';}
          if (!current.accepting || session !== current || token !== revision || !canAccept()) return;
          // Con object-fit: cover se confirma únicamente la zona que el usuario
          // ve. El lector oculto usa ese mismo recorte, sin bandas negras.
          const video = source === 'camera' ? element.querySelector?.('video') : null;
          if (video?.videoWidth && document.createElement && typeof reader.qrcode?.decodeRobustlyAsync === 'function') {
            const frame = visibleFrame(video);
            if (!isQR && (frame.width < video.videoWidth - 2 || frame.height < video.videoHeight - 2)) return;
          }
          if (!validate(code)) { current.confirm(code); return; }
          if (source === 'camera') current.lastPrimaryDetection = performance.now();
          const confirmed = current.confirm(code);
          if (!confirmed) {
            element.classList.add('ean-detecting'); onStatus('Detectando código…'); clearTimeout(current.progressTimer);
            current.progressTimer = setTimeout(() => { if (current.accepting && token === revision) { element.classList.remove('ean-detecting'); onStatus('Centra un solo código dentro del recuadro.'); } }, 700);
            return;
          }
          if (!isQR) rememberScanPhoto(code, element);
          current.accepting = false; clearTimeout(current.progressTimer); current.disposeBackground?.(); element.classList.remove('ean-detecting');
          element.classList.add('ean-confirmed'); onStatus('Código confirmado.');
          if(keepOpen()){
            current.lastDelivered=code;current.lastDeliveredSeen=Date.now();
            Promise.resolve().then(()=>{beep();return onCode(code);}).then(()=>new Promise(resolve=>setTimeout(resolve,350))).then(()=>{
              if(token!==revision||session!==current)return;
              current.confirm=confirmation(validate);current.accepting=true;element.classList.remove('ean-confirmed');
              try{background(current,accept);}catch{}
            }).catch(error=>{if(token===revision)onError(error);});
            return;
          }
          enqueue(async () => {
            await new Promise(resolve => setTimeout(resolve, 100)); await release(current);
            if (token === revision) Promise.resolve().then(() => { if (token === revision) { beep(); return onCode(code); } }).catch(error => { if (token === revision) onError(error); });
          }).catch(onError);
        };
        onStatus('Abriendo cámara…');
        try {
          current.starting = reader.start({ facingMode: 'environment' }, {
            fps: 15,
            videoConstraints: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
            disableFlip: true,
            qrbox: (width, height) => isQR ? { width: Math.max(50, Math.floor(Math.min(width, height) * .85)), height: Math.max(50, Math.floor(Math.min(width, height) * .85)) } : { width: Math.max(50, Math.floor(width * .94)), height: Math.max(50, Math.floor(height * .85)) }
          }, code => accept(code, 'camera'), () => {});
          await current.starting;
          if (token !== revision) { await release(current); return false; }
          // Solicita enfoque continuo solo si la cámara lo admite, sin retrasar
          // la lectura ni fallar en navegadores que no exponen este control.
          try {
            const track = element.querySelector?.('video')?.srcObject?.getVideoTracks?.()[0];
            if (track?.getCapabilities?.().focusMode?.includes('continuous')) {
              current.cameraUpdates = track.applyConstraints({ ...track.getConstraints(), advanced: [{ focusMode: 'continuous' }] }).catch(() => {});
            }
          } catch {}
          try { cameraControls(current); } catch { /* Los controles opcionales no bloquean el escáner. */ }
          try { background(current, accept); } catch { /* La cámara sigue leyendo si el análisis auxiliar no está disponible. */ }
          onStatus('Centra un solo código dentro del recuadro.');
          return true;
        } catch (error) {
          current.accepting = false;
          await release(current);
          if (token !== revision) return false;
          throw error;
        }
      });
    }
    page?.onLeave(stop);
    const normalCode=onCode;if(!relayBypass&&elementId!=='userReader')onCode=code=>window.RepoScanMethods?window.RepoScanMethods.route(code,normalCode,elementId):normalCode(code);const raw={start,stop};return !relayBypass&&window.RepoScanMethods?window.RepoScanMethods.wrap(elementId,{onCode,onError,onStatus,onManual,scanContext},raw):raw;
  }
  function rawCreate(elementId,options){return create(elementId,{...options,relayBypass:true});}
  window.RepoEANScanner = { create, rawCreate, validEAN, confirmation, takeScanPhoto, clearScanPhoto };
})();
