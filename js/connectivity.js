(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  let offlineIdentity=0;try{offlineIdentity=JSON.parse(localStorage.getItem('repotools-offline-view')||'null')?.user||0;}catch{}
  const user=Number(window.repotoolsOfflineUser??offlineIdentity),running=new Set();
  let queueCount=0,runnable=0,reviews=0,queueVersion=0;let tickVersion=0;let state={kind:navigator.onLine===false?'offline':'checking',user},left=false,pending,timer,controller;
  const badge=doc.createElement('span');badge.className='repo-connection-badge';badge.setAttribute('role','status');badge.hidden=true;
  const icon=doc.createElementNS('http://www.w3.org/2000/svg','svg');icon.setAttribute('viewBox','0 0 24 24');icon.setAttribute('aria-hidden','true');icon.innerHTML='<path d="M3 9a15 15 0 0 1 18 0M6 12a10 10 0 0 1 12 0M9 15a5 5 0 0 1 6 0M12 19h.01"/>';
  const label=doc.createElement('span');badge.append(icon,label);doc.querySelector('.logo,header h1,header a')?.append(badge);
  function render(){const kind=['offline','server','auth'].includes(state.kind)?state.kind:running.size&&runnable?'syncing':queueCount?(reviews?'review':'pending'):state.kind;badge.hidden=['online','checking'].includes(kind);badge.dataset.state=kind;label.textContent=({offline:'Sin conexión',server:'Servidor inaccesible',auth:'Reingresá',syncing:'Sincronizando',pending:queueCount+' pendiente'+(queueCount===1?'':'s'),review:reviews+' para revisar'})[kind]||'';icon.innerHTML='<path d="'+(['pending','syncing'].includes(kind)?'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M12 7v5l3 2':kind==='review'?'M12 3 2 21h20ZM12 9v5m0 3h.01':'M3 9a15 15 0 0 1 18 0M6 12a10 10 0 0 1 12 0M9 15a5 5 0 0 1 6 0M12 19h.01')+'"/>';badge.title=kind==='auth'?'Iniciá sesión con la cuenta que guardó las cargas.':label.textContent;}
  async function queues(){if(!user||left)return;const version=++queueVersion;try{const rows=[...await RepoStockOffline(user).list(),...await RepoLibraryOffline(user).list()];if(left||version!==queueVersion)return;queueCount=rows.length;runnable=rows.filter(r=>!['conflict','rejected'].includes(r.state)).length;reviews=rows.filter(r=>['rejected','conflict'].includes(r.state)).length;render();}catch{}}
  function report(kind){if(left)return;const changed=state.kind!==kind;state={kind,user,at:Date.now()};render();if(changed)window.dispatchEvent(new CustomEvent('repotools:connectivity',{detail:state}));}
  async function check(){if(left)return state;if(navigator.onLine===false){report('offline');return state;}if(pending)return pending;
    controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),6000);
    pending=(async()=>{try{const response=await fetch('api/conectividad.php',{credentials:'same-origin',cache:'no-store',signal:controller.signal});if(response.status===401||response.status===403){report('auth');return state;}const data=await response.json();if(!response.ok||data.reachable!==true)report('server');else report(user&&Number(data.user_id)!==user?'auth':'online');}catch(error){if(!left)report(error.name==='AbortError'?'server':'offline');}finally{clearTimeout(timeout);pending=null;}return state;})();return pending;
  }
  async function tick(){if(left)return;const version=++tickVersion;await check();if(left||version!==tickVersion)return;if(state.kind==='online')window.dispatchEvent(new Event('repotools:sync-tick'));timer=setTimeout(tick,state.kind==='online'?20000:30000);}
  const listen=page?page.listen:window.addEventListener.bind(window);listen('online',()=>{clearTimeout(timer);tick();});listen('offline',()=>report('offline'));
  const visible=()=>{if(!doc.hidden){clearTimeout(timer);tick();}};doc.addEventListener('visibilitychange',visible);listen('pageshow',visible);
  listen('repotools:sync-state',e=>{if(e.detail.busy)running.add(e.detail.engine);else running.delete(e.detail.engine);render();queues();});listen('repotools:queue-changed',queues);
  const dispose=()=>{left=true;clearTimeout(timer);controller?.abort();doc.removeEventListener('visibilitychange',visible);badge.remove();};page?.onLeave(dispose);if(!page)window.addEventListener('pagehide',dispose,{once:true});
  window.RepoConnectivity={check,get state(){return state;},report};render();queues();tick();
})();
