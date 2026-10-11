(() => {
  'use strict';
  const ready = new Promise((resolve,reject) => {
    const request = indexedDB.open('repotools-stock-offline',1);
    request.onupgradeneeded = () => {request.result.createObjectStore('loads',{keyPath:'key'});request.result.createObjectStore('palets',{keyPath:'key'});};
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(Error('No pudimos guardar en este dispositivo. Mantén abierto el formulario.'));
  });
  ready.catch(()=>{});
  async function transact(store,mode,fn) {
    const db=await ready;
    return new Promise((resolve,reject)=>{const tx=db.transaction(store,mode),request=fn(tx.objectStore(store));let value;request.onsuccess=()=>value=request.result;tx.oncomplete=()=>resolve(value);tx.onerror=tx.onabort=()=>reject(Error('No pudimos guardar en este dispositivo. Mantén abierto el formulario.'));});
  }
  window.RepoStockOffline = user => ({
    async list(){return (await transact('loads','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>a.created-b.created);},
    async put(row){return transact('loads','readwrite',s=>s.put({...row,user}));},
    async remove(key){return transact('loads','readwrite',s=>s.delete(key));},
    async cache(pallet){
      const db=await ready,{members,...snapshot}=pallet,key=user+':'+pallet.code,at=Date.now();
      return new Promise((resolve,reject)=>{const tx=db.transaction('palets','readwrite'),store=tx.objectStore('palets'),request=store.getAll();request.onsuccess=()=>{const previous=request.result.filter(r=>r.user===user&&r.key!==key).sort((a,b)=>b.at-a.at);previous.forEach((r,i)=>{if(i>=49||r.at<at-7*86400000)store.delete(r.key);});store.put({key,user,pallet:{...snapshot,members:[]},at});};tx.oncomplete=resolve;tx.onerror=tx.onabort=()=>reject(Error('No pudimos conservar la consulta de este palet.'));});
    },
    async cached(code){const row=await transact('palets','readonly',s=>s.get(user+':'+code));return row&&row.at>Date.now()-7*86400000?row:null;},
    async advance(code,before,after){
      const db=await ready;
      return new Promise((resolve,reject)=>{const tx=db.transaction('loads','readwrite'),store=tx.objectStore('loads'),r=store.getAll();r.onsuccess=()=>{for(const row of r.result)if(row.user===user&&row.data.code===code&&Number(row.data.offline_revision)===Number(before)){row.data.offline_revision=after;store.put(row);}};tx.oncomplete=resolve;tx.onerror=tx.onabort=()=>reject(Error('No pudimos actualizar las cargas pendientes.'));});
    }
  });
})();
