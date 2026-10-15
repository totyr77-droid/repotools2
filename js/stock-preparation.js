(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  const user=Number(window.repotoolsOfflineUser||0);if(!user)return;
  const store=RepoStockOffline(user);let generation=0,controller,left=false,latest;
  const status=doc.createElement('small');status.className='stock-local-preparation';status.setAttribute('role','status');
  function progress(total,ready,error=false){
    if(left)return;status.hidden=total===0;const host=doc.getElementById('stockOverview');if(host&&!status.isConnected)host.append(status);
    status.dataset.error=String(error);status.textContent=error?'Copia local incompleta · '+ready+'/'+total+' palets.':ready<total?'Preparando copia local · '+ready+'/'+total+' palets':'Copia local lista · '+total+' palets';
  }
  async function prepare(data){
    latest=data;const version=++generation;controller?.abort();const activeController=controller=new AbortController(),signal=activeController.signal;
    const manifest=data.pallets||[],pending=[];let ready=0;
    const save=async complete=>{if(left||version!==generation)return;const saved=await RepoOfflineView.save('stock',{pallets:manifest,warning_days:data.warning_days,offline_preparation:{total:manifest.length,ready,complete}});if(saved===false)throw Error('No pudimos guardar la copia local');};
    try{
      for(const p of manifest){const local=await store.cached(p.code);if(left||version!==generation)return;if(local&&Number(local.pallet.id)===Number(p.id)&&Number(local.pallet.revision)===Number(p.revision)&&local.at>Date.now()-86400000)ready++;else pending.push(p);}
      // Revoked/deleted entries leave the read cache, never the operation queue.
      if(store.reconcile)await store.reconcile(manifest,()=>!left&&version===generation);
      if(left||version!==generation)return;
      await save(!pending.length);if(left||version!==generation)return;progress(manifest.length,ready);
      for(let offset=0;offset<pending.length;offset+=5){
        if(left||signal.aborted||navigator.onLine===false)throw Error('Sin conexión');
        const batch=pending.slice(offset,offset+5),timer=setTimeout(()=>activeController.abort(),15000);
        let response;try{response=await (page?.fetch||fetch.bind(window))('api/stock.php?offline_codes='+batch.map(p=>p.code).join(','),{credentials:'same-origin',cache:'no-store',signal});}finally{clearTimeout(timer);}
        if(!response.ok)throw Error('No pudimos preparar este lote');const result=await response.json();
        if(left||version!==generation)return;
        if(Number(result.user_id)!==user||!Array.isArray(result.pallets)||result.pallets.length!==batch.length)throw Error('No pudimos verificar el lote');
        for(const p of result.pallets){const expected=batch.find(v=>v.code===p.code);if(!expected||Number(expected.id)!==Number(p.id)||!Number.isSafeInteger(Number(p.revision))||Number(p.revision)<Number(expected.revision||0)||p.available||!Array.isArray(p.products)||p.products.some(row=>Number(row.pallet_id)!==Number(p.id)))throw Error('El palet cambió durante la preparación');await store.cache(p);if(left||version!==generation)return;ready++;}
        await save(ready===manifest.length);if(left||version!==generation)return;progress(manifest.length,ready);
        await new Promise(resolve=>setTimeout(resolve,0));
      }
      navigator.storage?.persist?.().catch(()=>{});
    }catch(error){if(!left&&version===generation){progress(manifest.length,ready,true);status.title=error.message;await save(false).catch(()=>{});}}
  }
  const retry=()=>{if(!left&&latest)prepare(latest);};
  page?.listen('online',retry);page?.listen('repotools:connectivity',event=>{if(event.detail.kind==='online')retry();});
  page?.onLeave(()=>{left=true;++generation;controller?.abort();status.remove();});
  window.RepoStockPreparation={prepare};
})();
