(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document,request=page?.fetch||fetch;
  let left=false;page?.onLeave(()=>left=true);
  const $=id=>doc.getElementById(id),toast=(message,kind='success')=>!left&&window.RepoToast?.(message,{kind});
  if(location.hash==='#emailVerification'&&$('emailVerification'))$('emailVerification').open=true;
  let shareLink=null;
  $('shareApp')?.addEventListener('click',async event=>{event.preventDefault();try{
    if(!shareLink){const r=await request('api/referrals.php?link=1',{credentials:'same-origin',cache:'no-store'}),data=await r.json();if(left)return;if(!r.ok||!data.mine?.link)throw Error(data.error||'No pudimos preparar tu enlace.');shareLink=data.mine.link;}
    if(navigator.share){try{await navigator.share({title:'RepoTools',text:'Te invito a usar RepoTools:',url:shareLink});return;}catch(e){if(e.name==='AbortError')return;}}
    try{await navigator.clipboard.writeText(shareLink);toast('Enlace de invitación copiado.');}catch{let input=$('shareAppCopy');if(!input){input=doc.createElement('input');input.id='shareAppCopy';input.readOnly=true;input.setAttribute('aria-label','Tu enlace de invitación para copiar');input.style.gridColumn='1 / -1';$('shareApp').after(input);}input.value=shareLink;input.focus();input.select();toast('Copiá tu enlace de invitación.', 'info');}
  }catch(e){toast(e.message,'error');}});
  async function verify(button,email,password){button.disabled=true;try{const csrf=doc.querySelector('#accountEditForm [name=csrf]')?.value;const r=await request('api/verificacion.php',{method:'POST',body:new URLSearchParams({csrf,...(email?{email,current_password:password}:{})})}),data=await r.json();if(!r.ok)throw Error(data.error);toast(data.new_address?'Solicitud recibida. Si la dirección puede usarse, vas a recibir la verificación.':data.needed?'Correo enviado. Revisá tu bandeja.':'Tu correo ya está verificado.');}catch(e){toast(e.message,'error');}finally{button.disabled=false;}}
  $('resendVerification')?.addEventListener('click',e=>verify(e.currentTarget));$('changeEmailForm')?.addEventListener('submit',e=>{e.preventDefault();verify(e.currentTarget.querySelector('button'),e.currentTarget.elements.email.value,e.currentTarget.elements.current_password.value);e.currentTarget.elements.current_password.value='';});
})();
