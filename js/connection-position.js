(() => {
  'use strict';
  if (window.RepoConnectionPosition) return;
  window.RepoConnectionPosition = (button, doc = document) => {
    const controller = new AbortController(), signal = controller.signal;
    const key = 'repotools_connection_side_' + (doc.body.dataset.uiUser || '0');
    let side, y, drag, suppressUntil = 0, frame;
    try { side = localStorage.getItem(key); } catch {}
    let explicitSide=['left','right'].includes(side);
    if (!explicitSide) side = doc.body.dataset.hand === 'left' ? 'left' : 'right';
    const listen = (target, name, fn, options = {}) => target.addEventListener(name, fn, { ...options, signal });
    function bounds() {
      const vv = window.visualViewport, top = vv?.offsetTop || 0, bottom = top + (vv?.height || innerHeight);
      const header = doc.querySelector('header')?.getBoundingClientRect();
      const nav = doc.querySelector('.repo-mobile-nav')?.getBoundingClientRect();
      return { minX: 12, maxX: Math.max(12, innerWidth - button.offsetWidth - 12), minY: Math.max(top + 12, (header?.bottom || top) + 8), maxY: Math.max(top + 12, Math.min(bottom - 12, nav?.height ? nav.top - 12 : bottom - 12) - button.offsetHeight) };
    }
    function place(x, nextY, moving = false) {
      if (button.hidden) return;
      const b = bounds(), width = button.offsetWidth, height = button.offsetHeight;
      x = Math.max(b.minX, Math.min(b.maxX, x));
      let ceiling = b.maxY;
      if (doc.body.dataset.oneHand === '1' && doc.querySelector('.repo-mobile-nav[data-open=true]')) {
        for (const group of doc.querySelectorAll('.one-hand-fabs')) {
          const r = group.getBoundingClientRect();
          if (r.height && x < r.right + 8 && x + width > r.left - 8) ceiling = Math.min(ceiling, r.top - height - 12);
        }
      }
      y = Math.max(b.minY, Math.min(Math.max(b.minY, ceiling), nextY));
      button.dataset.dragging = String(moving);
      button.style.setProperty('left', x + 'px', 'important');
      button.style.setProperty('top', y + 'px', 'important');
      button.style.setProperty('right', 'auto', 'important');
      button.style.setProperty('bottom', 'auto', 'important');
    }
    function settle() { if(!explicitSide)side=doc.body.dataset.hand==='left'?'left':'right';const b = bounds(); place(side === 'left' ? b.minX : b.maxX, y ?? b.maxY); }
    const schedule = () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(settle); };
    listen(button, 'pointerdown', event => {
      if (event.button !== 0 || !event.isPrimary) return;
      const r = button.getBoundingClientRect();
      drag = { id: event.pointerId, x: event.clientX, y: event.clientY, left: r.left, top: r.top, moved: false };
    });
    listen(window, 'pointermove', event => {
      if (!drag || event.pointerId !== drag.id) return;
      const dx = event.clientX - drag.x, dy = event.clientY - drag.y;
      if (!drag.moved && Math.hypot(dx, dy) < 7) return;
      drag.moved = true;
      event.preventDefault();
      place(drag.left + dx, drag.top + dy, true);
    }, { passive: false });
    function finish(event) {
      if (!drag || event.pointerId !== drag.id) return;
      if (drag.moved) {
        suppressUntil = performance.now() + 500;explicitSide=true;
        side = button.getBoundingClientRect().left + button.offsetWidth / 2 < innerWidth / 2 ? 'left' : 'right';
        try { localStorage.setItem(key, side); } catch {}
        settle();
      }
      drag = null;
    }
    listen(window, 'pointerup', finish); listen(window, 'pointercancel', finish);
    listen(button, 'click', event => { if (performance.now() < suppressUntil) { event.preventDefault(); event.stopImmediatePropagation(); } }, { capture: true });
    listen(window, 'resize', schedule); listen(window, 'orientationchange', schedule);
    if (window.visualViewport) { listen(visualViewport, 'resize', schedule); listen(visualViewport, 'scroll', schedule); }
    const observer = new MutationObserver(schedule);
    observer.observe(doc.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['hidden', 'data-open', 'data-one-hand', 'data-hand'] });
    schedule();
    return () => { controller.abort(); observer.disconnect(); cancelAnimationFrame(frame); };
  };
})();
