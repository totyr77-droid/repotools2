const CACHE='repotools-shell-v40';
const ICON_ASSETS=['css/vendor/fontawesome-6.7.2/css/fontawesome.min.css','css/vendor/fontawesome-6.7.2/css/solid.min.css','css/vendor/fontawesome-6.7.2/css/brands.min.css','css/vendor/fontawesome-6.7.2/webfonts/fa-solid-900.woff2','css/vendor/fontawesome-6.7.2/webfonts/fa-brands-400.woff2','img/onboarding-logo.png','img/Repotools_Logo_Pack_Blanco/06_horizontal_blanco_transparente/repotools-horizontal-blanco-480w.png'];
const ASSETS=['js/ux-polish.js','css/ux-polish.css','js/vendor/JsBarcode.ean-upc-3.11.6.min.js','js/one-hand-mode.js','css/one-hand-mode.css','js/mobile-navigation.js','css/mobile-navigation.css',...ICON_ASSETS,'offline.html','js/connectivity.js','css/connectivity.css','js/offline-sync.js','js/action-notices.js','css/action-notices.css','css/device-workspace.css','css/responsive.css','css/motion.css','js/motion.js','css/offline.css','js/offline-page.js','js/stock-offline.js','js/sync-status.js','css/sync-status.css','js/stock-utils.js','js/vendor/html5-qrcode-2.3.8.min.js','js/vendor/quagga-1.12.1.min.js','js/ean-scanner.js','js/dialog-scroll.js','css/ean-scanner.css'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(ASSETS)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('repotools-shell-')&&key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  const url=new URL(event.request.url),base=new URL(self.registration.scope);
  if(url.origin!==base.origin||event.request.method!=='GET')return;
  const path=url.pathname.slice(base.pathname.length);
  if(event.request.mode==='navigate'&&['','index.php','index.html','stock.php','biblioteca.php','offline.html','palet.php','stock-compartido.php','cuenta.php','reportes.php','tops.php'].includes(path)){
    event.respondWith((async()=>{const control=new AbortController(),timer=setTimeout(()=>control.abort(),8000);try{const response=await fetch(event.request,{signal:control.signal});if(response.status>=500)return await caches.match(new URL('offline.html',base).href)||response;return response;}catch{return await caches.match(new URL('offline.html',base).href)||Response.error();}finally{clearTimeout(timer);}})());return;
  }
  if(path.startsWith('js/vendor/ocr/')) { event.respondWith(caches.open('repotools-ocr-v1').then(async cache => { const old=await cache.match(event.request); if(old)return old; const response=await fetch(event.request); if(response.ok)await cache.put(event.request,response.clone()); return response; })); return; }
  if(ICON_ASSETS.includes(path)){event.respondWith(caches.match(event.request,{ignoreSearch:true}).then(cached=>cached||fetch(event.request)));return;}
  if(ASSETS.includes(path))event.respondWith(fetch(event.request).catch(()=>caches.match(event.request,{ignoreSearch:true})));
});

let preparingOCR;
self.addEventListener('message',event=>{if(event.data?.type!=='prepare-ocr')return;if(!preparingOCR)preparingOCR=(async()=>{const cache=await caches.open('repotools-ocr-v1');for(const name of ['tesseract.min.js','worker.min.js','tesseract-core-lstm.wasm.js','tesseract-core-simd-lstm.wasm.js','eng.traineddata.gz']){const url=new URL('js/vendor/ocr/'+name,self.registration.scope).href;if(await cache.match(url))continue;const response=await fetch(url);if(!response.ok)throw Error('OCR preparation failed');await cache.put(url,response);}})().catch(()=>{}).finally(()=>preparingOCR=null);event.waitUntil(preparingOCR);});
