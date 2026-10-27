(()=>{'use strict';const page=window.RepoNavigation?.page,doc=page?.document||document,dialog=doc.getElementById('proUpgradeDialog');if(!dialog)return;
const abort=new AbortController(),join=doc.getElementById('proUpgradeJoin'),review=doc.getElementById('proUpgradeReview');let reviewButton=null,busy=false,skip=null,planIndex=0,plansMode=false;
const features={plans:'Elegí cómo querés trabajar: tu cuenta personal o un equipo.',transfer:'Transferir a otro palet o usuario es una función Pro.',share_pallet:'Compartir un palet es una función Pro.',share_stock:'Compartir tu stock es una función Pro.',transfer_owner:'Transferir la propiedad de un palet es una función Pro.',invite:'Invitar a otra cuenta es una función Pro.'};
let moving=false,motionTimer=null;
const cards=['proPersonalPanel','proSupervisionPanel','proSupermarketPanel'].map(id=>doc.getElementById(id));
const slot=(i,selected)=>((i-selected+4)%3)-1;
function selectPlan(index){
 const next=(index+3)%3;if(moving&&dialog.open)return;
 const animate=dialog.open&&next!==planIndex&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 const moveFocus=dialog.open&&dialog.contains(doc.activeElement),previous=planIndex;planIndex=next;
 moving=animate;clearTimeout(motionTimer);
 for(const [i,card] of cards.entries()){
  const position=slot(i,planIndex),wrap=animate&&Math.abs(position-slot(i,previous))===2;
  // A recycled neighbor must never sweep across the selected card.
  card.style.transition=wrap||!animate?'none':'';
  card.style.opacity=wrap?'0':'1';
  card.style.transform='translateX(calc('+position+' * (100% + 12px)))';
  card.inert=i!==planIndex;card.setAttribute('aria-hidden',String(i!==planIndex));
 }
 review.hidden=planIndex!==0||!reviewButton;
 if(moveFocus)dialog.querySelector('article:not([inert]) [data-pro-close]').focus({preventScroll:true});
 if(animate)motionTimer=setTimeout(()=>{for(const card of cards){card.style.transition='opacity 100ms ease-out';card.style.opacity='1';}moving=false;},280);
}


function show(feature,ready=false,button=null){clearTimeout(motionTimer);moving=false;plansMode=feature==='plans';dialog.classList.toggle('pro-plans-mode',plansMode);join.textContent='Unirme a PRO';doc.getElementById('proUpgradeReason').textContent=features[feature]||'Esta función está incluida en Pro.';join.disabled=!ready;doc.getElementById('proUpgradeAvailability').textContent=ready?'Podés consultar el importe y la renovación antes de contratar.':'La contratación está en preparación. Tu stock sigue disponible.';reviewButton=button?.id==='managePalletAccess'?button:null;selectPlan(0);if(!dialog.open)dialog.showModal();}
dialog.addEventListener('keydown',event=>{if(plansMode&&(event.key==='ArrowLeft'||event.key==='ArrowRight')){event.preventDefault();selectPlan(planIndex+(event.key==='ArrowRight'?1:-1));dialog.querySelector('article:not([inert]) [data-pro-close]').focus({preventScroll:true});}},{signal:abort.signal});let swipe=null;dialog.addEventListener('touchstart',event=>{if(!plansMode||event.target.closest('button,a,input,select,textarea'))return;const t=event.touches[0];swipe=t?{x:t.clientX,y:t.clientY}:null;},{passive:true,signal:abort.signal});dialog.addEventListener('touchend',event=>{const t=event.changedTouches[0];if(swipe&&t&&Math.abs(t.clientX-swipe.x)>70&&Math.abs(t.clientY-swipe.y)<45)selectPlan(planIndex+(t.clientX<swipe.x?1:-1));swipe=null;},{passive:true,signal:abort.signal});
let drag=null,ignoreClick=false;dialog.addEventListener('pointerdown',event=>{if(event.pointerType!=='mouse'||event.button!==0||!plansMode||event.target.closest('button,a,input,select,textarea'))return;drag={x:event.clientX,y:event.clientY};dialog.setPointerCapture(event.pointerId);},{signal:abort.signal});dialog.addEventListener('pointerup',event=>{if(drag&&Math.abs(event.clientX-drag.x)>70&&Math.abs(event.clientY-drag.y)<45){ignoreClick=true;selectPlan(planIndex+(event.clientX<drag.x?1:-1));setTimeout(()=>{ignoreClick=false;},0);}drag=null;},{signal:abort.signal});dialog.addEventListener('pointercancel',()=>{drag=null;},{signal:abort.signal});
async function allowed(feature,button=null){if(busy)return false;busy=true;if(button){button.disabled=true;button.setAttribute('aria-busy','true');}try{const response=await fetch('api/pro-access.php',{cache:'no-store',credentials:'same-origin',signal:abort.signal});const data=await response.json();if(!response.ok)throw Error(data.error||'No pudimos comprobar tu plan.');if(data.pro)return true;show(feature,data.checkout_ready,button);return false;}catch(e){if(e.name!=='AbortError')window.RepoToast?.(e.message,{kind:'error'});return false;}finally{busy=false;if(button){button.disabled=false;button.removeAttribute('aria-busy');}}}
doc.addEventListener('click',async event=>{const link=event.target.closest?.('a[data-pro-join],a[data-pro-plans]');if(!link||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;event.preventDefault();if(link.hasAttribute('data-pro-plans')){if(busy)return;busy=true;try{const response=await fetch('api/pro-access.php',{cache:'no-store',credentials:'same-origin',signal:abort.signal});const data=await response.json();if(!response.ok)throw Error(data.error||'No pudimos consultar tu plan.');show('plans',data.checkout_ready);join.textContent=data.pro?'Gestionar PRO':'Unirme a PRO';}catch(e){if(e.name!=='AbortError')window.RepoToast?.(e.message,{kind:'error'});}finally{busy=false;}}else if(await allowed('subscription'))location.assign(link.href);},{capture:true,signal:abort.signal});
const targets={transferPallet:'transfer',stockTransferToPallet:'transfer',sharePalletLabel:'share_pallet',shareStockSelection:'share_stock',stockTransferToUser:'transfer_owner',managePalletAccess:'invite'};
doc.addEventListener('click',async event=>{const button=event.target.closest?.('button');if(!button||!targets[button.id])return;if(skip===button){skip=null;return;}event.preventDefault();event.stopImmediatePropagation();if(await allowed(targets[button.id],button)){skip=button;button.click();}},{capture:true,signal:abort.signal});
doc.addEventListener('repotools:pro-required',event=>show(event.detail?.feature),{signal:abort.signal});
dialog.addEventListener('click',event=>{if(ignoreClick)return;if(event.target.closest('[data-pro-close]'))dialog.close();else if(plansMode&&!event.target.closest('button,a,input,select,textarea')){const rect=doc.getElementById('proPlanTrack').getBoundingClientRect();if(event.clientX<rect.left)selectPlan(planIndex-1);else if(event.clientX>rect.right)selectPlan(planIndex+1);}},{signal:abort.signal});
review.addEventListener('click',()=>{const button=reviewButton;dialog.close();if(button){skip=button;button.click();}},{signal:abort.signal});
join.addEventListener('click',()=>{if(!join.disabled)location.assign('pro.php?join=1');},{signal:abort.signal});
window.RepoProAccess={require:allowed,show};page?.onLeave(()=>{clearTimeout(motionTimer);abort.abort();if(window.RepoProAccess?.require===allowed)delete window.RepoProAccess;});})();
