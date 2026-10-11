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
    document.querySelectorAll('[data-stock-expiry]').forEach(dot => { dot.hidden = result.has_expiring_stock !== true; });
    const badge = get('notificationBadge'); badge.hidden = result.unread === 0; badge.textContent = result.unread > 99 ? '99+' : result.unread;
    button.setAttribute('aria-label', result.unread ? `Notificaciones: ${result.unread} sin leer` : 'Notificaciones');
    get('readAllNotifications').hidden = result.unread === 0;
    get('notificationStatus').textContent = items.length ? '' : 'Todavía no hay notificaciones.';
    get('notificationList').replaceChildren();
    items.forEach(item => {
      const link = document.createElement('a'); link.className = 'notification-item';
      const state = item.type === 'report_review' ? (item.report_status || (item.title.includes('aprobado') ? 'approved' : item.title.includes('rechazado') ? 'rejected' : 'info')) : item.type === 'stock_expiry' ? 'warning' : 'info';
      link.dataset.state = state;
      const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); icon.classList.add('notification-icon'); icon.setAttribute('viewBox', '0 0 24 24'); icon.setAttribute('fill', 'none'); icon.setAttribute('stroke', 'currentColor'); icon.setAttribute('stroke-width', '1.8'); icon.setAttribute('aria-hidden', 'true');
      const path = document.createElementNS(icon.namespaceURI, 'path'); path.setAttribute('d', state === 'approved' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0' : state === 'rejected' ? 'm9 9 6 6m0-6-6 6M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0' : state === 'warning' ? 'M12 8v5m0 3v1M3 20h18L12 3Z' : 'M12 11v6m0-10v1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0'); icon.append(path);
      link.href = item.report_id ? `reportes.php?report=${encodeURIComponent(item.report_id)}` : item.type?.startsWith('stock_') ? 'stock.php' : 'cuenta.php';
      if (!item.read_at) link.dataset.unread = '';
      const title = document.createElement('strong'); title.textContent = item.title;
      const body = document.createElement('span'); body.textContent = item.body;
      const date = document.createElement('time'); date.dateTime = item.created_at;
      const instant = new Date(item.created_at); date.textContent = Number.isNaN(instant.getTime()) ? '' : instant.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
      const content = document.createElement('div'); content.className = 'notification-content'; content.append(title, body, date); link.append(icon, content);
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
