(() => {
  'use strict';
  const badges = [...document.querySelectorAll('[data-report-pending]')]; if (!badges.length) return;
  let pending = false;
  async function refresh() {
    if (pending || document.hidden) return; pending = true;
    try {
      const response = await fetch('api/reportes.php?pending_count=1', { credentials: 'same-origin', cache: 'no-store' });
      if (response.status === 401 || response.status === 403) { document.querySelectorAll('.report-nav-link').forEach(link => link.hidden = true); badges.forEach(badge => badge.hidden = true); return; }
      if (!response.ok) return;
      const result = await response.json(); if (!Number.isInteger(result.pending_count) || result.pending_count < 0) return;
      for (const badge of badges) { badge.textContent = result.pending_count > 99 ? '99+' : String(result.pending_count); badge.hidden = result.pending_count === 0; badge.setAttribute('aria-label', `${result.pending_count} reportes pendientes`); }
    } catch {} finally { pending = false; }
  }
  setInterval(refresh, 60000); window.addEventListener('focus', refresh); window.addEventListener('repotools:reports-changed', refresh);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
