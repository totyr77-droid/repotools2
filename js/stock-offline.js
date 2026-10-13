(() => {
  'use strict';
  const ready = new Promise((resolve,reject) => {
    const request = indexedDB.open('repotools-stock-offline',2);
    request.onupgradeneeded = () => {for(const name of ['loads','palets','libraryLoads','catalog','sent'])if(!request.result.objectStoreNames.contains(name))request.result.createObjectStore(name,{keyPath:'key'});};
    request.onsuccess = () => {request.result.onversionchange=()=>request.result.close();resolve(request.result);};
    request.onblocked=()=>reject(Error("Cierra las pestañas antiguas de Repotools y vuelve a intentar."));
    request.onerror = () => reject(Error('No pudimos guardar en este dispositivo. Mantén abierto el formulario.'));
  });
  ready.catch(()=>{});
  async function transact(store,mode,fn) {
    const db=await ready;
    return new Promise((resolve,reject)=>{const tx=db.transaction(store,mode),request=fn(tx.objectStore(store));let value;request.onsuccess=()=>value=request.result;tx.oncomplete=()=>{resolve(value);if(mode==='readwrite'&&['loads','libraryLoads'].includes(store))window.dispatchEvent(new CustomEvent('repotools:queue-changed'));};tx.onerror=tx.onabort=()=>reject(Error('No pudimos guardar en este dispositivo. Mantén abierto el formulario.'));});
  }
  window.RepoLibraryOffline=user=>({
    async list(){return (await transact('libraryLoads','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>a.created-b.created);},
    async put(row){return transact('libraryLoads','readwrite',s=>s.put({...row,user}));},
    async remove(key){return transact('libraryLoads','readwrite',s=>s.delete(key));}
  });
  window.RepoOfflineCatalog={
    async put(user,ean,product){if(!user||!product)return;const row={key:user+':'+ean,user,ean,at:Date.now(),product:{name:product.name||'',brand:product.brand||'',quantity:product.quantity||''}};await transact('catalog','readwrite',s=>s.put(row));const rows=(await transact('catalog','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>b.at-a.at);for(const old of rows.slice(500))await transact('catalog','readwrite',s=>s.delete(old.key));},
    async get(user,ean){const row=await transact('catalog','readonly',s=>s.get(user+':'+ean));return row&&row.at>Date.now()-30*86400000?row.product:null;}
  };
  window.RepoOfflineSent={
    async list(user){return (await transact('sent','readonly',s=>s.getAll())).filter(r=>r.user===user&&r.at>Date.now()-7*86400000).sort((a,b)=>b.at-a.at);},
    async put(user,row){await transact('sent','readwrite',s=>s.put({...row,user,at:Date.now()}));const rows=(await transact('sent','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>b.at-a.at);for(const old of rows.slice(100))await transact('sent','readwrite',s=>s.delete(old.key));window.dispatchEvent(new CustomEvent('repotools:offline-sent'));}
  };
  window.RepoStockOffline = user => ({
    async list(){return (await transact('loads','readonly',s=>s.getAll())).filter(r=>r.user===user).sort((a,b)=>a.created-b.created);},
    async put(row){return transact('loads','readwrite',s=>s.put({...row,user}));},
    async remove(key){return transact('loads','readwrite',s=>s.delete(key));},
    async cache(pallet){
      const db=await ready,{members,...snapshot}=pallet,key=user+':'+pallet.code,at=Date.now();
      return new Promise((resolve,reject)=>{const tx=db.transaction('palets','readwrite'),store=tx.objectStore('palets'),request=store.getAll();request.onsuccess=()=>{const previous=request.result.filter(r=>r.user===user&&r.key!==key).sort((a,b)=>b.at-a.at);previous.forEach((r,i)=>{if(i>=49||r.at<at-7*86400000)store.delete(r.key);});store.put({key,user,pallet:{...snapshot,members:[]},at});};tx.oncomplete=resolve;tx.onerror=tx.onabort=()=>reject(Error('No pudimos conservar la consulta de este palet.'));});
    },
    async cached(code){const row=await transact('palets','readonly',s=>s.get(user+':'+code));return row&&row.at>Date.now()-7*86400000?row:null;},
    async clearCached(){const rows=await transact('palets','readonly',s=>s.getAll());for(const row of rows)if(row.user===user)await transact('palets','readwrite',s=>s.delete(row.key));},
    async advance(code,before,after){
      const db=await ready;
      return new Promise((resolve,reject)=>{const tx=db.transaction('loads','readwrite'),store=tx.objectStore('loads'),r=store.getAll();r.onsuccess=()=>{for(const row of r.result)if(row.user===user&&row.data.code===code&&Number(row.data.offline_revision)===Number(before)){row.data.offline_revision=after;store.put(row);}};tx.oncomplete=resolve;tx.onerror=tx.onabort=()=>reject(Error('No pudimos actualizar las cargas pendientes.'));});
    }
  });
})();
