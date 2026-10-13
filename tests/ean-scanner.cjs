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
const window = { Html5Qrcode: Reader, Html5QrcodeSupportedFormats: { EAN_13: 9, EAN_8: 10, QR_CODE: 0 } };
let audioCreated = 0, audioResumed = 0, beeps = 0, unlocked = 0, lastAudio, toneSession;
window.localStorage = { getItem: () => 'off' };
window.navigator = { audioSession: { type: 'auto' } };
window.AudioContext = class {
  constructor() { audioCreated++; lastAudio = this; this.state = 'suspended'; this.currentTime = 0; this.destination = {}; }
  resume() { audioResumed++; this.state = 'running'; return Promise.resolve(); }
  createOscillator() { return { frequency: { setValueAtTime() {} }, connect() {}, disconnect() {}, start() { beeps++; toneSession = window.navigator.audioSession?.type; }, stop() { this.onended?.(); } }; }
  createGain() { return { gain: { setValueAtTime() {}, linearRampToValueAtTime() {}, exponentialRampToValueAtTime() {} }, connect() {}, disconnect() {} }; }
  createBuffer() { return {}; }
  createBufferSource() { return { connect() {}, disconnect() {}, start() { unlocked++; this.onended?.(); } }; }
};
const frame = new Set();
const document = { getElementById: () => ({ classList: { add: value => frame.add(value), remove: value => frame.delete(value) } }) };
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../js/ean-scanner.js'), 'utf8'), { window, document, setTimeout: (callback, delay) => { if (delay <= 180) callback(); return 1; }, clearTimeout: () => {}, performance: { now: () => now } });
const service = window.RepoEANScanner;
const a = '7791234567898', b = '96385074';
const flush = async () => { for (let i = 0; i < 20; i++) await Promise.resolve(); };
function read(reader, code) { now += 100; reader.success(code, { decodedText: code }); }
(async () => {
  assert.equal(service.validEAN(a), true);
  assert.equal(service.validEAN(b), true);
  for (const code of ['55007732', '7791234567890', '96385075', '00000000', '123', 7791234567898]) assert.equal(service.validEAN(code), false);
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
  const firstOpening = scanner.start();
  assert.equal(audioCreated, 1, 'El audio se prepara dentro del toque que abre la cámara');
  assert.equal(audioResumed, 1);
  assert.equal(unlocked, 1, 'Desbloquea el audio al tocar Escanear, incluso con una preferencia antigua de silencio');
  assert.equal(window.navigator.audioSession.type, 'ambient', 'No solicita audio exclusivo');
  await firstOpening;
  lastAudio.state = 'interrupted';
  const first = readers.at(-1);
  for (let i = 0; i < 10; i++) read(first, '55007732');
  assert.equal(accepted.length, 0, 'El codigo incorrecto informado nunca se confirma');
  assert.equal(first.scanConfig.videoConstraints.width.ideal, 1920, 'Solicita detalle de barras pequeñas sin exigir resolución al dispositivo');
  assert.equal(first.scanConfig.videoConstraints.height.ideal, 1080);
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
  assert.equal(beeps, 1, 'El primer resultado confirmado suena una sola vez');
  assert.equal(toneSession, 'playback', 'El pitido confirmado usa reproducción audible con silencio en iOS compatible');
  assert.equal(window.navigator.audioSession.type, 'ambient', 'Al terminar restaura la política de mezcla anterior');
  assert.equal(audioResumed, 2, 'Recupera el audio si la apertura de cámara lo interrumpe');
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
  assert.equal(beeps, 1, 'Una confirmación cancelada tampoco suena');

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
  const qrCodes = [];
  const qr = service.create('qr', { onCode: code => qrCodes.push(code) });
  await qr.start('qr'); const qrReader = readers.at(-1);
  assert.deepEqual(Array.from(qrReader.config.formatsToSupport), [0], 'El palet usa el motor compartido en modo QR');
  read(qrReader, ''); read(qrReader, 'PALET-001'); read(qrReader, 'PALET-001');
  assert.equal(frame.has('ean-detecting'), true); assert.equal(qrCodes.length, 0);
  read(qrReader, 'PALET-001'); await flush();
  assert.deepEqual(qrCodes, ['PALET-001']); assert.equal(qrReader.stops, 1);
  assert.equal(toneSession, 'playback', 'QR comparte la misma confirmación sonora que EAN');
  const soundFailureCodes = [], soundFailure = service.create('audio-failure', { onCode: code => soundFailureCodes.push(code) });
  await soundFailure.start(); const soundFailureReader = readers.at(-1), originalResume = lastAudio.resume;
  lastAudio.state = 'interrupted'; lastAudio.resume = () => Promise.reject(new Error('Audio interrupted'));
  const tonesBeforeFailure = beeps;
  read(soundFailureReader, b); read(soundFailureReader, b); read(soundFailureReader, b); await flush();
  assert.deepEqual(soundFailureCodes, [b], 'Una falla de audio no pierde el código confirmado');
  assert.equal(beeps, tonesBeforeFailure);
  assert.equal(window.navigator.audioSession.type, 'ambient', 'Una falla al reanudar también libera playback');
  lastAudio.resume = originalResume;
  const visibleCodes = [];
  const video = { videoWidth: 1280, videoHeight: 720, clientWidth: 320, clientHeight: 330 };
  const element = { classList: { add() {}, remove() {} }, querySelector: () => video };
  document.getElementById = () => element;
  document.createElement = () => ({ getContext: () => null });
  const visibleScanner = service.create('visible', { onCode: code => visibleCodes.push(code) });
  await visibleScanner.start();
  const visibleReader = readers.at(-1);
  visibleReader.qrcode = { decodeRobustlyAsync() {} };
  for (let i = 0; i < 5; i++) read(visibleReader, a);
  await flush();
  assert.deepEqual(visibleCodes, [], 'El segundo argumento del lector no evita validar el encuadre visible');
  video.clientWidth = 1280; video.clientHeight = 720;
  for (let i = 0; i < 3; i++) read(visibleReader, '7803403004612');
  await flush();
  assert.deepEqual(visibleCodes, ['7803403004612']);
  console.log('OK: checksum EAN8/13, encuadre visible, confirmacion estable y ciclo de camara.');
})().catch(error => { console.error(error); process.exitCode = 1; });
