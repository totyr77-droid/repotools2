(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  // One camera lease and viewport implementation for every recognition mode.
  let cameraQueue = Promise.resolve(), cameraOwner = null;const metricListeners=new Set();
  function cameraTask(task) { const next = cameraQueue.then(task); cameraQueue = next.catch(() => {}); return next; }
  function cameraViewport(element, isQR, pallet = false) {
    if (!document.createElement || !element.append) return { track() {}, reset() {}, dispose() {} };
    element.classList.add('repo-camera-viewport'); element.dataset.recognition = isQR ? 'qr' : 'ean';
    const guide = document.createElement('div'); guide.className = 'repo-camera-guide'; guide.setAttribute('aria-hidden','true');
    const marker = document.createElement('div'); marker.className = 'ean-identification-track'; marker.hidden = true; marker.setAttribute('aria-hidden','true');
    let numberGuide;
    if(isQR&&pallet){guide.classList.add('repo-pallet-qr-guide');guide.innerHTML='<svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M3 24V3h21M76 3h21v21M3 76v21h21M76 97h21V76"/><path d="M12 12h14v14H12ZM74 12h14v14H74ZM12 74h14v14H12Z"/></svg>';numberGuide=document.createElement('div');numberGuide.className='repo-pallet-number-guide';numberGuide.setAttribute('aria-hidden','true');numberGuide.innerHTML='<span>········</span><small>Número de palet</small>';element.append(numberGuide);}
    element.append(guide,marker); let timer;
    function layout() { const width=element.clientWidth,height=element.clientHeight;const side=pallet&&isQR?Math.min(width*.62,height*.58):Math.min(width,height)*.85;if(numberGuide){guide.style.top=height*.40+'px';numberGuide.style.width=Math.min(width*.72,300)+'px';numberGuide.style.top=(height*.40+side/2+12)+'px';}guide.style.width=(isQR?side:width*.86)+'px';guide.style.height=(isQR?side:Math.min(height*.65,width*.86/2.5))+'px'; }
    layout(); const observer=typeof ResizeObserver==='function'?new ResizeObserver(layout):null;observer?.observe(element);
    function reset() { clearTimeout(timer); marker.hidden = true; guide.hidden = false; }
    return {
      track(box, width, height) {
        reset(); if (!box || !width || !height || ![box.x0,box.y0,box.x1,box.y1].every(Number.isFinite)) return;
        const x0=Math.max(0,box.x0), y0=Math.max(0,box.y0), x1=Math.min(width,box.x1), y1=Math.min(height,box.y1);
        if(x1<=x0||y1<=y0)return;
        Object.assign(marker.style,{left:x0/width*100+'%',top:y0/height*100+'%',width:(x1-x0)/width*100+'%',height:(y1-y0)/height*100+'%'});
        marker.hidden=false; guide.hidden = !isQR; timer=setTimeout(reset,700);
      }, reset,
      dispose() { observer?.disconnect(); reset(); guide.remove(); numberGuide?.remove(); marker.remove(); element.classList.remove('repo-camera-viewport'); delete element.dataset.recognition; }
    };
  }
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
  async function beep(preferences = {}, onScheduled = () => {}) {
    if (preferences.vibration !== false) { try { window.navigator?.vibrate?.(60); } catch {} }
    if (preferences.sound === false || !audioContext) return;
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
      oscillator.type = 'sine'; oscillator.frequency.setValueAtTime(1600, now);
      gain.gain.setValueAtTime(0, now); gain.gain.linearRampToValueAtTime(.72, now + .008); gain.gain.exponentialRampToValueAtTime(.0001, now + .14);
      oscillator.connect(gain); gain.connect(audioContext.destination);
      const restore = restoreSession;
      oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); restore(); };
      oscillator.start(now); onScheduled(); oscillator.stop(now + .15);
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
    let code = '', count = 0, first = 0, last = 0, lastFrame = null;
    const confirm = (value, now = performance.now(), frame = null) => {
      if (!validate(value)) { code = ''; count = 0; lastFrame=null; return false; }
      if(frame!==null&&frame===lastFrame)return false;lastFrame=frame;
      if (value !== code || now - last > 650 || now - first > 1500) {
        code = value; count = 1; first = last = now; return false;
      }
      if (now - last < 60) return false;
      last = now;
      return ++count >= 3;
    };
    Object.defineProperty(confirm,'startedAt',{get:()=>count?first:null});return confirm;
  }

  // Shared identification engine. Camera/QR decoder and feedback remain in create().
  const ocrBase = (() => { try { return new URL('vendor/ocr/', document.currentScript?.src || new URL('js/ean-scanner.js', window.location.href)).href; } catch { return 'js/vendor/ocr/'; } })();
  let ocrLibrary;
  function loadOCR() {
    if (window.Tesseract) return Promise.resolve(window.Tesseract);
    if (!ocrLibrary) ocrLibrary = new Promise((resolve, reject) => {
      const script = document.createElement('script'); script.src = ocrBase + 'tesseract.min.js';
      script.onload = () => resolve(window.Tesseract); script.onerror = () => { script.remove(); ocrLibrary = null; reject(Error('OCR unavailable')); };
      document.head.append(script);
    });
    return ocrLibrary;
  }
  function palletIdentifier(value) {
    const text = String(value ?? '').trim(); if (/^\d{8}$/.test(text)) return text;
    try {
      const url = new URL(text), here = new URL(window.location.href);
      const production = ['repotools.online', 'www.repotools.online', 'repotools2.onrender.com'];
      const trusted = url.origin === here.origin || (production.includes(url.hostname) && !url.port);
      if (!trusted || !['http:', 'https:'].includes(url.protocol) || url.username || url.password || (!url.pathname.endsWith('/palet.php')&&!/\/q\/\d{8}\/?$/.test(url.pathname))) return null;
      const code = /\/q\/(\d{8})\/?$/.exec(url.pathname)?.[1]||url.searchParams.get('codigo'); return /^\d{8}$/.test(code || '') ? code : null;
    } catch { return null; }
  }
  function ocrConsensus() {
    let code = '', count = 0, lastFrame = null, lastTime = 0;
    return (value, frame, now = performance.now()) => {
      if (!/^\d{8}$/.test(value || '')) { code = ''; count = 0; return false; }
      if (frame === lastFrame) return false;
      lastFrame = frame;
      if (value !== code || now - lastTime > 12000) { code = value; count = 0; }
      lastTime = now; return ++count === 3;
    };
  }
  function ocrCandidate(data) {
    const lines = (data.blocks || []).flatMap(b => (b.paragraphs || []).flatMap(p => p.lines || []));
    const rows = lines.length ? lines : [{ text: data.text, confidence: data.confidence }];
    const valid = rows.filter(r => Number(r.confidence) >= 80 && /^\s*(?:\d\s*){8}$/.test(r.text || '')).map(r => ({ code: r.text.replace(/\s/g, ''), box: r.bbox }));
    return new Set(valid.map(r => r.code)).size === 1 ? valid[0] : null;
  }
  function palletOCR(element, active, accept, status, viewport, canAnalyze = () => true) {
    let disposed = false, timer, worker, creating, busy = false, lastFrame = null, confirm = ocrConsensus();
    const canvas = document.createElement('canvas'), context = canvas.getContext('2d');
    if (!context) return { dispose() {}, resume() {}, reset() {} };

    function schedule() { if (!disposed) timer = setTimeout(read, 550); }
    async function read() {
      if (disposed || busy) return; if (document.hidden || !active() || !canAnalyze()) { schedule(); return; }
      const video = element.querySelector('video');
      if (!video?.videoWidth || video.readyState < 2 || video.currentTime === lastFrame) { schedule(); return; }
      busy = true;
      try {
        if (!worker) {
          const library = await loadOCR(); if (disposed || !active()) return;
          creating = library.createWorker('eng', 1, { workerPath: ocrBase + 'worker.min.js', corePath: ocrBase, langPath: ocrBase.replace(/\/$/, ''), workerBlobURL: false, errorHandler: () => {} });
          worker = await creating;
          if (disposed) { await worker.terminate(); worker = null; return; }
          await worker.setParameters({ tessedit_char_whitelist: '0123456789', tessedit_pageseg_mode: '11' });
        }
        if (!active() || disposed) return;
        const frame = visibleFrame(video), scale = Math.min(1, 1280 / frame.width);
        const stamp = video.currentTime; if (stamp === lastFrame) return; lastFrame = stamp;
        canvas.width = Math.round(frame.width * scale); canvas.height = Math.round(frame.height * scale);
        context.drawImage(video, frame.x, frame.y, frame.width, frame.height, 0, 0, canvas.width, canvas.height);
        const result = await worker.recognize(canvas, {}, { text: true, blocks: true });
        if (disposed || !active()) return;
        const candidate = ocrCandidate(result.data);
        viewport?.track(candidate?.box, canvas.width, canvas.height);
        if (candidate) { status(); if (confirm(candidate.code, stamp)) accept(candidate.code, 'ocr'); }
        else confirm(null, stamp);
      } catch { if (!disposed) { status(true); disposed = true; viewport?.reset(); try { await worker?.terminate(); } catch {} } }
      finally { busy = false; if (disposed) { canvas.width = canvas.height = 0; } else schedule(); }
    }
    schedule();
    return {
      reset() { confirm = ocrConsensus(); viewport?.reset(); },
      resume() { clearTimeout(timer); if (!busy && !disposed) schedule(); },
      dispose() { disposed = true; clearTimeout(timer); viewport?.reset(); if (!busy) { canvas.width = canvas.height = 0; try { worker?.terminate().catch(() => {}); } catch {} } else { creating?.then(w => w.terminate()).catch(() => {}); } }
    };
  }

  function create(elementId, { onCode, onError = () => {}, onStatus = () => {}, onManual, scanContext, identification = 'qr', validateQR, feedbackPreferences = () => ({ sound: document.body?.dataset?.scanSound !== '0', vibration: document.body?.dataset?.scanVibration !== '0' }), canAccept = () => true, keepOpen = () => false, repeatCooldown = 900, rearmRepeated = false, relayBypass = false, onMetric = () => {} }) {
    const element = document.getElementById(elementId);
    const statusArea=element?.parentElement?.querySelector?.(':scope > .scanner-status,:scope > #userScannerStatus')||(elementId==='contenedor'?document.getElementById('mainScannerStatus'):null);if(statusArea){statusArea.classList.add('camera-message-area','scanner-status');statusArea.classList.add('camera-message-overlay');}
    let revision = 0, session = null;
    function metric(current,stage,detail={}){if(stage==='action')current.actionStarted=true;if(stage==='confirmed')current.cycleConfirmed=true;if(stage==='error')current.failed=true;const event={stage,at:performance.now(),elapsed:performance.now()-current.requestedAt,sessionId:current.metricId,cycle:current.metricCycle||0,reader:elementId,kind:current.kind,rapid:Boolean(keepOpen()),mode:current.isQR?'qr':'ean',...detail};for(const listener of [onMetric,...metricListeners]){try{listener(event);}catch{}}}
    const enqueue = cameraTask;
    // Drop concurrent decode requests; never queue old frames behind a slow decoder.
    function serialDecoder(current){
      // Pinned html5-qrcode 2.3.8 adapter; fail back to its native scan loop if unavailable.
      const decoder=current.reader.qrcode;if(current.adapterUnavailable||!decoder)return false;if(current.serialDecoder)return true;
      try{for(const name of ['decodeAsync','decodeRobustlyAsync']){if(typeof decoder[name]!=='function')continue;const decode=decoder[name].bind(decoder);decoder[name]=async input=>{if(current.decodeBusy)throw Error('Decoder busy');current.decodeBusy=true;try{return await decode(input);}finally{current.decodeBusy=false;}};}current.serialDecoder=true;return true;}
      catch{current.adapterUnavailable=true;metric(current,'adapter-unavailable');return false;}
    }
    function cameraControls(current) {
      const track = element.querySelector?.('video')?.srcObject?.getVideoTracks?.()[0];
      if (!document.createElement) return;
      const capabilities = track?.getCapabilities?.() ?? {}, settings = track?.getSettings?.() ?? {};metric(current,'camera-capabilities',{width:settings.width,height:settings.height,zoom:capabilities.zoom?{...capabilities.zoom,current:settings.zoom}:null,focusContinuous:Boolean(capabilities.focusMode?.includes('continuous')),torch:capabilities.torch===true});
      const zoom = capabilities.zoom;
      const minZoom = !current.isQR || current.kind === 'pallet' ? Math.max(1,zoom?.min ?? 1) : zoom?.min ?? 1;
      const hasZoom = Number.isFinite(zoom?.min) && Number.isFinite(zoom?.max) && minZoom>0 && zoom.max > minZoom && Number.isFinite(settings.zoom);
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
          const actual = track.getSettings()[name];metric(current,'camera-control',{name,requested:value,actual});
          if (name === 'torch' ? actual !== value : !Number.isFinite(actual) || Math.abs(actual - value) > Math.max(.1, (zoom?.step || 0) / 2)) throw new Error('Control no disponible');
          return true;
        });
        updates = operation;
        return operation;
      }
      if (hasZoom) {
        const label = document.createElement('div'); label.className = 'ean-camera-zoom';label.setAttribute('role','group');label.setAttribute('aria-label','Zoom de cámara');
        const text = document.createElement('span'); text.className = 'ean-camera-zoom-icon'; text.setAttribute('aria-hidden', 'true');
        text.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10" cy="10" r="6"/><path d="m15 15 5 5M7 10h6m-3-3v6"/></svg>';
        const input = document.createElement('input'); input.type = 'range'; input.setAttribute('aria-label', 'Zoom de cámara');
        input.hidden=false;
        const step = Number.isFinite(zoom.step) && zoom.step > 0 ? zoom.step : .1;
        const snap = value => Math.max(Number(input.min), Math.min(Number(input.max), Number((zoom.min + Math.round((value - zoom.min) / step) * step).toFixed(6))));
        input.min = Math.max(minZoom, Number((zoom.min + Math.ceil((minZoom - zoom.min) / step) * step).toFixed(6)));
        input.max = Number((zoom.min + Math.floor((zoom.max - zoom.min) / step) * step).toFixed(6)); input.step = step;
        let applied = settings.zoom;
        let pending = null, running = false, timer = null, disposed = false;
        const output = document.createElement('span'); output.className = 'ean-camera-zoom-value';
        const shortcuts = document.createElement('div'); shortcuts.className = 'ean-camera-zoom-shortcuts';
        shortcuts.setAttribute('role', 'group'); shortcuts.setAttribute('aria-label', 'Zoom rápido');
        let minus,plus;
        const render = value => {
          input.value = value;if(minus)minus.disabled=value<=Number(input.min);if(plus)plus.disabled=value>=Number(input.max);
          const display = Number(value.toFixed(2)).toString();
          output.textContent = display + '×';
          input.setAttribute('aria-valuetext', display + ' aumentos');
          shortcuts.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(Math.abs(Number(button.dataset.zoom) - value) < .001)));
        };
        const flush = async () => {
          timer = null;
          if (disposed || !active() || running || pending === null) return;
          running = true;
          const value = pending; pending = null;
          try {
            if (Math.abs(value - applied) > .000001 && await apply('zoom', value)) applied = track.getSettings().zoom;
          } catch {
            if (!disposed && active() && pending === null) {
              render(applied);
              onStatus('No pudimos ajustar el zoom. Podés seguir escaneando.');
            }
          } finally {
            running = false;
            if (!disposed && active()) {
              if (pending !== null) timer = setTimeout(flush, 60);
              else render(applied);
            }
          }
        };
        const updateZoom = () => {
          if (disposed || !active()) return;
          pending = snap(Number(input.value));
          render(pending);
          if (!running && timer === null) timer = setTimeout(flush, 60);
        };
        input.addEventListener('input', updateZoom);
        input.addEventListener('change', updateZoom);
        label.addEventListener('contextmenu',event=>event.preventDefault());
        label.addEventListener('touchmove', event => event.stopPropagation(), { passive: true });
        for (const value of [1, 2, 5]) {
          if (value < Number(input.min) || value > zoom.max || Math.abs(snap(value) - value) > .001) continue;
          const button = document.createElement('button'); button.type = 'button'; button.dataset.zoom = value;
          button.textContent = value + '×'; button.setAttribute('aria-label', 'Zoom ' + value + ' aumentos');
          button.addEventListener('click', () => { input.value = value; updateZoom(); }); shortcuts.append(button);
        }
        render(applied);
        // Pallet scanning excludes ultrawide zoom, including an inherited 0.5x setting.
        if(applied<Number(input.min)){input.value=Number(input.min);updateZoom();}
        let holdDelay,holdTimer;const holdController=new AbortController();
        const stopHold=()=>{clearTimeout(holdDelay);clearInterval(holdTimer);};
        const adjust=direction=>{if(disposed||!active())return;input.value=snap(Number(input.value)+direction*Math.max(.1,step));updateZoom();};
        function zoomButton(direction){const b=document.createElement('button');b.type='button';b.className='ean-zoom-step';b.textContent=direction<0?'−':'+';b.setAttribute('aria-label',direction<0?'Reducir zoom':'Aumentar zoom');b.addEventListener('pointerdown',e=>{if(e.button!==0||b.disabled)return;e.preventDefault();stopHold();b.setPointerCapture?.(e.pointerId);adjust(direction);holdDelay=setTimeout(()=>holdTimer=setInterval(()=>adjust(direction),90),300);});b.addEventListener('click',e=>{if(e.detail===0)adjust(direction);});b.addEventListener('lostpointercapture',stopHold);return b;}
        minus=zoomButton(-1);plus=zoomButton(1);for(const event of ['pointerup','pointercancel','blur'])window.addEventListener(event,stopHold,{signal:holdController.signal});render(applied);
        let pinch=null;const distance=e=>Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);
        element.addEventListener('touchstart',e=>{if(e.touches.length===2&&!e.target.closest('.ean-camera-controls')){pinch={distance:distance(e),zoom:Number(input.value)};e.preventDefault();}},{passive:false,signal:holdController.signal});
        element.addEventListener('touchmove',e=>{if(pinch&&e.touches.length===2){e.preventDefault();input.value=snap(pinch.zoom*distance(e)/Math.max(1,pinch.distance));updateZoom();}},{passive:false,signal:holdController.signal});
        for(const event of ['touchend','touchcancel'])element.addEventListener(event,()=>{pinch=null;},{signal:holdController.signal});
        label.append(plus,input,output,minus); controls.append(label);
        if (shortcuts.childElementCount > 1) controls.append(shortcuts);
        current.cancelZoom = () => { stopHold();holdController.abort();disposed = true; pending = null; if (timer !== null) clearTimeout(timer); };
      }
      if (capabilities.torch === true) {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'ean-camera-light';
        let enabled = settings.torch === true;
        button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h12v4l-4 5v9h-4v-9L6 7V3Zm0 4h12M10 15h4"/><path class="torch-off-mark" d="m3 3 18 18"/></svg>';
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
      current.disposeControls = () => { current.cancelZoom?.(); controls.remove(); };
      element.append(controls);
    }
    function background(current, accept) {
      if (!document.createElement || !element.querySelector) return;
      const canvas = document.createElement('canvas'), context = canvas.getContext('2d');
      // Adaptador para html5-qrcode 2.3.8: reutiliza su decodificador sobre el canvas,
      // sin convertir cada fotograma en archivos ni crear URLs de imágenes.
      const decoder = current.reader.qrcode;
      if (!context || typeof decoder?.decodeRobustlyAsync !== 'function' || !serialDecoder(current)) return;
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
      let variant = 0, probeAttempt = 0, lastProfile = null, running = false, disposed = false, lastFrame=null,lastQuagga=-Infinity,attempts=0;
      const lowPower=Number(window.navigator?.hardwareConcurrency||8)<=4;
      if(typeof current.reader.pause==='function'){try{current.reader.pause(false);if(current.reader.scannerPausedUiElement)current.reader.scannerPausedUiElement.style.display='none';current.exclusive=true;}catch{}}
      let detailButton,detailTimer,manualRequest=false;
      function offerDetail(){
        if(!current.accepting||session!==current)return;
        const video=element.querySelector('video'),frame=video?.videoWidth?visibleFrame(video):null;
        if(!frame||!canAccept()||document.hidden){detailTimer=setTimeout(offerDetail,500);return;}
        if(Math.max(frame.width,frame.height)<=(lowPower?640:960))return;
        detailButton=document.createElement('button');detailButton.type='button';detailButton.className='ean-camera-detail';
        detailButton.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8V3h5m8 0h5v5M3 16v5h5m8 0h5v-5"/><circle cx="12" cy="12" r="4"/></svg><span>Analizar detalle</span>';
        detailButton.addEventListener('click',()=>{if(!current.accepting||!canAccept())return;current.requestDetail=true;detailButton.disabled=true;detailButton.setAttribute('aria-busy','true');detailButton.querySelector('span').textContent='Analizando…';metric(current,'detail-request');});element.append(detailButton);
      }
      if(current.isQR)detailTimer=setTimeout(offerDetail,1800);
      serialDecoder(current);
      const dispose = () => { if (disposed) return; disposed = true;clearTimeout(detailTimer);detailButton?.remove(); canvas.width = canvas.height = probe.width = probe.height = 0; };
      current.disposeBackground = () => {clearTimeout(detailTimer);detailButton?.remove();clearTimeout(current.backgroundTimer); if (!running) dispose(); };
      async function analyze() {
        if (!current.accepting || session !== current || current.token !== revision) { dispose(); return; }
        // No supplemental JPEG/OCR work while the page is in the background.
        if (document.hidden || !canAccept()) { current.backgroundTimer = setTimeout(analyze, 500); return; }
        const video = element.querySelector('video');
        const started = performance.now();
        if (video?.videoWidth && video.videoHeight && video.readyState>=2 && video.currentTime!==lastFrame && !current.decodeBusy && (current.exclusive||started - (current.lastPrimaryDetection ?? -Infinity) > 180)) {
          running = true;lastFrame=video.currentTime;
          try {
            const frame = visibleFrame(video);
            manualRequest=Boolean(current.requestDetail);const detailed=current.requestDetail||(++attempts%(lowPower&&current.isQR?3:4)===(lowPower&&current.isQR?2:0)&&started-(current.cameraReadyAt??current.requestedAt)>(lowPower?350:500));current.requestDetail=false;
            const adaptive = current.isQR ? { angle: 0, crop: 1, detailed } : lastProfile ?? (probeAttempt++ % 2 === 0 ? locate(video, frame) : null);
            if (!current.isQR && window.Quagga?.decodeSingle && !adaptive && started-lastQuagga>1200) {
              // Localiza barras y su orientación en una sola imagen. El canvas
              // y las cajas del localizador permanecen fuera de la interfaz.
              const scale = Math.min(1, 1920 / Math.max(frame.width, frame.height));
              canvas.width = Math.round(frame.width * scale); canvas.height = Math.round(frame.height * scale);
              context.drawImage(video, frame.x, frame.y, frame.width, frame.height, 0, 0, canvas.width, canvas.height);
              let result;lastQuagga=started;
              try {
                result = await window.Quagga.decodeSingle({ src: canvas.toDataURL('image/jpeg', .92), inputStream: { size: 0 }, locate: true, locator: { halfSample: false, patchSize: 'small' }, decoder: { readers: ['ean_reader', 'ean_8_reader'] }, numOfWorkers: 0 });
              } catch { /* El decodificador alternativo sigue disponible. */ }
              if (validEAN(result?.codeResult?.code)) {
                if (Array.isArray(result.box) && result.box.length >= 4 && result.box.every(p=>Array.isArray(p)&&p.length>=2&&p.every(Number.isFinite))) {
                  current.viewport?.track({x0:Math.min(...result.box.map(p=>p[0])),y0:Math.min(...result.box.map(p=>p[1])),x1:Math.max(...result.box.map(p=>p[0])),y1:Math.max(...result.box.map(p=>p[1]))},canvas.width,canvas.height);
                }
                accept(result.codeResult.code, 'background',lastFrame);
                return;
              }
            }
            // Recorre orientaciones fuera de la vista del usuario; una sola tarea a la vez.
            const profile = adaptive ?? profiles[variant++ % profiles.length], radians = profile.angle * Math.PI / 180;
            // Conserva los píxeles originales de las barras pequeñas. El recorte
            // central reduce trabajo sin reducir resolución; la vista completa
            // también busca códigos que estén fuera del centro.
            const sourceWidth = Math.round(frame.width * profile.crop), sourceHeight = Math.round(frame.height * profile.crop);
            const limit=current.isQR?(profile.detailed?1920:lowPower?640:960):(detailed?1920:lowPower?960:1280);
            const scale = Math.min(1, limit / Math.max(sourceWidth, sourceHeight));
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
              const code = result.text;metric(current,'decoded',{source:'background',width:canvas.width,height:canvas.height,detail:Boolean(profile.detailed),costMs:performance.now()-started});
              if (current.validate(code)) { lastProfile = profile; accept(code, 'background',lastFrame); } else lastProfile = null;
            }
          } catch { lastProfile = null; }
          finally {
            running = false;if(detailButton&&manualRequest){detailButton.disabled=false;detailButton.removeAttribute('aria-busy');detailButton.querySelector('span').textContent='Analizar detalle';}
            if (!current.accepting || session !== current || current.token !== revision) dispose();
            else {const cost=performance.now()-started;metric(current,'analysis',{costMs:cost,width:canvas.width,height:canvas.height});current.backgroundTimer=setTimeout(analyze,Math.max(50,Math.min(600,cost*(lastProfile&&!current.isQR ? .4 : lowPower ? 2 : 1.2))));}
          }
          return;
        }
        if (!current.accepting || session !== current || current.token !== revision) { dispose(); return; }
        current.backgroundTimer = setTimeout(analyze, 60);
      }
      current.backgroundTimer = setTimeout(analyze, 100);
    }
    async function release(current) {
      if (!current) return;
      if (!current.cleanup) current.cleanup = (async () => {
        metric(current,'closed',{outcome:current.failed?'error':current.cycleConfirmed?'confirmed':'cancelled'});
        current.accepting = false;current.frameObserver?.disconnect();current.frameAbort?.abort(); current.ocr?.dispose(); current.disposeBackground?.(); current.disposeControls?.(); clearTimeout(current.progressTimer);
        try { await current.starting; } catch {}
        try { if (current.reader.isScanning) await current.reader.stop(); }
        finally {
          element.querySelector?.('video')?.srcObject?.getTracks?.().forEach(track=>track.stop());
          current.viewport?.dispose(); element.classList.remove('repo-camera-viewport'); if(element.dataset)delete element.dataset.recognition;
          if(statusArea&&statusArea.parentElement===element)element.before(statusArea);
          try { current.reader.clear(); } finally { if (cameraOwner?.current === current) cameraOwner = null; }
        }
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
      const requestedAt=performance.now();clearScanPhoto();
      // Se habilita el audio en el toque que abre la cámara, antes de esperar
      // permisos o fotogramas, para que el primer escaneo también tenga sonido.
      if (feedbackPreferences().sound !== false) prepareSound();
      const token = ++revision;
      if (session) session.accepting = false;
      return enqueue(async () => {
        await cameraOwner?.release();
        await release(session);
        if (token !== revision) return false;
        if(statusArea&&statusArea.parentElement===element)element.before(statusArea);
        element.classList.remove('ean-confirmed');
        element.classList.remove('ean-detecting');
        if (!window.Html5Qrcode || !window.Html5QrcodeSupportedFormats) throw new Error('No se pudo cargar el lector. Intenta nuevamente.');
        const formats = window.Html5QrcodeSupportedFormats;
        const isQR = format === 'qr';
        const kind = typeof identification === 'function' ? identification() : identification;
        const normalize = isQR && kind === 'pallet' ? palletIdentifier : value => value;
        const validate = isQR ? code => kind === 'pallet' ? Boolean(palletIdentifier(code)) : validateQR ? Boolean(validateQR(code)) : typeof code === 'string' && code.trim().length > 0 && code.length <= 2048 : validEAN;
        if (element.dataset) { element.classList.add('repo-camera-viewport'); element.dataset.recognition = isQR ? 'qr' : 'ean'; }
        const reader = new window.Html5Qrcode(elementId, { formatsToSupport: isQR ? [formats.QR_CODE] : [formats.EAN_13, formats.EAN_8], verbose: false });
        const current = { reader, metricId:globalThis.crypto?.randomUUID?.()||String(requestedAt)+':'+token, accepting: true, token, isQR, kind, validate, requestedAt, confirm: confirmation(validate) };metric(current,'camera-request');
        serialDecoder(current);session = current; cameraOwner = { current, release: () => release(current) };
        const accept = (code, source = 'camera', recognizedFrame = null) => {
          code = normalize(code);
          if (code === null) { if (current.accepting && session === current && token === revision && canAccept()) { current.confirm(null); current.ocr?.reset(); onStatus('Este QR no corresponde a un palet de Repotools.'); } return; }
          if (isQR && kind === 'pallet' && current.lastDelivered === code) return;
          if(current.lastDelivered===code){const elapsed=Date.now()-current.lastDeliveredSeen;current.lastDeliveredSeen=Date.now();if(elapsed<repeatCooldown)return;if(rearmRepeated)current.lastDelivered='';}
          if (!current.accepting || session !== current || token !== revision || !canAccept()) return;
          // Con object-fit: cover se confirma únicamente la zona que el usuario
          // ve. El lector oculto usa ese mismo recorte, sin bandas negras.
          const video = element.querySelector?.('video');
          if (video?.videoWidth && document.createElement && typeof reader.qrcode?.decodeRobustlyAsync === 'function') {
            const frame = visibleFrame(video);
            if (!current.exclusive && source==='camera' && !isQR && (frame.width < video.videoWidth - 2 || frame.height < video.videoHeight - 2)) return;
          }
          if (!validate(code)) { current.confirm(code); return; }
          if (source === 'camera') current.lastPrimaryDetection = performance.now();
          metric(current,'decoded',{source});
          const confirmed = source === 'ocr' || isQR || current.confirm(code,performance.now(),recognizedFrame??(video?.videoWidth&&video.readyState>=2?video.currentTime:null));
          if (!confirmed) {
            element.classList.add('ean-detecting'); onStatus('Detectando código…'); clearTimeout(current.progressTimer);
            current.progressTimer = setTimeout(() => { if (current.accepting && token === revision) { element.classList.remove('ean-detecting'); onStatus('Centra un solo código dentro del recuadro.'); } }, 700);
            return;
          }
          metric(current,'confirmed',{source,confirmationMs:source==='ocr'?null:isQR?0:Math.max(0,performance.now()-(current.confirm.startedAt??performance.now()))});
          current.accepting = false; current.ocr?.reset(); clearTimeout(current.progressTimer); current.disposeBackground?.(); element.classList.remove('ean-detecting');
          element.classList.add('ean-confirmed'); onStatus('Código confirmado.');
          if(keepOpen()){
            current.lastDelivered=code;current.lastDeliveredSeen=Date.now();
            Promise.resolve().then(()=>{metric(current,'feedback-request');beep(feedbackPreferences(),()=>metric(current,'tone-scheduled'));if(!isQR)rememberScanPhoto(code,element);metric(current,'action');return onCode(code);}).then(()=>metric(current,'action-complete')).catch(error=>{if(token===revision&&session===current){onError(error);}}).then(()=>new Promise(resolve=>setTimeout(resolve,350))).then(()=>{
              if(token!==revision||session!==current)return;
              current.confirm=confirmation(validate);current.accepting=true;current.cycleConfirmed=false;current.metricCycle=(current.metricCycle||0)+1;metric(current,'ready');element.classList.remove('ean-confirmed');
              try{background(current,accept);current.ocr?.resume();}catch{}
            }).catch(error=>{if(token===revision)onError(error);});
            return;
          }
          enqueue(async () => {
            if(token!==revision)return;
            metric(current,'feedback-request');beep(feedbackPreferences(),()=>metric(current,'tone-scheduled'));if(!isQR)rememberScanPhoto(code,element); await new Promise(resolve => setTimeout(resolve, 180)); await release(current);
            if (token === revision) Promise.resolve().then(() => { if (token === revision) {metric(current,'action');return onCode(code);} }).then(()=>{if(current.actionStarted)metric(current,'action-complete');}).catch(error => { if (token === revision) onError(error); });
          }).catch(onError);
        };
        onStatus('Abriendo cámara…');
        try {
          if(typeof MutationObserver==='function'&&element.querySelector){current.frameAbort=new AbortController();const observed=()=>{const video=element.querySelector('video');if(!video||video===current.observedVideo)return;current.observedVideo=video;const first=()=>{if(!current.firstFrame){current.firstFrame=true;metric(current,'first-frame',{width:video.videoWidth,height:video.videoHeight});current.frameObserver?.disconnect();}};if(video.readyState>=2)first();else video.addEventListener('loadeddata',first,{once:true,signal:current.frameAbort.signal});};current.frameObserver=new MutationObserver(observed);current.frameObserver.observe(element,{childList:true,subtree:true});observed();}
          current.starting = reader.start({ facingMode: 'environment' }, {
            fps: 15,
            videoConstraints: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 }, frameRate:{ideal:24} },
            disableFlip: true,
            qrbox: (width, height) => isQR ? { width: Math.max(50, Math.floor(Math.min(width, height) * .85)), height: Math.max(50, Math.floor(Math.min(width, height) * .85)) } : { width: Math.max(50, Math.floor(width * .94)), height: Math.max(50, Math.floor(height * .85)) }
          }, code => accept(code, 'camera'), () => {});
          await current.starting;current.cameraReadyAt=performance.now();metric(current,'camera-open');const firstVideo=element.querySelector?.('video');if(!current.firstFrame&&firstVideo?.readyState>=2){current.firstFrame=true;metric(current,'first-frame',{width:firstVideo.videoWidth,height:firstVideo.videoHeight});}
          if (token !== revision) { await release(current); return false; }
          // Solicita enfoque continuo solo si la cámara lo admite, sin retrasar
          // la lectura ni fallar en navegadores que no exponen este control.
          try {
            const track = element.querySelector?.('video')?.srcObject?.getVideoTracks?.()[0];
            if (track?.getCapabilities?.().focusMode?.includes('continuous')) {
              current.cameraUpdates = track.applyConstraints({ ...track.getConstraints(), advanced: [{ focusMode: 'continuous' }] }).catch(() => {});
            }
          } catch {}
          current.viewport = cameraViewport(element, isQR, kind === 'pallet');
          if(statusArea)element.append(statusArea);
          try { cameraControls(current); } catch { /* Los controles opcionales no bloquean el escáner. */ }
          try { background(current, accept); } catch { /* La cámara sigue leyendo si el análisis auxiliar no está disponible. */ }
          if (isQR && kind === 'pallet') current.ocr = palletOCR(element, () => !document.hidden && performance.now()-(current.cameraReadyAt??current.requestedAt)>1500 && current.accepting && session === current && token === revision && canAccept(), accept, unavailable => { if (unavailable) onStatus('OCR no disponible. Escaneá el QR o ingresá el número.'); else { element.classList.add('ean-detecting'); onStatus('Confirmando número del palet…'); clearTimeout(current.progressTimer); current.progressTimer = setTimeout(() => { if (current.accepting && token === revision) { element.classList.remove('ean-detecting'); onStatus('Centrá el QR o el número de 8 dígitos.'); } }, 1800); } }, current.viewport,()=>!current.decodeBusy);
          onStatus(isQR && kind === 'pallet' ? 'Centrá el QR o el número de 8 dígitos.' : 'Centra un solo código dentro del recuadro.');
          return true;
        } catch (error) {
          metric(current,'error');
          current.accepting = false;
          await release(current);
          if (token !== revision) return false;
          if(statusArea)element.append(statusArea);
          throw error;
        }
      });
    }
    page?.onLeave(stop);
    const normalCode=onCode;if(!relayBypass&&elementId!=='userReader')onCode=code=>window.RepoScanMethods?window.RepoScanMethods.route(code,normalCode,elementId):normalCode(code);const raw={start,stop};return !relayBypass&&window.RepoScanMethods?window.RepoScanMethods.wrap(elementId,{onCode,onError,onStatus,onManual,scanContext},raw):raw;
  }
  function rawCreate(elementId,options){return create(elementId,{...options,relayBypass:true});}
  window.RepoEANScanner = { observe(listener){if(typeof listener!=='function')throw TypeError('Observer required');metricListeners.add(listener);return()=>metricListeners.delete(listener);}, create, rawCreate, validEAN, confirmation, palletIdentifier, ocrConsensus, ocrCandidate, takeScanPhoto, clearScanPhoto };
})();
