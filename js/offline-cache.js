(() => {
  const page=window.RepoNavigation?.page;
  const uid=Number(window.repotoolsOfflineUser||0),key='repotools-offline-view';
  delete window.RepoOfflineView;
  if(!uid){try{localStorage.removeItem(key);}catch{}return;}
  let previous;try{previous=JSON.parse(localStorage.getItem(key)||'null');}catch{}
  if(previous?.user!==uid)try{localStorage.removeItem(key);}catch{}
  window.RepoOfflineView={save(kind,data){try{let value=JSON.parse(localStorage.getItem(key)||'null');if(value?.user!==uid)value={user:uid};value[kind]={at:Date.now(),data};const json=JSON.stringify(value);if(json.length<2000000)localStorage.setItem(key,json);}catch{}},clear(){localStorage.removeItem(key);}};
  if('serviceWorker' in navigator)navigator.serviceWorker.register('sw.js').catch(()=>{});
  const doc=page?.document||document;
  doc.querySelector('form[action="salir.php"]')?.addEventListener('submit',()=>window.RepoOfflineView.clear());
})();
