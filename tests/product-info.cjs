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
let fetcher = async url => { assert.match(url, /^api\/productos\.php\?ean=/); return { ok: true, json: async () => ({ product: { name: 'Agua', brand: 'Marca', quantity: '1 L' }, can_edit: false }) }; };
const context = { window: {}, document: { createElement: tag => new Element(tag) }, URL, AbortController, setTimeout, clearTimeout, fetch: (...args) => { calls++; return fetcher(...args); } };
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../js/product-info.js'), 'utf8'), context);
const service = context.window.RepoProductInfo;
(async () => {
  const [first, second] = await Promise.all([service.get('7791234567898'), service.get('7791234567898')]);
  assert.equal(calls, 1); assert.equal(first, second); assert.equal(first.name, 'Agua');
  assert.equal(service.summary(first), 'Marca Agua | 1 L');
  assert.equal(service.summary({ name: 'Agua', brand: 'Marca' }), 'Marca Agua | Contenido no disponible');
  assert.equal(service.summary({ name: 'Energizante Lata', brand: 'Red Bull', quantity: '250 ml' }), 'Red Bull Energizante Lata | 250 ml');
  assert.equal(service.summary({ name: 'Red Bull Energizante', brand: 'Red Bull', quantity: '250 ml' }), 'Red Bull Energizante | 250 ml');
  assert.equal(service.displayName({ name: 'Energizante Lata', brand: 'Red Bull' }), 'Red Bull Energizante Lata');
  assert.equal(service.displayName({ name: 'Red Bull Energizante', brand: 'Red Bull' }), 'Red Bull Energizante');
  assert.equal(service.summary(null), 'No tenemos información de este producto');
  assert.equal(service.referenceName({ name: 'Energizante Lata', brand: 'Red Bull', quantity: '250 ml' }), 'Red Bull Energizante Lata 250 ml');
  assert.equal(service.referenceName({ name: 'Agua', brand: '', quantity: '1 L' }), 'Agua 1 L');
  assert.equal(service.referenceName(null), '');
  const container = new Element('section'); await service.show(container, '7791234567898');
  assert.equal(container.children[0].textContent, 'Agua'); assert.equal(container.attrs['aria-busy'], 'false');
  await service.showSummary(container, '7791234567898', { compact: true }); assert.equal(container.textContent, 'Nombre sugerido. Puedes editarlo.');
  fetcher = async () => ({ ok: true, json: async () => ({ product: null, can_edit: false }) });
  await service.show(container, '96385074'); assert.match(container.children[0].textContent, /No hay información/);
  await service.showSummary(container, '96385074', { compact: true }); assert.match(container.textContent, /No tenemos información.*Escribe un nombre/);
  fetcher = async () => { throw new Error('offline'); };
  await service.show(container, '1234567890128'); assert.match(container.children[0].textContent, /EAN sigue disponible/);
  await service.showSummary(container, '1234567890128', { compact: true }); assert.match(container.textContent, /Puedes guardar el EAN con un nombre propio/);
  fetcher = async () => ({ ok: true, json: async () => ({ product: { name: '<script>alert(1)</script>' }, can_edit: false }) });
  await service.show(container, '1234567890128'); assert.equal(container.children[0].textContent, '<script>alert(1)</script>');
  assert.equal(container.children[1].children.length, 0);
  let resolveOld;
  fetcher = async url => {
    if (url.includes('1111111111111')) return new Promise(resolve => resolveOld = resolve);
    return { ok: true, json: async () => ({ product: { name: 'Producto actual' }, can_edit: false }) };
  };
  const old = service.show(container, '1111111111111');
  await service.show(container, '2222222222222');
  resolveOld({ ok: true, json: async () => ({ product: { name: 'Producto anterior' }, can_edit: false }) });
  await old; assert.equal(container.children[0].textContent, 'Producto actual');
  assert.equal(container.attrs['aria-busy'], 'false');
  console.log('OK: datos, caché, producto ausente, errores recuperables y navegación sin mezclar fichas.');
})().catch(error => { console.error(error); process.exitCode = 1; });
