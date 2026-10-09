const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
class Element {
  constructor(tag) { this.tag = tag; this.children = []; this.attrs = {}; this.textContent = ''; }
  append(...children) { this.children.push(...children); }
  replaceChildren(...children) { this.children = children; }
  setAttribute(name, value) { this.attrs[name] = value; }
  addEventListener() {}
}
let calls = 0;
let fetcher = async () => ({ ok: true, json: async () => ({ status: 1, product: { product_name_es: 'Agua', brands: 'Marca', quantity: '1 L' } }) });
const context = { window: {}, document: { createElement: tag => new Element(tag) }, URL, AbortController, setTimeout, clearTimeout, fetch: (...args) => { calls++; return fetcher(...args); } };
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../js/product-info.js'), 'utf8'), context);
const service = context.window.RepoProductInfo;
(async () => {
  const [first, second] = await Promise.all([service.get('7791234567898'), service.get('7791234567898')]);
  assert.equal(calls, 1); assert.equal(first, second); assert.equal(first.name, 'Agua');
  assert.equal(service.summary(first), 'Agua | 1 L');
  assert.equal(service.summary({ name: 'Agua', brand: 'Marca' }), 'Agua | Presentación no disponible');
  assert.equal(service.summary(null), 'Producto sin información');
  const container = new Element('section'); await service.show(container, '7791234567898');
  assert.equal(container.children[0].textContent, 'Agua'); assert.equal(container.attrs['aria-busy'], 'false');
  fetcher = async () => ({ ok: true, json: async () => ({ status: 0 }) });
  await service.show(container, '96385074'); assert.match(container.children[0].textContent, /No hay información/);
  fetcher = async () => { throw new Error('offline'); };
  await service.show(container, '1234567890128'); assert.match(container.children[0].textContent, /EAN sigue disponible/);
  fetcher = async () => ({ ok: true, json: async () => ({ status: 1, product: { product_name: '<script>alert(1)</script>' } }) });
  await service.show(container, '1234567890128'); assert.equal(container.children[0].textContent, '<script>alert(1)</script>');
  assert.equal(container.children[1].children.length, 0);
  let resolveOld;
  fetcher = async url => {
    if (url.includes('1111111111111')) return new Promise(resolve => resolveOld = resolve);
    return { ok: true, json: async () => ({ status: 1, product: { product_name: 'Producto actual' } }) };
  };
  const old = service.show(container, '1111111111111');
  await service.show(container, '2222222222222');
  resolveOld({ ok: true, json: async () => ({ status: 1, product: { product_name: 'Producto anterior' } }) });
  await old; assert.equal(container.children[0].textContent, 'Producto actual');
  assert.equal(container.attrs['aria-busy'], 'false');
  console.log('OK: datos, caché, producto ausente, errores recuperables y navegación sin mezclar fichas.');
})().catch(error => { console.error(error); process.exitCode = 1; });
