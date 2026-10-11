const CACHE='repotools-shell-v2';
const ASSETS=['offline.html','css/offline.css','js/offline-page.js','js/stock-offline.js','js/stock-utils.js','js/vendor/html5-qrcode-2.3.8.min.js','js/vendor/quagga-1.12.1.min.js','js/ean-scanner.js','js/dialog-scroll.js','css/ean-scanner.css'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(ASSETS)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('repotools-shell-')&&key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  const url=new URL(event.request.url),base=new URL(self.registration.scope);
  if(url.origin!==base.origin||event.request.method!=='GET')return;
  const path=url.pathname.slice(base.pathname.length);
  if(event.request.mode==='navigate'&&['','index.php','index.html','stock.php','biblioteca.php','offline.html'].includes(path)){
    event.respondWith(fetch(event.request).catch(()=>caches.match(new URL('offline.html',base).href)));return;
  }
  if(ASSETS.includes(path))event.respondWith(fetch(event.request).catch(()=>caches.match(event.request,{ignoreSearch:true})));
});
