(() => {
  'use strict';
  function validEAN(code) {
    if (typeof code !== 'string' || !/^(?:\d{8}|\d{13})$/.test(code) || /^0+$/.test(code)) return false;
    let sum = 0;
    for (let i = code.length - 2, weight = 3; i >= 0; i--, weight = 4 - weight) sum += Number(code[i]) * weight;
    return (10 - sum % 10) % 10 === Number(code.at(-1));
  }

  // Confirma el mismo EAN en fotogramas separados y dentro de una ventana corta.
  function confirmation() {
    let code = '', count = 0, first = 0, last = 0;
    return (value, now = performance.now()) => {
      if (!validEAN(value)) { code = ''; count = 0; return false; }
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
    function background(current, accept) {
      if (!document.createElement || !element.querySelector) return;
      const canvas = document.createElement('canvas'), context = canvas.getContext('2d');
      // Adaptador para html5-qrcode 2.3.8: reutiliza su decodificador sobre el canvas,
      // sin convertir cada fotograma en archivos ni crear URLs de imágenes.
      const decoder = current.reader.qrcode;
      if (!context || typeof decoder?.decodeRobustlyAsync !== 'function') return;
      let variant = 0, lastAngle = null, running = false, disposed = false;
      const dispose = () => { if (disposed) return; disposed = true; canvas.width = canvas.height = 0; };
      current.disposeBackground = () => { clearTimeout(current.backgroundTimer); if (!running) dispose(); };
      async function analyze() {
        if (!current.accepting || session !== current || current.token !== revision) { dispose(); return; }
        const video = element.querySelector('video');
        if (video?.videoWidth && video.videoHeight && performance.now() - (current.lastPrimaryDetection ?? -Infinity) > 450) {
          running = true;
          try {
            // Recorre orientaciones fuera de la vista del usuario; una sola tarea a la vez.
            const angle = lastAngle ?? [0, 90, -18, 18][variant++ % 4], radians = angle * Math.PI / 180;
            const scale = Math.min(1, 960 / Math.max(video.videoWidth, video.videoHeight));
            const width = video.videoWidth * scale, height = video.videoHeight * scale;
            canvas.width = Math.ceil(Math.abs(width * Math.cos(radians)) + Math.abs(height * Math.sin(radians)));
            canvas.height = Math.ceil(Math.abs(width * Math.sin(radians)) + Math.abs(height * Math.cos(radians)));
            context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height); context.save(); context.translate(canvas.width / 2, canvas.height / 2); context.rotate(radians); context.drawImage(video, -width / 2, -height / 2, width, height); context.restore();
            if (current.accepting && current.token === revision) {
              const result = await decoder.decodeRobustlyAsync(canvas);
              const code = result.text;
              if (validEAN(code)) { lastAngle = angle; accept(code, 'background'); } else lastAngle = null;
            }
          } catch { lastAngle = null; }
          finally { running = false; }
        }
        if (!current.accepting || session !== current || current.token !== revision) { dispose(); return; }
        current.backgroundTimer = setTimeout(analyze, lastAngle === null ? 260 : 140);
      }
      current.backgroundTimer = setTimeout(analyze, 500);
    }
    async function release(current) {
      if (!current) return;
      if (!current.cleanup) current.cleanup = (async () => {
        current.accepting = false; current.disposeBackground?.(); clearTimeout(current.progressTimer);
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
    function start() {
      const token = ++revision;
      if (session) session.accepting = false;
      return enqueue(async () => {
        await release(session);
        if (token !== revision) return false;
        element.classList.remove('ean-confirmed');
        element.classList.remove('ean-detecting');
        if (!window.Html5Qrcode || !window.Html5QrcodeSupportedFormats) throw new Error('No se pudo cargar el lector. Intenta nuevamente.');
        const formats = window.Html5QrcodeSupportedFormats;
        const reader = new window.Html5Qrcode(elementId, { formatsToSupport: [formats.EAN_13, formats.EAN_8], verbose: false });
        const current = { reader, accepting: true, token, confirm: confirmation() };
        session = current;
        const accept = (code, source = 'camera') => {
          if (!current.accepting || session !== current || token !== revision) return;
          if (!validEAN(code)) { current.confirm(code); return; }
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
            videoConstraints: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
            disableFlip: true,
            qrbox: (width, height) => ({ width: Math.floor(width * .94), height: Math.floor(height * .85) })
          }, accept, () => {});
          await current.starting;
          if (token !== revision) { await release(current); return false; }
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
