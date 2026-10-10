(() => {
  'use strict';
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

  function create(elementId, { onCode, onError = () => {}, onStatus = () => {} }) {
    const element = document.getElementById(elementId);
    let revision = 0, session = null, queue = Promise.resolve();
    const enqueue = task => {
      const operation = queue.then(task);
      queue = operation.catch(() => {});
      return operation;
    };
    function cameraControls(current) {
      const track = element.querySelector?.('video')?.srcObject?.getVideoTracks?.()[0];
      if (!track?.getCapabilities || !track.applyConstraints || !document.createElement) return;
      const capabilities = track.getCapabilities(), settings = track.getSettings();
      const zoom = capabilities.zoom;
      const hasZoom = Number.isFinite(zoom?.min) && Number.isFinite(zoom?.max) && zoom.max > zoom.min;
      if (!hasZoom && capabilities.torch !== true) return;
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
        const text = document.createElement('span'); text.textContent = 'Zoom';
        const input = document.createElement('input'); input.type = 'range'; input.setAttribute('aria-label', 'Zoom de cámara');
        input.min = zoom.min; input.max = Math.max(zoom.min, Math.min(zoom.max, Math.max(3, zoom.min + (zoom.step || .1)))); input.step = zoom.step > 0 ? zoom.step : .1;
        let applied = Math.max(Number(input.min), Math.min(Number(input.max), settings.zoom ?? zoom.min));
        input.value = applied;
        const output = document.createElement('span'); output.className = 'ean-camera-zoom-value'; output.textContent = `${applied.toFixed(1)}×`;
        input.addEventListener('change', async () => {
          if (!active()) return;
          input.disabled = true;
          try { if (await apply('zoom', Number(input.value))) { applied = track.getSettings().zoom; output.textContent = `${applied.toFixed(1)}×`; } }
          catch { if (active()) { input.value = applied; onStatus('No pudimos ajustar el zoom. Puedes seguir escaneando.'); } }
          finally { input.disabled = false; }
        });
        label.append(text, input, output); controls.append(label);
      }
      if (capabilities.torch === true) {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'ean-camera-light';
        let enabled = settings.torch === true;
        const render = () => { button.textContent = enabled ? 'Apagar luz' : 'Encender luz'; button.setAttribute('aria-pressed', String(enabled)); };
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
      current.disposeControls = () => controls.remove();
      element.after(controls);
    }
    function background(current, accept) {
      if (current.isQR || !document.createElement || !element.querySelector) return;
      const canvas = document.createElement('canvas'), context = canvas.getContext('2d');
      // Adaptador para html5-qrcode 2.3.8: reutiliza su decodificador sobre el canvas,
      // sin convertir cada fotograma en archivos ni crear URLs de imágenes.
      const decoder = current.reader.qrcode;
      if (!context || typeof decoder?.decodeRobustlyAsync !== 'function') return;
      const profiles = [{ angle: 0, crop: .7 }, { angle: -45, crop: .7 }, { angle: 45, crop: .7 }, { angle: 90, crop: .7 }, { angle: 0, crop: 1 }, { angle: -30, crop: .7 }, { angle: 30, crop: .7 }, { angle: -60, crop: .7 }, { angle: 60, crop: .7 }, { angle: 90, crop: 1 }, { angle: -18, crop: .7 }, { angle: 18, crop: .7 }];
      const probe = document.createElement('canvas'), probeContext = probe.getContext('2d', { willReadFrequently: true });
      function locate(video) {
        if (!probeContext) return null;
        const scale = Math.min(1, 640 / Math.max(video.videoWidth, video.videoHeight));
        probe.width = Math.round(video.videoWidth * scale); probe.height = Math.round(video.videoHeight * scale);
        probeContext.drawImage(video, 0, 0, probe.width, probe.height);
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
            const adaptive = lastProfile ?? (probeAttempt++ % 2 === 0 ? locate(video) : null);
            if (window.Quagga?.decodeSingle && !adaptive) {
              // Localiza barras y su orientación en una sola imagen. El canvas
              // y las cajas del localizador permanecen fuera de la interfaz.
              const scale = Math.min(1, 1920 / Math.max(video.videoWidth, video.videoHeight));
              canvas.width = Math.round(video.videoWidth * scale); canvas.height = Math.round(video.videoHeight * scale);
              context.drawImage(video, 0, 0, canvas.width, canvas.height);
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
            const sourceWidth = Math.round(video.videoWidth * profile.crop), sourceHeight = Math.round(video.videoHeight * profile.crop);
            const scale = Math.min(1, 1920 / Math.max(sourceWidth, sourceHeight));
            const width = sourceWidth * scale, height = sourceHeight * scale;
            // drawImage recorta lo que queda fuera del video; el fondo blanco
            // permite mantener centradas también las barras cercanas a un borde.
            const sourceX = Math.round(video.videoWidth * (profile.centerX ?? .5) - sourceWidth / 2);
            const sourceY = Math.round(video.videoHeight * (profile.centerY ?? .5) - sourceHeight / 2);
            canvas.width = Math.ceil(Math.abs(width * Math.cos(radians)) + Math.abs(height * Math.sin(radians)) - 1e-6);
            canvas.height = Math.ceil(Math.abs(width * Math.sin(radians)) + Math.abs(height * Math.cos(radians)) - 1e-6);
            context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height); context.save(); context.translate(canvas.width / 2, canvas.height / 2); context.rotate(radians); context.drawImage(video, sourceX, sourceY, sourceWidth, sourceHeight, -width / 2, -height / 2, width, height); context.restore();
            if (current.accepting && current.token === revision) {
              const result = await decoder.decodeRobustlyAsync(canvas);
              const code = result.text;
              if (validEAN(code)) { lastProfile = profile; accept(code, 'background'); } else lastProfile = null;
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
        const current = { reader, accepting: true, token, isQR, confirm: confirmation(validate) };
        session = current;
        const accept = (code, source = 'camera') => {
          if (!current.accepting || session !== current || token !== revision) return;
          if (!validate(code)) { current.confirm(code); return; }
          if (source === 'camera') current.lastPrimaryDetection = performance.now();
          const confirmed = current.confirm(code);
          if (!confirmed) {
            element.classList.add('ean-detecting'); onStatus('Detectando código…'); clearTimeout(current.progressTimer);
            current.progressTimer = setTimeout(() => { if (current.accepting && token === revision) { element.classList.remove('ean-detecting'); onStatus('Centra un solo código dentro del recuadro.'); } }, 700);
            return;
          }
          current.accepting = false; clearTimeout(current.progressTimer); current.disposeBackground?.(); element.classList.remove('ean-detecting');
          element.classList.add('ean-confirmed'); onStatus('Código confirmado.');
          enqueue(async () => {
            await new Promise(resolve => setTimeout(resolve, 100)); await release(current);
            if (token === revision) Promise.resolve().then(() => { if (token === revision) return onCode(code); }).catch(error => { if (token === revision) onError(error); });
          }).catch(onError);
        };
        onStatus('Abriendo cámara…');
        try {
          current.starting = reader.start({ facingMode: 'environment' }, {
            fps: 15,
            videoConstraints: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
            disableFlip: true,
            qrbox: (width, height) => isQR ? { width: Math.floor(Math.min(width, height) * .85), height: Math.floor(Math.min(width, height) * .85) } : { width: Math.floor(width * .94), height: Math.floor(height * .85) }
          }, accept, () => {});
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
    return { start, stop };
  }
  window.RepoEANScanner = { create, validEAN, confirmation };
})();
