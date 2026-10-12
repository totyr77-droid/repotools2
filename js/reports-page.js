(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;

  const list = document.getElementById('reportsList'), status = document.getElementById('reportsStatus');
  const dialog = document.getElementById('reviewReportDialog'), details = document.getElementById('reviewReportDetails'), form = document.getElementById('reviewReportForm'), message = document.getElementById('reviewReportStatus');
  const close = document.getElementById('closeReviewReport'), approve = document.getElementById('approveReport'), reject = document.getElementById('rejectReport');
  let queue = document.querySelector('[data-report-view]').dataset.reportView === 'queue', version = 0, listVersion = 0, active, csrf, busy = false, photos = [];
  const labels = { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' };
  const fields = { brand: 'Marca', name: 'Producto', quantity: 'Contenido neto', category: 'Categoría', packaging: 'Envase' };
  let moderationView = 'pending', auditPage = 0, reportState = 'pending';
  document.getElementById('moderationReports')?.addEventListener('click',()=>document.querySelector(`[data-moderation-view=${reportState}]`).click());
  const logFilter=document.getElementById('reportLogFilter');logFilter?.addEventListener('change',()=>{auditPage=0;load();});
  const stamp = value => window.RepoDateTime.stamp(value);
  const logLabels = {branch:'Sucursal',product_edit:'Producto editado',report_review:'Reporte revisado',report_block:'Permiso de reportes',pallet_admin:'Gestión de palet',user_role:'Cambio de rango',user_profile:'Datos de cuenta'};
  const actionLabels = {branch_created:'Sucursal creada',branch_updated:'Sucursal actualizada',branch_deleted:'Sucursal eliminada',approved:'Aprobado',rejected:'Rechazado',blocked:'Bloqueado',unblocked:'Habilitado',profile_updated:'Perfil actualizado',edited:'Editado',save_product:'Producto guardado',delete_product:'Producto eliminado',description:'Descripción actualizada',delete:'Palet eliminado',invite:'Invitación enviada',revoke:'Acceso eliminado'};
  document.querySelectorAll('[data-moderation-view]').forEach(button => button.addEventListener('click', () => {
    moderationView = button.dataset.moderationView; auditPage = 0;
    if(queue){const url=new URL(location.href);url.searchParams.set('view',moderationView);history.replaceState({...history.state},'',url);}
    const reports = ['pending','history'].includes(moderationView); if(reports)reportState=moderationView;
    const filters=document.getElementById('reportStateTabs'); if(filters)filters.hidden=!reports;
    document.getElementById('moderationReports')?.setAttribute('aria-pressed',String(reports));
    document.querySelectorAll('[data-moderation-view]').forEach(tab => tab.setAttribute('aria-pressed', String(tab === button)));
    load();
  }));
  document.getElementById('moderationPrevious').addEventListener('click', () => { if(auditPage>0){auditPage--;load();} });
  document.getElementById('moderationNext').addEventListener('click', () => { auditPage++;load(); });
  function text(tag, value, container = details) { const node = document.createElement(tag); node.textContent = value; container.append(node); return node; }
  function icon(container, type) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('aria-hidden', 'true'); svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor'); svg.setAttribute('stroke-width', '1.8');
    const shapes={clock:'M12 3a9 9 0 1 1-9 9 9 9 0 0 1 9-9ZM12 7v5l3 2',check:'m5 12 4 4L19 6',cross:'m6 6 12 12M18 6 6 18',shield:'M12 3 3 7v5c0 5 9 9 9 9s9-4 9-9V7Z',pallet:'M3 21h18M4 17h16V4H4zM4 10h16M12 4v13',edit:'M12 20H4v-8M16 3l5 5-11 11-5 1 1-5Z',eye:'M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',file:'M6 3h12v18H6zM9 7h6m-6 5h6m-6 5h4',brand:'M3 3h8l10 10-8 8L3 11ZM7 7h.01',name:'M3 7l9-4 9 4v10l-9 4-9-4ZM3 7l9 5 9-5M12 12v9',quantity:'M3 8h18v8H3zM7 8v4m5-4v4m5-4v4',category:'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',packaging:'M3 7h18v14H3zM3 3h18v4M9 11h6'};
    const path = document.createElementNS(svg.namespaceURI, 'path'); path.setAttribute('d', shapes[type] || (type === 'ean' ? 'M3 4v16m3-16v16m4-16v16m3-16v16m4-16v16m3-16v16' : type === 'user' ? 'M20 21v-2a6 6 0 0 0-12 0v2M18 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0' : 'M4 4l16 16M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0')); svg.append(path); container.prepend(svg);
  }
  function badge(container, state) { const node = text('span', labels[state], container); node.className = 'report-badge report-badge-' + state; }
  function clearPhotos() { photos.forEach(url => URL.revokeObjectURL(url)); photos = []; }
  page?.onLeave(() => { ++version; ++listVersion; clearPhotos(); });
  async function load() {
    const filterBox=document.getElementById('reportLogFilters');if(filterBox)filterBox.hidden=moderationView!=='logs';
    const attempt = ++listVersion; const branches=moderationView==='branches';const branchWorkspace=document.getElementById('branchManagement');if(branchWorkspace)branchWorkspace.hidden=!branches;document.dispatchEvent(new CustomEvent('moderation:branches',{detail:branches}));const users=moderationView==='users'; const workspace=document.getElementById('userWorkspace'); if(workspace)workspace.hidden=!users; list.hidden=users||branches; document.getElementById('moderationPagination').hidden=true; if(users||branches){status.textContent='';return;} status.textContent = 'Cargando…'; list.replaceChildren();
    document.getElementById('moderationPagination').hidden = true;
    try {
      if (queue && moderationView !== 'pending') {
        const result = await RepoReports.request(`?view=${moderationView}&page=${auditPage}${moderationView==='logs'?'&filter='+encodeURIComponent(logFilter?.value||''):''}`); if(attempt !== listVersion)return; csrf=result.csrf;
        status.textContent=result.items.length?'':moderationView==='history'?'Todavía no hay reportes revisados.':'Todavía no hay actividad registrada.';
        for(const item of result.items){
          const card=document.createElement('article');card.className='report-card';card.dataset.layout=moderationView;list.append(card);const info=document.createElement('div');info.className='report-card-info';card.append(info);
          if(moderationView==='history'){
            const heading=document.createElement('div');heading.className='report-card-heading';info.append(heading);text('h2',`#${item.id}`,heading);badge(heading,item.status);
            const ean=text('p',item.ean,info);ean.className='report-inline';icon(ean,'ean');text('p',`${item.submitter_name} · Revisado por ${item.reviewer_name||'Staff'}`,info);text('p',stamp(item.reviewed_at),info);
            const changes=text('div','',info);changes.className='report-change-tags';for(const change of item.changes||[]){const chip=text('span',fields[change.field],changes);chip.className='report-change-tag';icon(chip,'edit');}if(!item.changes?.length)text('span','Fotos o comentarios',changes);
            const button=text('button','Ver cambios',card);icon(button,'eye');button.type='button';button.className='library-secondary';button.addEventListener('click',()=>open(item.id));
          }else{
            const heading=text('div','',info);heading.className='report-card-heading report-log-heading';
            const typeIcon={branch:'brand',product_edit:'edit',report_review:'file',report_block:'shield',pallet_admin:'pallet',user_role:'user',user_profile:'edit'}[item.event_type]||'file';
            const title=text('h2',logLabels[item.event_type]||'Actividad',heading);icon(title,typeIcon);
            const tone=['approved','unblocked','save_product','branch_created'].includes(item.action)?'success':['rejected','blocked','delete','delete_product','branch_deleted'].includes(item.action)?'danger':['revoke','leave'].includes(item.action)?'warning':['user_role','user_profile'].includes(item.event_type)?'role':'info';
            const label=text('span',actionLabels[item.action]||(item.event_type==='user_role'?'Rango actualizado':'Cambio registrado'),heading);label.className='report-activity-badge report-activity-'+tone;icon(label,tone==='success'?'check':tone==='danger'?'cross':typeIcon);
            const actor=text('p',item.actor||'Staff',info);actor.className='report-inline';icon(actor,'user');
            const target=text('p',(item.event_type==='pallet_admin'?'Palet ':['product_edit','report_review'].includes(item.event_type)?'EAN ':'')+(item.target||''),info);target.className='report-inline';icon(target,item.event_type==='pallet_admin'?'pallet':['product_edit','report_review'].includes(item.event_type)?'ean':'user');
            if(item.event_type==='user_role')text('p',item.action,info);
            const date=text('p',stamp(item.created_at),info);date.className='report-inline report-log-date';icon(date,'clock');
          }
        }
        const pagination=document.getElementById('moderationPagination');pagination.hidden=!auditPage&&!result.has_more;document.getElementById('moderationPrevious').disabled=auditPage===0;document.getElementById('moderationNext').disabled=!result.has_more;document.getElementById('moderationPageNumber').textContent=String(auditPage+1);return;
      }
      const result = await RepoReports.request(queue ? '?queue=1' : ''); if (attempt !== listVersion) return;
      if (!result.can_review) queue = false;
      csrf = result.csrf;
      status.textContent = result.reports.length ? '' : (queue ? 'No hay reportes pendientes.' : 'Todavía no enviaste reportes.');
      for (const report of result.reports) {
        const card = document.createElement('article'); card.className = 'report-card';if(queue)card.dataset.layout='pending';
        const info = document.createElement('div'); info.className = 'report-card-info'; card.append(info);
        const heading = document.createElement('div'); heading.className = 'report-card-heading'; info.append(heading); text('h2', `#${report.id}`, heading); badge(heading, report.status);
        const ean = text('p', report.ean, info); ean.className = 'report-inline'; ean.setAttribute('aria-label', 'EAN ' + report.ean); icon(ean, 'ean');
        if (queue) { const sender = text('p', report.submitter_name || 'Invitado', info); sender.className = 'report-inline report-card-sender'; icon(sender, 'user'); }
        else text('p', report.description, info);
        const button = text('button', queue ? 'Revisar' : 'Ver reporte', card); button.type = 'button'; button.className = 'library-secondary'; button.addEventListener('click', () => open(report.id)); list.append(card);
      }
      if (queue && result.blocked_reports?.length) {
        const section = document.createElement('details'); section.className = 'report-sender'; list.append(section);
        text('summary', `Cuentas con reportes bloqueados (${result.blocked_reports.length})`, section);
        for (const sender of result.blocked_reports) {
          const button = text('button', sender.name, section); button.type = 'button'; button.className = 'library-secondary'; button.addEventListener('click', () => open(sender.report_id));
        }
      }
    } catch (error) { if (attempt === listVersion) status.textContent = error.message; }
  }
  async function open(id) {
    const attempt = ++version; active = null; clearPhotos(); details.replaceChildren(); form.hidden = true; message.textContent = 'Cargando…'; if (!dialog.open) dialog.showModal();
    try {
      const result = await RepoReports.request('?id=' + id); if (attempt !== version || !dialog.open) return;
      active = result.report; csrf = result.csrf; document.getElementById('reviewReportTitle').textContent = `Reporte #${id}`;
      message.replaceChildren(); badge(message, active.status);const barcode=document.createElementNS('http://www.w3.org/2000/svg','svg'),barcodeBox=document.createElement('figure');barcodeBox.className='report-barcode';barcode.setAttribute('role','img');barcode.setAttribute('aria-label','Código de barras '+active.ean);text('figcaption','EAN del reporte',barcodeBox);barcodeBox.append(barcode);details.append(barcodeBox);try{window.JsBarcode(barcode,active.ean,{format:active.ean.length===8?'EAN8':'EAN13',width:2,height:72,displayValue:true,fontSize:16,margin:12});}catch{barcode.remove();text('p','EAN '+active.ean,barcodeBox);} if (!/^Revisar las fotos adjuntas|^Correcci\u00f3n propuesta:|^Solicitud de alta de producto\./.test(active.description)) text('p', active.description);
      if (active.review_note) text('p', active.review_note);
      if (result.can_review && active.submitter) {
        const sender = active.submitter, section = document.createElement('section'); section.className = 'report-sender'; details.append(section);
        const senderIdentity = document.createElement('div'); senderIdentity.className = 'report-sender-identity'; section.append(senderIdentity);
        const identity = text('p', sender.name, senderIdentity); identity.className = 'report-inline'; identity.setAttribute('aria-label', 'Enviado por ' + sender.name); icon(identity, 'user');
        const more = document.createElement('details'); more.className = 'report-sender-more'; senderIdentity.append(more); text('summary', 'Datos', more);
        const senderData = document.createElement('div'); senderData.className = 'report-sender-data'; more.append(senderData);
        text('p', `Correo: ${sender.email || 'No disponible'}`, senderData);
        text('p', `Empresa: ${sender.company || 'No indicada'}`, senderData);
        if (Object.hasOwn(active, 'submitter_ip')) text('p', `IP registrada: ${active.submitter_ip || 'No disponible'}`, senderData);
        more.addEventListener('toggle', () => { if (more.open) { const outside = event => { if (!more.contains(event.target)) { more.open = false; document.removeEventListener('pointerdown', outside); } }; document.addEventListener('pointerdown', outside); more.addEventListener('toggle', () => { if (!more.open) document.removeEventListener('pointerdown', outside); }, { once: true }); } });
        if (sender.can_manage) {
          const button = text('button', sender.blocked ? 'Habilitar' : 'Bloquear', section); button.type = 'button'; button.className = 'library-secondary report-block-button'; icon(button, 'block');
          const hint = text('p', '', section); hint.className = 'report-block-status'; hint.setAttribute('role', 'status');
          button.addEventListener('click', async () => {
            if (busy) return;
            const id = active.id, attempt = version;
            if (!await window.RepoConfirm({ title: sender.blocked ? 'Habilitar reportes' : 'Bloquear reportes', message: sender.blocked ? `¿Permitir que ${sender.name} vuelva a enviar reportes?` : `¿Bloquear los reportes de ${sender.name}? Podrá seguir usando las demás funciones.`, confirmLabel: sender.blocked ? 'Habilitar' : 'Bloquear' })) return;
            if (attempt !== version || !dialog.open || busy) return;
            busy = true; button.disabled = approve.disabled = reject.disabled = close.disabled = true;
            try {
              const body = new FormData(); body.set('csrf', csrf); body.set('id', id); body.set('action', sender.blocked ? 'unblock_reports' : 'block_reports');
              const response = await RepoReports.request('', body); sender.blocked = response.report.submitter.blocked; sender.can_manage = response.report.submitter.can_manage;
              hint.textContent = sender.blocked ? 'Bloqueado. Esta cuenta no puede enviar nuevos reportes.' : 'Habilitado. Esta cuenta puede volver a enviar reportes.';
              await load();
            } catch (error) { hint.textContent = error.message; }
            finally { busy = false; button.disabled = approve.disabled = reject.disabled = close.disabled = false; button.hidden = !sender.can_manage; button.textContent = sender.blocked ? 'Habilitar' : 'Bloquear'; icon(button, 'block'); }
          });
        } else if (sender.blocked) { const state = text('span', 'Reportes bloqueados', section); state.className = 'report-blocked-state'; }
      }
      const resolved=active.status!=='pending', baseline=resolved?active.original:active.current;
      const changed = Object.keys(fields).filter(key => (baseline?.[key] || '').trim() !== (active.proposed?.[key] || '').trim());
      const shownFields = Object.keys(fields).filter(key => baseline?.[key] || active.proposed?.[key]);
      const changedFields=shownFields.filter(key=>changed.includes(key)),unchangedFields=shownFields.filter(key=>!changed.includes(key));
      function compare(keys,container) {
      const comparison = document.createElement('div'); comparison.className = 'report-comparison'; container.append(comparison);
      comparison.style.setProperty('--report-rows', String(keys.length ? keys.length + 2 : 3));
      for (const [heading, product, proposed] of [[resolved?'Ficha anterior':'Ficha actual', baseline, false], ['Propuesta', active.proposed, true]]) {
        const section = document.createElement('section'); section.className = proposed ? 'report-proposed' : 'report-current'; const title=text('h3', heading, section);icon(title,proposed?'edit':'file');
        text('small', proposed ? (keys.some(key=>changed.includes(key)) ? `${changed.length} ${changed.length === 1 ? 'dato modificado' : 'datos modificados'}` : 'Sin cambios en los datos') : 'Datos guardados', section);
        for (const key of keys) { const label=fields[key];
          const row = document.createElement('div'); row.className = 'report-field' + (changed.includes(key) ? ' report-field-changed' : ''); section.append(row);
          const labelNode=text('small', label, row);icon(labelNode,key); text('p', product?.[key] || 'Sin datos', row);
        }
        if (!keys.length) text('p', 'Sin cambios en los datos.', section);
        comparison.append(section);
      }
      }
      compare(changedFields,details);
      if(unchangedFields.length){const more=text('details','',details);more.className='report-unchanged';text('summary',`Datos sin cambios (${unchangedFields.length})`,more);compare(unchangedFields,more);}
      if(!active.photos.includes('product')){
        const wrapper=text('figure','',details);wrapper.className='report-photo-evidence report-reference-photo';
        text('figcaption','Foto de referencia',wrapper);
        const note=text('p',active.reference_image?'El usuario no adjuntó una foto del producto. Mostramos la imagen que ya tenemos.':'El usuario no adjuntó una foto del producto. No encontramos una imagen de referencia disponible en nuestra base ni en la consulta externa.',wrapper);note.className='product-edit-note';icon(note,'file');
        if(active.reference_image){const image=document.createElement('img');image.src=active.reference_image;image.alt='Foto existente del producto; no enviada en este reporte';image.className='report-evidence';image.addEventListener('error',()=>{image.remove();note.textContent='El usuario no adjuntó una foto del producto. No pudimos cargar la imagen de referencia.';},{once:true});wrapper.append(image);}
      }
      for (const kind of active.photos) {
        const wrapper = document.createElement('figure'); wrapper.className = 'report-photo-evidence'; text('figcaption', kind === 'ean' ? 'Foto del EAN' : 'Foto enviada por el usuario', wrapper); details.append(wrapper);
        if (kind === 'ean') { barcodeBox.classList.add('report-barcode-attached'); wrapper.append(barcodeBox); }
        const hint = text('p', 'Cargando foto…', wrapper);
        RepoReports.photo(id, kind).then(url => {
          if (attempt !== version || !dialog.open) { URL.revokeObjectURL(url); return; }
          photos.push(url); hint.remove(); const image = document.createElement('img'); image.src = url; image.alt = kind === 'ean' ? 'Código del producto' : 'Frente del producto'; image.className = 'report-evidence'; wrapper.append(image);
        }).catch(error => { if (attempt === version) hint.textContent = error.message; });
      }
      if (queue && result.can_review && active.status === 'pending') {
        form.reset(); for (const key of Object.keys(fields)) form.elements[key].value = active.proposed[key] || '';
        form.hidden = false;
      }
    } catch (error) { if (attempt === version) message.textContent = error.message; }
  }
  async function review(decision) {
    if (!active || busy) return;
    if (decision === 'approved' && !form.reportValidity()) return;
    busy = true; approve.disabled = reject.disabled = close.disabled = true; message.textContent = 'Guardando…';
    try {
      const body = new FormData(form); body.set('action', 'review'); body.set('id', active.id); body.set('decision', decision); body.set('revision', active.current?.revision || 0); body.set('csrf', csrf);
      const result = await RepoReports.request('', body); active = result.report;form.hidden=true;const notice=document.getElementById('reportActionStatus');notice.textContent=decision==='approved'?'Aprobado. Producto actualizado.':'Reporte rechazado.';dialog.close(); window.dispatchEvent(new Event('repotools:reports-changed'));await load();list.querySelector('button')?.focus();
    } catch (error) { message.textContent = error.message + (error.status === 409 ? ' Cierra y abre el reporte para revisar los datos actuales.' : ''); }
    finally { busy = false; approve.disabled = reject.disabled = close.disabled = false; }
  }
  close.addEventListener('click', () => { if (!busy) dialog.close(); }); dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); }); dialog.addEventListener('close', () => { ++version; active = null; clearPhotos(); });
  form.addEventListener('submit', event => { event.preventDefault(); review('approved'); }); reject.addEventListener('click', () => review('rejected'));
  const savedView=new URL(location.href).searchParams.get('view');const savedTab=queue&&['pending','history','logs','users','branches'].includes(savedView)?document.querySelector(`[data-moderation-view=${savedView}]`):null;if(savedTab)savedTab.click();else load();
  const linkedReport = new URL(location.href).searchParams.get('report');
  if (/^[1-9]\d*$/.test(linkedReport || '')) open(Number(linkedReport));
})();
