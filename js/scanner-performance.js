(() => {
  'use strict';
  let config=window.repotoolsScannerMetrics;
  if(!config&&document.body.classList.contains('offline-shell'))try{const snapshot=JSON.parse(localStorage.getItem('repotools-offline-view')||'null');if(Number(snapshot?.user)>0&&/^\d{1,3}\.\d{2}$/.test(snapshot.metricsVersion||'')&&Math.max(snapshot.stock?.at||0,snapshot.libraries?.at||0)>Date.now()-7*86400000)config={user:Number(snapshot.user),csrf:'',version:snapshot.metricsVersion,manager:snapshot.metricsManager===true};}catch{}
  if(!config?.user||!window.RepoEANScanner)return;
  const page=window.RepoNavigation?.page,key='repotools:scanner-metrics:'+config.user;
  const sessions=new Map();let memory=[],timer,persistTimer,pendingRecords=[],inFlight=false,left=false;
  const read=()=>{try{const value=JSON.parse(localStorage.getItem(key)||'[]');return Array.isArray(value)?value.slice(-100):[];}catch{return memory;}};
  const save=rows=>{memory=rows.slice(-100);try{localStorage.setItem(key,JSON.stringify(memory));}catch{}};
  const browser=()=>{const ua=navigator.userAgent;return /Edg\//.test(ua)?'edge':/Firefox|FxiOS/.test(ua)?'firefox':/Chrome|CriOS/.test(ua)?'chrome':/Safari/.test(ua)?'safari':'other';};
  const device=()=>/iPad|Tablet/.test(navigator.userAgent)||(/Macintosh/.test(navigator.userAgent)&&navigator.maxTouchPoints>1)?'tablet':/Mobile/.test(navigator.userAgent)?'mobile':'desktop';
  const context=reader=>reader==='offlineReader'?'offline':reader==='contenedor'?'home':reader==='stockReader'?'stock':reader==='libraryReader'?'library':/user/i.test(reader)?'users':/relay/i.test(reader)?'relay':'other';
  const ms=value=>Math.max(0,Math.round(value));
  function schedule(delay=1500){clearTimeout(timer);if(!left)timer=setTimeout(flush,delay);}
  function commit(){clearTimeout(persistTimer);if(pendingRecords.length)save([...read(),...pendingRecords.splice(0)]);}
  async function flush(){
    commit();
    if(inFlight||navigator.onLine===false||!config.csrf)return;
    const batch=read().slice(0,20);if(!batch.length)return;
    inFlight=true;const control=new AbortController(),timeout=setTimeout(()=>control.abort(),8000);
    try{
      const response=await fetch(new URL('api/scanner-metrics.php',document.baseURI),{method:'POST',credentials:'same-origin',cache:'no-store',keepalive:true,signal:control.signal,body:new URLSearchParams({csrf:config.csrf,user:String(config.user),records:JSON.stringify(batch)})});
      if(response.ok){const result=await response.json();config.manager=result.manager===true;const sent=new Set(batch.map(r=>r.event_id));save(read().filter(r=>!sent.has(r.event_id)));window.dispatchEvent(new Event('repotools:scanner-metrics-synced'));if(read().length)schedule(1000);}
      else if(response.status===429||response.status>=500)schedule(response.status===429?60000:15000);
    }catch{schedule(15000);/* Diagnostic delivery never interrupts a scan. */}
    finally{clearTimeout(timeout);inFlight=false;}
  }
  function record(state,event,outcome){
    if(state.recorded)return;state.recorded=true;
    const paused=(state.paused||0)+(state.hiddenAt!=null?Math.max(0,event.at-state.hiddenAt):0),scan=ms(event.at-(state.ready??state.started)-paused),opening=state.cycle===0&&state.open!=null?ms(state.open):null;
    if(scan>600000||opening>600000)return;
    const from=event.at-(event.confirmationMs||0),ranges=[...(state.hiddenRanges||[])];if(state.hiddenAt!=null)ranges.push([state.hiddenAt,event.at]);
    const hiddenConfirmation=ranges.reduce((sum,[start,end])=>sum+Math.max(0,Math.min(end,event.at)-Math.max(start,from)),0);
    const confirmation=event.confirmationMs==null?null:Math.min(scan,ms(event.confirmationMs-hiddenConfirmation));
    const row={event_id:crypto.randomUUID(),mode:event.mode,context:context(event.reader),outcome,source:outcome==='confirmed'?(event.source||'camera'):'none',app_version:config.version,browser:browser(),device:device(),rapid:event.rapid?1:0,open_ms:opening,scan_ms:scan,confirm_ms:outcome==='confirmed'?confirmation:null,total_ms:scan+(opening||0),width:state.width||0,height:state.height||0};
    pendingRecords.push(row);clearTimeout(persistTimer);persistTimer=setTimeout(()=>{commit();schedule();if(left)flush();},0);
    if(outcome==='confirmed'&&config.manager)setTimeout(()=>{if(left||!config.manager)return;const label=event.mode==='qr'?'QR':'EAN';window.RepoToast?.(`${label} confirmado en ${(scan/1000).toLocaleString('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2})} s desde cámara lista.`,{kind:'info',key:'scanner-performance',duration:1500});},0);
  }
  const stop=RepoEANScanner.observe(event=>{
    if(!event.sessionId)return;
    let state=sessions.get(event.sessionId);
    if(event.stage==='camera-request'){state={started:event.at,cycle:0};sessions.set(event.sessionId,state);}
    if(!state)return;
    if((event.stage==='first-frame'||event.stage==='camera-open')&&state.ready==null){state.open=event.elapsed;state.ready=event.at;if(document.hidden)state.hiddenAt=event.at;}
    if(event.stage==='camera-capabilities'){state.width=Number.isInteger(event.width)?event.width:0;state.height=Number.isInteger(event.height)?event.height:0;}
    if(event.stage==='ready'){state.cycle=event.cycle;state.ready=event.at;state.firstDecoded=null;state.recorded=false;state.paused=0;state.hiddenRanges=[];state.hiddenAt=document.hidden?event.at:null;}
    if(event.stage==='decoded'&&state.firstDecoded==null)state.firstDecoded=event.at;
    if(event.stage==='confirmed')record(state,event,'confirmed');
    if(event.stage==='closed'){if(!state.recorded)record(state,event,event.outcome==='error'?'error':'cancelled');sessions.delete(event.sessionId);}
  });
  const visibility=()=>{const now=performance.now();for(const state of sessions.values()){if(state.recorded||state.ready==null)continue;if(document.hidden&&state.hiddenAt==null)state.hiddenAt=now;else if(!document.hidden&&state.hiddenAt!=null){state.paused=(state.paused||0)+Math.max(0,now-state.hiddenAt);(state.hiddenRanges||=[]).push([state.hiddenAt,now]);state.hiddenAt=null;}}};document.addEventListener('visibilitychange',visibility);
  const online=()=>flush(),hide=()=>flush();
  window.addEventListener('online',online);window.addEventListener('pagehide',hide);
  page?.onLeave(()=>{left=true;clearTimeout(timer);stop();flush();window.removeEventListener('online',online);window.removeEventListener('pagehide',hide);document.removeEventListener('visibilitychange',visibility);});
  schedule(2000);
})();
