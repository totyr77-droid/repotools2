(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document,root=doc.getElementById('accountPreferences');
  if(!root)return;
  const days=doc.getElementById('stockExpiryDays'),handForm=doc.getElementById('handPreferenceForm'),hands=[...handForm.querySelectorAll('input')];
  let csrf=root.dataset.csrf,savedDays=days.value,expiryBusy=false,expiryTimer,savedHand='';
  const notify=(message,kind='success')=>{if(root.isConnected)window.RepoToast?.(message,{kind});};
  function applyHand(value){savedHand=value;doc.body.dataset.handPreference=['left','right'].includes(value)?value:'';doc.body.dataset.hand=value==='left'?'left':'right';hands.forEach(input=>input.checked=input.value===value);doc.querySelectorAll('[data-profile-pending]').forEach(el=>el.hidden=!!value);}
  async function save(body){
    const response=await window.fetch('api/preferencias.php',{method:'POST',credentials:'same-origin',keepalive:true,body:new URLSearchParams({...body,csrf})});
    const data=await response.json();if(!response.ok)throw Error(data.error||'No pudimos guardar la preferencia.');csrf=data.csrf;return data;
  }
  function validDays(){return days.value!==''&&Number.isInteger(Number(days.value))&&Number(days.value)>=0&&Number(days.value)<=365;}
  async function saveDays(){
    clearTimeout(expiryTimer);if(expiryBusy||!validDays()||days.value===savedDays)return;
    const rawValue=days.value,value=String(Number(rawValue));expiryBusy=true;days.setAttribute('aria-busy','true');
    try{const data=await save({expiry_days:value});savedDays=String(data.expiry_days);if(days.value===rawValue)days.value=savedDays;notify('Días de anticipación actualizados.');}
    catch(error){if(days.value===rawValue)days.value=savedDays;notify(error.message,'error');}
    finally{expiryBusy=false;days.removeAttribute('aria-busy');if(validDays()&&days.value!==savedDays)expiryTimer=setTimeout(saveDays,600);}
  }
  days.addEventListener('input',()=>{clearTimeout(expiryTimer);if(validDays())expiryTimer=setTimeout(saveDays,600);});
  const finishDays=()=>{if(!validDays()){days.value=savedDays;notify('Elegí entre 0 y 365 días de anticipación.','error');}else saveDays();};
  days.addEventListener('change',finishDays);days.addEventListener('blur',finishDays);
  doc.getElementById('stockPreferences').addEventListener('submit',event=>{event.preventDefault();finishDays();});
  for(const field of ['sound','vibration']){
    const input=doc.getElementById('preference-'+field),state=root.querySelector('[data-preference-state="'+field+'"]');
    let saved=input.checked;
    input.addEventListener('change',async()=>{
      const value=input.checked;input.disabled=true;
      try{const data=await save({[field]:value?'1':'0'});saved=data[field];input.checked=saved;doc.body.dataset[field==='sound'?'scanSound':'scanVibration']=saved?'1':'0';notify(field==='sound'?(saved?'Sonido activado.':'Sonido desactivado.'):(saved?'Vibración activada.':'Vibración desactivada.'));}
      catch(error){input.checked=saved;notify(error.message,'error');}
      finally{state.textContent=input.checked?'Activado':'Desactivado';input.disabled=false;}
    });
  }
  savedHand=hands.find(input=>input.checked)?.value||'';
  handForm.addEventListener('submit',event=>event.preventDefault());
  hands.forEach(input=>input.addEventListener('change',async()=>{
    if(!input.checked)return;const value=input.value;hands.forEach(field=>field.disabled=true);
    try{const data=await save({handedness:value});applyHand(data.handedness);notify('Comodidad de uso actualizada.');}
    catch(error){applyHand(savedHand);notify(error.message,'error');}
    finally{hands.forEach(field=>field.disabled=false);}
  }));
  // Keep the last valid edit when leaving during the debounce. Its POST
  // finishes independently of canceled AJAX content requests.
  page?.onLeave(()=>{clearTimeout(expiryTimer);saveDays();});
  (page?.listen||window.addEventListener.bind(window))('pagehide',()=>{clearTimeout(expiryTimer);saveDays();});
})();
