(() => {
  'use strict';
  const ready = new Promise((resolve,reject) => {
    const request = indexedDB.open('repotools-stock-offline',4);
    request.onupgradeneeded = () => {for(const name of ['loads','palets','libraryLoads','catalog','sent','leases','views'])if(!request.result.objectStoreNames.contains(name))request.result.createObjectStore(name,{keyPath:'key'});};
    request.onsuccess = () => {request.result.onversionchange=()=>request.result.close();resolve(request.result);};
    request.onblocked=()=>reject(Error("Cierra las pestañas antiguas de Repotools y vuelve a intentar."));
    request.onerror = () => reject(Error('No pudimos guardar en este dispositivo. Mantén abierto el formulario.'));
  });
  ready.catch(()=>{});
  async function transact(store,mode,fn) {
    const db=await ready;
    return new Promise((resolve,reject)=>{const tx=db.transaction(store,mode),request=fn(tx.objectStore(store));let value;request.onsuccess=()=>value=request.result;tx.oncomplete=()=>{resolve(value);if(mode==='readwrite'&&['loads','libraryLoads'].includes(store))window.dispatchEvent(new CustomEvent('repotools:queue-changed'));};tx.onerror=tx.onabort=()=>reject(Error('No pudimos guardar en este dispositivo. Mantén abierto el formulario.'));});
  }
  function clean(value) { if(Array.isArray(value))return value.map(clean);if(!value||typeof value!=='object')return value;return Object.fromEntries(Object.entries(value).filter(([k])=>!['csrf','csrf_token','token','device_token','access_token','password','password_hash','email','ip','members'].includes(k)).map(([k,v])=>[k,clean(v)])); }
  function operation(row,user){return {...row,offline_wait:row.offline_wait??(navigator.onLine===false||['offline','server'].includes(window.RepoConnectivity?.state?.kind)),user,operation_id:row.key,created:row.created||Date.now(),state:row.state||(row.error?'conflict':'pending'),attempts:row.attempts||0,data:clean(row.data)};}
  const leaseOwner=crypto.randomUUID(),localMutexes=new Map();
  async function lease(key,release=false){const db=await ready;return new Promise((resolve,reject)=>{const tx=db.transaction('leases','readwrite'),store=tx.objectStore('leases'),r=store.get(key);let acquired=false;r.onsuccess=()=>{const old=r.result;if(release){if(old?.owner===leaseOwner)store.delete(key);return;}if(!old||old.until<Date.now()||old.owner===leaseOwner){store.put({key,owner:leaseOwner,until:Date.now()+30000});acquired=true;}};tx.oncomplete=()=>resolve(acquired);tx.onerror=tx.onabort=()=>reject(Error('No pudimos bloquear la sincronización.'));});}
  window.RepoOfflineStorage={clean,async saveView(user,kind,data,at=Date.now()){const db=await ready,key=user+':'+kind;return new Promise((resolve,reject)=>{const tx=db.transaction('views','readwrite'),store=tx.objectStore('views'),r=store.get(key);r.onsuccess=()=>{if(!r.result||r.result.at<=at)store.put({key,user,kind,at,data:clean(data)});};tx.oncomplete=()=>resolve(true);tx.onerror=tx.onabort=()=>reject(Error('No pudimos guardar la copia local.'));});},async getView(user,kind){return transact('views','readonly',s=>s.get(user+':'+kind));},async clearPrivate(user){for(const store of ['palets','catalog','sent','views'])for(const row of await transact(store,'readonly',s=>s.getAll()))if(row.user===user)await transact(store,'readwrite',s=>s.delete(row.key));},runExclusive(key,fn){const previous=localMutexes.get(key)||Promise.resolve();const job=previous.catch(()=>{}).then(async()=>{if(navigator.locks)return navigator.locks.request(key,fn);if(!await lease(key))return false;const renewal=setInterval(()=>lease(key).catch(()=>{}),8000);try{return await fn();}finally{clearInterval(renewal);await lease(key,true);}});const tail=job.catch(()=>{});localMutexes.set(key,tail);tail.then(()=>{if(localMutexes.get(key)===tail)localMutexes.delete(key);});return job;}, retry(row,status,message){row.attempts=(row.attempts||0)+1;row.state=status===409?'conflict':[403,404,422].includes(status)?'rejected':status===401?'auth':'pending';row.lastError=message;row.lastErrorAt=Date.now();row.error=row.state==='pending'?'':message;if(row.state==='pending'&&row.data?.action==='save_product')row.state='retryable';row.nextAttemptAt=Date.now()+Math.min(120000,2000*2**Math.min(row.attempts,6));return row;}};
  window.RepoLibraryOffline=user=>({
    async list(){return (await transact('libraryLoads','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>a.created-b.created);},
    async put(row){return transact('libraryLoads','readwrite',s=>s.put(operation(row,user)));},
    async remove(key){return transact('libraryLoads','readwrite',s=>s.delete(key));}
  });
  window.RepoOfflineCatalog={
    async put(user,ean,product){if(!user||!product)return;const row={key:user+':'+ean,user,ean,at:Date.now(),product:{name:product.name||'',brand:product.brand||'',quantity:product.quantity||''}};await transact('catalog','readwrite',s=>s.put(row));const rows=(await transact('catalog','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>b.at-a.at);for(const old of rows.slice(500))await transact('catalog','readwrite',s=>s.delete(old.key));},
    async get(user,ean,metadata=false){const row=await transact('catalog','readonly',s=>s.get(user+':'+ean));return row&&row.at>Date.now()-30*86400000?(metadata?{product:row.product,at:row.at}:row.product):null;}
  };
  window.RepoOfflineSent={
    async list(user){return (await transact('sent','readonly',s=>s.getAll())).filter(r=>r.user===user&&r.at>Date.now()-7*86400000).sort((a,b)=>b.at-a.at);},
    async put(user,row){await transact('sent','readwrite',s=>s.put({...row,user,at:Date.now()}));const rows=(await transact('sent','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>b.at-a.at);for(const old of rows.slice(100))await transact('sent','readwrite',s=>s.delete(old.key));window.dispatchEvent(new CustomEvent('repotools:offline-sent'));}
  };
  window.RepoStockOffline = user => ({
    async list(){return (await transact('loads','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>a.created-b.created);},
    async put(row){return transact('loads','readwrite',s=>s.put(operation(row,user)));},
    async remove(key){return transact('loads','readwrite',s=>s.delete(key));},
    async confirm(row,pallet,receipt){
      const db=await ready;
      return new Promise((resolve,reject)=>{const tx=db.transaction(['loads','sent','palets'],'readwrite'),loads=tx.objectStore('loads'),r=loads.get(row.key);r.onsuccess=()=>{
        if(!r.result||r.result.user!==user||JSON.stringify(clean(r.result.data))!==JSON.stringify(clean(row.data))){tx.abort();return;}
        tx.objectStore('sent').put({...receipt,key:row.key,user,at:Date.now(),operation_id:row.key,state:'completed'});
        const snapshots=tx.objectStore('palets'),key=user+':'+pallet.code,old=snapshots.get(key);old.onsuccess=()=>{if(!old.result||Number(old.result.pallet.id)!==Number(pallet.id)||Number(old.result.pallet.revision||0)<=Number(pallet.revision||0))snapshots.put({key,user,pallet:clean(pallet),at:Date.now()});};
        loads.delete(row.key);
      };tx.oncomplete=()=>{window.dispatchEvent(new CustomEvent('repotools:offline-sent'));window.dispatchEvent(new CustomEvent('repotools:queue-changed'));resolve();};tx.onerror=tx.onabort=()=>reject(Error('No pudimos conservar la confirmación. La carga sigue guardada.'));});
    },
    async cache(pallet){
      const db=await ready,key=user+':'+pallet.code;return new Promise((resolve,reject)=>{const tx=db.transaction('palets','readwrite'),store=tx.objectStore('palets'),r=store.get(key);r.onsuccess=()=>{if(r.result&&Number(r.result.pallet.id)===Number(pallet.id)&&Number(r.result.pallet.revision||0)>Number(pallet.revision||0))return;store.put({key,user,pallet:clean(pallet),at:Date.now()});};tx.oncomplete=resolve;tx.onerror=tx.onabort=()=>reject(Error('No pudimos conservar la consulta de este palet.'));});
    },
    async removeCached(code){await transact('palets','readwrite',s=>s.delete(user+':'+code));},
    async cached(code){const row=await transact('palets','readonly',s=>s.get(user+':'+code));return row&&row.at>Date.now()-7*86400000?row:null;},
    async reconcile(manifest,active=()=>true){const allowed=new Map(manifest.map(p=>[p.code,Number(p.id)]));for(const row of await transact('palets','readonly',s=>s.getAll()))if(active()&&row.user===user&&allowed.get(row.pallet.code)!==Number(row.pallet.id))await transact('palets','readwrite',s=>s.delete(row.key));},
    async clearCached(){const rows=await transact('palets','readonly',s=>s.getAll());for(const row of rows)if(row.user===user)await transact('palets','readwrite',s=>s.delete(row.key));},
    async advance(code,before,after){
      const db=await ready;
      return new Promise((resolve,reject)=>{const tx=db.transaction('loads','readwrite'),store=tx.objectStore('loads'),r=store.getAll();r.onsuccess=()=>{for(const row of r.result)if(row.user===user&&row.data.code===code&&Number(row.data.offline_revision)===Number(before)){row.data.offline_revision=after;store.put(row);}};tx.oncomplete=resolve;tx.onerror=tx.onabort=()=>reject(Error('No pudimos actualizar las cargas pendientes.'));});
    }
  });
})();
