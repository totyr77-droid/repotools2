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
    async function release(current) {
      if (!current) return;
      if (!current.cleanup) current.cleanup = (async () => {
        try { await current.starting; } catch {}
        if (current.reader.isScanning) await current.reader.stop();
        current.reader.clear();
        element.classList.remove('ean-confirmed');
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
        if (!window.Html5Qrcode || !window.Html5QrcodeSupportedFormats) throw new Error('No se pudo cargar el lector. Intenta nuevamente.');
        const formats = window.Html5QrcodeSupportedFormats;
        const reader = new window.Html5Qrcode(elementId, { formatsToSupport: [formats.EAN_13, formats.EAN_8], verbose: false });
        const current = { reader, accepting: true, token, confirm: confirmation() };
        session = current;
        onStatus('Abriendo cámara…');
        try {
          current.starting = reader.start({ facingMode: 'environment' }, {
            fps: 12,
            disableFlip: true,
            qrbox: (width, height) => ({ width: Math.min(320, Math.floor(width * .85)), height: Math.min(140, Math.floor(height * .6)) })
          }, code => {
            if (!current.accepting || session !== current || token !== revision || !current.confirm(code)) return;
            current.accepting = false;
            element.classList.add('ean-confirmed');
            onStatus('Código confirmado.');
            // La cámara se libera antes de mostrar o guardar el resultado.
            enqueue(async () => {
              await new Promise(resolve => setTimeout(resolve, 180));
              await release(current);
              if (token === revision) {
                Promise.resolve().then(() => {
                  if (token === revision) return onCode(code);
                }).catch(error => { if (token === revision) onError(error); });
              }
            }).catch(onError);
          }, () => {});
          await current.starting;
          if (token !== revision) { await release(current); return false; }
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
