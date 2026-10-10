(() => {
 const page=window.RepoNavigation?.page,document=page?.document||globalThis.document,fetch=page?.fetch||globalThis.fetch.bind(globalThis),form=document.getElementById('stockPreferences');if(!form)return;
 const days=document.getElementById('stockExpiryDays'),status=document.getElementById('stockPreferenceStatus');let csrf,key,busy=false;
 async function request(data){const response=await fetch('api/stock.php',data?{method:'POST',credentials:'same-origin',body:new URLSearchParams({...data,csrf,operation_key:key})}:{credentials:'same-origin',cache:'no-store'}),result=await response.json();if(!response.ok)throw Error(result.error);return result;}
 days.disabled=true;form.querySelector('button').disabled=true;
 days.addEventListener('input',()=>key=null);
 request().then(data=>{csrf=data.csrf;days.value=data.expiry_days;}).catch(e=>status.textContent=e.message).finally(()=>{days.disabled=false;form.querySelector('button').disabled=false;});
 form.addEventListener('submit',async e=>{e.preventDefault();if(busy)return;busy=true;form.querySelector('button').disabled=true;try{if(!csrf)csrf=(await request()).csrf;key??=crypto.randomUUID();await request({action:'preferences',expiry_days:days.value});key=null;status.textContent='Preferencia guardada.';}catch(e){status.textContent=e.message;}finally{busy=false;form.querySelector('button').disabled=false;}});
})();
