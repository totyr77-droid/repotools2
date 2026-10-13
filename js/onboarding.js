(() => {
  'use strict';
  const page = window.RepoNavigation?.page, doc = page?.document || document;
  const dialog = doc.getElementById('onboardingDialog');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const version = '1', identity = dialog.dataset.user === '0' ? 'guest' : dialog.dataset.user;
  const seenKey = 'repotools_onboarding_seen_' + identity, versionKey = 'repotools_onboarding_version_' + identity;
  const memory = window.RepoOnboardingMemory ??= new Set();
  const title = doc.getElementById('onboardingTitle'), text = doc.getElementById('onboardingText'), icon = doc.getElementById('onboardingIcon');
  const back = doc.getElementById('onboardingBack'), next = doc.getElementById('onboardingNext'), progress = doc.getElementById('onboardingProgress');
  const steps = [
    ['Escaneá productos', 'Usá la cámara para leer códigos EAN y encontrar productos rápidamente.', 'M3 5v14m3-14v14m4-14v14m3-14v14m5-14v14m3-14v14'],
    ['Organizá tu stock', 'Con tu cuenta, cargá cantidades y mantené tus productos organizados mientras trabajás.', 'M3 7l9-4 9 4v10l-9 4-9-4ZM3 7l9 5 9-5M12 12v9'],
    ['Trabajá con palets', 'Creá palets, organizá productos y mantené todo separado por tarea o sucursal.', 'M3 21h18M4 17h16V4H4zM4 10h16M12 4v13'],
    ['Compartí cuando lo necesites', 'Compartí stock o palets de forma rápida con otras personas.', 'M12 16V3m-5 5 5-5 5 5M5 12v9h14v-9']
  ];
  let index = 0, previousFocus, timer, disposed = false, interacted = false;
  function seen() {
    if (memory.has(identity + ':' + version)) return true;
    try { return localStorage.getItem(seenKey) === 'true' && localStorage.getItem(versionKey) === version; } catch { return false; }
  }
  function markSeen() {
    memory.add(identity + ':' + version);
    try { localStorage.setItem(versionKey, version); localStorage.setItem(seenKey, 'true'); } catch { /* Optional when storage is unavailable. */ }
  }
  function render() {
    title.textContent = steps[index][0]; text.textContent = steps[index][1];
    const path = doc.createElementNS('http://www.w3.org/2000/svg', 'path'); path.setAttribute('d', steps[index][2]); icon.replaceChildren(path);
    progress.textContent = (index + 1) + ' de 4'; back.textContent = index ? 'Atrás' : 'Omitir';
    next.firstChild.textContent = index === 3 ? 'Empezar' : 'Siguiente';
  }
  function safe() {
    return !disposed && dialog.isConnected && !document.documentElement.classList.contains('app-navigating') && !document.querySelector('.repotools-loader--fullscreen') && !doc.querySelector('dialog[open], [aria-busy="true"]') && ![...doc.querySelectorAll('video')].some(video => video.srcObject);
  }
  function open() {
    if (!safe()) return;
    clearTimeout(timer); previousFocus = document.activeElement; index = 0; render(); dialog.showModal(); next.focus();
  }
  function finish() { markSeen(); dialog.close(); }
  back.addEventListener('click', () => { if (!index) finish(); else { index--; render(); } });
  next.addEventListener('click', () => { if (index === 3) finish(); else { index++; render(); } });
  dialog.querySelector('.onboarding-close').addEventListener('click', finish);
  dialog.addEventListener('cancel', () => { if (!disposed) markSeen(); });
  dialog.addEventListener('keydown', event => {
    if (event.key !== 'Tab') return;
    const controls = [...dialog.querySelectorAll('button:not([disabled])')];
    const current = controls.indexOf(document.activeElement);
    event.preventDefault();
    controls[(current + (event.shiftKey ? -1 : 1) + controls.length) % controls.length]?.focus();
  });
  dialog.addEventListener('close', () => {
    if (disposed) return;
    markSeen();
    if (previousFocus?.isConnected && previousFocus !== doc.body) previousFocus.focus({preventScroll:true});
    else { const heading = doc.querySelector('main h1'); if (heading) { heading.tabIndex = -1; heading.focus({preventScroll:true}); } }
  });
  doc.getElementById('replayOnboarding')?.addEventListener('click', open);
  const interaction = () => { interacted = true; clearTimeout(timer); };
  doc.addEventListener('pointerdown', interaction, {capture:true, once:true});
  doc.addEventListener('keydown', interaction, {capture:true, once:true});
  function schedule() {
    if (disposed || interacted || seen() || dialog.dataset.auto !== 'true') return;
    timer = setTimeout(() => { if (!interacted && !seen()) open(); }, 400);
  }
  // Wait for page resources, not for a camera or any real operation.
  if (document.readyState === 'complete') schedule();
  else (page?.listen || window.addEventListener.bind(window))('load', schedule, {once:true});
  page?.onLeave(() => { disposed = true; clearTimeout(timer); dialog.remove(); });
})();
