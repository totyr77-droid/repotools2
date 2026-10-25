(()=>{
 'use strict';
 const page=window.RepoNavigation?.page,doc=page?.document||document,request=page?.fetch||fetch.bind(window),root=doc.getElementById('homeLocationNotice');if(!root)return;
 const user=root.dataset.user,key='repotools_location_notice_v1_user_'+user,memory=window.RepoLocationNoticeMemory??=new Set(),choices=window.RepoLocationNoticeChoices??=new Map(),$=id=>doc.getElementById(id),intro=$('onboardingDialog'),actions=$('homeLocationActions'),body=$('homeLocationBody'),status=$('homeLocationStatus');
 let left=false,decided=false,shown=false,busy=false,timer,turn=0,controller,branches=[];
 const glyph=name=>root.querySelector('[data-location-icon="'+name+'"] svg').cloneNode(true);
 const el=(tag,text,parent,cls='')=>{const n=doc.createElement(tag);n.textContent=text;n.className=cls;parent.append(n);return n;};
 function remembered(){if(memory.has(user))return true;try{return Boolean(localStorage.getItem(key));}catch{return false;}}
 function remember(choice){memory.add(user);choices.set(user,choice);try{localStorage.setItem(key,choice);}catch{}decided=true;}
 function introDone(){if(!intro||intro.dataset.auto!=='true')return true;if(intro.open)return false;const id=intro.dataset.user==='0'?'guest':intro.dataset.user;if(window.RepoOnboardingMemory?.has(id+':1'))return true;try{return localStorage.getItem('repotools_onboarding_seen_'+id)==='true'&&localStorage.getItem('repotools_onboarding_version_'+id)==='1';}catch{return false;}}
 function safe(){return !left&&root.isConnected&&introDone()&&!doc.querySelector('dialog[open],.repotools-loader--fullscreen')&&!document.documentElement.classList.contains('app-navigating')&&!document.documentElement.classList.contains('is-loading');}
 function update(){if(left)return;const visible=shown?safe():!decided&&!remembered()&&safe()&&Boolean(navigator.geolocation)&&window.isSecureContext!==false;if(visible)shown=true;if(root.hidden===visible)root.hidden=!visible;}
 function schedule(){clearTimeout(timer);timer=setTimeout(update,100);}
 function dismiss(){if(!decided)remember('dismissed');shown=false;root.hidden=true;}
 function action(label,primary,fn,icon){const b=el('button','',actions,primary?'library-primary':'library-secondary');b.type='button';if(icon)b.append(glyph(icon));b.append(doc.createTextNode(label));b.addEventListener('click',fn);return b;}
 function resetActions(){actions.replaceChildren();}
 function finish(title,text){$('homeLocationTitle').textContent=title;body.replaceChildren();if(text)el('p',text,body);status.textContent='';resetActions();action('Listo',true,dismiss,'check');}
 function found(branch){finish('Sucursal encontrada','Podés elegirla al usar un palet.');const badge=el('span',branch.name,body,'home-location-branch');badge.prepend(glyph('branch'));}
 function noMatch(){finish('Ubicación habilitada','La usaremos para sugerirte una sucursal al trabajar con un palet.');}
 function failure(message,retry=true){finish('Ubicación opcional',message);if(retry){resetActions();action('Ahora no',false,dismiss);action('Reintentar',true,locate,'locate');}}
 async function locate(){
  if(left||busy||!safe())return;remember('allowed');busy=true;const current=++turn;status.textContent='Buscando tu sucursal…';for(const b of actions.querySelectorAll('button'))b.disabled=true;
  try{
   const position=await new Promise((resolve,reject)=>navigator.geolocation.getCurrentPosition(resolve,reject,{enableHighAccuracy:true,timeout:12000,maximumAge:60000}));if(left||current!==turn)return;
   const {latitude,longitude,accuracy}=position.coords;if(!Number.isFinite(latitude)||!Number.isFinite(longitude)||Math.abs(latitude)>90||Math.abs(longitude)>180||!Number.isFinite(accuracy)||accuracy<0||accuracy>100)throw Error('No pudimos precisar tu ubicación. Probá de nuevo cuando tengas mejor señal.');
   controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),10000);let data;try{const response=await request('api/sucursales.php',{credentials:'same-origin',cache:'no-store',signal:controller.signal});data=await response.json();if(!response.ok)throw Error(data.error||'No pudimos consultar las sucursales.');}finally{clearTimeout(timeout);}if(left||current!==turn)return;
   branches=(data.branches||[]).filter(b=>b&&typeof b.name==='string');const rad=n=>n*Math.PI/180;
   const nearest=branches.filter(b=>b.latitude!==null&&b.longitude!==null&&b.latitude!==''&&b.longitude!==''&&Number.isFinite(Number(b.latitude))&&Number.isFinite(Number(b.longitude))).map(branch=>{const a=rad(Number(branch.latitude)-latitude),c=rad(Number(branch.longitude)-longitude),h=Math.sin(a/2)**2+Math.cos(rad(latitude))*Math.cos(rad(Number(branch.latitude)))*Math.sin(c/2)**2;return {branch,distance:6371000*2*Math.asin(Math.sqrt(Math.min(1,Math.max(0,h))))};}).filter(b=>b.distance+accuracy<=100).sort((a,b)=>a.distance-b.distance||Number(a.branch.id)-Number(b.branch.id));
   if(nearest.length)found(nearest[0].branch);else noMatch();
  }catch(error){if(left||current!==turn)return;if(error.code===1)remember('denied');failure(error.code===1?'No compartiste tu ubicación. Podés seguir usando la app y habilitarla desde los permisos del navegador.':error.code===2?'No pudimos obtener tu ubicación. Probá de nuevo cuando tengas mejor señal.':error.code===3?'La ubicación tardó demasiado. Podés reintentar o seguir usando la app.':error.name==='AbortError'?'No pudimos consultar las sucursales. Probá de nuevo con conexión.':error.message,error.code!==1);}finally{if(!left&&current===turn){busy=false;for(const b of actions.querySelectorAll('button'))b.disabled=false;}}
 }
 $('homeLocationLater').addEventListener('click',dismiss);$('homeLocationAllow').addEventListener('click',locate);
 const observer=new MutationObserver(schedule);observer.observe(doc.documentElement,{subtree:true,childList:true,attributes:true,attributeFilter:['open','class']});
 const listen=page?.listen||window.addEventListener.bind(window);listen('load',schedule,{once:true});listen('repotools:page-ready',schedule);
 if(remembered()){decided=true;}else if(navigator.permissions?.query){navigator.permissions.query({name:'geolocation'}).then(value=>{if(left||decided)return;if(value.state==='granted'||value.state==='denied'){remember(value.state==='granted'?'allowed':'denied');shown=false;root.hidden=true;}schedule();}).catch(schedule);}
 schedule();
 const leave=()=>{left=true;turn++;clearTimeout(timer);observer.disconnect();controller?.abort();};if(page)page.onLeave(leave);else window.addEventListener('pagehide',leave,{once:true});
})();
