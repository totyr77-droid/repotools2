(() => {
  'use strict';
  const context=window.RepoSupport?.context;if(!context)return;
  const originalFetch=window.fetch.bind(window),base=new URL('.',document.baseURI),nativeSession=window.sessionStorage;
  const scoped=value=>{const url=new URL(value,document.baseURI);if(url.origin===location.origin&&url.pathname.startsWith(base.pathname)&&!url.pathname.match(/\.(?:js|css|png|svg|jpg|woff2|wasm)$/i))url.searchParams.set('support',context);return url;};
  document.addEventListener('DOMContentLoaded',()=>{const bar=document.querySelector('.support-session-bar');if(bar)new ResizeObserver(()=>document.body.style.setProperty('--support-bar-height',bar.getBoundingClientRect().height+'px')).observe(bar);},{once:true});
  const here=new URL(location.href);here.searchParams.set('support',context);history.replaceState(history.state,'',here);
  window.fetch=(input,options)=>{const request=input instanceof Request?input:null,url=scoped(request?request.url:input);return originalFetch(request?new Request(url,request):url,options);};
  // Never read or overwrite the manager's regular offline data in this window.
  for(const name of ['localStorage','sessionStorage']){
    const prefix='repotools-support:'+context+':'+name+':';
    const storage={getItem:key=>nativeSession.getItem(prefix+key),setItem:(key,value)=>nativeSession.setItem(prefix+key,String(value)),removeItem:key=>nativeSession.removeItem(prefix+key),clear:()=>{for(const key of Object.keys(nativeSession))if(key.startsWith(prefix))nativeSession.removeItem(key);},key:index=>Object.keys(nativeSession).filter(k=>k.startsWith(prefix))[index]?.slice(prefix.length)??null,get length(){return Object.keys(nativeSession).filter(k=>k.startsWith(prefix)).length;}};
    Object.defineProperty(window,name,{value:storage});
  }
  const open=indexedDB.open.bind(indexedDB);indexedDB.open=(name,version)=>open('support-'+context+'-'+name,version);
  const remove=indexedDB.deleteDatabase.bind(indexedDB);indexedDB.deleteDatabase=name=>remove('support-'+context+'-'+name);
  document.addEventListener('click',event=>{const link=event.target.closest('a[href]');if(link&&!link.hasAttribute('download')&&link.origin===location.origin&&!link.hash.startsWith('#!'))link.href=scoped(link.href).href;},true);
  document.addEventListener('submit',event=>{const form=event.target;if(form instanceof HTMLFormElement)form.setAttribute('action',scoped(form.getAttribute('action')||location.href).href);},true);
  window.addEventListener('pageshow',event=>{if(event.persisted)window.fetch('api/conectividad.php',{cache:'no-store'}).then(r=>{if(r.status===401)location.replace('reportes.php?queue=1&view=users&support=exit');}).catch(()=>{});});
  if(window.BroadcastChannel){const NativeChannel=window.BroadcastChannel;window.BroadcastChannel=class extends NativeChannel{constructor(name){super('support-'+context+'-'+name);}};}
  document.addEventListener('submit',event=>{const form=event.target;if(form.matches('.support-session-bar form')){for(const key of Object.keys(nativeSession))if(key.startsWith('repotools-support:'+context+':'))nativeSession.removeItem(key);try{indexedDB.databases?.().then(rows=>{for(const row of rows)if(row.name?.startsWith('support-'+context+'-'))remove(row.name);});}catch{}}},true);
})();
