const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const assert = require('node:assert/strict');
const window = new EventTarget(), document = new EventTarget();
let stopped = 0, closed = 0, cleared = 0, calls = 0, cleanup = 0;
const originalBody = {
  querySelector: selector => selector === '#original' ? 'original element' : null,
  querySelectorAll: selector => selector === 'video' ? [{ srcObject: { getTracks: () => [{ stop() { stopped++; } }] } }] : selector === 'dialog[open]' ? [{ close() { closed++; } }] : []
};
document.body = originalBody; document.scripts = [];
document.documentElement = { dataset: {} };
const requests = [];
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../js/navigation.js'), 'utf8'), {
  window, document, location: { href: 'https://example.invalid/index.php', origin: 'https://example.invalid' }, history: {},
  URL, AbortController, AbortSignal, CSS: { escape: value => value },
  setInterval: () => 123, clearInterval: id => { assert.equal(id, 123); cleared++; },
  fetch: async (url, options) => { requests.push(options); return {}; }
});
(async () => {
  const page = window.RepoNavigation.page;
  document.dispatchEvent(new Event('keydown'));
  assert.equal(document.documentElement.dataset.keyboardFocus, 'true');
  document.dispatchEvent(new Event('pointerdown'));
  assert.equal(document.documentElement.dataset.keyboardFocus, 'false');
  page.listen('activity', () => calls++);
  page.document.addEventListener('activity', () => calls++);
  window.dispatchEvent(new Event('activity')); document.dispatchEvent(new Event('activity'));
  assert.equal(calls, 2);
  page.interval(() => {}, 60000);
  page.onLeave(async () => { cleanup++; });
  page.onLeave(() => { throw new Error('A failed cleanup must not keep the camera running'); });
  await page.fetch('/api/example');
  const requestController = new AbortController();
  await page.fetch('/api/example', { signal: requestController.signal });
  document.body = { querySelector: () => 'new element' };
  assert.equal(page.document.getElementById('original'), 'original element', 'Late callbacks retain their original section');
  await page.dispose();
  window.dispatchEvent(new Event('activity')); document.dispatchEvent(new Event('activity'));
  assert.equal(calls, 2, 'Leaving removes document and window listeners');
  assert.equal(cleared, 1, 'Leaving stops notification polling');
  assert.equal(cleanup, 1);
  assert.equal(stopped, 1, 'Leaving releases the camera even when another cleanup fails');
  assert.equal(closed, 1);
  assert.equal(requests.every(request => request.signal.aborted), true, 'Leaving cancels requests, including those with their own signal');
  console.log('OK: navigation cleans listeners, polling, requests, dialogs and camera; late callbacks retain the old section.');
})().catch(error => { console.error(error); process.exitCode = 1; });
