const { spawn, spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const assert = require('node:assert/strict');
const { randomUUID } = require('node:crypto');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'repotools-reports-'));
const php = process.env.PHP_BIN || 'C:/xampp/php/php.exe';
const env = { ...process.env, DATABASE_URL: '', REPOTOOLS_REQUIRE_DATABASE_URL: '0', REPOTOOLS_DB_PATH: path.join(dir, 'accounts.sqlite') };
const options = ['-d', 'extension=gd', ...(process.env.REPOTOOLS_TEST_CA ? ['-d', 'curl.cainfo=' + process.env.REPOTOOLS_TEST_CA] : [])];
const seed = spawnSync(php, ['-r', `require 'includes/database.php'; $db = database(); foreach (['usuario','moderador','administrador'] as $role) $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')->execute([$role, $role.'@example.invalid', password_hash('test-password-123', PASSWORD_DEFAULT), $role]); $db->prepare('INSERT INTO products (ean, name, quantity) VALUES (?, ?, ?)')->execute(['7791234567898', 'Agua', '1 L']);`], { env, encoding: 'utf8' });
assert.equal(seed.status, 0, seed.stderr);
const server = spawn(php, [...options, '-d', `session.save_path=${dir}`, '-S', '127.0.0.1:18759', '-t', path.resolve(__dirname, '..')], { env, stdio: 'ignore' });
const base = 'http://127.0.0.1:18759/';
function client(owner = 'a'.repeat(64)) {
  let cookie = '';
  return async (url, data) => {
    const response = await fetch(base + url, { redirect: 'manual', headers: { Cookie: cookie, 'X-Report-Owner': owner }, ...(data ? { method: 'POST', body: data instanceof FormData ? data : new URLSearchParams(data) } : {}) });
    const next = response.headers.get('set-cookie'); if (next) cookie = next.split(';')[0];
    const bytes = Buffer.from(await response.arrayBuffer()); const text = bytes.toString();
    return { status: response.status, text, bytes, json: () => JSON.parse(text) };
  };
}
const guest = client(), user = client(), mod = client();
const fields = { ean: '7791234567898', name: 'Agua corregida', quantity: '500 ml', brand: 'Marca', category: 'Bebidas', packaging: 'Botella', description: 'La presentación correcta es 500 ml.' };
async function login(request, role) {
  const form = await request('cuenta.php'); const csrf = form.text.match(/name="csrf" value="([^"]+)"/)[1];
  assert.equal((await request('cuenta.php', { csrf, email: role + '@example.invalid', password: 'test-password-123' })).status, 302);
}
async function create(request, more = {}) {
  const config = (await request('api/reportes.php')).json();
  const data = { ...fields, submission_key: randomUUID(), csrf: config.csrf, owner_token: 'a'.repeat(64), ...more };
  const result = await request('api/reportes.php', data); assert.equal(result.status, 200, result.text); return { data, report: result.json().report };
}
(async () => {
  for (let i = 0; i < 30; i++) { try { await guest('index.php'); break; } catch { await new Promise(resolve => setTimeout(resolve, 100)); } }
  const anon = await create(guest);
  assert.equal((await client('b'.repeat(64))('api/reportes.php?id=' + anon.report.id)).status, 404);
  await login(user, 'usuario'); const registered = await create(user);
  assert.equal((await user('api/reportes.php', registered.data)).json().report.id, registered.report.id);
  assert.equal((await guest('api/reportes.php?id=' + registered.report.id)).status, 404);
  const userCsrf = (await user('api/reportes.php')).json().csrf;
  assert.equal((await user('api/reportes.php', { ...fields, action: 'review', id: registered.report.id, csrf: userCsrf, decision: 'approved', revision: 1 })).status, 403);
  assert.equal((await user('api/reportes.php', { ...fields, csrf: 'invalid' })).status, 403);
  const badPhoto = new FormData(); for (const [key, value] of Object.entries({ ...fields, submission_key: randomUUID(), csrf: userCsrf })) badPhoto.set(key, value);
  badPhoto.set('photo_ean', new Blob(['<script>alert(1)</script>'], { type: 'image/jpeg' }), 'fake.jpg');
  assert.equal((await user('api/reportes.php', badPhoto)).status, 422);
  await login(mod, 'moderador'); const modCsrf = (await mod('api/reportes.php?queue=1')).json().csrf;
  const review = { ...fields, action: 'review', id: registered.report.id, csrf: modCsrf, decision: 'approved', revision: 1 };
  assert.equal((await mod('api/reportes.php', { ...review, revision: 999 })).status, 409);
  assert.equal((await mod('api/reportes.php', review)).status, 200);
  assert.equal((await mod('api/reportes.php', review)).status, 200);
  const top = await guest('tops.php?categoria=aportes'); assert.match(top.text, /usuario/); assert.match(top.text, /<td>1<\/td>/); assert.doesNotMatch(top.text, /moderador<\/th>/);
  assert.match((await guest('tops.php?categoria=reportes')).text, /usuario/);
  assert.equal((await mod('api/reportes.php', { ...review, id: anon.report.id, revision: 2 })).status, 200);
  const points = spawnSync(php, ['-r', `require 'includes/database.php'; echo database()->query('SELECT COUNT(*) FROM report_rewards')->fetchColumn();`], { env, encoding: 'utf8' }); assert.equal(points.stdout, '1');
  if (env.AWS_ENDPOINT_URL_S3) {
    const form = new FormData(); for (const [key, value] of Object.entries({ ...registered.data, submission_key: randomUUID() })) form.set(key, value);
    const jpeg = spawnSync(php, [...options, '-r', '$image = imagecreatetruecolor(20, 20); imagejpeg($image);'], { env }); assert.equal(jpeg.status, 0);
    form.set('photo_ean', new Blob([jpeg.stdout], { type: 'image/jpeg' }), 'ean.jpg'); form.set('photo_product', new Blob([jpeg.stdout], { type: 'image/jpeg' }), 'product.jpg');
    const result = await user('api/reportes.php', form); assert.equal(result.status, 200, result.text); const id = result.json().report.id;
    const own = await user(`api/reportes.php?id=${id}&photo=ean`); assert.equal(own.status, 200); assert.equal(own.bytes.subarray(0, 2).toString('hex'), 'ffd8');
    assert.equal((await guest(`api/reportes.php?id=${id}&photo=ean`)).status, 404);
    assert.equal((await mod(`api/reportes.php?id=${id}&photo=product`)).status, 200);
    const cleanup = spawnSync(php, [...options, '-r', `require 'includes/database.php'; require 'includes/reports.php'; foreach (database()->query('SELECT object_key FROM report_photos')->fetchAll(PDO::FETCH_COLUMN) as $key) report_object('DELETE', $key);`], { env, encoding: 'utf8' }); assert.equal(cleanup.status, 0, cleanup.stderr);
  }
  console.log('OK: API de tickets, CSRF, privacidad, rechazo de archivos falsos, moderación, TOPs públicos e invitados sin puntos.');
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => server.kill());
