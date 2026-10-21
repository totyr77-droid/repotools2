(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  const fetch = page?.fetch || globalThis.fetch?.bind(globalThis);
  const setInterval = page?.interval || globalThis.setInterval?.bind(globalThis);

  const config = window.repotoolsNotifications;
  if (!config) return;
  const get = id => document.getElementById(id), panel = get('notificationPanel'), button = get('openNotifications');
  let items = [], busy = false, version = 0;
  function toggle(open) { panel.hidden = !open; button.setAttribute('aria-expanded', String(open)); }
  function render(result) {
    items = result.notifications;
    window.dispatchEvent(new CustomEvent('repotools:achievement-notifications',{detail:items.filter(i=>i.type==='achievement'&&!i.read_at)}));
    document.querySelectorAll('[data-stock-expiry]').forEach(badge => { const count=Number(result.expiring_stock_count)||0;badge.hidden=count===0;badge.textContent=count>99?'99+':String(count);badge.setAttribute('aria-label',count+' productos por vencer o vencidos'); });
    const badge = get('notificationBadge'); badge.hidden = result.unread === 0; badge.textContent = result.unread > 99 ? '99+' : result.unread;
    button.setAttribute('aria-label', result.unread ? `Notificaciones: ${result.unread} sin leer` : 'Notificaciones');
    get('readAllNotifications').hidden = result.unread === 0;
    get('notificationStatus').textContent = items.length ? '' : 'Todavía no hay notificaciones.';
    get('notificationList').replaceChildren();
    items.forEach(item => {
      const link = document.createElement('a'); link.className = 'notification-item';
      const state = item.type === 'report_review' ? (item.report_status || (item.title.includes('aprobado') ? 'approved' : item.title.includes('rechazado') ? 'rejected' : 'info')) : item.type === 'stock_expiry' ? (item.expiry_state || 'warning') : 'info';
      link.dataset.state = state;
      const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); icon.classList.add('notification-icon'); icon.setAttribute('viewBox', '0 0 24 24'); icon.setAttribute('fill', 'none'); icon.setAttribute('stroke', 'currentColor'); icon.setAttribute('stroke-width', '1.8'); icon.setAttribute('aria-hidden', 'true');
      const path = document.createElementNS(icon.namespaceURI, 'path'); path.setAttribute('d', ['stock_invitation','team_invitation'].includes(item.type) ? 'M3 5h18v14H3zM3 5l9 7 9-7' : state === 'approved' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0' : state === 'rejected' ? 'm9 9 6 6m0-6-6 6M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0' : ['warning','today','expired'].includes(state) ? 'M12 8v5m0 3v1M3 20h18L12 3Z' : 'M12 11v6m0-10v1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0'); icon.append(path);if(item.type==='library_invitation')icon.innerHTML='<path d="M3 4h7l2 2 2-2h7v15h-7l-2 2-2-2H3zM12 6v15"/>';if(item.type==='referral_joined')icon.innerHTML='<circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 14 0v3M17 3a4 4 0 0 1 0 8m3 10v-3a7 7 0 0 0-2-5"/>';
      link.href = item.type==='team_pallet_request' ? 'stock.php?solicitud_equipo='+encodeURIComponent(item.team_request_id||'') : item.type==='library_invitation' ? 'biblioteca.php'+(item.library_token?'?share='+encodeURIComponent(item.library_token):'') : item.type==='referral_joined' ? 'referidos.php' : item.type==='achievement' ? 'cuenta.php#insignias' : item.type==='team_invitation' ? 'sucursales.php' : item.type==='stock_invitation' ? 'stock.php?invitacion='+encodeURIComponent(item.invitation_code||'') : item.report_id ? `reportes.php?report=${encodeURIComponent(item.report_id)}` : item.pallet_code ? 'stock.php?codigo='+encodeURIComponent(item.pallet_code)+'&producto='+encodeURIComponent(item.product_id) : item.type?.startsWith('stock_') ? 'stock.php' : 'cuenta.php';
      if (!item.read_at) link.dataset.unread = '';
      const title = document.createElement('strong'); title.textContent = item.title;
      const body = document.createElement('span'); body.textContent = item.body;
      const date = document.createElement('time'); date.dateTime = item.created_at;
      const instant = window.RepoDateTime?.parse(item.created_at)||new Date(item.created_at); date.textContent = Number.isNaN(instant.getTime()) ? '' : instant.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short', timeZone:'America/Buenos_Aires', hourCycle:'h23' });
      const content = document.createElement('div'); content.className = 'notification-content'; content.append(title, body);if(item.achievement_detail){const detail=document.createElement('span');detail.className='notification-achievement-detail';detail.textContent=item.achievement_detail;content.append(detail);}if(item.expiry_label){const tag=document.createElement('span');tag.className='notification-expiry-label';tag.textContent=item.expiry_label;content.append(tag);}content.append(date); if(item.type==='achievement'&&/^img\/achievements\/[a-z_]+\/[a-z_]+_[0-9]{2}_[a-z]+\.svg$/.test(item.badge||'')){const badge=document.createElement('img');badge.className='notification-badge';badge.src=item.badge;badge.alt='';badge.width=44;badge.height=50;link.append(badge,content);}else link.append(icon, content);
      link.addEventListener('click', async event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        try { await refresh({ action: 'read', id: item.id }); } finally { if (window.RepoNavigation) window.RepoNavigation.navigate(link.href); else location.assign(link.href); }
      });
      get('notificationList').append(link);
    });
  }
  async function refresh(data) {
    if (!data && (busy || document.hidden)) return;
    const attempt = ++version; busy = true;
    if (!items.length && !panel.hidden) get('notificationStatus').textContent = 'Cargando…';
    try {
      const response = await fetch('api/notificaciones.php', { credentials: 'same-origin', cache: 'no-store', ...(data ? { method: 'POST', body: new URLSearchParams({ csrf: config.csrf, ...data }) } : {}) });
      if (response.status === 401) { toggle(false); button.hidden = true; return; }
      const result = await response.json(); if (!response.ok) throw new Error(result.error || 'No pudimos cargar las notificaciones.');
      if (attempt === version) render(result);
    } catch (error) { if (attempt === version && !panel.hidden) get('notificationStatus').textContent = error.message; }
    finally { if (attempt === version) busy = false; }
  }
  button.addEventListener('click', () => { const open = panel.hidden; toggle(open); if (open) refresh(); });
  get('closeNotifications').addEventListener('click', () => { toggle(false); button.focus(); });
  get('readAllNotifications').addEventListener('click', async () => {
    if (!items.length) return;
    const control = get('readAllNotifications'); control.disabled = true;
    try { await refresh({ action: 'read_all', id: items[0].id }); } finally { control.disabled = false; }
  });
  document.addEventListener('click', event => { if (!event.target.closest('.notification-control')) toggle(false); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel.hidden) { toggle(false); button.focus(); } });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
  (page ? page.listen : window.addEventListener.bind(window))('focus', () => refresh()); (page ? page.listen : window.addEventListener.bind(window))('repotools:reports-changed', () => refresh());
  setInterval(() => refresh(), 60000); refresh();
  (page ? page.listen : window.addEventListener.bind(window))('repotools:stock-changed', () => refresh());
})();
