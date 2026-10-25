(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  // Camera engines are only needed after a user opens a reader; SW keeps them available offline.
  const libraryLoads=window.RepoScannerLibraryLoads??=new Map();
  function cameraLibrary(src,ready){
    if(ready())return Promise.resolve();
    if(libraryLoads.has(src))return libraryLoads.get(src);
    const promise=new Promise((resolve,reject)=>{
      const script=globalThis.document.createElement('script');script.src=new URL(src,globalThis.document.baseURI).href;
      const finish=error=>{clearTimeout(timer);script.onload=script.onerror=null;if(error){script.remove();libraryLoads.delete(src);reject(error);}else resolve();};
      const timer=setTimeout(()=>finish(Error('No pudimos cargar el lector. Volvé a intentar.')),15000);
      script.onload=()=>finish(ready()?null:Error('No pudimos cargar el lector. Volvé a intentar.'));
      script.onerror=()=>finish(Error('No pudimos cargar el lector. Revisá la conexión y volvé a intentar.'));
      globalThis.document.head.append(script);
    });
    libraryLoads.set(src,promise);return promise;
  }
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
    function layout() {
      const width=element.clientWidth,height=element.clientHeight;
      let side=Math.min(width,height)*.85;
      if(numberGuide){
        // Keep the QR square and the optional number strip in separate rows.
        const showNumber=height>=280&&width>=220,topPadding=Math.min(48,height*.22),bottomPadding=Math.min(68,height*.3),available=height-topPadding-bottomPadding-(showNumber?52:0);
        side=Math.max(1,Math.min(width*.62,height*.58,available));
        const top=topPadding+Math.max(0,(available-side)/2);
        guide.style.top=top+side/2+'px';numberGuide.hidden=!showNumber;
        numberGuide.style.width=side+'px';numberGuide.style.top=top+side+8+'px';
        numberGuide.querySelector('span').style.fontSize=Math.min(24,side/8)+'px';
      }
      guide.style.width=(isQR?side:width*.86)+'px';guide.style.height=(isQR?side:Math.min(height*.65,width*.86/2.5))+'px';
    }
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

  let audioContext = window.RepoScannerAudioContext || null, audioPrimed = false;
  function prepareSound() {
    try {
      try { if (window.navigator?.audioSession) window.navigator.audioSession.type = 'ambient'; } catch {}
      const Context = window.AudioContext || window.webkitAudioContext;
      if (!Context) return;
      if (!audioContext || audioContext.state === 'closed') { audioContext = new Context(); window.RepoScannerAudioContext = audioContext; audioPrimed = false; }
      if (audioContext.state !== 'running') audioPrimed = false;
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
        try {
        const previous = session.type;
        let restored = false;
        session.type = 'playback';
        restoreSession = () => {
          if (restored) return; restored = true;
          try { if (session.type === 'playback') session.type = previous; } catch {}
        };
        } catch {}
      }
      if (audioContext.state !== 'running') {
        let timer;
        try { await Promise.race([audioContext.resume(),new Promise(resolve=>{timer=setTimeout(resolve,200);})]); }
        finally { clearTimeout(timer); }
      }
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
  function palletFrame(video,element){
    const frame=visibleFrame(video),v=video.getBoundingClientRect?.(),g=element.querySelector?.('.repo-pallet-qr-guide')?.getBoundingClientRect?.();
    if(!v?.width||!v.height||!g?.width||!g.height)return null;
    const number=element.querySelector?.('.repo-pallet-number-guide'),n=number&&!number.hidden?number.getBoundingClientRect?.():null;
    const left=Math.max(v.left,g.left),top=Math.max(v.top,g.top),right=Math.min(v.right,g.right),bottom=Math.min(v.bottom,Math.max(g.bottom,n?.bottom||g.bottom));
    if(right<=left||bottom<=top)return null;
    return {x:frame.x+(left-v.left)/v.width*frame.width,y:frame.y+(top-v.top)/v.height*frame.height,width:(right-left)/v.width*frame.width,height:(bottom-top)/v.height*frame.height};
  }
  function palletSelection(deliver,ambiguous,now=()=>performance.now()){
    let pending=null,blockedUntil=0,candidate=null;
    return {
      ocr(code){pending={code,at:now()};},
      qr(codes,capturedAt=now(),positions=null){
        const time=now(),unique=[...new Set(codes.map(palletIdentifier).filter(Boolean))];
        if(time-capturedAt>1500)return;
        if(unique.length>1||(unique.length===1&&pending&&time-pending.at<1500&&pending.code!==unique[0])){pending=null;candidate=null;blockedUntil=time+400;ambiguous();return;}
        if(time<blockedUntil)return;
        if(unique.length===1&&positions){const symbol=positions.find(p=>palletIdentifier(p.text)===unique[0]);if(!symbol||Math.abs(symbol.x-.5)>.20||Math.abs(symbol.y-.5)>.30){pending=null;candidate=null;ambiguous();return;}}
        if(!unique.length)candidate=null;
        if(unique.length===1){if(candidate?.code===unique[0]&&capturedAt>candidate.frame&&time-candidate.at<=700){pending=null;candidate=null;deliver(unique[0],'scoped-qr');}else candidate={code:unique[0],frame:capturedAt,at:time};}
        else if(pending&&capturedAt>=pending.at){const value=pending;pending=null;if(time-value.at<1500)deliver(value.code,'scoped-ocr');}
      },conflict(){pending=null;candidate=null;blockedUntil=now()+400;ambiguous();},reset(){pending=null;candidate=null;blockedUntil=0;}
    };
  }
  function validEAN(code) {
    if (typeof code !== 'string' || !/^(?:\d{8}|\d{13})$/.test(code) || /^0+$/.test(code)) return false;
    let sum = 0;
    for (let i = code.length - 2, weight = 3; i >= 0; i--, weight = 4 - weight) sum += Number(code[i]) * weight;
    return (10 - sum % 10) % 10 === Number(code.at(-1));
  }

  // Confirma el mismo EAN en fotogramas separados y dentro de una ventana corta.
  function confirmation(validate = validEAN, timing = () => 650) {
    const gap = () => Math.max(650,Math.min(1000,Number(timing())||650));
    let code = '', count = 0, first = 0, last = 0, lastFrame = null;
    const confirm = (value, now = performance.now(), frame = null) => {
      if (!validate(value)) { code = ''; count = 0; lastFrame=null; return false; }
      if(frame!==null&&frame===lastFrame)return false;lastFrame=frame;
      if (value !== code || now - last > gap() || now - first > gap()*2+200) {
        code = value; count = 1; first = last = now; return false;
      }
      if (now - last < 60) return false;
      last = now;
      return ++count >= 3;
    };
    confirm.progress=(now=performance.now())=>count&&now-last<=gap()&&now-first<=gap()*2+200?Math.min(count,3):0;
    Object.defineProperty(confirm,'startedAt',{get:()=>count?first:null});return confirm;
  }

  // Shared identification engine. Camera/QR decoder and feedback remain in create().
  const qrRescueURL=(()=>{try{return new URL('qr-rescue-worker.js?v=bbcb731b62c9',document.currentScript?.src||new URL('js/ean-scanner.js',window.location.href)).href;}catch{return 'js/qr-rescue-worker.js?v=bbcb731b62c9';}})();
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
  function ocrConsensus(getZoom=()=>1) {
    const valid=value=>/^\d{8}$/.test(value||'');
    const tolerance=()=>{const z=Number(getZoom()),zoom=Number.isFinite(z)&&z>=1?z:1;return Math.min(4000,2000+(zoom-1)*500);};
    const windowMs=()=>tolerance()*2+1000;
    let code='',count=0,first=0,last=0,lastFrame=null;
    // OCR may lose the digits between clear frames while digital zoom magnifies hand movement.
    const read=(value,frame,now=performance.now())=>{
      if(!valid(value))return false;
      if(frame!==null&&frame!==undefined&&frame===lastFrame)return false;
      lastFrame=frame;
      if(value!==code||now<last||now-last>tolerance()||now-first>windowMs()){code=value;count=1;first=last=now;return false;}
      if(now-last<60)return false;
      last=now;return ++count>=3;
    };
    read.progress=(now=performance.now())=>count&&now>=last&&now-last<=tolerance()&&now-first<=windowMs()?Math.min(count,3):0;
    read.tolerance=tolerance;return read;
  }
  function ocrReadings(data){const lines=(data.blocks||[]).flatMap(b=>(b.paragraphs||[]).flatMap(p=>p.lines||[])),rows=lines.length?lines:[{text:data.text,confidence:data.confidence}];return {rows,numbers:rows.filter(r=>/^\s*(?:\d\s*){8}$/.test(r.text||'')).map(r=>r.text.replace(/\s/g,''))};}
  function ocrCandidate(data) {
    const {rows,numbers}=ocrReadings(data);
    if(new Set(numbers).size>1)return null;
    const valid = rows.filter(r => Number(r.confidence) >= 80 && /^\s*(?:\d\s*){8}$/.test(r.text || '')).map(r => ({ code: r.text.replace(/\s/g, ''), box: r.bbox }));
    return new Set(valid.map(r => r.code)).size === 1 ? valid[0] : null;
  }
function palletNumberRegion(image){
  const {data,width:w,height:h}=image,gray=new Uint8Array(w*h),hist=new Uint32Array(256);for(let i=0;i<gray.length;i++){gray[i]=Math.round((data[i*4]+data[i*4+1]+data[i*4+2])/3);hist[gray[i]]++;}
  let sum=0,white=255;for(let i=0;i<256;i++){sum+=hist[i];if(sum>=gray.length*.98){white=i;break;}}
  let best=null;const rowsFound=[];const mask=new Uint8Array(w*h),queue=new Int32Array(w*h);
  for(const fraction of [.55,.68,.8]){
    const threshold=Math.max(30,Math.min(165,white*fraction));for(let i=0;i<mask.length;i++)mask[i]=gray[i]<threshold?1:0;
    const parts=[];for(let i=0;i<mask.length;i++){if(!mask[i])continue;let head=0,tail=1;queue[0]=i;mask[i]=0;let x0=w,x1=0,y0=h,y1=0;
      while(head<tail){const p=queue[head++],x=p%w,y=(p/w)|0;x0=Math.min(x0,x);x1=Math.max(x1,x);y0=Math.min(y0,y);y1=Math.max(y1,y);if(x&&mask[p-1]){mask[p-1]=0;queue[tail++]=p-1;}if(x+1<w&&mask[p+1]){mask[p+1]=0;queue[tail++]=p+1;}if(y&&mask[p-w]){mask[p-w]=0;queue[tail++]=p-w;}if(y+1<h&&mask[p+w]){mask[p+w]=0;queue[tail++]=p+w;}}
      const width=x1-x0+1,height=y1-y0+1;if(height>=6&&height<h*.17&&width>=2&&width<height*2.4&&height<width*5&&tail>=8&&tail/(width*height)>.13)parts.push({x0,x1,y0,y1,width,height,x:(x0+x1)/2,y:(y0+y1)/2});
    }
    if(parts.length>500)continue;parts.sort((a,b)=>a.x-b.x);
    const baselines=new Set();for(const seed of parts)for(const slopeGuess of [0,-.8,-.6,-.4,-.2,.2,.4,.6,.8]){const intercept=Math.round((seed.y-slopeGuess*seed.x)/Math.max(3,seed.height*.3)),key=slopeGuess+':'+Math.round(seed.height/8)+':'+intercept;if(baselines.has(key))continue;baselines.add(key);const neighbors=parts.filter(p=>Math.abs(p.y-seed.y-slopeGuess*(p.x-seed.x))<seed.height*.45&&p.height>seed.height*.62&&p.height<seed.height*1.6);
      for(let start=0;start+8<=neighbors.length;start++){const row=neighbors.slice(start,start+8),x0=row[0].x0,x1=row[7].x1,width=x1-x0,heights=row.map(p=>p.height).sort((a,b)=>a-b),height=heights[4];if(width/height<4||width/height>16)continue;
        if(start>0&&row[0].x0-neighbors[start-1].x1<height*1.3||start+8<neighbors.length&&neighbors[start+8].x0-row[7].x1<height*1.3)continue;const gaps=row.slice(1).map((p,i)=>p.x0-row[i].x1);if(gaps.some(g=>g<-(Math.abs(slopeGuess)>.1?height*.35:0)||g>height*1.3))continue;
        const mx=row.reduce((s,p)=>s+p.x,0)/8,my=row.reduce((s,p)=>s+p.y,0)/8,xx=row.reduce((s,p)=>s+(p.x-mx)**2,0),slope=row.reduce((s,p)=>s+(p.x-mx)*(p.y-my),0)/xx,error=Math.sqrt(row.reduce((s,p)=>s+(p.y-my-slope*(p.x-mx))**2,0)/8);if(Math.abs(slope)>.85||error>height*.18)continue;
        rowsFound.push({y:my,height});
        // A QR-like ink field above the eight aligned glyphs identifies our printed layout.
        let ink=0,total=0;for(let y=Math.max(0,Math.round(my-height*5));y<my-height;y++)for(let x=x0;x<=x1;x++){ink+=gray[y*w+x]<threshold?1:0;total++;}const density=ink/total;if(density<.08||density>.7)continue;
        const score=error/height+Math.abs(width/height-8)*.015;if(!best||score<best.score)best={x0:Math.max(0,x0-height*.4),y0:Math.max(0,Math.min(...row.map(p=>p.y0))-height*.4),x1:Math.min(w,x1+height*.4),y1:Math.min(h,Math.max(...row.map(p=>p.y1))+height*.4),angle:-Math.atan(slope)*180/Math.PI,centerX:mx,centerY:my,glyphHeight:height,score};
      }
    }
  }
  if(best&&rowsFound.some(row=>row.height>(best.y1-best.y0)*.4&&Math.abs(row.y-(best.y0+best.y1)/2)>row.height*1.5))return null;
  return best;
}

  function palletOCR(element, active, accept, status, viewport, canAnalyze = () => true,onAnalysis=()=>{}) {
    const getZoom=()=>{try{return element.querySelector('video')?.srcObject?.getVideoTracks?.()[0]?.getSettings?.().zoom||1;}catch{return 1;}};
    let disposed = false, timer, worker, creating, busy = false, lastFrame = null, confirm = ocrConsensus(getZoom),pageMode='11',lockedRegion=null;
    const canvas = document.createElement('canvas'), context = canvas.getContext('2d'),lineCanvas=document.createElement('canvas'),lineContext=lineCanvas.getContext('2d');
    if (!context) return { dispose() {}, resume() {}, reset() {} };

    const source=document.createElement('canvas'),sourceContext=source.getContext('2d'),probe=document.createElement('canvas'),probeContext=probe.getContext('2d',{willReadFrequently:true});let angleIndex=0,lockedAngle=null,nextDelay=550;
    function normalizeDimFrame(){
      if(!probeContext||!sourceContext)return;
      const scale=Math.min(1,160/Math.max(source.width,source.height));probe.width=Math.max(1,Math.round(source.width*scale));probe.height=Math.max(1,Math.round(source.height*scale));probeContext.drawImage(source,0,0,probe.width,probe.height);
      const values=probeContext.getImageData(0,0,probe.width,probe.height).data,hist=new Uint32Array(256);for(let i=0;i<values.length;i+=4)hist[Math.round((values[i]+values[i+1]+values[i+2])/3)]++;
      let count=0,white=255;for(let i=0;i<256;i++){count+=hist[i];if(count>=probe.width*probe.height*.95){white=i;break;}}
      if(white<60||white>=180)return;
      const gain=Math.min(2.8,255/white),image=sourceContext.getImageData(0,0,source.width,source.height),pixels=image.data;for(let i=0;i<pixels.length;i+=4){pixels[i]*=gain;pixels[i+1]*=gain;pixels[i+2]*=gain;}sourceContext.putImageData(image,0,0);
    }
    // Ink moments estimate the number-line angle; uncertain images keep the sweep fallback.
    function orientation(){
      if(!probeContext)return 0;
      const scale=Math.min(1,320/Math.max(source.width,source.height));
      probe.width=Math.max(1,Math.round(source.width*scale));probe.height=Math.max(1,Math.round(source.height*scale));
      probeContext.drawImage(source,0,0,probe.width,probe.height);
      const data=probeContext.getImageData(0,0,probe.width,probe.height).data;
      const levels=new Uint32Array(256);for(let i=0;i<data.length;i+=4)levels[Math.round((data[i]+data[i+1]+data[i+2])/3)]++;
      let cumulative=0,low=0,high=255;for(let i=0;i<256;i++){cumulative+=levels[i];if(cumulative>=probe.width*probe.height*.1){low=i;break;}}cumulative=0;for(let i=0;i<256;i++){cumulative+=levels[i];if(cumulative>=probe.width*probe.height*.9){high=i;break;}}const inkLimit=Math.max(5,Math.min(100,low-(high-low)*.6));
      let n=0,x=0,y=0,xx=0,yy=0,xy=0;
      for(let j=0;j<probe.height;j++)for(let i=0;i<probe.width;i++){
        const k=(j*probe.width+i)*4;
        if((data[k]+data[k+1]+data[k+2])/3>inkLimit)continue;
        n++;x+=i;y+=j;xx+=i*i;yy+=j*j;xy+=i*j;
      }
      if(n<20||n>probe.width*probe.height*.45)return 0;
      xx-=x*x/n;yy-=y*y/n;xy-=x*y/n;
      return Math.hypot(xx-yy,2*xy)/(xx+yy)>.55?-Math.atan2(2*xy,xx-yy)*90/Math.PI:0;
    }
    function clearCanvases(){for(const surface of [canvas,source,probe,lineCanvas])surface.width=surface.height=0;}
    function schedule(delay=nextDelay) { if (!disposed) timer = setTimeout(read, delay); }
    async function read() {
      if (disposed || busy) return; if (document.hidden || !active() || !canAnalyze()) { schedule(550); return; }
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
        const frame = palletFrame(video,element);if(!frame)return;const scale = Math.min(1, 1280 / frame.width,1920/Math.max(frame.width,frame.height));
        const stamp = video.currentTime; if (stamp === lastFrame) return; lastFrame = stamp;
        source.width=Math.round(frame.width*scale);source.height=Math.round(frame.height*scale);sourceContext.drawImage(video,frame.x,frame.y,frame.width,frame.height,0,0,source.width,source.height);
        normalizeDimFrame();
        const fullWidth=source.width,fullHeight=source.height;let cropX=0,cropY=0,cropScale=1,region=null,regionRatio=1;
        if(probeContext){const ratio=Math.min(1,640/Math.max(source.width,source.height));if(lockedRegion&&performance.now()-lockedRegion.at<650&&lockedRegion.width===fullWidth&&lockedRegion.height===fullHeight){region=lockedRegion.region;}else{probe.width=Math.round(source.width*ratio);probe.height=Math.round(source.height*ratio);probeContext.drawImage(source,0,0,probe.width,probe.height);region=palletNumberRegion(probeContext.getImageData(0,0,probe.width,probe.height));}if(region){regionRatio=ratio;cropX=region.x0/ratio;cropY=region.y0/ratio;const width=(region.x1-region.x0)/ratio,height=(region.y1-region.y0)/ratio;cropScale=Math.min(3,600/width);canvas.width=Math.round(width*cropScale);canvas.height=Math.round(height*cropScale);context.drawImage(source,cropX,cropY,width,height,0,0,canvas.width,canvas.height);source.width=canvas.width;source.height=canvas.height;sourceContext.drawImage(canvas,0,0);}}
        const mode=region?'7':'11';if(mode!==pageMode){await worker.setParameters({tessedit_pageseg_mode:mode});pageMode=mode;}if(disposed||!active())return;
        const offsets=[0,180,90,-90,30,-30,60,-60],angle=region?.angle??lockedAngle??orientation()+offsets[angleIndex++%offsets.length],radians=angle*Math.PI/180,cos=Math.cos(radians),sin=Math.sin(radians);
        canvas.width=region?source.width:Math.ceil(Math.abs(source.width*cos)+Math.abs(source.height*sin));canvas.height=region?source.height:Math.ceil(Math.abs(source.width*sin)+Math.abs(source.height*cos));context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);context.save();context.translate(canvas.width/2,canvas.height/2);context.rotate(radians);context.drawImage(source,-source.width/2,-source.height/2);context.restore();
        let ocrSurface=canvas,lineOffset=0;if(region&&lineContext){const centerX=(region.centerX/regionRatio-cropX)*cropScale-source.width/2,centerY=(region.centerY/regionRatio-cropY)*cropScale-source.height/2,lineHeight=Math.ceil(region.glyphHeight/regionRatio*cropScale*1.7);lineOffset=Math.max(0,Math.round(canvas.height/2+centerX*sin+centerY*cos-lineHeight/2));lineCanvas.width=canvas.width;lineCanvas.height=Math.max(1,Math.min(lineHeight,canvas.height-lineOffset));lineContext.drawImage(canvas,0,lineOffset,canvas.width,lineCanvas.height,0,0,lineCanvas.width,lineCanvas.height);ocrSurface=lineCanvas;}
        const analysisStarted=performance.now();
        const result = await worker.recognize(ocrSurface, {rotateAuto:false}, { text: true, blocks: true });
        if (disposed || !active()) return;
        if(new Set(ocrReadings(result.data).numbers).size>1){confirm(null,stamp);onAnalysis({matched:false,ambiguous:true,costMs:performance.now()-analysisStarted});accept(null,'ocr-ambiguous');return;}
        const candidate = ocrCandidate(result.data);onAnalysis({angle,numberRegion:Boolean(region),matched:Boolean(candidate),zoom:Number(getZoom())||1,toleranceMs:confirm.tolerance(),costMs:performance.now()-analysisStarted});
        let centeredCandidate=false;
        if(candidate?.box){
          const box=candidate.box,points=[[box.x0,box.y0],[box.x1,box.y0],[box.x1,box.y1],[box.x0,box.y1]].map(([x,y])=>{x-=canvas.width/2;y+=lineOffset-canvas.height/2;return {x:x*cos+y*sin+source.width/2,y:-x*sin+y*cos+source.height/2};});
          const localBox={x0:Math.max(0,cropX+Math.min(...points.map(p=>p.x))/cropScale),y0:Math.max(0,cropY+Math.min(...points.map(p=>p.y))/cropScale),x1:Math.min(fullWidth,cropX+Math.max(...points.map(p=>p.x))/cropScale),y1:Math.min(fullHeight,cropY+Math.max(...points.map(p=>p.y))/cropScale)},visible=visibleFrame(video),sx=frame.width/fullWidth,sy=frame.height/fullHeight;
          centeredCandidate=Math.abs((localBox.x0+localBox.x1)/2/fullWidth-.5)<=.20;
          viewport?.track({x0:frame.x-visible.x+localBox.x0*sx,y0:frame.y-visible.y+localBox.y0*sy,x1:frame.x-visible.x+localBox.x1*sx,y1:frame.y-visible.y+localBox.y1*sy},visible.width,visible.height);
        }else viewport?.reset();
        if(candidate&&!centeredCandidate){confirm(null,stamp);lockedRegion=null;lockedAngle=null;nextDelay=350;accept(null,'ocr-off-center');return;}
        if (candidate) { lockedRegion=region?{region,width:fullWidth,height:fullHeight,at:performance.now()}:null;lockedAngle=angle;nextDelay=80;const confirmed=confirm(candidate.code, stamp);status(false,confirm.progress()); if (confirmed) accept(candidate.code, 'ocr'); }
        else {lockedRegion=null;lockedAngle=null;nextDelay=350;confirm(null, stamp);}
      } catch { if (!disposed) { status(true); disposed = true; viewport?.reset(); try { await worker?.terminate(); } catch {} } }
      finally { busy = false; if (disposed) { clearCanvases(); } else schedule(); }
    }
    schedule();
    return {
      hasCandidate(){return !disposed&&lockedAngle!==null;},
      tolerance(){return confirm.tolerance();},
      reset() { confirm = ocrConsensus(getZoom);lockedRegion=null;lockedAngle=null;angleIndex=0;nextDelay=550; viewport?.reset(); },
      resume() { clearTimeout(timer); if (!busy && !disposed) schedule(); },
      dispose() { disposed = true; clearTimeout(timer); viewport?.reset(); if (!busy) { clearCanvases(); try { worker?.terminate().catch(() => {}); } catch {} } else { creating?.then(w => w.terminate()).catch(() => {}); } }
    };
  }

  function create(elementId, { onCode, onError = () => {}, onStatus = () => {}, onManual, scanContext, identification = 'qr', validateQR, allowZoom = true, feedbackPreferences = () => ({ sound: document.body?.dataset?.scanSound !== '0', vibration: document.body?.dataset?.scanVibration !== '0' }), canAccept = () => true, keepOpen = () => false, repeatCooldown = 900, rearmRepeated = false, relayBypass = false, onMetric = () => {} }) {
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
      const hasZoom = allowZoom && Number.isFinite(zoom?.min) && Number.isFinite(zoom?.max) && minZoom>0 && zoom.max > minZoom && Number.isFinite(settings.zoom);
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
      const scopedPallet=current.isQR&&current.kind==='pallet';
      if (!context || (!scopedPallet&&(typeof decoder?.decodeRobustlyAsync !== 'function' || !serialDecoder(current)))) return;
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
      // Optional rescue for a bright label seen as a trapezoid. Native decoding stays first.
      let rectMask=null,rectQueue=null;
      function rectifyLabel(video,frame){
        if(!probeContext)return false;
        const ratio=Math.min(1,320/Math.max(frame.width,frame.height));probe.width=Math.round(frame.width*ratio);probe.height=Math.round(frame.height*ratio);
        probeContext.drawImage(video,frame.x,frame.y,frame.width,frame.height,0,0,probe.width,probe.height);
        const image=probeContext.getImageData(0,0,probe.width,probe.height),pixels=image.data,w=probe.width,h=probe.height,mask=rectMask?.length===w*h?rectMask:(rectMask=new Uint8Array(w*h)),queue=rectQueue?.length===w*h?rectQueue:(rectQueue=new Int32Array(w*h));let best=null;
        const highlights=new Uint32Array(256);for(let i=0;i<pixels.length;i+=4)highlights[Math.min(pixels[i],pixels[i+1],pixels[i+2])]++;
        let accumulated=0,bright=255;for(let i=0;i<256;i++){accumulated+=highlights[i];if(accumulated>=mask.length*.995){bright=i;break;}}const paperLimit=Math.max(70,Math.min(225,bright-12));
        for(let i=0;i<mask.length;i++){const k=i*4;mask[i]=Math.min(pixels[k],pixels[k+1],pixels[k+2])>paperLimit?1:0;}
        for(let i=0;i<mask.length;i++){
          if(!mask[i])continue;let head=0,tail=1;queue[0]=i;mask[i]=0;let minSum=Infinity,maxSum=-Infinity,minDiff=Infinity,maxDiff=-Infinity,quad=[];
          while(head<tail){const index=queue[head++],x=index%w,y=(index/w)|0,sum=x+y,diff=x-y;
            if(sum<minSum){minSum=sum;quad[0]={x,y};}if(diff>maxDiff){maxDiff=diff;quad[1]={x,y};}if(sum>maxSum){maxSum=sum;quad[2]={x,y};}if(diff<minDiff){minDiff=diff;quad[3]={x,y};}
            if(x&&mask[index-1]){mask[index-1]=0;queue[tail++]=index-1;}
            if(x+1<w&&mask[index+1]){mask[index+1]=0;queue[tail++]=index+1;}
            if(y&&mask[index-w]){mask[index-w]=0;queue[tail++]=index-w;}
            if(y+1<h&&mask[index+w]){mask[index+w]=0;queue[tail++]=index+w;}
          }
          if(tail<200||tail>mask.length*.75||best&&tail<=best.area)continue;
          const edges=quad.map((q,j)=>Math.hypot(q.x-quad[(j+1)%4].x,q.y-quad[(j+1)%4].y));
          if(Math.min(...edges)<18||Math.max(...edges)>Math.min(...edges)*5)continue;
          best={area:tail,quad};
        }
        if(!best)return false;
        const [a,b,c,d]=best.quad,dx1=b.x-c.x,dx2=d.x-c.x,dx3=a.x-b.x+c.x-d.x,dy1=b.y-c.y,dy2=d.y-c.y,dy3=a.y-b.y+c.y-d.y,det=dx1*dy2-dx2*dy1;
        if(Math.abs(det)<1)return false;
        const g=(dx3*dy2-dx2*dy3)/det,k=(dx1*dy3-dx3*dy1)/det,A=b.x-a.x+g*b.x,B=d.x-a.x+k*d.x,D=b.y-a.y+g*b.y,E=d.y-a.y+k*d.y;
        const scale=Math.min(1,960/Math.max(frame.width,frame.height)),sw=Math.round(frame.width*scale),sh=Math.round(frame.height*scale);
        canvas.width=sw;canvas.height=sh;context.drawImage(video,frame.x,frame.y,frame.width,frame.height,0,0,sw,sh);
        const src=context.getImageData(0,0,sw,sh),size=Math.max(160,Math.min(lowPower?480:640,Math.round(Math.max(...best.quad.map((q,j)=>Math.hypot(q.x-best.quad[(j+1)%4].x,q.y-best.quad[(j+1)%4].y)))/ratio*scale))),out=context.createImageData(size,size);
        for(let y=0;y<size;y++)for(let x=0;x<size;x++){
          const u=x/(size-1),v=y/(size-1),den=g*u+k*v+1,sx=Math.round((A*u+B*v+a.x)/den/w*sw),sy=Math.round((D*u+E*v+a.y)/den/h*sh),dest=(y*size+x)*4;
          if(!Number.isFinite(sx+sy)||sx<0||sy<0||sx>=sw||sy>=sh){out.data[dest]=out.data[dest+1]=out.data[dest+2]=255;}else{const from=(sy*sw+sx)*4;out.data[dest]=src.data[from];out.data[dest+1]=src.data[from+1];out.data[dest+2]=src.data[from+2];}out.data[dest+3]=255;
        }
        canvas.width=canvas.height=size;context.putImageData(out,0,0);return true;
      }
      let rescueWorker,rescueReady=false,rescueFailed=false,rescuePending=null,rescueInitTimer,rescueSequence=0,rescueLast=-Infinity;
      function stopRescue(){clearTimeout(rescueInitTimer);rescueWorker?.terminate();rescueWorker=null;rescueReady=false;if(rescuePending){clearTimeout(rescuePending.timer);rescuePending.resolve(null);rescuePending=null;}}
      function startRescue(){if(rescueWorker||rescueFailed)return;if(typeof Worker!=='function'){rescueFailed=true;return;}try{rescueWorker=new Worker(qrRescueURL);rescueInitTimer=setTimeout(()=>{rescueFailed=true;stopRescue();},12000);rescueWorker.onmessage=event=>{const value=event.data;if(value?.type==='ready'){clearTimeout(rescueInitTimer);rescueReady=true;metric(current,'qr-rescue-ready');return;}if(value?.type==='unavailable'){rescueFailed=true;stopRescue();return;}if(value?.type!=='result'||!rescuePending||value.id!==rescuePending.id)return;const pending=rescuePending;rescuePending=null;clearTimeout(pending.timer);const codes=Array.isArray(value.codes)?[...new Set(value.codes.filter(code=>typeof code==='string'&&current.validate(code)))]:[];metric(current,'qr-rescue-analysis',{costMs:performance.now()-pending.started,matched:codes.length===1,ambiguous:codes.length>1});if(scopedPallet&&pending.scoped&&current.accepting&&session===current&&!document.hidden&&canAccept()){if(value.verified===true)current.selection.qr(codes,pending.started,Array.isArray(value.symbols)?value.symbols:[]);else onStatus('No pudimos comprobar la etiqueta. Volvé a centrarla.');}pending.resolve(codes.length===1?{text:codes[0]}:null);};rescueWorker.onerror=event=>{event.preventDefault?.();rescueFailed=true;stopRescue();};}catch{rescueFailed=true;stopRescue();}}
      const rescueWarmup=current.isQR?setTimeout(()=>{if(current.accepting&&session===current&&!document.hidden&&canAccept())startRescue();},scopedPallet?0:450):null;
      async function decodeImage(surface){const original=current.isQR&&rescueReady?surface.getContext('2d').getImageData(0,0,surface.width,surface.height):null;try{return await decoder.decodeRobustlyAsync(surface);}catch(error){if(!current.isQR||!current.accepting||session!==current||document.hidden||!canAccept()||performance.now()-(current.cameraReadyAt??current.requestedAt)<650)throw error;startRescue();if(!rescueReady||rescuePending||performance.now()-rescueLast<350)throw error;rescueLast=performance.now();const image=original||surface.getContext('2d').getImageData(0,0,surface.width,surface.height),id=++rescueSequence;const result=await new Promise(resolve=>{rescuePending={id,resolve,started:performance.now(),timer:setTimeout(()=>{rescueFailed=true;stopRescue();},2000)};try{rescueWorker.postMessage({type:'decode',id,width:image.width,height:image.height,buffer:image.data.buffer},[image.data.buffer]);}catch{rescueFailed=true;stopRescue();}});if(result&&current.accepting&&session===current&&!document.hidden&&canAccept())return result;throw error;}}
      let lastRectify=-Infinity;
      let variant = 0, probeAttempt = 0, lastProfile = null, running = false, disposed = false, lastFrame=null,lastQuagga=-Infinity,quaggaCandidate=false,attempts=0;
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
        detailButton.addEventListener('click',()=>{if(!current.accepting||!canAccept())return;startRescue();current.requestDetail=true;detailButton.disabled=true;detailButton.setAttribute('aria-busy','true');detailButton.querySelector('span').textContent='Analizando…';metric(current,'detail-request');});element.append(detailButton);
      }
      if(current.isQR)detailTimer=setTimeout(offerDetail,1800);
      serialDecoder(current);
      const dispose = () => { if (disposed) return; disposed = true;clearTimeout(rescueWarmup);stopRescue();rectMask=rectQueue=null;clearTimeout(detailTimer);detailButton?.remove(); canvas.width = canvas.height = probe.width = probe.height = 0; };
      current.disposeBackground = () => {clearTimeout(rescueWarmup);stopRescue();clearTimeout(detailTimer);detailButton?.remove();clearTimeout(current.backgroundTimer); if (!running) dispose(); };
      async function analyze() {
        if (!current.accepting || session !== current || current.token !== revision) { dispose(); return; }
        // No supplemental JPEG/OCR work while the page is in the background.
        if (document.hidden || !canAccept()) { current.backgroundTimer = setTimeout(analyze, 500); return; }
        const video = element.querySelector('video');
        const started = performance.now();
        if (video?.videoWidth && video.videoHeight && video.readyState>=2 && video.currentTime!==lastFrame && !current.decodeBusy && (current.exclusive||started - (current.lastPrimaryDetection ?? -Infinity) > 180)) {
          running = true;lastFrame=video.currentTime;
          try {
            const frame = scopedPallet?palletFrame(video,element):visibleFrame(video);
            if(!frame)return;
            if(scopedPallet){
              startRescue();
              if(!rescueReady){if(rescueFailed)onStatus('No pudimos verificar el QR. Ingresá el número con el teclado.');return;}
              const scale=Math.min(1,(current.requestDetail?1920:1280)/Math.max(frame.width,frame.height));current.requestDetail=false;
              canvas.width=Math.max(1,Math.round(frame.width*scale));canvas.height=Math.max(1,Math.round(frame.height*scale));
              context.drawImage(video,frame.x,frame.y,frame.width,frame.height,0,0,canvas.width,canvas.height);
              const image=context.getImageData(0,0,canvas.width,canvas.height),id=++rescueSequence;current.decodeBusy=true;
              try{await new Promise(resolve=>{rescuePending={id,resolve,scoped:true,started:performance.now(),timer:setTimeout(()=>{rescueFailed=true;stopRescue();},2000)};try{rescueWorker.postMessage({type:'decode',id,multiple:true,width:image.width,height:image.height,buffer:image.data.buffer},[image.data.buffer]);}catch{rescueFailed=true;stopRescue();}});}finally{current.decodeBusy=false;}
              return;
            }
            if(current.isQR&&!current.ocr?.hasCandidate?.()&&started-(current.cameraReadyAt??current.requestedAt)>900&&started-lastRectify>1100){
              lastRectify=started;
              if(rectifyLabel(video,frame)){metric(current,'perspective-attempt',{width:canvas.width,height:canvas.height});const result=await decodeImage(canvas);if(current.validate(result.text))accept(result.text,'background',lastFrame);return;}
            }
            manualRequest=Boolean(current.requestDetail);const detailed=current.requestDetail||(++attempts%(lowPower&&current.isQR?3:4)===(lowPower&&current.isQR?2:0)&&started-(current.cameraReadyAt??current.requestedAt)>(lowPower?350:500));current.requestDetail=false;
            const adaptive = current.isQR ? { angle: 0, crop: 1, detailed } : lastProfile ?? (probeAttempt++ % 2 === 0 ? locate(video, frame) : null);
            // A successful fallback needs new frames inside the adaptive confirmation window.
            // Orientation hints must not starve this independent decoder.
            if (!current.isQR && window.Quagga?.decodeSingle && started-lastQuagga>(quaggaCandidate?100:900)) {
              // Localiza barras y su orientación en una sola imagen. El canvas
              // y las cajas del localizador permanecen fuera de la interfaz.
              const scale = Math.min(1, 1920 / Math.max(frame.width, frame.height));
              canvas.width = Math.round(frame.width * scale); canvas.height = Math.round(frame.height * scale);
              context.drawImage(video, frame.x, frame.y, frame.width, frame.height, 0, 0, canvas.width, canvas.height);
              let result;lastQuagga=started;
              try {
                result = await window.Quagga.decodeSingle({ src: canvas.toDataURL('image/jpeg', .92), inputStream: { size: 0 }, locate: true, locator: { halfSample: false, patchSize: 'small' }, decoder: { readers: ['ean_reader', 'ean_8_reader'] }, numOfWorkers: 0 });
              } catch { /* El decodificador alternativo sigue disponible. */ }
              quaggaCandidate=validEAN(result?.codeResult?.code);
              if (quaggaCandidate) {
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
              const result = await decodeImage(canvas);
              const code = result.text;metric(current,'decoded',{source:'background',width:canvas.width,height:canvas.height,detail:Boolean(profile.detailed),costMs:performance.now()-started});
              if (current.validate(code)) { lastProfile = profile; accept(code, 'background',lastFrame); } else lastProfile = null;
            }
          } catch { lastProfile = null; }
          finally {
            running = false;if(detailButton&&manualRequest){detailButton.disabled=false;detailButton.removeAttribute('aria-busy');detailButton.querySelector('span').textContent='Analizar detalle';}
            if (!current.accepting || session !== current || current.token !== revision) dispose();
            else {const cost=performance.now()-started;current.analysisCost=current.analysisCost===undefined?cost:current.analysisCost*.5+cost*.5;metric(current,'analysis',{costMs:cost,width:canvas.width,height:canvas.height});current.backgroundTimer=setTimeout(analyze,Math.max(50,Math.min(quaggaCandidate?100:600,cost*(lastProfile&&!current.isQR ? .4 : lowPower ? 2 : 1.2))));}
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
        current.accepting = false;current.selection?.reset();current.frameObserver?.disconnect();current.frameAbort?.abort(); current.ocr?.dispose(); current.disposeBackground?.(); current.disposeControls?.(); clearTimeout(current.progressTimer);
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
      const isQR = format === 'qr';
      const ready=window.Html5Qrcode&&window.Html5QrcodeSupportedFormats?Promise.resolve():(onStatus('Preparando cámara…'),cameraLibrary('js/vendor/html5-qrcode-2.3.8.min.js',()=>Boolean(window.Html5Qrcode&&window.Html5QrcodeSupportedFormats)));
      // Loading a shared engine must not keep Stop (or closing a modal) waiting for the network.
      return ready.then(()=>enqueue(async () => {
        await cameraOwner?.release();
        await release(session);
        if (token !== revision) return false;
        if(statusArea&&statusArea.parentElement===element)element.before(statusArea);
        element.classList.remove('ean-confirmed');
        element.classList.remove('ean-detecting');
        if(!isQR)cameraLibrary('js/vendor/quagga-1.12.1.min.js',()=>Boolean(window.Quagga?.decodeSingle)).catch(()=>{});
        const formats = window.Html5QrcodeSupportedFormats;
        const kind = typeof identification === 'function' ? identification() : identification;
        const normalize = isQR && kind === 'pallet' ? palletIdentifier : value => value;
        const validate = isQR ? code => kind === 'pallet' ? Boolean(palletIdentifier(code)) : validateQR ? Boolean(validateQR(code)) : typeof code === 'string' && code.trim().length > 0 && code.length <= 2048 : code => validEAN(code)||Boolean(window.RepoTeamInvitation?.token(code));
        if (element.dataset) { element.classList.add('repo-camera-viewport'); element.dataset.recognition = isQR ? 'qr' : 'ean'; }
        const reader = new window.Html5Qrcode(elementId, { formatsToSupport: isQR ? [formats.QR_CODE] : [formats.EAN_13, formats.EAN_8, formats.QR_CODE], verbose: false });
        const current = { reader, metricId:globalThis.crypto?.randomUUID?.()||String(requestedAt)+':'+token, accepting: true, token, isQR, kind, validate, requestedAt, confirm: confirmation(validate,()=>isQR?650:((current.analysisCost||0)+100)*2) };metric(current,'camera-request');
        serialDecoder(current);session = current; cameraOwner = { current, release: () => release(current) };
        const accept = (code, source = 'camera', recognizedFrame = null) => {
          const scopedVideo=element.querySelector?.('video');
          if(source==='ocr-ambiguous'||source==='ocr-off-center'){current.selection.conflict();return;}
          if(isQR&&kind==='pallet'&&scopedVideo?.videoWidth){
            if(source==='camera'||source==='background')return;
            if(source==='ocr'){current.selection.ocr(code);return;}
          }
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
          const confirmed = source === 'ocr' || source === 'scoped-ocr' || isQR || current.confirm(code,performance.now(),recognizedFrame??(video?.videoWidth&&video.readyState>=2?video.currentTime:null));
          if (!confirmed) {
            element.classList.add('ean-detecting'); onStatus('Detectando código…'); clearTimeout(current.progressTimer);
            current.progressTimer = setTimeout(() => { if (current.accepting && token === revision) { element.classList.remove('ean-detecting'); onStatus('Centra un solo código dentro del recuadro.'); } }, 700);
            return;
          }
          if(source!=='ocr'&&source!=='scoped-ocr'&&typeof CustomEvent==='function'&&typeof window.dispatchEvent==='function')window.dispatchEvent(new CustomEvent('repotools:accepted-code',{detail:{code,isQR,reader:elementId}}));
          metric(current,'confirmed',{source,confirmationMs:source==='ocr'||source==='scoped-ocr'?null:isQR?0:Math.max(0,performance.now()-(current.confirm.startedAt??performance.now()))});
          current.accepting = false; current.ocr?.reset(); clearTimeout(current.progressTimer); current.disposeBackground?.(); element.classList.remove('ean-detecting');
          element.classList.add('ean-confirmed'); onStatus(source==='ocr'||source==='scoped-ocr'?'Número confirmado · 3/3':'Código confirmado.');
          if(keepOpen()){
            current.lastDelivered=code;current.lastDeliveredSeen=Date.now();
            Promise.resolve().then(()=>{metric(current,'feedback-request');beep(feedbackPreferences(),()=>metric(current,'tone-scheduled'));if(!isQR&&validEAN(code))rememberScanPhoto(code,element);metric(current,'action');return onCode(code);}).then(()=>metric(current,'action-complete')).catch(error=>{if(token===revision&&session===current){onError(error);}}).then(()=>new Promise(resolve=>setTimeout(resolve,350))).then(()=>{
              if(token!==revision||session!==current)return;
              current.confirm=confirmation(validate,()=>isQR?650:((current.analysisCost||0)+100)*2);current.accepting=true;current.cycleConfirmed=false;current.metricCycle=(current.metricCycle||0)+1;metric(current,'ready');element.classList.remove('ean-confirmed');
              try{background(current,accept);current.ocr?.resume();}catch{}
            }).catch(error=>{if(token===revision)onError(error);});
            return;
          }
          enqueue(async () => {
            if(token!==revision)return;
            metric(current,'feedback-request');await beep(feedbackPreferences(),()=>metric(current,'tone-scheduled'));if(!isQR&&validEAN(code))rememberScanPhoto(code,element); await release(current);
            if (token === revision) Promise.resolve().then(() => { if (token === revision) {metric(current,'action');return onCode(code);} }).then(()=>{if(current.actionStarted)metric(current,'action-complete');}).catch(error => { if (token === revision) onError(error); });
          }).catch(onError);
        };
        current.selection=palletSelection(accept,()=>{current.ocr?.reset();element.classList.add('ean-detecting');onStatus('Centrá una sola etiqueta dentro de la guía.');});
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
          if (isQR && kind === 'pallet') current.ocr = palletOCR(element, () => !document.hidden && performance.now()-(current.cameraReadyAt??current.requestedAt)>(Number(window.navigator?.hardwareConcurrency||8)<=4?900:650) && current.accepting && session === current && token === revision && canAccept(), accept, (unavailable,count) => { if (unavailable) onStatus('OCR no disponible. Escaneá el QR o ingresá el número.'); else { element.classList.add('ean-detecting'); onStatus('Número detectado · '+count+'/3'); clearTimeout(current.progressTimer); current.progressTimer = setTimeout(() => { if (current.accepting && token === revision) { element.classList.remove('ean-detecting'); onStatus('Centrá el QR o el número de 8 dígitos.'); } }, current.ocr?.tolerance?.()||2000); } }, current.viewport,()=>!current.decodeBusy,detail=>metric(current,'ocr-analysis',detail));
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
      })).catch(error=>{if(token!==revision)return false;throw error;});
    }
    page?.onLeave(stop);
    const originalCode=onCode;const normalCode=code=>window.RepoTeamInvitation?.token(code)?window.RepoTeamInvitation.open(code):originalCode(code);if(!relayBypass&&elementId!=='userReader')onCode=code=>window.RepoScanMethods?window.RepoScanMethods.route(code,normalCode,elementId):normalCode(code);const raw={start,stop};return !relayBypass&&window.RepoScanMethods?window.RepoScanMethods.wrap(elementId,{onCode,onError,onStatus,onManual,scanContext},raw):raw;
  }
  function rawCreate(elementId,options){return create(elementId,{...options,relayBypass:true});}
  window.RepoEANScanner = { observe(listener){if(typeof listener!=='function')throw TypeError('Observer required');metricListeners.add(listener);return()=>metricListeners.delete(listener);}, create, rawCreate, validEAN, confirmation, palletIdentifier, palletFrame, palletSelection, ocrConsensus, ocrCandidate, takeScanPhoto, clearScanPhoto };
})();
