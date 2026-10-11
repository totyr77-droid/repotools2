(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document,dialog=doc.getElementById('accountSecurityDialog');if(!dialog)return;
  const form=doc.getElementById('accountSecurityForm'),status=doc.getElementById('securityFormStatus'),saved=doc.getElementById('accountSecurityStatus'),submit=doc.getElementById('saveAccountSecurity'),fetch=page?.fetch||window.fetch.bind(window);
  let busy=false;
  function open(action){form.reset();status.textContent='';form.elements.action.value=action;const changing=action==='password';doc.getElementById('accountSecurityTitle').textContent=changing?'Cambiar contraseña':'Cerrar otras sesiones';submit.querySelector('span').textContent=changing?'Guardar contraseña':'Cerrar otras sesiones';for(const field of ['password','confirmation']){const input=form.elements[field];input.disabled=!changing;input.closest('label').hidden=!changing;}dialog.showModal();form.elements.current_password.focus();}
  doc.getElementById('changePassword').onclick=()=>open('password');doc.getElementById('closeOtherSessions').onclick=()=>open('sessions');dialog.querySelector('.app-close').onclick=()=>dialog.close();dialog.addEventListener('close',()=>{form.reset();form.querySelectorAll('input[type=text]').forEach(input=>input.type='password');form.querySelectorAll('.password-toggle').forEach(button=>button.setAttribute('aria-pressed','false'));});
  form.onsubmit=async event=>{event.preventDefault();if(busy)return;if(form.elements.action.value==='password'&&form.elements.password.value!==form.elements.confirmation.value){status.textContent='Las contraseñas nuevas no coinciden.';return;}
    if(form.elements.action.value==='sessions'&&!await window.RepoConfirm({title:'Cerrar otras sesiones',message:'Los otros dispositivos deberán volver a ingresar. Este dispositivo seguirá conectado.',confirmLabel:'Cerrar sesiones'}))return;
    if(!form.isConnected||!dialog.open||busy)return;busy=true;submit.disabled=true;status.textContent='Guardando…';
    try{const response=await fetch('api/seguridad.php',{method:'POST',body:new FormData(form)}),data=await response.json();if(!form.isConnected)return;if(!response.ok)throw Error(data.error||'No pudimos guardar el cambio.');saved.textContent=data.message+(data.mail_sent===false?' No pudimos enviar el correo de aviso.':'');dialog.close();}catch(error){if(form.isConnected)status.textContent=error.message;}finally{busy=false;submit.disabled=false;}
  };
})();
