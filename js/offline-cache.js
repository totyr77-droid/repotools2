(() => {
  const page=window.RepoNavigation?.page;
  const uid=Number(window.repotoolsOfflineUser||0),key='repotools-offline-view';
  if('serviceWorker' in navigator&&!window.RepoOfflinePreparation){
    const doc=page?.document||document,host=doc.querySelector('main');let notice;
    function feedback(message,error=false){if(!host?.isConnected)return;if(!notice){notice=doc.createElement('p');notice.className='offline-preparation-notice';notice.setAttribute('role','status');host.prepend(notice);}notice.dataset.error=String(error);notice.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="'+(error?'M12 8v5m0 3h.01M12 3 2 21h20Z':'m5 12 4 4L19 6')+'"/></svg>';const label=doc.createElement('span');label.textContent=message;notice.append(label);}
    const prepare=async()=>{const registration=await navigator.serviceWorker.register('sw.js',{updateViaCache:'none'});const worker=registration.installing||registration.waiting;if(worker&&worker.state!=='activated')await new Promise((resolve,reject)=>{const changed=()=>{if(worker.state==='activated'||worker.state==='redundant'){worker.removeEventListener('statechange',changed);worker.state==='activated'?resolve():reject(Error('Offline installation failed'));}};worker.addEventListener('statechange',changed);changed();});await navigator.serviceWorker.ready;if(!navigator.serviceWorker.controller){feedback('Preparando el uso sin conexión…');await new Promise(resolve=>navigator.serviceWorker.addEventListener('controllerchange',resolve,{once:true}));}const shell=await caches.match(new URL('offline.html',registration.scope).href);if(!shell)throw Error('Offline not prepared');feedback('Modo sin conexión preparado.');setTimeout(()=>notice?.remove(),6000);return true;};let timeout;window.RepoOfflinePreparation=Promise.race([prepare(),new Promise((_,reject)=>{timeout=setTimeout(()=>reject(Error('Offline preparation timeout')),30000);})]).finally(()=>clearTimeout(timeout)).catch(()=>{feedback('No pudimos preparar el modo sin conexión. Volvé a cargar con internet.',true);window.RepoOfflinePreparation=null;return false;});
  }
  delete window.RepoOfflineView;
  if(!uid){try{localStorage.removeItem(key);}catch{}return;}
  let previous;try{previous=JSON.parse(localStorage.getItem(key)||'null');}catch{}
  if(previous?.user!==uid)try{localStorage.removeItem(key);}catch{}
  window.RepoOfflineView={save(kind,data){try{let value=JSON.parse(localStorage.getItem(key)||'null');if(value?.user!==uid)value={user:uid};value.hand=(page?.document||document).body.dataset.hand||'right';value[kind]={at:Date.now(),data};const json=JSON.stringify(value);if(json.length<2000000)localStorage.setItem(key,json);}catch{}},clear(){localStorage.removeItem(key);}};

  const doc=page?.document||document;
  doc.querySelector('form[action="salir.php"]')?.addEventListener('submit',()=>window.RepoOfflineView.clear());
})();
