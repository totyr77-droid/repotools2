importScripts('js/push-worker.js');
const CACHE='repotools-shell-release-179';
const ICON_ASSETS=['img/brand/repotools-beta-favicon.svg','img/brand/repotools-beta-favicon-32.png','css/vendor/fontawesome-6.7.2/css/fontawesome.min.css','css/vendor/fontawesome-6.7.2/css/solid.min.css','css/vendor/fontawesome-6.7.2/css/brands.min.css','css/vendor/fontawesome-6.7.2/webfonts/fa-solid-900.woff2','css/vendor/fontawesome-6.7.2/webfonts/fa-brands-400.woff2','img/brand/apple-touch-icon-180x180.png','img/brand/repotools-horizontal-blanco-1024w.png','img/brand/repotools-horizontal-fondo-violeta.svg','img/brand/repotools-horizontal-transparente.svg','img/brand/repotools-icon-violeta-16x16.png','img/brand/repotools-icon-violeta-32x32.png','img/brand/repotools-isotipo-fondo-violeta.svg','img/brand/repotools-isotipo-transparente.svg','img/brand/repotools-maskable-192x192.png','img/brand/repotools-maskable-512x512.png','img/brand/repotools-pwa-192x192.png','img/brand/repotools-pwa-512x512.png'];
const ASSETS=['css/push-notice.css','js/push-worker.js','js/push-settings.js','js/home-location.js','css/home-location.css','js/team-pallet-requests.js','js/inventory-goods.js','js/stock-goods.js','css/inventory-goods.css','js/team-shortcut.js','css/team-shortcut.css','js/team-invitation.js','js/user-qr.js','css/user-qr.css','js/vendor/qrcode.min.js','js/branch-manager.js','css/branch-manager.css','css/profile-showcase.css','js/profile-showcase.js','js/achievement-events.js','css/branch-team.css','js/branch-team.js','js/team-inventory.js','js/stock-live.js','js/stock-session.js','js/qr-rescue-worker.js','js/vendor/zxing-wasm-3.1.5/reader.js','js/vendor/zxing-wasm-3.1.5/zxing_reader.wasm','js/scanner-performance.js','js/date-keypad.js','js/vendor/lz-string-1.4.4.min.js','js/pwa-install.js','js/account-sharing.js','js/share-links.js','js/pallet-label-pdf.js','js/connection-position.js','js/label-files.js','js/product-crop.js','css/product-crop.css','js/ux-polish.js','css/ux-polish.css','js/vendor/JsBarcode.ean-upc-3.11.6.min.js','js/one-hand-mode.js','css/one-hand-mode.css','js/mobile-navigation.js','css/mobile-navigation.css',...ICON_ASSETS,'offline.html','js/connectivity.js','css/connectivity.css','js/offline-sync.js','js/action-notices.js','css/action-notices.css','css/device-workspace.css','css/responsive.css','css/motion.css','js/motion.js','css/offline.css','js/offline-page.js','js/stock-offline.js','js/sync-status.js','css/sync-status.css','js/stock-utils.js','js/vendor/html5-qrcode-2.3.8.min.js','js/vendor/quagga-1.12.1.min.js','js/ean-scanner.js','js/dialog-scroll.js','css/ean-scanner.css'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(ASSETS.map(url=>new Request(url,{cache:'reload'})))).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('repotools-shell-')&&key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  const url=new URL(event.request.url),base=new URL(self.registration.scope);
  if(url.origin!==base.origin||event.request.method!=='GET')return;
  const path=url.pathname.slice(base.pathname.length);
  const supportReferrer=new URL(event.request.referrer||base.href),supportMode=url.searchParams.get('support')??(supportReferrer.origin===base.origin?supportReferrer.searchParams.get('support'):null);
  if(/^[a-f0-9]{32}$/.test(supportMode||'')){if(path==='offline.html'){event.respondWith(Promise.resolve(new Response('Este acceso requiere conexión. Volvé a Stock con internet.',{status:503,headers:{'Content-Type':'text/plain; charset=utf-8','Cache-Control':'no-store'}})));return;}event.respondWith(fetch(event.request,{cache:'no-store'}).catch(()=>new Response('Este acceso requiere conexión. Volvé a intentar.',{status:503,headers:{'Content-Type':'text/plain; charset=utf-8','Cache-Control':'no-store'}})));return;}
  const operationalQR=/^q\/[0-9]{8}\/?$/.test(path);
  const offlinePage=async()=>{const cached=await caches.match(new URL('offline.html',base).href);if(!cached)return Response.error();return new Response(operationalQR?(await cached.text()).replace('<head>','<head><base href="'+base.href+'">'):await cached.text(),{headers:{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store','X-Repotools-Offline':'1'}});};
  if(event.request.mode==='navigate'&&(operationalQR||['','index.php','index.html','stock.php','biblioteca.php','offline.html','palet.php','stock-compartido.php','cuenta.php','reportes.php','tops.php'].includes(path))){
    event.respondWith((async()=>{const control=new AbortController(),timer=setTimeout(()=>control.abort(),8000);try{const response=await fetch(event.request,{signal:control.signal,cache:'no-store'});if(response.status>=500)return await offlinePage();return response;}catch{return await offlinePage();}finally{clearTimeout(timer);}})());return;
  }
  if(path.startsWith('js/vendor/ocr/')) { event.respondWith(caches.open('repotools-ocr-v1').then(async cache => { const old=await cache.match(event.request); if(old)return old; const response=await fetch(event.request); if(response.ok)await cache.put(event.request,response.clone()); return response; })); return; }
  if(/^img\/achievements\/[a-z_]+\/[a-z_]+_[0-9]{2}_[a-z]+\.svg$/.test(path)){event.respondWith(caches.open(CACHE).then(async cache=>{const old=await cache.match(event.request,{ignoreSearch:true});if(old)return old;const response=await fetch(event.request);if(response.ok)await cache.put(event.request,response.clone());return response;}));return;}
  if(ICON_ASSETS.includes(path)){event.respondWith(caches.match(event.request,{ignoreSearch:true}).then(cached=>cached||fetch(event.request)));return;}
  if(ASSETS.includes(path))event.respondWith(caches.open(CACHE).then(async cache=>{const hashed=/^[a-f0-9]{12}$/.test(url.searchParams.get('v')||'');const cached=await cache.match(event.request,{ignoreSearch:!hashed});if(cached)return cached;try{const response=await fetch(event.request);if(response.ok)await cache.put(event.request,response.clone());return response;}catch(error){if(hashed){const fallback=await cache.match(event.request,{ignoreSearch:true});if(fallback)return fallback;}throw error;}}));
});

let preparingOCR;
self.addEventListener('message',event=>{if(event.data?.type!=='prepare-ocr')return;if(!preparingOCR)preparingOCR=(async()=>{const cache=await caches.open('repotools-ocr-v1');for(const name of ['tesseract.min.js','worker.min.js','tesseract-core-lstm.wasm.js','tesseract-core-simd-lstm.wasm.js','eng.traineddata.gz']){const url=new URL('js/vendor/ocr/'+name,self.registration.scope).href;if(await cache.match(url))continue;const response=await fetch(url);if(!response.ok)throw Error('OCR preparation failed');await cache.put(url,response);}})().catch(()=>{}).finally(()=>preparingOCR=null);event.waitUntil(preparingOCR);});

// Refresh only application files; preserve OCR, IndexedDB, drafts and offline queues.
self.addEventListener('message',event=>{
 if(!['manager-refresh-cache','beta-refresh-cache'].includes(event.data?.type)||!event.ports[0])return;
 event.waitUntil((async()=>{const port=event.ports[0];try{
  const base=new URL(self.registration.scope);if(event.data.type==='manager-refresh-cache'){const auth=await fetch(new URL('api/manager-cache.php',base),{method:'POST',credentials:'same-origin',cache:'no-store',body:new URLSearchParams({csrf:event.data.csrf||''})});if(!auth.ok)throw Error(auth.status===403?'No tenés permiso para actualizar la caché.':'No se pudo validar tu sesión.');}
  const names=(await caches.keys()).filter(name=>name.startsWith('repotools-shell-')),targets=new Map();
  const allowed=value=>{const url=new URL(value,base);if(url.origin!==base.origin||!url.pathname.startsWith(base.pathname))return false;const path=url.pathname.slice(base.pathname.length);return !path.startsWith('js/vendor/ocr/')&&(/^(js|css|img)\//.test(path)||path==='offline.html');};
  for(const name of names){const cache=await caches.open(name);for(const request of await cache.keys())if(allowed(request.url)){const list=targets.get(request.url)||[];list.push(cache);targets.set(request.url,list);}}
  const current=await caches.open(CACHE);for(const asset of [...ASSETS,...(Array.isArray(event.data.assets)?event.data.assets:[])])if(typeof asset==='string'&&allowed(asset)){const url=new URL(asset,base).href,list=targets.get(url)||[];if(!list.includes(current))list.push(current);targets.set(url,list);}
  const jobs=[...targets];let index=0;await Promise.all(Array.from({length:4},async()=>{while(index<jobs.length){const [url,stores]=jobs[index++],response=await fetch(url,{cache:'reload',credentials:'same-origin'});if(!response.ok)throw Error('No se pudieron actualizar todos los archivos. Volvé a intentar.');for(const store of stores)await store.put(url,response.clone());}}));port.postMessage({ok:true});
 }catch(error){port.postMessage({ok:false,error:error.message||'No se pudo actualizar la caché.'});}})());
});

// Shell refresh: shared dialog backdrop dismissal, online and offline.

// Shell refresh: confirm before leaving modified dialog forms.

// Shell refresh: compact expiry keypad and explicit hand preference.

// Shell refresh: keypad headings and expiry input anchoring.

// Shell refresh: retain left-handed placement with older preference snapshots.

// Shell refresh: follow expiry input after dialog animations and lookup layout changes.

// Refresh quantity calculator placement and offline spacing without changing app version.

// Refresh printable PDF logo proportions without changing app version.

// Refresh saved-form tracking and short print link sharing without a version bump.

// Refresh viewport-root mobile navigation placement without changing app version.

// Refresh pending counter updates without replaying the panel entrance animation.
