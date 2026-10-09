const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
class Element {
  constructor() { this.textContent = ''; this.style = {}; this.disabled = false; this.open = false; }
  addEventListener() {}
  showModal() { this.open = true; }
  close() { this.open = false; }
}
const elements = new Map();
const get = id => { if (!elements.has(id)) elements.set(id, new Element()); return elements.get(id); };
const storage = new Map([['lista', JSON.stringify(['96385074'])]]);
const copied = [], drawn = [];
let finishCopy, oldProduct;
const context = {
  window: { location: { href: 'https://example.invalid/index.php' }, addEventListener() {}, RepoEANScanner: { create: () => ({ start: async () => {}, stop: async () => {} }), validEAN: code => ['7791234567898', '96385074'].includes(code) }, RepoProductInfo: { show() {}, clear() {}, editButton() {}, get: code => code === '7791234567898' ? new Promise(resolve => oldProduct = resolve) : Promise.resolve({ name: 'Actual', quantity: '1 L' }), summary: product => product.name + ' | ' + product.quantity } },
  document: { getElementById: get, querySelector: get, addEventListener() {}, body: { classList: { add() {}, remove() {} } } },
  localStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) },
  Audio: class { play() { return Promise.resolve(); } },
  navigator: { clipboard: { writeText: code => { copied.push(code); return new Promise(resolve => finishCopy = resolve); } } },
  JsBarcode: (target, code) => drawn.push(code), URL, console
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../js/script.js'), 'utf8'), context);
(async () => {
  get('contenedormax').showModal();
  const old = context.mostrarResultadoEscaneo('7791234567898');
  assert.equal(get('resultado').textContent, '7791234567898');
  const copying = context.copiarResultado();
  await context.iniciarScanner();
  assert.equal(get('copy').disabled, true);
  await context.mostrarResultadoEscaneo('96385074');
  finishCopy(); await copying;
  oldProduct({ name: 'Anterior', quantity: '500 ml' }); await old;
  assert.equal(get('.resultado-api').textContent, 'Actual | 1 L', 'Una respuesta tardía no cambia el producto actual');
  assert.equal(get('scanCopyStatus').textContent, '', 'Una copia tardía no anuncia el EAN anterior');
  const currentCopy = context.copiarResultado(); finishCopy(); await currentCopy;
  assert.deepEqual(copied, ['7791234567898', '96385074']);
  assert.equal(get('scanCopyStatus').textContent, 'EAN copiado: 96385074');
  context.verCodigo(1);
  assert.equal(drawn.at(-1), '96385074');
  assert.equal(JSON.parse(storage.get('lista')).length, 2, 'Escanear un duplicado no duplica la lista');
  context.cerrarScanner();
  await context.copiarResultado();
  assert.equal(copied.length, 2, 'No copia un resultado antiguo después de cerrar');
  console.log('OK: EAN visible y copiado coinciden, duplicados preservados, producto y portapapeles tardíos no mezclan resultados.');
})().catch(error => { console.error(error); process.exitCode = 1; });

