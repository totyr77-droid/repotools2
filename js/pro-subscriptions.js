(()=>{'use strict';const page=window.RepoNavigation?.page,doc=page?.document||document,root=doc.getElementById('proSubscription');if(!root)return;
const abort=new AbortController(),start=doc.getElementById('proCheckout'),cancel=doc.getElementById('proCancel'),status=doc.getElementById('proStatus');let csrf=root.dataset.csrf,busy=false,subscription=null,cardForm=null;
const notify=(message,kind='error')=>window.RepoToast?.(message,{kind});
async function request(action,token=''){const response=await fetch('api/pro.php',{cache:'no-store',credentials:'same-origin',signal:abort.signal,...(action?{method:'POST',body:new URLSearchParams({csrf,action,...(token?{card_token:token}:{})})}:{})});const data=await response.json();if(!response.ok)throw Error(data.error||'No pudimos consultar la suscripción.');return data;}
function render(data){csrf=data.csrf||csrf;const s=data.subscription;subscription=s;const labels={beta_trial:'Acceso de prueba activo',free:'Cuenta Free',new:'Preparando suscripción',creating:'Verificando intento de suscripción',pending:'Pendiente de autorización',authorized:'Renovación autorizada',paused:'Renovación pausada',cancelled:'Renovación cancelada'};
status.textContent=s.enabled?(s.pro?'Pro de prueba hasta '+new Date(s.paid_until*1000).toLocaleDateString('es-AR')+' · ':'')+(labels[s.status]||'Pendiente de verificación'):'Estamos preparando las pruebas de pago. Todavía no podés contratar.';
const price=doc.getElementById('proPrice');price.hidden=!s.enabled;price.textContent='Importe de prueba: '+new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS'}).format(s.amount_cents/100)+' por mes.';doc.getElementById('proTerms').hidden=!s.enabled;
start.hidden=!s.enabled||!['free','new','creating','pending','beta_trial'].includes(s.status);start.textContent=s.status==='pending'?'Continuar en Mercado Pago':s.pro?'Probar suscripción':'Unirme a PRO · prueba';cancel.hidden=!s.enabled||!['pending','authorized','paused'].includes(s.status);}
async function run(action,button,token=''){if(busy)return;if(action==='cancel'&&!await window.RepoConfirm({title:'¿Cancelar renovación?',message:'Conservás Pro hasta terminar el período pagado.',confirmLabel:'Cancelar renovación',cancelLabel:'Volver'}))return;busy=true;start.disabled=cancel.disabled=true;button.setAttribute('aria-busy','true');button.disabled=true;try{const data=await request(action,token);if(data.checkout_url){const url=new URL(data.checkout_url);if(url.protocol!=='https:'||url.hostname!=='www.mercadopago.com.ar')throw Error('Enlace de pago no válido.');window.location.assign(url.href);}else{render(data);notify(action==='cancel'?'Renovación cancelada.':'Autorización recibida. El acceso se confirma al acreditarse el pago.','success');if(action==='checkout'){cardForm?.unmount?.();doc.getElementById('mpTestCardForm')?.remove();};}}catch(e){if(e.name!=='AbortError')notify(e.message);}finally{busy=false;start.disabled=cancel.disabled=false;button.removeAttribute('aria-busy');button.disabled=false;}}
async function openCard(){
 if(busy||doc.getElementById('mpTestCardForm'))return;
 if(!subscription.public_key){notify('Falta configurar la Public Key de prueba.');return;}
 busy=true;start.disabled=true;
 try{
   if(!window.MercadoPago)await new Promise((resolve,reject)=>{const script=doc.createElement('script');script.src='https://sdk.mercadopago.com/js/v2';script.onload=resolve;script.onerror=()=>reject(Error('No pudimos cargar Mercado Pago.'));doc.head.append(script);});
   const form=doc.createElement('form');form.id='mpTestCardForm';form.className='product-edit-form';
   const title=doc.createElement('p');title.textContent='Usá únicamente una tarjeta de prueba de Mercado Pago.';form.append(title);
   const map={id:form.id};
   for(const [key,label,type] of [['cardNumber','Número de tarjeta','frame'],['expirationDate','Vencimiento','frame'],['securityCode','Código de seguridad','frame'],['cardholderName','Titular','text'],['issuer','Emisor','select'],['installments','Cuotas','select'],['identificationType','Tipo de documento','select'],['identificationNumber','Documento de prueba','text'],['cardholderEmail','Correo del comprador de prueba','email']]){
     const wrapper=doc.createElement('label');wrapper.textContent=label;const field=doc.createElement(type==='frame'?'div':type==='select'?'select':'input');field.id='mpTest_'+key;if(type==='frame'){field.style.cssText='height:44px;border:1px solid #ccc;border-radius:8px;padding:10px;box-sizing:border-box';}else if(type!=='select')field.type=type;
     if(key==='cardholderEmail'){field.value=subscription.test_buyer;field.readOnly=true;}
     wrapper.append(field);form.append(wrapper);map[key]={id:field.id};
   }
   const submit=doc.createElement('button');submit.type='submit';submit.className='library-primary';submit.textContent='Autorizar suscripción de prueba';form.append(submit);
   root.querySelector('.pro-actions').after(form);
   cardForm=new window.MercadoPago(subscription.public_key,{locale:'es-AR'}).cardForm({amount:String(subscription.amount_cents/100),iframe:true,form:map,callbacks:{onFormMounted:error=>{if(error)notify('No pudimos preparar la tarjeta de prueba.');},onSubmit:async event=>{event.preventDefault();const {token}=cardForm.getCardFormData();if(!token){notify('Revisá los datos de prueba.');return;}await run('checkout',submit,token);},onError:()=>notify('Revisá los datos de la tarjeta de prueba.')}});
 }catch(e){doc.getElementById('mpTestCardForm')?.remove();notify(e.message);}finally{busy=false;start.disabled=false;}
}
start.addEventListener('click',()=>subscription?.card_required?openCard():run('checkout',start),{signal:abort.signal});cancel.addEventListener('click',()=>run('cancel',cancel),{signal:abort.signal});
request().then(render).catch(e=>{if(e.name!=='AbortError'){status.textContent='No pudimos consultar tu suscripción. Volvé a abrir esta pantalla.';notify(e.message);}});
page?.onLeave?.(()=>{abort.abort();cardForm?.unmount?.();});})();
