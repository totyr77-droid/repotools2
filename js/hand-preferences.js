(()=>{'use strict';
const page=window.RepoNavigation?.page,doc=page?.document||document,form=doc.getElementById('handPreferenceForm');if(!form)return;
const status=doc.getElementById('handPreferenceStatus'),fetch=page?.fetch||window.fetch.bind(window),fields=[...form.querySelectorAll('input')];let csrf,busy=false,saved='';
function apply(hand){saved=hand;doc.body.dataset.hand=hand==='left'?'left':'right';doc.querySelectorAll('[data-profile-pending]').forEach(el=>el.hidden=!!hand);fields.forEach(input=>input.checked=input.value===hand);}
function lock(value){busy=value;fields.forEach(input=>input.disabled=value);form.setAttribute('aria-busy',String(value));}
async function request(body){const response=await fetch('api/preferencias.php',body?{method:'POST',body:new URLSearchParams(body)}:{cache:'no-store'}),data=await response.json();if(!response.ok)throw Error(data.error);return data;}
lock(true);request().then(data=>{csrf=data.csrf;apply(data.handedness);}).catch(e=>status.textContent=e.message).finally(()=>lock(false));
form.addEventListener('submit',event=>event.preventDefault());
fields.forEach(input=>input.addEventListener('change',async()=>{if(busy||!input.checked)return;const hand=input.value,previous=saved;lock(true);status.textContent='Guardando…';try{if(!csrf)csrf=(await request()).csrf;const data=await request({csrf,handedness:hand});apply(data.handedness);status.textContent='Preferencia guardada.';}catch(e){apply(previous);status.textContent=e.message;}finally{lock(false);}}));
})();
