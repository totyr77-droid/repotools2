(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  const user=Number(window.repotoolsOfflineUser||0);if(!user)return;
  const store=RepoStockOffline(user);let generation=0,controller,left=false,latest;
  const status=doc.createElement('small');status.className='stock-local-preparation';status.setAttribute('role','status');
  function progress(total,ready,error=false){
    if(left)return;status.hidden=total===0;const host=doc.getElementById('stockLocalStatus');if(host&&!status.isConnected)host.append(status);
    status.dataset.error=String(error);if(!error)status.removeAttribute('title');
    const label=total===1?'palet':'palets';
    const message=error?'Copia local incompleta':ready<total?'Preparando copia local':'Copia local lista';
    status.replaceChildren(doc.createTextNode(message+' · '));
    const count=doc.createElement('span');count.className='stock-local-count';
    const icon=doc.getElementById('palletIcon')?.querySelector('svg')?.cloneNode(true);
    if(icon){icon.setAttribute('aria-hidden','true');count.append(icon);}
    count.append(doc.createTextNode((error||ready<total?ready+'/'+total:total)+' '+label));status.append(count);
  }
  let lastInteraction=0;
  const interact=()=>{lastInteraction=Date.now();};
  const listen=page?.listen||window.addEventListener.bind(window);
  for(const type of ['pointerdown','keydown','input','wheel'])listen(type,interact,{passive:true});
  const pause=(signal,ms)=>new Promise(resolve=>{
    if(signal.aborted)return resolve();
    const finish=()=>{clearTimeout(timer);signal.removeEventListener('abort',finish);resolve();};
    const timer=setTimeout(finish,ms);signal.addEventListener('abort',finish,{once:true});
  });
  async function background(signal){
    // Foreground forms, camera and queued writes take priority over read preparation.
    do{
      await pause(signal,200);
      if(left||signal.aborted)throw Error('Preparación detenida');
      if(navigator.onLine===false)throw Error('Sin conexión');
    }while(globalThis.document.hidden||doc.querySelector('dialog[open]')||
      window.RepoOfflineSync?.busy('stock')||window.RepoOfflineSync?.busy('library')||Date.now()-lastInteraction<400);
  }
  async function prepare(data){
    latest=data;const version=++generation;controller?.abort();const activeController=controller=new AbortController(),signal=activeController.signal;
    const manifest=data.pallets||[],pending=[];let ready=0,lastSaved=0;
    const save=async (complete,force=false)=>{if(left||version!==generation)return;if(!force&&!complete&&Date.now()-lastSaved<2000)return;const saved=await RepoOfflineView.save('stock',{pallets:manifest,tags:data.tags||[],warning_days:data.warning_days,offline_preparation:{total:manifest.length,ready,complete}});if(saved===false)throw Error('No pudimos guardar la copia local');lastSaved=Date.now();};
    try{
      for(const [index,p] of manifest.entries()){if(index%32===0)await background(signal);const local=await store.cached(p.code);if(left||version!==generation)return;if(local&&Number(local.pallet.id)===Number(p.id)&&Number(local.pallet.revision)===Number(p.revision)&&local.at>Date.now()-86400000)ready++;else pending.push(p);}
      // Revoked/deleted entries leave the read cache, never the operation queue.
      if(store.reconcile)await store.reconcile(manifest,()=>!left&&version===generation);
      if(left||version!==generation)return;
      await save(!pending.length,true);if(left||version!==generation)return;progress(manifest.length,ready);
      for(let offset=0;offset<pending.length;offset+=5){
        if(left||signal.aborted||navigator.onLine===false)throw Error('Sin conexión');
        await background(signal);
        const batch=pending.slice(offset,offset+5),timer=setTimeout(()=>activeController.abort(),15000);
        let response;try{response=await (page?.fetch||fetch.bind(window))('api/stock.php?offline_codes='+batch.map(p=>p.code).join(','),{credentials:'same-origin',cache:'no-store',signal});}finally{clearTimeout(timer);}
        if(!response.ok)throw Error('No pudimos preparar este lote');const result=await response.json();
        if(left||version!==generation)return;
        if(Number(result.user_id)!==user||!Array.isArray(result.pallets)||result.pallets.length!==batch.length)throw Error('No pudimos verificar el lote');
        for(const p of result.pallets){const expected=batch.find(v=>v.code===p.code);if(!expected||Number(expected.id)!==Number(p.id)||!Number.isSafeInteger(Number(p.revision))||Number(p.revision)<Number(expected.revision||0)||p.available||!Array.isArray(p.products)||p.products.some(row=>Number(row.pallet_id)!==Number(p.id)))throw Error('El palet cambió durante la preparación');await store.cache(p);if(left||version!==generation)return;ready++;}
        await save(ready===manifest.length);if(left||version!==generation)return;progress(manifest.length,ready);
      }
      navigator.storage?.persist?.().catch(()=>{});
    }catch(error){if(!left&&version===generation){progress(manifest.length,ready,true);status.title=error.message;await save(false,true).catch(()=>{});}}
  }
  const retry=()=>{if(!left&&latest)prepare(latest);};
  listen('online',retry);listen('repotools:connectivity',event=>{if(event.detail.kind==='online')retry();});
  const dispose=()=>{left=true;++generation;controller?.abort();status.remove();if(!page)for(const type of ['pointerdown','keydown','input','wheel'])window.removeEventListener(type,interact);};
  if(page)page.onLeave(dispose);else window.addEventListener('pagehide',dispose,{once:true});
  window.RepoStockPreparation={prepare};
})();
