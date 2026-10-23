(() => {
'use strict';
let generation=0;
const number=v=>Number(v).toLocaleString('es-AR');
const roles={usuario:'Usuario',moderador:'Moderador',administrador:'Administrador',manager:'Manager'};
const outcomes={denied:'Sin permiso',conflict:'En conflicto',rejected:'Rechazado'};
function render(item,list,{document,request,stamp,icon}){
  const node=(tag,value,host,cls='')=>{const el=document.createElement(tag);if(value!==undefined)el.textContent=value;if(cls)el.className=cls;host?.append(el);return el;};
  const drawIcon=(host,name)=>{const svg=document.querySelector('[data-audit-icon="'+name+'"] svg');if(svg)host.prepend(svg.cloneNode(true));else icon(host,name);};
  const compare=(host,before,after)=>{const row=node('div','',host,'audit-comparison');for(const [label,value,cls]of [['Antes',before,'before'],['Después',after,'after']]){const side=node('span','',row,'audit-comparison-'+cls);node('small',label,side);node('strong',value,side);}return row;};
  const linked=(host,label,link,cls='')=>{const el=node(link?'a':'span',label,host,cls);if(link)el.href=link.href;return el;};
  const card=node('article','',list,'report-card report-audit-card');
  card.dataset.tone=item.action_tone||'neutral';
  const button=node('button','',card,'audit-record-open');button.type='button';button.setAttribute('aria-haspopup','dialog');
  const heading=node('div','',button,'report-log-heading');const title=node('strong',item.action_label,heading);drawIcon(title,item.action_icon||'info');
  node('time',stamp(item.created_at),heading,'audit-record-date');
  const by=node('span',item.actor+' · '+(roles[item.actor_role]||item.actor_role),button,'audit-record-actor');icon(by,'user');
  const meta=node('span','',button,'audit-record-meta');
  if(Number(item.event_count)>1)node('span',number(item.event_count)+' movimientos · una operación',meta,'audit-count');
  if(item.pallet_code){const code=node('span','Palet '+item.pallet_code,meta,'audit-entity');drawIcon(code,'pallet');}
  if(item.ean){const code=node('span',item.ean,meta,'audit-entity');drawIcon(code,'barcode');}
  if(item.target_user){const user=node('span',item.target_name||'Usuario #'+item.target_user,meta,'audit-entity');drawIcon(user,'user');}
  if(item.branch_name){const branch=node('span',item.branch_name,meta,'stock-branch-label');icon(branch,'branch');}
  if(outcomes[item.result])node('span',outcomes[item.result],meta,'report-activity-badge report-activity-danger');
  if(!meta.childNodes.length)meta.remove();
  button.setAttribute('aria-label','Ver detalle: '+item.action_label+' · '+item.actor);
  button.addEventListener('click',async()=>{
    const dialog=document.getElementById('auditRecordDialog'),content=document.getElementById('auditRecordContent'),status=document.getElementById('auditRecordStatus'),title=document.getElementById('auditRecordTitle');
    const attempt=++generation;content.replaceChildren();status.textContent='Cargando detalle…';title.textContent=item.action_label;title.dataset.tone=item.action_tone||'neutral';drawIcon(title,item.action_icon||'info');
    const close=dialog.querySelector('[data-close-audit]');close.onclick=()=>dialog.close();dialog.onclose=()=>{if(dialog.open)return;++generation;if(button.isConnected)button.focus({preventScroll:true});};dialog.showModal();
    try{
      const response=await request('?view=logs&grouped=1&event='+encodeURIComponent(item.event_id));if(attempt!==generation||!dialog.isConnected||!dialog.open)return;
      const record=response.items?.[0];if(!record)throw Error('El registro ya no está disponible con tus permisos.');
      status.textContent='';
      const identity=node('p','',content,'audit-record-identity');linked(identity,item.actor,record.events?.[0]?.links?.actor);node('span',' · '+(roles[item.actor_role]||item.actor_role)+' · '+stamp(item.created_at),identity);
      for(const event of record.events||[]){
        const part=node('section','',content,'audit-operation-part');
        if(event.pallet_code){const heading=node('h3','Palet '+event.pallet_code,part,'audit-pallet-heading');drawIcon(heading,'pallet');if(event.action==='delete')node('small','Eliminado',heading,'audit-deleted-label');}else if(record.events.length>1){const heading=node('h3',event.action_label,part);drawIcon(heading,event.action_icon||'info');}
        if(event.branch_name){const label=node('span',event.branch_name,part,'stock-branch-label');icon(label,'branch');}
        if(event.target_user){const person=node('p','Persona: ',part,'audit-target-user');linked(person,event.target_name||'Usuario #'+event.target_user,event.links?.target);}
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
          drawIcon(delta,transferred?'transfer':product.delta<0?'package-minus':product.delta>0?'package-plus':'settings');
          delta.dataset.tone=transferred?'info':product.delta<0?'danger':product.delta>0?'success':'info';
          if(product.status==='updated'&&product.name_before!==product.name_after)node('p','Referencia: '+(product.name_before||'Sin referencia')+' → '+(product.name_after||'Sin referencia'),row);
          if(!transferred)compare(row,number(product.before)+' unidades',number(product.after)+' unidades');
          const date=v=>v?v.split('-').reverse().join('/'):'Sin vencimiento';
          node('small',product.expires_before!==product.expires_after&&product.status==='updated'?'Vencimiento: '+date(product.expires_before)+' → '+date(product.expires_after):'Vencimiento: '+date(product.expires_after||product.expires_before),row);
        }
        const changes=(event.changes||[]).filter(c=>!c.label.startsWith('Producto ')||!event.products?.length);
        const value=(v,key)=>v===null||v===''?'Sin dato':typeof v==='boolean'||['released','activated','blocked','verified'].includes(key)?(v===true||v===1||v==='1'?'Sí':'No'):key==='role'?(roles[v]||String(v)):['status','state'].includes(key)?({pending:'Pendiente',approved:'Aprobado',rejected:'Rechazado',accepted:'Aceptado',declined:'Rechazado',revoked:'Revocado',validated:'Validado',invalid:'Invalidado',blocked:'Bloqueado'}[v]||String(v)):key==='kind'?({units:'Unidades',boxes:'Cajas',packs:'Bultos'}[v]||String(v)):key==='expires'&&/^\d{4}-\d{2}-\d{2}$/.test(String(v))?String(v).split('-').reverse().join('/'):key==='units'?number(v)+' unidades':String(v);
        for(const change of changes){const row=node('div','',part,'audit-change');node('strong',change.label,row);compare(row,value(change.before,change.field),value(change.after,change.field));if(change.delta!==null&&change.delta!==undefined)node('small',(change.delta>0?'+':'')+number(change.delta)+' unidades',row);}
        if(!changes.length&&!event.products?.length&&!event.transfer&&!event.device_kind&&!event.label_codes?.length&&!event.account)node('p',event.action_label+'.',part);
        const destinations=[['pallet','Ver palet'],['source','Palet de origen'],['destination','Palet de destino']].filter(([key])=>event.links?.[key]);const used=new Set();if(destinations.length){const actions=node('div','',part,'audit-record-actions');for(const[key,label]of destinations){const link=event.links[key];if(used.has(link.href))continue;used.add(link.href);const a=linked(actions,label,link,'library-secondary');drawIcon(a,'pallet');}}
        if(event.synced_at!==event.occurred_at)node('small','Sincronizado: '+stamp(event.synced_at),part);
      }
      if(record.events?.some(e=>e.can_technical)){const technical=node('details','',content,'audit-technical');node('summary','Auditoría técnica',technical);node('pre',JSON.stringify(record.events.map(e=>({evento:e.event_id,operacion:e.operation_id,antes:e.before,despues:e.after})),null,2),technical);}
    }catch(error){if(attempt===generation){status.textContent=error.message;const retry=node('button','Reintentar',content,'library-secondary');retry.type='button';retry.onclick=()=>{dialog.close();button.click();};}}
  });
}
window.RepoAuditRecords={render};
})();
