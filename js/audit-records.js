(() => {
'use strict';
let generation=0;
const number=v=>Number(v).toLocaleString('es-AR');
const roles={usuario:'Usuario',moderador:'Moderador',administrador:'Administrador',manager:'Manager'};
const outcomes={denied:'Sin permiso',conflict:'En conflicto',rejected:'Rechazado'};
function render(item,list,{document,request,stamp,icon}){
  const node=(tag,value,host,cls='')=>{const el=document.createElement(tag);if(value!==undefined)el.textContent=value;if(cls)el.className=cls;host?.append(el);return el;};
  const card=node('article','',list,'report-card report-audit-card');
  const button=node('button','',card,'audit-record-open');button.type='button';button.setAttribute('aria-haspopup','dialog');
  const heading=node('div','',button,'report-log-heading');const title=node('strong',item.action_label,heading);icon(title,({users:'user',goods:'pallet',moderation:'shield',management:'branch'})[item.section]||'file');
  node('time',stamp(item.created_at),heading,'audit-record-date');
  const by=node('span',item.actor+' · '+(roles[item.actor_role]||item.actor_role),button,'audit-record-actor');icon(by,'user');
  const meta=node('span','',button,'audit-record-meta');
  if(Number(item.event_count)>1)node('span',number(item.event_count)+' movimientos · una operación',meta,'audit-count');
  else if(item.pallet_code)node('span','Palet '+item.pallet_code,meta);
  else if(item.ean)node('span','EAN '+item.ean,meta);
  else if(item.target_user)node('span',item.target_name||'Usuario #'+item.target_user,meta);
  if(item.branch_name){const branch=node('span',item.branch_name,meta,'stock-branch-label');icon(branch,'branch');}
  if(outcomes[item.result])node('span',outcomes[item.result],meta,'report-activity-badge report-activity-danger');
  if(!meta.childNodes.length)meta.remove();
  button.setAttribute('aria-label','Ver detalle: '+item.action_label+' · '+item.actor);
  button.addEventListener('click',async()=>{
    const dialog=document.getElementById('auditRecordDialog'),content=document.getElementById('auditRecordContent'),status=document.getElementById('auditRecordStatus'),title=document.getElementById('auditRecordTitle');
    const attempt=++generation;content.replaceChildren();status.textContent='Cargando detalle…';title.textContent=item.action_label;icon(title,({users:'user',goods:'pallet',moderation:'shield',management:'branch'})[item.section]||'file');
    const close=dialog.querySelector('[data-close-audit]');close.onclick=()=>dialog.close();dialog.onclose=()=>{if(dialog.open)return;++generation;if(button.isConnected)button.focus({preventScroll:true});};dialog.showModal();
    try{
      const response=await request('?view=logs&grouped=1&event='+encodeURIComponent(item.event_id));if(attempt!==generation||!dialog.isConnected||!dialog.open)return;
      const record=response.items?.[0];if(!record)throw Error('El registro ya no está disponible con tus permisos.');
      status.textContent='';
      node('p',item.actor+' · '+(roles[item.actor_role]||item.actor_role)+' · '+stamp(item.created_at),content,'audit-record-identity');
      for(const event of record.events||[]){
        const part=node('section','',content,'audit-operation-part');
        if(record.events.length>1)node('h3',event.pallet_code?'Palet '+event.pallet_code:event.action_label,part);
        if(event.branch_name){const label=node('span',event.branch_name,part,'stock-branch-label');icon(label,'branch');}
        if(event.target_user)node('p','Persona: '+(event.target_name||'Usuario #'+event.target_user),part);
        if(outcomes[event.result])node('p',outcomes[event.result],part,'report-activity-badge report-activity-danger');
        if(event.account){node('p','Invitó: '+event.account.inviter,part);node('p',event.account.verified?'Correo verificado':'Verificación pendiente',part);}
        if(event.device_kind)node('p',(event.action==='session_ended'?'Cerró sesión desde ':'Inició sesión desde ')+(event.device_kind==='mobile'?'un celular.':'una computadora o tablet.'),part);
        if(event.action==='transfer_commit'&&!event.transfer)node('p','El destino no quedó registrado en este movimiento anterior.',part);
        if(event.transfer){const t=event.transfer;node('p',t.mode==='user'?'Propiedad transferida a '+t.target_name:'Palet '+t.source_code+' → Palet '+t.target_code,part,'audit-transfer-route');node('p',number(t.units)+' unidades transferidas',part);}
        if(event.label_codes?.length)node('p',number(event.label_codes.length)+' etiquetas · '+event.label_codes.join(' · '),part);
        for(const product of event.products||[]){
          const row=node('div','',part,'audit-product-change');node('strong',product.name||'Producto',row);node('small','EAN '+product.ean,row);
          const transferred=event.action==='transfer_commit';
          const delta=node('span',transferred?'Transferido · '+number(product.before)+' unidades':(product.status==='added'?'Agregado':product.status==='removed'?'Eliminado':product.delta>0?'Carga':product.delta<0?'Retiro':'Datos actualizados')+(product.delta?' · '+(product.delta>0?'+':'')+number(product.delta)+' unidades':''),row,'audit-delta');
          delta.dataset.tone=transferred?'info':product.delta<0?'danger':product.delta>0?'success':'info';
          if(product.status==='updated'&&product.name_before!==product.name_after)node('p','Referencia: '+(product.name_before||'Sin referencia')+' → '+(product.name_after||'Sin referencia'),row);
          if(!transferred)node('p',number(product.before)+' → '+number(product.after)+' unidades',row);
          const date=v=>v?v.split('-').reverse().join('/'):'Sin vencimiento';
          node('small',product.expires_before!==product.expires_after&&product.status==='updated'?'Vencimiento: '+date(product.expires_before)+' → '+date(product.expires_after):'Vencimiento: '+date(product.expires_after||product.expires_before),row);
        }
        const changes=(event.changes||[]).filter(c=>!c.label.startsWith('Producto ')||!event.products?.length);
        const value=(v,key)=>v===null||v===''?'Sin dato':typeof v==='boolean'||['released','activated','blocked','verified'].includes(key)?(v===true||v===1||v==='1'?'Sí':'No'):key==='role'?(roles[v]||String(v)):String(v);
        for(const change of changes){const row=node('div','',part,'audit-change');node('strong',change.label,row);node('p',value(change.before,change.field)+' → '+value(change.after,change.field),row);if(change.delta!==null&&change.delta!==undefined)node('small',(change.delta>0?'+':'')+number(change.delta)+' unidades',row);}
        if(!changes.length&&!event.products?.length&&!event.transfer&&!event.device_kind&&!event.label_codes?.length&&!event.account)node('p',event.action_label+'.',part);
        if(event.synced_at!==event.occurred_at)node('small','Sincronizado: '+stamp(event.synced_at),part);
      }
      if(record.events?.some(e=>e.can_technical)){const technical=node('details','',content,'audit-technical');node('summary','Auditoría técnica',technical);node('pre',JSON.stringify(record.events.map(e=>({evento:e.event_id,operacion:e.operation_id,antes:e.before,despues:e.after})),null,2),technical);}
    }catch(error){if(attempt===generation){status.textContent=error.message;const retry=node('button','Reintentar',content,'library-secondary');retry.type='button';retry.onclick=()=>{dialog.close();button.click();};}}
  });
}
window.RepoAuditRecords={render};
})();
