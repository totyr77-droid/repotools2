(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  let toast, toastTimer;
  window.RepoToast = message => {
    clearTimeout(toastTimer); toast?.remove();
    toast=doc.createElement('p');toast.className='repotools-send-toast';toast.setAttribute('role','status');
    toast.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/></svg>';
    const label=doc.createElement('span');label.textContent=message;toast.append(label);
    if(typeof toast.showPopover==='function'){
      toast.setAttribute('popover','manual');doc.body.append(toast);toast.showPopover();
    }else{
      const dialogs=[...doc.querySelectorAll('dialog[open]')];(doc.activeElement?.closest('dialog[open]')||dialogs.at(-1)||doc.body).append(toast);
    }
    toastTimer=setTimeout(()=>toast?.remove(),2400);
  };
  page?.onLeave(()=>{clearTimeout(toastTimer);toast?.remove();});
  const selector='[role="status"],.library-status,.library-form-status,.account-error';
  function decorate(el){
    const text=el.textContent.trim();
    const success=/^(?:Producto (?:guardado|eliminado|actualizado)|Colección (?:guardada|creada|eliminada|renombrada)|Sucursal (?:guardada|eliminada|seleccionada)|Enlace (?:copiado|desactivado|listo)|Contraseña actualizada|Las otras sesiones se cerraron|Preferencia guardada|Rango actualizado|Datos de cuenta actualizados|Invitación enviada|Descripción guardada|Acceso eliminado|Palet (?:eliminado|guardado|disponible|listo)|Etiquetas listas|Reporte (?:#\d+ )?enviado|Imagen (?:descargada|compartida)|Dejaste de participar|Solicitud enviada|Aprobado\.|Reporte rechazado\.|Guardado en|Se guardó|Se agregó|Carga el primer producto antes del)/i.test(text);
    const error=el.classList.contains('account-error')||/^(?:No pudimos|No puedes|Error|La sesión venció|Revisa |Ingresa .*válido)/i.test(text);
    if(success||error)el.dataset.notice=error?'error':'success';else delete el.dataset.notice;
  }
  function refresh(){doc.querySelectorAll(selector).forEach(decorate);}
  refresh();
  const observer=new MutationObserver(refresh);
  observer.observe(doc.body,{childList:true,subtree:true,characterData:true});
  page?.onLeave(()=>observer.disconnect());
})();
