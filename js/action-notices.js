(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document;
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
