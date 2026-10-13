const CACHE='repotools-shell-v11';
const ICON_ASSETS=['css/vendor/fontawesome-6.7.2/css/fontawesome.min.css','css/vendor/fontawesome-6.7.2/css/solid.min.css','css/vendor/fontawesome-6.7.2/css/brands.min.css','css/vendor/fontawesome-6.7.2/webfonts/fa-solid-900.woff2','css/vendor/fontawesome-6.7.2/webfonts/fa-brands-400.woff2','img/onboarding-logo.png','img/Repotools_Logo_Pack_Blanco/06_horizontal_blanco_transparente/repotools-horizontal-blanco-480w.png'];
const ASSETS=[...ICON_ASSETS,'offline.html','css/offline.css','js/offline-page.js','js/stock-offline.js','js/sync-status.js','css/sync-status.css','js/stock-utils.js','js/vendor/html5-qrcode-2.3.8.min.js','js/vendor/quagga-1.12.1.min.js','js/ean-scanner.js','js/dialog-scroll.js','css/ean-scanner.css'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(ASSETS)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('repotools-shell-')&&key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  const url=new URL(event.request.url),base=new URL(self.registration.scope);
  if(url.origin!==base.origin||event.request.method!=='GET')return;
  const path=url.pathname.slice(base.pathname.length);
  if(event.request.mode==='navigate'&&['','index.php','index.html','stock.php','biblioteca.php','offline.html','palet.php','stock-compartido.php','cuenta.php','reportes.php','tops.php'].includes(path)){
    event.respondWith(fetch(event.request).catch(()=>caches.match(new URL('offline.html',base).href)));return;
  }
  if(ICON_ASSETS.includes(path)){event.respondWith(caches.match(event.request,{ignoreSearch:true}).then(cached=>cached||fetch(event.request)));return;}
  if(ASSETS.includes(path))event.respondWith(fetch(event.request).catch(()=>caches.match(event.request,{ignoreSearch:true})));
});
