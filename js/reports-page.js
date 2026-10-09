(() => {
  'use strict';
  const list = document.getElementById('reportsList'), status = document.getElementById('reportsStatus');
  const dialog = document.getElementById('reviewReportDialog'), details = document.getElementById('reviewReportDetails'), form = document.getElementById('reviewReportForm'), message = document.getElementById('reviewReportStatus');
  const close = document.getElementById('closeReviewReport'), approve = document.getElementById('approveReport'), reject = document.getElementById('rejectReport');
  let queue = false, version = 0, listVersion = 0, active, csrf, busy = false, photos = [];
  const labels = { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' };
  const fields = { name: 'Nombre', quantity: 'Presentación', brand: 'Marca', category: 'Categoría', packaging: 'Envase' };
  function text(tag, value, container = details) { const node = document.createElement(tag); node.textContent = value; container.append(node); return node; }
  function clearPhotos() { photos.forEach(url => URL.revokeObjectURL(url)); photos = []; }
  async function load() {
    const attempt = ++listVersion; status.textContent = 'Cargando…'; list.replaceChildren();
    try {
      const result = await RepoReports.request(queue ? '?queue=1' : ''); if (attempt !== listVersion) return;
      csrf = result.csrf; document.getElementById('queueReports').hidden = !result.can_review;
      document.getElementById('ownReports').setAttribute('aria-pressed', String(!queue)); document.getElementById('queueReports').setAttribute('aria-pressed', String(queue));
      status.textContent = result.reports.length ? '' : (queue ? 'No hay reportes pendientes.' : 'Todavía no enviaste reportes.');
      for (const report of result.reports) {
        const card = document.createElement('article'); card.className = 'report-card';
        text('h2', `#${report.id} · ${labels[report.status]}`, card); text('p', `EAN ${report.ean}`, card); text('p', report.description, card);
        const button = text('button', queue ? 'Revisar' : 'Ver reporte', card); button.type = 'button'; button.className = 'library-secondary'; button.addEventListener('click', () => open(report.id)); list.append(card);
      }
    } catch (error) { if (attempt === listVersion) status.textContent = error.message; }
  }
  async function open(id) {
    const attempt = ++version; active = null; clearPhotos(); details.replaceChildren(); form.hidden = true; message.textContent = 'Cargando…'; if (!dialog.open) dialog.showModal();
    try {
      const result = await RepoReports.request('?id=' + id); if (attempt !== version || !dialog.open) return;
      active = result.report; csrf = result.csrf; document.getElementById('reviewReportTitle').textContent = `Reporte #${id}`;
      message.textContent = labels[active.status]; text('p', `EAN ${active.ean}`); text('p', active.description);
      if (active.review_note) text('p', active.review_note);
      const comparison = document.createElement('div'); comparison.className = 'report-comparison'; details.append(comparison);
      for (const [heading, product] of [['Ficha actual', active.current], ['Propuesta', active.proposed]]) {
        const section = document.createElement('section'); text('h3', heading, section);
        for (const [key, label] of Object.entries(fields)) if (product?.[key]) text('p', `${label}: ${product[key]}`, section);
        if (!product || !Object.keys(fields).some(key => product[key])) text('p', 'Sin datos.', section);
        comparison.append(section);
      }
      for (const kind of active.photos) {
        const wrapper = document.createElement('figure'); text('figcaption', kind === 'ean' ? 'Foto del EAN' : 'Foto del producto', wrapper); details.append(wrapper);
        const hint = text('p', 'Cargando foto…', wrapper);
        RepoReports.photo(id, kind).then(url => {
          if (attempt !== version || !dialog.open) { URL.revokeObjectURL(url); return; }
          photos.push(url); hint.remove(); const image = document.createElement('img'); image.src = url; image.alt = kind === 'ean' ? 'Código del producto' : 'Frente del producto'; image.className = 'report-evidence'; wrapper.append(image);
        }).catch(error => { if (attempt === version) hint.textContent = error.message; });
      }
      if (result.can_review && active.status === 'pending') {
        form.reset(); for (const key of Object.keys(fields)) form.elements[key].value = active.proposed[key] || '';
        form.hidden = false;
      }
    } catch (error) { if (attempt === version) message.textContent = error.message; }
  }
  async function review(decision) {
    if (!active || busy) return;
    if (decision === 'approved' && !form.reportValidity()) return;
    if (decision === 'rejected' && form.elements.review_note.value.trim().length < 5) { message.textContent = 'Explica el motivo del rechazo.'; form.elements.review_note.focus(); return; }
    busy = true; approve.disabled = reject.disabled = close.disabled = true; message.textContent = 'Guardando…';
    try {
      const body = new FormData(form); body.set('action', 'review'); body.set('id', active.id); body.set('decision', decision); body.set('revision', active.current?.revision || 0); body.set('csrf', csrf);
      const result = await RepoReports.request('', body); active = result.report; form.hidden = true; message.textContent = decision === 'approved' ? 'Aprobado. Producto actualizado.' : 'Reporte rechazado.'; await load();
    } catch (error) { message.textContent = error.message + (error.status === 409 ? ' Cierra y abre el reporte para revisar los datos actuales.' : ''); }
    finally { busy = false; approve.disabled = reject.disabled = close.disabled = false; }
  }
  document.getElementById('ownReports').addEventListener('click', () => { queue = false; load(); });
  document.getElementById('queueReports').addEventListener('click', () => { queue = true; load(); });
  close.addEventListener('click', () => { if (!busy) dialog.close(); }); dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); }); dialog.addEventListener('close', () => { ++version; active = null; clearPhotos(); });
  form.addEventListener('submit', event => { event.preventDefault(); review('approved'); }); reject.addEventListener('click', () => review('rejected')); load();
})();
