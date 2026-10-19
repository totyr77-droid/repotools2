(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document,request=page?.fetch||fetch.bind(window),$=id=>doc.getElementById(id);
  const home=doc.querySelector('[data-team-home]'),panel=doc.querySelector('[data-team-page]');if(!home&&!panel)return;
  let state=null,csrf=panel?.dataset.csrf||'',busy=false,left=false,generation=0,scanner=null,scanBranch=0,nextCursor=null;
  const roles={admin:'Administrador',manager:'Encargado',operator:'Operador'};
  const status=(message)=>{const node=panel?$('teamStatus'):home.querySelector('[data-team-home-status]');node.textContent=message;node.hidden=!message;};
  async function api(data=null,query=''){
    const response=await request('api/equipo-sucursal.php'+query,{method:data?'POST':'GET',cache:'no-store',headers:data?{'Content-Type':'application/x-www-form-urlencoded'}:{},body:data?new URLSearchParams({...data,csrf}):undefined});
    const result=await response.json();if(!response.ok)throw Error(result.error||'No pudimos completar la acción.');if(result.csrf)csrf=result.csrf;return result;
  }
  function node(tag,value,parent,cls=''){const item=doc.createElement(tag);item.textContent=value;item.className=cls;parent.append(item);return item;}
  function action(label,parent,callback,danger=false){const b=node('button',label,parent,'team-text'+(danger?' team-danger':''));b.type='button';b.addEventListener('click',()=>run(()=>callback()));return b;}
  async function run(work){if(busy||left)return;busy=true;const buttons=[...doc.querySelectorAll('[data-team-page] button,[data-team-home] button')];buttons.forEach(b=>b.disabled=true);status('Guardando…');try{await work();}catch(error){if(!left)status(error.message);}finally{busy=false;buttons.forEach(b=>b.disabled=false);}}
  const active=()=>state?.locations.find(b=>Number(b.id)===Number(state.active));
  function renderLocations(){const select=panel?$('teamLocation'):home.querySelector('[data-team-location]');select.replaceChildren(new Option('Uso personal','0'));for(const b of state.locations)select.append(new Option(b.name+' · '+roles[b.role],b.id));select.value=String(state.active||0);if(home){home.querySelector('[data-team-scan]').hidden=!state.active;select.closest('label').hidden=!state.locations.length;}}
  async function load(message=''){
    const turn=++generation;const result=await api();if(left||turn!==generation)return;state=result;renderLocations();if(panel)renderPanel();status(message);
  }
  function row(parent,title,subtitle=''){const r=node('div','',parent,'team-row'),info=node('div','',r);node('strong',title,info);if(subtitle)node('small',subtitle,info);return {r,info};}
  function showPallets(items,append=false){const box=$('teamPallets');if(!append)box.replaceChildren();if(!items.length&&!append)node('p','Todavía no hay palets en esta sucursal. Escaneá uno desde Inicio.',box);for(const p of items){const {info}=row(box,'',p.products+' productos · '+p.units+' unidades'+(p.owner_name?' · '+p.owner_name:''));const a=node('a','Palet '+p.code,info);a.href='stock.php?codigo='+encodeURIComponent(p.code);}}
  function renderPanel(){
    const branch=active(),manage=branch&&branch.role!=='operator',admin=branch?.role==='admin';$('teamOverview').hidden=!manage;$('teamOperatorHint').hidden=!branch||manage;$('teamLeave').hidden=!branch||admin;$('teamRole').textContent=branch?roles[branch.role]+' · '+branch.workspace_name:'Tu cuenta personal sigue funcionando igual.';
    $('teamInviteResult').hidden=true;$('teamInviteLink').value='';$('teamInviteQR').replaceChildren();$('teamManagerOption').disabled=!admin;$('teamInviteForm').elements.role.value='operator';
    const directory=$('teamDirectory');directory.replaceChildren(new Option('Crear una sucursal','0'));for(const b of state.available||[])directory.append(new Option(b.name+' · '+b.address,b.id));branchFields();
    const workspace=admin?branch.workspace_id:0;$('teamCreateForm').dataset.workspace=String(workspace);$('teamWorkspaceLabel').hidden=!!workspace;$('teamCreateButton').textContent=workspace?'Añadir sucursal':'Crear equipo';$('teamSetup').querySelector('summary').textContent=workspace?'Añadir otra sucursal':'Administrar una sucursal';
    if(!manage)return;
    const overview=state.overview||{};showPallets(overview.pallets||[]);nextCursor=overview.next_cursor;$('teamMore').hidden=!nextCursor;
    $('teamMembers').replaceChildren();if(!(overview.members||[]).length)node('p','Todavía no hay otras personas vinculadas. Creá una invitación.', $('teamMembers'));
    for(const m of overview.members||[]){const {r}=row($('teamMembers'),m.name,roles[m.role]);if(admin||m.role==='operator')action('Quitar acceso',r,()=>confirm('Quitar acceso','Esta persona perderá su acceso a la sucursal. Las invitaciones pendientes se cancelarán.',async()=>{await api({action:'revoke',branch_id:branch.id,target_id:m.user_id,revision:m.revision});await load('Acceso revocado.');}),true);}
    $('teamInvites').replaceChildren();for(const i of overview.invites||[]){const {r}=row($('teamInvites'),roles[i.role],'Vence '+new Date(Number(i.expires)*1000).toLocaleDateString('es-AR'));if(admin||i.role==='operator')action('Revocar',r,async()=>{await api({action:'cancel_invite',branch_id:branch.id,invite_id:i.token_hash});await load('Invitación revocada.');},true);}if(!overview.invites?.length)node('p','Sin invitaciones pendientes.',$('teamInvites'));
    const labels={team_created:'Equipo habilitado',invite_created:'Invitación creada',invite_revoked:'Invitación revocada',member_joined:'Vínculo aceptado',member_revoked:'Acceso revocado',member_left:'Desvinculación',pallet_assigned:'Sucursal asignada'};
    $('teamEvents').replaceChildren();for(const e of overview.events||[])row($('teamEvents'),labels[e.action]||'Acción del equipo',[e.actor,e.target,e.code?'Palet '+e.code:'',date(e.created_at)].filter(Boolean).join(' · '));
    const movement={branch_updated:'Sucursal asignada',product_added:'Producto agregado',quantity_updated:'Cantidad modificada',quantity_deducted:'Mercadería retirada',product_removed:'Producto eliminado',expiry_updated:'Vencimiento modificado',expiry_added:'Vencimiento agregado',expiry_removed:'Vencimiento quitado',reference_updated:'Nombre modificado',pallet_created:'Palet preparado',description_updated:'Descripción modificada',product_merged:'Productos unificados'};
    $('teamMovements').replaceChildren();for(const e of overview.movements||[])row($('teamMovements'),movement[e.action]||'Movimiento del palet',[e.code?'Palet '+e.code:'',e.metadata.reference||e.metadata.ean,e.metadata.old!==undefined&&e.metadata.new!==undefined?e.metadata.old+' → '+e.metadata.new:'',e.actor,date(e.created_at)].filter(Boolean).join(' · '));if(!overview.movements?.length)node('p','Sin movimientos registrados.',$('teamMovements'));
  }
  function date(value){const d=new Date(value.includes('T')?value:value.replace(' ','T')+'Z');return Number.isNaN(d.getTime())?'':d.toLocaleString('es-AR',{dateStyle:'short',timeStyle:'short'});}
  function confirm(title,message,yes){$('teamConfirmTitle').textContent=title;$('teamConfirmText').textContent=message;$('teamConfirmYes').onclick=()=>{$('teamConfirm').close();run(yes);};$('teamConfirm').showModal();}
  const locationSelect=panel?$('teamLocation'):home.querySelector('[data-team-location]');locationSelect.addEventListener('change',()=>run(async()=>{try{await api({action:'select',branch_id:locationSelect.value});await load('Sucursal activa actualizada.');}catch(error){renderLocations();throw error;}}));
  if(panel){
    $('teamRefresh').addEventListener('click',()=>run(()=>load('Información actualizada.')));
    $('teamMore').addEventListener('click',()=>run(async()=>{const result=await api(null,'?branch_id='+state.active+'&cursor='+nextCursor);showPallets(result.overview.pallets,true);nextCursor=result.overview.next_cursor;$('teamMore').hidden=!nextCursor;status('');}));
    $('teamConfirmCancel').addEventListener('click',()=>$('teamConfirm').close());
    $('teamLeave').addEventListener('click',()=>confirm('Desvincularme','Perderás el acceso por este vínculo. Conservás tus palets y sus movimientos.',async()=>{await api({action:'leave',branch_id:state.active});await load('Vínculo finalizado.');}));
    $('teamAccept')?.addEventListener('click',()=>run(async()=>{await api({action:'accept',token:$('teamAccept').dataset.token});$('teamInvitation').remove();history.replaceState(null,'','sucursales.php');await load('Vínculo aceptado.');}));
    $('teamInviteForm').addEventListener('submit',event=>{event.preventDefault();run(async()=>{const result=await api({action:'invite',branch_id:state.active,role:event.target.elements.role.value});const url=new URL('sucursales.php',location.href);url.searchParams.set('invitacion',result.token);await load('Invitación creada. Compartila con la persona que autorizás.');$('teamInviteLink').value=url.href;$('teamInviteResult').hidden=false;if(window.QRCode)new QRCode($('teamInviteQR'),{text:url.href,width:160,height:160,correctLevel:QRCode.CorrectLevel.M});});});
    $('teamCopy').addEventListener('click',()=>run(async()=>{await navigator.clipboard.writeText($('teamInviteLink').value);status('Enlace copiado.');}));
    $('teamShare').addEventListener('click',async()=>{try{if(navigator.share)await navigator.share({title:'Invitación a mi sucursal',url:$('teamInviteLink').value});else{await navigator.clipboard.writeText($('teamInviteLink').value);status('Enlace copiado.');}}catch(error){if(error.name!=='AbortError')status('Podés copiar el enlace del campo.');}});
    $('teamDirectory').addEventListener('change',branchFields);
    $('teamCreateForm').addEventListener('submit',event=>{event.preventDefault();run(async()=>{const data=Object.fromEntries(new FormData(event.target));const workspace=event.target.dataset.workspace;await api({...data,action:Number(workspace)?'add_branch':'create',workspace_id:workspace||'0'});event.target.reset();$('teamSetup').open=false;await load('Sucursal habilitada. Podés invitar a tu equipo.');});});
  }
  function branchFields(){const isNew=$('teamDirectory').value==='0';$('teamNewBranch').hidden=!isNew;for(const input of $('teamNewBranch').querySelectorAll('input'))input.required=isNew;}
  if(home){
    const dialog=$('teamScanner'),hint=$('teamScanStatus');
    async function found(raw){const code=RepoEANScanner.palletIdentifier(raw);if(!code){hint.textContent='Ingresá los 8 números de un palet de Repotools.';return;}try{hint.textContent='Consultando palet…';const result=await api({action:'scan',branch_id:scanBranch,code});if(left)return;if(result.assigned)window.RepoToast?.('Palet asignado a '+active().name+'.');await scanner?.stop();dialog.close();location.href='stock.php?codigo='+encodeURIComponent(result.code);}catch(error){hint.textContent=error.message;}}
    const manual=()=>{scanner?.stop();$('teamCodeForm').hidden=false;$('teamCodeForm').elements.code.focus();};
    scanner=window.RepoEANScanner?.create('teamReader',{identification:'pallet',scanContext:'Sucursal activa',onCode:found,onStatus:message=>hint.textContent=message,onManual:manual,onError:()=>{hint.textContent='Podés ingresar el número del palet.';manual();}});
    home.querySelector('[data-team-scan]').addEventListener('click',async()=>{if(!state.active)return;scanBranch=state.active;$('teamScanBranch').textContent=active().name+' · Los palets sin sucursal se asignan al escanear.';$('teamCodeForm').hidden=true;hint.textContent='';dialog.showModal();try{await scanner.start('qr');}catch{manual();}});
    $('teamManual').addEventListener('click',manual);$('teamCodeForm').addEventListener('submit',event=>{event.preventDefault();if(busy)return;busy=true;found(event.target.elements.code.value).finally(()=>busy=false);});
    dialog.querySelector('[data-team-close]').addEventListener('click',()=>dialog.close());dialog.addEventListener('close',()=>scanner?.stop());
  }
  page?.onLeave(()=>{left=true;generation++;scanner?.stop();});
  (page?.listen||window.addEventListener.bind(window))('pagehide',()=>scanner?.stop());
  load().catch(error=>status(error.message));
})();
