(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document,user=Number(window.repotoolsOfflineUser||0);
  const host=doc.querySelector('.stock-workspace,.library-app');
  if(!user||!host)return;
  const box=doc.createElement('div');box.className='repo-sync-status';box.id='repoSyncStatus';box.setAttribute('aria-live','polite');
  const icon=doc.createElement('i'),label=doc.createElement('span'),retry=doc.createElement('button');icon.setAttribute('aria-hidden','true');retry.type='button';retry.textContent='Reintentar';box.append(icon,label,retry);host.prepend(box);
  const running=new Set(),failures=new Set();let left=false,version=0;
  async function render(){const current=++version;try{
    const [stock,library]=await Promise.all([RepoStockOffline(user).list(),RepoLibraryOffline(user).list()]);if(left||current!==version)return;
    const rows=[...stock,...library],count=rows.length,online=window.RepoConnectivity?RepoConnectivity.state.kind==='online':navigator.onLine,error=online&&count&&(failures.size||rows.some(row=>row.error));
    const state=!online?'offline':running.size?'syncing':error?'error':count?'pending':'ready';box.dataset.state=state;box.hidden=count===0;
    icon.className='fa-solid '+({offline:'fa-wifi',syncing:'fa-arrows-rotate',error:'fa-triangle-exclamation',pending:'fa-clock',ready:'fa-check'}[state]);
    label.textContent=state==='offline'?'Sin conexión'+(count?' · '+count+' pendientes':''):state==='syncing'?'Sincronizando…':state==='error'?'No pudimos sincronizar algunos cambios':count?count+' cambio'+(count===1?' pendiente':'s pendientes'):'Todo sincronizado';
    retry.hidden=state!=='error';
  }catch{if(!left){label.textContent='No pudimos consultar las cargas pendientes';retry.hidden=true;}}}
  retry.addEventListener('click',()=>{failures.clear();window.dispatchEvent(new CustomEvent('repotools:sync-retry'));});
  const listen=page?page.listen:window.addEventListener.bind(window);
  listen('repotools:connectivity',()=>render());listen('repotools:queue-changed',()=>render());listen('online',()=>render());listen('offline',()=>render());
  listen('repotools:sync-state',event=>{const {engine,busy,error}=event.detail;if(busy)running.add(engine);else running.delete(engine);if(error)failures.add(engine);else if(!busy)failures.delete(engine);render();});
  page?.onLeave(()=>{left=true;++version;});render();
})();
