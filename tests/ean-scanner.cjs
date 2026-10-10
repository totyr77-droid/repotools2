const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');
const deferred = () => { let resolve, reject; const promise = new Promise((a, b) => { resolve = a; reject = b; }); return { promise, resolve, reject }; };
let now = 0, pendingStart = null, failStart = false;
const readers = [];
class Reader {
  constructor(id, config) { this.id = id; this.config = config; this.isScanning = false; this.stops = 0; this.clears = 0; readers.push(this); }
  async start(camera, config, success) {
    this.success = success; this.scanConfig = config;
    if (failStart) { failStart = false; throw new Error('Permission denied'); }
    if (pendingStart) { const pending = pendingStart; pendingStart = null; await pending.promise; }
    this.isScanning = true;
  }
  async stop() { this.stops++; this.isScanning = false; }
  clear() { assert.equal(this.isScanning, false); this.clears++; }
}
const window = { Html5Qrcode: Reader, Html5QrcodeSupportedFormats: { EAN_13: 9, EAN_8: 10 } };
const frame = new Set();
const document = { getElementById: () => ({ classList: { add: value => frame.add(value), remove: value => frame.delete(value) } }) };
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../js/ean-scanner.js'), 'utf8'), { window, document, setTimeout: (callback, delay) => { if (delay <= 180) callback(); return 1; }, clearTimeout: () => {}, performance: { now: () => now } });
const service = window.RepoEANScanner;
const a = '7791234567898', b = '96385074';
const flush = async () => { for (let i = 0; i < 20; i++) await Promise.resolve(); };
function read(reader, code) { now += 100; reader.success(code); }
(async () => {
  assert.equal(service.validEAN(a), true);
  assert.equal(service.validEAN(b), true);
  for (const code of ['7791234567890', '96385075', '00000000', '123', 7791234567898]) assert.equal(service.validEAN(code), false);
  const confirmation = service.confirmation();
  assert.equal(confirmation(a, 0), false);
  assert.equal(confirmation(a, 1), false, 'Callbacks del mismo fotograma no confirman');
  assert.equal(confirmation(a, 100), false);
  assert.equal(confirmation(b, 200), false, 'Un código diferente reinicia la confirmación');
  assert.equal(confirmation(b, 300), false);
  assert.equal(confirmation(b, 400), true);
  const stale = service.confirmation();
  stale(a, 0); stale(a, 100);
  assert.equal(stale(a, 900), false, 'Una lectura antigua no confirma otra posterior');

  const accepted = [], errors = [];
  const scanner = service.create('reader', { onCode: code => accepted.push(code), onError: error => errors.push(error) });
  await scanner.start();
  const first = readers.at(-1);
  read(first, '7791234567890'); read(first, a); read(first, b); read(first, a); read(first, a);
  assert.equal(accepted.length, 0);
  assert.equal(frame.has('ean-confirmed'), false, 'Las lecturas iniciales no muestran verde');
  assert.equal(frame.has('ean-detecting'), true, 'Las lecturas iniciales muestran amarillo');
  read(first, a);
  assert.equal(frame.has('ean-confirmed'), true, 'Solo la lectura confirmada cambia el marco a verde');
  assert.equal(frame.has('ean-detecting'), false, 'La confirmación quita el amarillo');
  read(first, b); read(first, a);
  await flush();
  assert.deepEqual(accepted, [a]);
  assert.equal(first.stops, 1); assert.equal(first.clears, 1);
  await scanner.stop();
  read(first, b); await flush(); assert.deepEqual(accepted, [a]);

  pendingStart = deferred();
  const pending = pendingStart;
  const opening = scanner.start(); await flush();
  const second = readers.at(-1);
  const closing = scanner.stop();
  read(second, b); read(second, b); read(second, b);
  pending.resolve();
  assert.equal(await opening, false);
  await closing;
  assert.equal(second.stops, 1); assert.equal(second.clears, 1);
  assert.deepEqual(accepted, [a], 'Cerrar durante el permiso de cámara cancela resultados');

  const before = readers.length;
  const doubleStart = scanner.start(); const lastStart = scanner.start();
  assert.equal(await doubleStart, false); assert.equal(await lastStart, true);
  assert.equal(readers.length, before + 1, 'Doble toque abre una sola cámara');
  const third = readers.at(-1);
  read(third, b); read(third, b); read(third, b);
  const cancel = scanner.stop(); await cancel; await flush();
  assert.deepEqual(accepted, [a], 'Cancelar una confirmación pendiente no guarda ni suma');

  failStart = true;
  await assert.rejects(scanner.start(), /Permission denied/);
  await scanner.start();
  const recovered = readers.at(-1);
  read(recovered, b); read(recovered, b); read(recovered, b); await flush();
  assert.deepEqual(accepted, [a, b]);
  assert.equal(errors.length, 0);
  // Una consulta de producto lenta no bloquea el cierre ni la siguiente cámara.
  const resultPending = deferred();
  const slow = service.create('slow', { onCode: () => resultPending.promise });
  await slow.start(); const last = readers.at(-1);
  read(last, a); read(last, a); read(last, a); await flush();
  await slow.stop(); await slow.start(); await slow.stop(); resultPending.resolve();
  console.log('OK: checksum EAN8/13, confirmación estable, un resultado por sesión, cierre durante permisos, doble toque y recuperación de cámara.');
})().catch(error => { console.error(error); process.exitCode = 1; });
