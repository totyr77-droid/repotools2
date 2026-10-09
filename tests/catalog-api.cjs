const { spawn, spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const assert = require('node:assert/strict');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'repotools-catalog-'));
const env = { ...process.env, DATABASE_URL: '', REPOTOOLS_REQUIRE_DATABASE_URL: '0', REPOTOOLS_DB_PATH: path.join(dir, 'accounts.sqlite') };
const php = process.env.PHP_BIN || 'C:/xampp/php/php.exe';
const seed = spawnSync(php, ['-r', `require 'includes/database.php'; $db = database(); foreach (['usuario','moderador','administrador'] as $role) $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')->execute([$role, $role . '@example.invalid', password_hash('test-password-123', PASSWORD_DEFAULT), $role]); $db->prepare('INSERT INTO products (ean, name, quantity, source) VALUES (?, ?, ?, ?)')->execute(['7791234567898', 'Agua', '1 L', 'openfoodfacts']);`], { cwd: path.resolve(__dirname, '..'), env, encoding: 'utf8' });
assert.equal(seed.status, 0, seed.stderr);
const server = spawn(php, ['-d', `session.save_path=${dir}`, '-S', '127.0.0.1:18757', '-t', path.resolve(__dirname, '..')], { env, stdio: 'ignore' });
let cookie = '';
const base = 'http://127.0.0.1:18757/';
async function request(url, data, method) {
  const response = await fetch(base + url, { redirect: 'manual', headers: { Cookie: cookie }, method: method || (data ? 'POST' : 'GET'), ...(data ? { body: new URLSearchParams(data) } : {}) });
  const next = response.headers.get('set-cookie'); if (next) cookie = next.split(';')[0];
  return { status: response.status, text: await response.text() };
}
const token = html => html.match(/name="csrf" value="([^"]+)"/)[1];
const fields = { ean: '7791234567898', revision: 1, name: 'Agua corregida', quantity: '500 ml', brand: 'Marca', category: 'Bebidas', packaging: 'Botella' };
(async () => {
  for (let i = 0; i < 30; i++) { try { await request('index.php'); break; } catch { await new Promise(r => setTimeout(r, 100)); } }
  const guest = JSON.parse((await request('api/productos.php?ean=7791234567898')).text);
  assert.equal(guest.product.name, 'Agua'); assert.equal(guest.can_edit, false); assert.equal(guest.csrf, null);
  assert.equal((await request('api/productos.php', fields)).status, 401);
  assert.equal((await request('api/productos.php?ean=7791234567890')).status, 422);
  assert.equal((await request('api/productos.php', null, 'PUT')).status, 405);
  for (const role of ['usuario', 'moderador', 'administrador']) {
    const form = await request('cuenta.php');
    assert.equal((await request('cuenta.php', { csrf: token(form.text), email: role + '@example.invalid', password: 'test-password-123' })).status, 302);
    const home = await request('index.php');
    const csrf = token(home.text);
    const lookup = JSON.parse((await request('api/productos.php?ean=7791234567898')).text);
    assert.equal(lookup.can_edit, role !== 'usuario');
    assert.equal((await request('api/productos.php', { ...fields, csrf: 'invalid' })).status, 403);
    if (role === 'usuario') {
      assert.equal((await request('api/productos.php', { ...fields, csrf, role: 'administrador' })).status, 403);
    } else {
      const saved = await request('api/productos.php', { ...fields, revision: lookup.product.revision, csrf });
      assert.equal(saved.status, 200, saved.text);
      assert.equal(JSON.parse(saved.text).product.quantity, '500 ml');
      assert.equal((await request('api/productos.php', { ...fields, revision: lookup.product.revision, csrf })).status, 409);
      assert.equal((await request('api/productos.php', { ...fields, revision: -1, csrf })).status, 422);
      assert.equal((await request('api/productos.php', { ...fields, revision: JSON.parse(saved.text).product.revision, name: '', csrf })).status, 422);
    }
    await request('salir.php', { csrf });
  }
  const publicProduct = JSON.parse((await request('api/productos.php?ean=7791234567898')).text);
  assert.equal(publicProduct.product.name, 'Agua corregida'); assert.equal(publicProduct.product.revision, 3);
  assert.equal(publicProduct.can_edit, false);
  console.log('OK: catálogo público, CSRF, edición solo por administradores/moderadores, versiones y correcciones visibles para invitados.');
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => server.kill());
