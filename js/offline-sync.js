(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  let cached;try{cached=JSON.parse(localStorage.getItem('repotools-offline-view')||'null');}catch{}
  const user=Number(window.repotoolsOfflineUser??cached?.user??0);if(!user)return;
  let left=false;const running=new Set(),controllers=new Set();
  const events=(type,detail)=>window.dispatchEvent(new CustomEvent(type,{detail}));
  async function request(url,options={}) {const control=new AbortController(),timer=setTimeout(()=>control.abort(),12000);controllers.add(control);try{const response=await fetch(url,{credentials:'same-origin',cache:'no-store',...options,signal:control.signal});const value=await response.json();if(!response.ok){const error=Error(value.error||'No pudimos enviar la carga.');error.status=response.status;throw error;}return value;}finally{clearTimeout(timer);controllers.delete(control);}}
  function saveView(kind,data){if(left)return;if(window.RepoOfflineView){RepoOfflineView.save(kind,data);return;}try{const value=JSON.parse(localStorage.getItem('repotools-offline-view')||'null');if(value?.user===user){value[kind]={at:Date.now(),data:RepoOfflineStorage.clean(data)};localStorage.setItem('repotools-offline-view',JSON.stringify(value));}}catch{}}
  async function flush(type,force=false){if(left||running.has(type))return 0;const store=type==='stock'?RepoStockOffline(user):RepoLibraryOffline(user);running.add(type);let count=0,error=false;
    try{if(!(await store.list()).length)return 0;if(window.RepoConnectivity){const connection=await RepoConnectivity.check();if(connection.kind!=='online'){if(['offline','server'].includes(connection.kind))for(const row of await store.list()){if(!row.deferred){row.deferred=true;await store.put(row);}}return 0;}}
      events('repotools:sync-state',{engine:type,busy:true});
      await RepoOfflineStorage.runExclusive('repotools-'+type+'-sync-'+user,async()=>{
        const url=type==='stock'?'api/stock.php':'api/biblioteca.php';const identity=await request(url);if(Number(identity.user_id)!==user){const e=Error('Iniciá sesión con la cuenta original.');e.status=401;throw e;}
        for(const queued of await store.list()){
          if(left)break;const row=(await store.list()).find(r=>r.key===queued.key);if(type==='stock'&&row?.state==='conflict'&&row.error==='El palet cambió desde tu última consulta. Revisa la carga pendiente antes de enviarla.'){row.state='pending';row.error='';row.deferred=true;row.nextAttemptAt=0;await store.put(row);}if(!row||['conflict','rejected'].includes(row.state)||(!force&&(row.nextAttemptAt||0)>Date.now()))continue;
          try{
            if(type==='stock'&&(row.data?.action!=='save_product'||Number(row.data?.product_id)!==0||Number(row.data?.pallet_id)<1||row.data?.offline_revision===undefined)){const e=Error('Esta operación requiere conexión y confirmación. No se envió.');e.status=422;throw e;}
            row.state='sending';await store.put(row);
            const data=await request(url,{method:'POST',body:new URLSearchParams({...row.data,...(type==='library'?{action:'offline_add',offline_owner:user}:{offline_owner:user}),operation_key:row.key,csrf:identity.csrf})});
            if(left)break;
            if(type==='stock'&&!data.ok||type==='library'&&!Array.isArray(data.libraries))throw Error('No recibimos la confirmación. La carga sigue guardada.');
            if(type==='stock')await store.advance(row.data.code,row.data.offline_revision,data.pallet_revision);
            await RepoOfflineSent.put(user,{key:row.key,recovered:row.deferred===true||row.offline_wait===true||navigator.onLine===false,engine:type,state:'completed',name:row.data.name,ean:row.data.ean,destination:type==='stock'?'Palet '+row.data.code:'Biblioteca '+row.libraryName,units:type==='stock'?Number(row.data.amount)*(row.data.kind==='units'?1:Number(row.data.pack_size)):0});
            await store.remove(row.key);count++;
            if(type==='library')saveView('libraries',data.libraries);
            else try{const updated=await request('api/stock.php?code='+encodeURIComponent(row.data.code));if(!left&&Number(updated.user_id)===user)await store.cache(updated.pallet);}catch{}
          }catch(e){error=true;if(!e.status||e.status>=500)row.deferred=true;RepoOfflineStorage.retry(row,e.status,e.message);await store.put(row);if(e.status===401)window.RepoConnectivity?.report('auth');else if(e.status>=500)window.RepoConnectivity?.report('server');else if(!e.status)window.RepoConnectivity?.report(navigator.onLine===false?'offline':'server');break;}
        }
      });
      if(count&&!left){events('repotools:'+type+'-synced',{count,user});window.RepoToast?.(count===1?'Carga sincronizada correctamente.':count+' cargas sincronizadas correctamente.');}
    }catch(e){error=true;if(e.status===401){window.RepoConnectivity?.report('auth');for(const row of await store.list()){if(['conflict','rejected'].includes(row.state))continue;row.state='auth';row.error='Iniciá sesión para enviar esta carga.';await store.put(row);}}else window.RepoConnectivity?.report(navigator.onLine===false?'offline':'server');}
    finally{running.delete(type);if(!left)events('repotools:sync-state',{engine:type,busy:false,error});}return count;
  }
  const flushAll=()=>Promise.all([flush('stock'),flush('library')]).catch(()=>{});
  const listen=page?page.listen:window.addEventListener.bind(window);listen('repotools:sync-tick',flushAll);listen('repotools:queue-changed',flushAll);listen('repotools:connectivity',e=>{if(e.detail.kind==='online')flushAll();});listen('online',flushAll);
  listen('repotools:sync-retry',async()=>{for(const type of ['stock','library']){const store=type==='stock'?RepoStockOffline(user):RepoLibraryOffline(user);for(const row of await store.list())if(row.state!=='conflict'&&row.state!=='rejected'){row.error='';row.state='pending';row.nextAttemptAt=0;await store.put(row);}}flushAll();});
  const dispose=()=>{left=true;for(const controller of controllers)controller.abort();};page?.onLeave(dispose);if(!page)window.addEventListener('pagehide',dispose,{once:true});
  listen('storage',event=>{if(event.key==='repotools-offline-view'){let owner;try{owner=JSON.parse(event.newValue||'null')?.user;}catch{}if(owner!==user)dispose();}});
  window.RepoOfflineSync={stop:dispose,flush,flushAll,busy:type=>running.has(type)};flushAll();
})();
