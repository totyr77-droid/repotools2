(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document,request=page?.fetch||fetch.bind(window);
  const root=doc.querySelector('[data-company-manager]');if(!root)return;
  const $=id=>doc.getElementById(id);
  let csrf=root.dataset.csrf,left=false,busy=false,company=null,editing=null,listCursor=null,branchCursor=null,listTurn=0,detailTurn=0,timer,listAbort,detailAbort,opener=null,deleteStamp='',assignmentStep='company',branchMode='choose',availableBranches=[],newBranchSnapshot=null,newBranchAccepted=false,locationMap=null,placeLookup=null;
  const status=message=>{$('companyStatus').textContent=message;$('companyStatus').hidden=!message;for(const id of ['companyAssignmentStatus','companyBranchAssignmentStatus','companyNewBranchStatus','companyBranchEditStatus']){$(id).textContent=message;$(id).hidden=!message;}};
  function node(tag,text,parent,cls=''){const n=doc.createElement(tag);n.textContent=text;n.className=cls;parent.append(n);return n;}
  async function api(query='',data=null,endpoint='api/gestion-empresas.php',signal){
    const response=await request(endpoint+query,{method:data?'POST':'GET',cache:'no-store',signal,headers:data?{'Content-Type':'application/x-www-form-urlencoded'}:{},body:data?new URLSearchParams({...data,csrf}):undefined});
    const result=await response.json();if(!response.ok){const error=Error(result.error||'No pudimos completar la acción.');error.status=response.status;throw error;}if(result.csrf)csrf=result.csrf;return result;
  }
  async function run(work){if(busy||left)return;busy=true;const controls=[...root.querySelectorAll('button,input,select')];const previous=controls.map(n=>n.disabled);controls.forEach(n=>n.disabled=true);root.setAttribute('aria-busy','true');status('Guardando…');try{await work();}catch(e){if(!left)status(e.message);}finally{busy=false;controls.forEach((n,i)=>n.disabled=previous[i]);root.removeAttribute('aria-busy');}}
  function listRow(item){
    const button=node('button','',$('companyList'),'company-result');button.type='button';button.dataset.companyId=item.id;button.setAttribute('aria-pressed',String(Number(company?.id)===Number(item.id)));
    const info=node('span','',button);node('strong',item.name,info);node('small',[item.owner_name||'Responsable',Number(item.branches)?Number(item.branches)+(Number(item.branches)===1?' sucursal':' sucursales'):'Sin sucursales'].join(' · '),info);
    button.addEventListener('click',()=>choose(item.id,button));
  }
  async function loadList(append=false){
    const turn=++listTurn;listAbort?.abort();listAbort=new AbortController();
    $('companyListStatus').textContent=append?'Cargando más empresas…':'Buscando empresas…';$('companyListStatus').hidden=false;$('companyMore').disabled=true;
    if(!append)$('companyList').replaceChildren();
    try{
      const result=await api('?'+new URLSearchParams({q:$('companySearch').value.trim(),cursor:append?listCursor||0:0}),null,undefined,listAbort.signal);
      if(left||turn!==listTurn)return;
      for(const item of result.companies)listRow(item);listCursor=result.next_cursor;$('companyMore').hidden=!listCursor;
      const empty=!$('companyList').children.length;$('companyListStatus').textContent=empty?($('companySearch').value.trim()?'No hay empresas para esta búsqueda.':'Todavía no hay empresas. Usá Asignar empresa para empezar.') : '';$('companyListStatus').hidden=!empty;
    }catch(e){if(!left&&turn===listTurn&&e.name!=='AbortError'){$('companyListStatus').textContent=e.message;$('companyMore').hidden=true;}}
    finally{if(turn===listTurn)$('companyMore').disabled=false;}
  }
  function hasDraft(){return !!editing||$('teamSetup').open||($('companyEdit').open&&company&&$('companyNameForm').elements.name.value!==company.name);}
  function branchRow(branch){
    const r=node('div','',$('companyBranches'),'team-row'),info=node('div','',r);const title=node('button',branch.name,info,'team-text company-branch-open');title.type='button';node('small',[branch.address,branch.locality,branch.province].filter(Boolean).join(' · '),info);node('small',Number(branch.people)+(Number(branch.people)===1?' persona':' personas')+' · '+Number(branch.pallets)+(Number(branch.pallets)===1?' palet':' palets'),info);
    const b=node('button','',r,'team-text company-configure');b.type='button';b.setAttribute('aria-label','Configurar '+branch.name);b.title='Configurar sucursal';b.append($('companyEdit').querySelector('svg').cloneNode(true));
    const open=()=>{if(busy)return;if(hasDraft()){status('Guardá o cancelá los cambios antes de continuar.');return;}editing=branch;const form=$('companyBranchForm');for(const key of ['name','address','locality','province','description'])form.elements[key].value=branch[key]||'';$('companyBranchEditTitle').textContent='Configurar '+branch.name;$('companyBranchEdit').hidden=false;$('companyBranchEdit').showModal();$('companyBranchEditHeading').focus({preventScroll:true});};b.addEventListener('click',open);title.addEventListener('click',open);
  }
  function clearBranchEdit(){editing=null;$('companyBranchEdit').close();$('companyBranchEdit').hidden=true;$('companyBranchForm').reset();}
  async function choose(id,source=null,append=false,refresh=false){
    if(busy&&!refresh)return;
    if(!append&&hasDraft()){status('Guardá o cancelá los cambios antes de cambiar de empresa.');return;}
    const turn=++detailTurn;detailAbort?.abort();detailAbort=new AbortController();
    if(!append){opener=source||opener;company=null;deleteStamp='';$('companyDelete').hidden=true;clearBranchEdit();$('companyDetail').hidden=false;$('companyTitle').textContent='Consultando empresa…';$('companyOwner').textContent='';$('companyBranches').replaceChildren();$('companyEdit').hidden=true;$('companyAddBranch').hidden=true;$('companyMoreBranches').hidden=true;}
    $('companyBranchesStatus').textContent='Cargando sucursales…';
    try{
      const result=await api('?'+new URLSearchParams({workspace_id:id,cursor:append?branchCursor||0:0}),null,undefined,detailAbort.signal);if(left||turn!==detailTurn)return;
      company=result.company;deleteStamp=result.delete_stamp;$('companyDelete').hidden=false;$('companyTitle').textContent=company.name;$('companyOwner').textContent='Responsable · '+(company.owner_name||'')+' · '+company.owner_email;$('companyEdit').hidden=false;$('companyAddBranch').hidden=false;
      if(!append){$('companyNameForm').elements.name.value=company.name;$('companyEdit').open=false;}
      for(const branch of result.branches)branchRow(branch);branchCursor=result.next_cursor;$('companyMoreBranches').hidden=!branchCursor;$('companyBranchesStatus').textContent=$('companyBranches').children.length?'':'Esta empresa quedó sin sucursales. Podés eliminarla o añadir una.';
      for(const b of $('companyList').children)b.setAttribute('aria-pressed',String(Number(b.dataset.companyId)===Number(company.id)));
      if(!append&&source&&matchMedia('(max-width:799px)').matches)$('companyDetail').focus();
    }catch(e){if(!left&&turn===detailTurn&&e.name!=='AbortError'){$('companyBranchesStatus').textContent=e.message;if(e.status===404){company=null;deleteStamp='';$('companyDetail').hidden=true;}status(e.message);if(refresh)throw e;}}
  }
  async function reloadCompany(id){await Promise.all([loadList(),choose(id,null,false,true)]);}
  async function openAssignment(existing=false){
    if(busy||hasDraft()){status('Guardá o cancelá los cambios antes de continuar.');return;}
    const selected=existing?company:null;status('Cargando sucursales disponibles…');$('companyNew').disabled=true;$('companyAddBranch').disabled=true;
    try{
      const data=await api('',null,'api/equipo-sucursal.php');if(left)return;
      if(!data.can_assign_company)throw Error('Tu cuenta ya no tiene permiso para asignar empresas.');
      const select=$('teamAssignWorkspace');select.replaceChildren(new Option('Nueva empresa','0'));if(selected)select.append(new Option(selected.name,selected.id));select.value=String(selected?.id||0);
      availableBranches=data.available||[];branchMode='choose';$('companyBranchSearch').value='';const directory=$('teamDirectory');directory.replaceChildren(new Option('Elegí una sucursal',''),new Option('Crear una sucursal','0'));for(const b of availableBranches)directory.append(new Option(b.name+' · '+b.address,b.id));
      $('companyNewBranchDialog').hidden=true;placeLookup?.clear();$('teamCreateForm').reset();directory.value='';select.value=String(selected?.id||0);$('companyAssignmentContext').textContent=selected?'Empresa · '+selected.name:'La persona responsable usa su cuenta habitual.';assignmentFields();$('teamSetup').hidden=false;$('teamSetup').open=true;$('companyAssignmentDialog').showModal();assignmentTab(selected?'branch':'company',false);const summary=$('teamSetup').querySelector('summary');const icon=root.querySelector('.team-page-title svg').cloneNode(true);summary.replaceChildren(icon,doc.createTextNode(selected?'Añadir sucursal a '+selected.name:'Asignar empresa'));status('');$('companyAssignmentTitle').querySelector('span').textContent=selected?'Añadir sucursal':'Asignar empresa';$(selected?'companyBranchTitle':'companyAssignmentTitle').focus({preventScroll:true});
    }catch(e){if(!left)status(e.message);}finally{$('companyNew').disabled=false;$('companyAddBranch').disabled=false;}
  }
  function assignmentFields(){const existing=$('teamAssignWorkspace').value!=='0';$('teamWorkspaceLabel').hidden=existing;$('teamOwnerLabel').hidden=existing;$('teamCreateForm').elements.owner_email.required=!existing;$('teamCreateButton').textContent=existing?'Añadir sucursal':'Asignar empresa';}
  function branchFields(){const fresh=branchMode==='new';$('teamNewBranch').hidden=false;for(const n of $('teamNewBranch').querySelectorAll('input'))n.required=n.type!=='hidden'&&!$('companyNewBranchDialog').hidden;$('teamCreateButton').disabled=branchMode==='choose';$('teamCreateButton').hidden=!$('companyNewBranchDialog').hidden;$('companyBranchNew').setAttribute('aria-expanded',String(!$('companyNewBranchDialog').hidden));renderBranchChoices();}
  const normalizeBranch=value=>String(value).normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es');
  function renderBranchChoices(){const term=normalizeBranch($('companyBranchSearch').value.trim()),items=availableBranches.filter(b=>normalizeBranch([b.name,b.address,b.locality,b.province,b.description].filter(Boolean).join(' ')).includes(term));$('companyBranchChoices').replaceChildren();if(branchMode==='new'){const draft=node('button','Nueva sucursal · '+$('teamCreateForm').elements.name.value,$('companyBranchChoices'),'branch-search-option');draft.type='button';draft.setAttribute('aria-pressed','true');draft.addEventListener('click',openNewBranch);}$('companyBranchEmpty').hidden=!!items.length;$('companyBranchEmpty').textContent=term?'No encontramos una sucursal con esos datos.':'No hay sucursales libres para asignar. Podés crear una nueva.';for(const b of items){const row=node('div','',$('companyBranchChoices'),'branch-choice-row'),button=node('button','',row,'branch-search-option');button.type='button';button.setAttribute('aria-pressed',String(branchMode==='existing'&&$('teamDirectory').value===String(b.id)));button.append(root.querySelector('.team-page-title svg').cloneNode(true));const info=node('span','',button);node('strong',b.name,info);node('small',[b.address,b.locality,b.province].filter(Boolean).join(', '),info);button.addEventListener('click',()=>{if(busy)return;if(!$('companyNewBranchDialog').hidden)closeNewBranch();branchMode='existing';$('teamDirectory').value=String(b.id);branchFields();$('teamCreateButton').focus({preventScroll:true});});}}
  $('companyBranchSearch').addEventListener('input',renderBranchChoices);
  function openNewBranch(){if(busy)return;newBranchSnapshot=[...$('teamNewBranch').querySelectorAll('input')].map(n=>n.value);newBranchAccepted=false;$('companyNewBranchDialog').hidden=false;branchFields();$('companyNewBranchTitle').focus({preventScroll:true});$('companyNewBranchDialog').scrollIntoView({block:'nearest',behavior:'instant'});}
  $('companyBranchNew').addEventListener('click',openNewBranch);
  for(const id of ['companyNewBranchClose','companyNewBranchCancel'])$(id).addEventListener('click',()=>closeNewBranch());
  $('companyNewBranchUse').addEventListener('click',()=>{for(const n of $('teamNewBranch').querySelectorAll('input'))if(!n.reportValidity())return;newBranchAccepted=true;branchMode='new';$('teamDirectory').value='0';closeNewBranch();});
  function closeNewBranch(){placeLookup?.clear();$('companyNewBranchDialog').hidden=true;if(!newBranchAccepted&&newBranchSnapshot)[...$('teamNewBranch').querySelectorAll('input')].forEach((n,i)=>n.value=newBranchSnapshot[i]);newBranchSnapshot=null;branchFields();$('companyBranchNew').focus({preventScroll:true});}
  function selectMapBranch(b){if(!availableBranches.some(item=>Number(item.id)===Number(b.id))){status('Esta sucursal ya está vinculada a otro equipo.');return;}branchMode='existing';$('teamDirectory').value=String(b.id);$('companyNewBranchDialog').hidden=true;placeLookup?.clear();branchFields();status('Sucursal seleccionada: '+b.name);}
  $('companyLocation').addEventListener('toggle',async()=>{if(!$('companyLocation').open)return;try{if(locationMap){locationMap.update(availableBranches);locationMap.resize();return;}locationMap=await RepoBranchMaps.create($('companyLocationMap'),$('companyLocationStatus'),{branches:availableBranches,onSelect:selectMapBranch,onBrowse:(latitude,longitude)=>{openNewBranch();const form=$('teamCreateForm');form.elements.latitude.value=latitude;form.elements.longitude.value=longitude;placeLookup.lookup({latitude,longitude});}});}catch(e){$('companyLocationStatus').textContent=e.message;}});
  $('companyLocate').addEventListener('click',()=>locationMap?.locate());
  async function refreshCompanies(quiet=false){if(left||busy||doc.hidden)return;if(hasDraft()){if(!quiet)status('Guardá o cancelá los cambios antes de actualizar.');return;}const id=company?.id;$('companyRefresh').disabled=true;try{await Promise.all([loadList(),id?choose(id,null,false,true):Promise.resolve()]);if(!quiet)status('Información actualizada.');}catch(e){if(!left)status(e.message);}finally{$('companyRefresh').disabled=false;}}
  $('companyRefresh').addEventListener('click',()=>refreshCompanies());
  const resume=()=>refreshCompanies(true);(page?.listen||window.addEventListener.bind(window))('focus',resume);doc.addEventListener('visibilitychange',resume);
  $('companySearch').addEventListener('input',()=>{clearTimeout(timer);listAbort?.abort();listTurn++;listCursor=null;$('companyMore').hidden=true;timer=setTimeout(()=>loadList(),220);});
  $('companyMore').addEventListener('click',()=>loadList(true));$('companyMoreBranches').addEventListener('click',()=>{if(company)choose(company.id,null,true);});
  $('companyNew').addEventListener('click',()=>openAssignment());$('companyAddBranch').addEventListener('click',()=>openAssignment(true));
  $('companyClose').addEventListener('click',()=>{if(hasDraft()){status('Guardá o cancelá los cambios antes de cerrar.');return;}detailTurn++;detailAbort?.abort();company=null;$('companyDetail').hidden=true;for(const b of $('companyList').children)b.setAttribute('aria-pressed','false');opener?.focus();});
  $('companyDelete').addEventListener('click',()=>{if(busy||!company)return;if(hasDraft()){status('Guardá o cancelá los cambios antes de eliminar.');return;}$('companyDeleteTitle').textContent='Eliminar '+company.name;$('companyDeleteDialog').showModal();$('companyDeleteCancel').focus();});
  $('companyDeleteCancel').addEventListener('click',()=>$('companyDeleteDialog').close());
  $('companyDeleteConfirm').addEventListener('click',()=>{if(!company)return;const data={action:'delete_company',workspace_id:company.id,delete_stamp:deleteStamp};$('companyDeleteDialog').close();run(async()=>{await api('',data);detailTurn++;detailAbort?.abort();company=null;deleteStamp='';clearBranchEdit();$('companyDetail').hidden=true;await loadList();status('Empresa eliminada. Las sucursales y la mercadería se conservaron.');$('companySearch').focus();});});
  $('companyBranchEditClose').addEventListener('click',clearBranchEdit);$('companyBranchEdit').addEventListener('cancel',event=>{event.preventDefault();if(!busy)clearBranchEdit();});
  $('companyBranchCancel').addEventListener('click',clearBranchEdit);
  $('companyNameForm').addEventListener('submit',event=>{event.preventDefault();if(!company)return;const id=company.id,data={action:'rename',workspace_id:id,expected_name:company.name,name:event.target.elements.name.value};run(async()=>{await api('',data);$('companyEdit').open=false;await reloadCompany(id);status('Nombre actualizado.');});});
  $('companyBranchForm').addEventListener('submit',event=>{event.preventDefault();if(!editing||!company)return;const id=company.id,data={...Object.fromEntries(new FormData(event.target)),action:'save_branch',workspace_id:id,branch_id:editing.id,revision:editing.revision};run(async()=>{await api('',data);clearBranchEdit();await reloadCompany(id);status('Sucursal actualizada.');});});
  $('teamSetup').querySelector('.company-assignment-tabs').remove();
  for(const el of [$('teamBranchFields'),$('teamCreateButton')]){$('companyBranchDialogBody').append(el);for(const input of [el,...el.querySelectorAll('input,select,button')])if(input.matches('input,select,button'))input.setAttribute('form','teamCreateForm');}
  $('companyBranchDialogBody').append($('companyNewBranchDialog'));$('companyNewBranchBody').append($('teamNewBranch'));for(const name of ['latitude','longitude']){const input=node('input','',$('teamNewBranch'));input.type='hidden';input.name=name;input.setAttribute('form','teamCreateForm');}$('teamAssignWorkspace').closest('label').hidden=true;$('teamCompanyFields').prepend($('teamWorkspaceLabel'));
  $('teamDirectory').closest('label').hidden=true;$('teamDirectory').tabIndex=-1;
  function assignmentTab(step,validate=true){if(step==='branch'&&validate){for(const input of $('teamCompanyFields').querySelectorAll('input'))if(!input.reportValidity())return false;}assignmentStep=step;$('teamCompanyFields').hidden=false;$('teamBranchFields').hidden=false;$('teamCreateButton').hidden=false;$('teamAssignmentNext').hidden=false;branchFields();if(step==='branch'&&!$('companyBranchDialog').open){$('companyBranchDialog').showModal();$('companyBranchDialog').scrollTop=0;$('companyBranchTitle').focus({preventScroll:true});}return true;}
  $('teamAssignmentNext').addEventListener('click',()=>{if(assignmentTab('branch'))$('companyBranchTitle').focus({preventScroll:true});});
  $('companyBranchBack').addEventListener('click',()=>$('companyBranchDialog').close());
  $('companyBranchDialog').addEventListener('close',()=>{assignmentStep='company';branchFields();$('teamAssignmentNext').focus();});
  for(const id of ['companyBranchDialog','companyAssignmentDialog'])$(id).addEventListener('cancel',event=>{if(busy)event.preventDefault();});
  $('companyAssignmentDialog').addEventListener('close',()=>{if(busy)return;$('teamSetup').open=false;$('teamSetup').hidden=true;});
  $('companyAssignmentClose').addEventListener('click',()=>$('companyAssignmentDialog').close());
  $('teamAssignWorkspace').addEventListener('change',assignmentFields);$('teamDirectory').addEventListener('change',branchFields);
  const actions=node('div','',$('teamCreateForm'),'company-form-actions');const cancel=node('button','Cancelar',actions,'team-text');actions.append($('teamAssignmentNext'));cancel.type='button';cancel.addEventListener('click',()=>{$('teamSetup').open=false;$('teamSetup').hidden=true;$('companyAssignmentDialog').close();$('teamCreateForm').reset();});
  $('teamCreateForm').addEventListener('submit',event=>{event.preventDefault();if(!$('companyNewBranchDialog').hidden){$('companyNewBranchUse').click();return;}if(assignmentStep==='company'){assignmentTab('branch');return;}if(branchMode==='choose'){status('Elegí una sucursal o creá una nueva.');return;}const data=Object.fromEntries(new FormData(event.target)),workspace=$('teamAssignWorkspace').value;run(async()=>{const result=await api('',{...data,action:workspace==='0'?'create':'add_branch',workspace_id:workspace},'api/equipo-sucursal.php');$('teamSetup').open=false;$('teamSetup').hidden=true;$('companyBranchDialog').close();$('companyAssignmentDialog').close();event.target.reset();await reloadCompany(result.workspace_id||Number(workspace));status(workspace==='0'?'Empresa asignada.':'Sucursal añadida.');});});
  page?.onLeave(()=>{left=true;doc.removeEventListener('visibilitychange',resume);clearTimeout(timer);listAbort?.abort();detailAbort?.abort();});
  placeLookup=RepoBranchLookup({form:$('teamCreateForm'),host:$('companyNewBranchBody'),csrf:()=>csrf,showLoading:false,valid:()=>!left&&!$('companyNewBranchDialog').hidden,onExisting:selectMapBranch});
  loadList();
})();
