const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const assert = require('node:assert/strict');

const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'repotools-test-'));
const server = spawn(process.env.PHP_BIN || 'C:/xampp/php/php.exe', ['-d', `session.save_path=${dir}`, '-S', '127.0.0.1:18743', '-t', path.resolve(__dirname, '..')], {
  env: { ...process.env, REPOTOOLS_DB_PATH: path.join(dir, 'accounts.sqlite') }, stdio: 'ignore'
});
let cookie = '';
const request = async (url, data) => {
  const result = await fetch('http://127.0.0.1:18743/' + url, {
    redirect: 'manual', headers: { Cookie: cookie },
    ...(data ? { method: 'POST', body: new URLSearchParams(data) } : {})
  });
  const next = result.headers.get('set-cookie');
  if (next) cookie = next.split(';')[0];
  return { status: result.status, location: result.headers.get('location'), text: await result.text() };
};
const token = html => html.match(/name="csrf" value="([^"]+)"/)[1];
(async () => {
  for (let i = 0; i < 30; i++) {
    try { await request('index.php'); break; } catch { await new Promise(r => setTimeout(r, 100)); }
  }
  assert.equal((await request('index.php')).status, 200);
  assert.equal((await request('stock.php')).location, 'cuenta.php?next=stock');
  assert.equal((await request('biblioteca.php')).location, 'cuenta.php?next=biblioteca');
  assert.equal((await request('api/biblioteca.php')).status, 401);
  const publicEmptyTop = await request('tops.php');
  assert.equal(publicEmptyTop.status, 200);
  assert.match(publicEmptyTop.text, /para participar/);
  assert.doesNotMatch(publicEmptyTop.text, /class="tops-stats"/);
  assert.equal((await request('api/escaneos.php', { code: '7791234567898' })).status, 401);
  const form = await request('cuenta.php?mode=registro&next=stock');
  const data = { csrf: token(form.text), name: 'Prueba', email: 'test@example.com', password: 'una-clave-de-prueba' };
  assert.match((await request('cuenta.php?mode=registro', { ...data, csrf: 'incorrecto' })).text, /venció/);
  const registered = await request('cuenta.php?mode=registro&next=stock', data);
  assert.equal(registered.location, 'stock.php', registered.text);
  const stock = await request('stock.php');
  assert.equal(stock.status, 200);
  assert.match(stock.text, /window.repotoolsUserId = 1/);
  assert.equal((await request('salir.php')).status, 403);
  assert.equal((await request('salir.php', { csrf: token(stock.text) })).location, 'index.php');
  assert.equal((await request('stock.php')).status, 302);
  const login = await request('cuenta.php');
  const credentials = { csrf: token(login.text), email: data.email, password: data.password };
  assert.match((await request('cuenta.php', { ...credentials, password: 'mal' })).text, /no son correctos/);
  assert.equal((await request('cuenta.php', credentials)).status, 302);
  const home = await request('index.php');
  assert.match(home.text, /Tus escaneos también cuentan para TOPs/);
  const csrf = token(home.text);
  assert.equal((await request('biblioteca.php')).status, 200);
  assert.equal((await request('api/biblioteca.php', { action: 'create', csrf: 'invalid', name: 'Bebidas' })).status, 403);
  const createdLibrary = JSON.parse((await request('api/biblioteca.php', { action: 'create', csrf, name: 'Bebidas' })).text).libraries[0];
  const libraryId = createdLibrary.id;
  assert.equal((await request('api/biblioteca.php', { action: 'create', csrf, name: 'Bebidas' })).status, 409);
  const productData = { csrf, action: 'add_product', library_id: libraryId, ean: '7791234567898', name: 'Agua' };
  assert.equal((await request('api/biblioteca.php', { ...productData, ean: 'abc' })).status, 422);
  assert.equal((await request('api/biblioteca.php', { ...productData, ean: '7791234567890' })).status, 422);
  let savedLibraries = JSON.parse((await request('api/biblioteca.php', productData)).text).libraries;
  assert.equal(savedLibraries[0].products.length, 1);
  savedLibraries = JSON.parse((await request('api/biblioteca.php', { ...productData, name: '' })).text).libraries;
  assert.equal(savedLibraries[0].products.length, 1);
  assert.equal(savedLibraries[0].products[0].name, 'Agua');
  await request('api/biblioteca.php', { csrf, action: 'create', name: 'Despensa' });
  savedLibraries = JSON.parse((await request('api/biblioteca.php', { csrf, action: 'rename', library_id: libraryId, name: 'Mis bebidas' })).text).libraries;
  assert.equal(savedLibraries[0].name, 'Mis bebidas');
  assert.equal(savedLibraries.length, 2);
  const scan = { csrf, code: '7791234567898', event: '11111111-1111-4111-8111-111111111111' };
  assert.equal((await request('api/escaneos.php', { ...scan, csrf: 'invalid' })).status, 403);
  assert.equal((await request('api/escaneos.php', { ...scan, code: '<script>' })).status, 422);
  assert.deepEqual(JSON.parse((await request('api/escaneos.php', scan)).text), { unique_codes: 1, total_scans: 1 });
  assert.deepEqual(JSON.parse((await request('api/escaneos.php', scan)).text), { unique_codes: 1, total_scans: 1 });
  assert.deepEqual(JSON.parse((await request('api/escaneos.php', { ...scan, event: '22222222-2222-4222-8222-222222222222' })).text), { unique_codes: 1, total_scans: 2 });
  assert.deepEqual(JSON.parse((await request('api/escaneos.php', { ...scan, code: '7799876543210', event: '33333333-3333-4333-8333-333333333333' })).text), { unique_codes: 2, total_scans: 3 });
  assert.match((await request('tops.php')).text, /Prueba \(vos\)/);
  await request('salir.php', { csrf: token(home.text) });
  const publicTop = await request('tops.php');
  assert.equal(publicTop.status, 200);
  assert.match(publicTop.text, /Prueba/);
  assert.match(publicTop.text, /<td>2<\/td><td>3<\/td>/);
  assert.doesNotMatch(publicTop.text, /\(vos\)|class="tops-me"|Warning|test@example.com/);
  const secondForm = await request('cuenta.php?mode=registro');
  assert.equal((await request('cuenta.php?mode=registro', { csrf: token(secondForm.text), name: 'Otro usuario', email: 'second@example.com', password: data.password, role: 'admin' })).status, 302);
  const secondHome = await request('index.php');
  const secondToken = token(secondHome.text);
  assert.deepEqual(JSON.parse((await request('api/biblioteca.php')).text).libraries, []);
  for (const action of ['rename', 'delete', 'add_product', 'delete_product']) {
    assert.equal((await request('api/biblioteca.php', { ...productData, csrf: secondToken, action, name: 'Otro' })).status, 404);
  }
  const ownLibrary = JSON.parse((await request('api/biblioteca.php', { csrf: secondToken, action: 'create', name: 'Bebidas' })).text).libraries[0];
  await request('api/biblioteca.php', { ...productData, csrf: secondToken, library_id: ownLibrary.id });
  const removed = JSON.parse((await request('api/biblioteca.php', { ...productData, csrf: secondToken, library_id: ownLibrary.id, action: 'delete_product' })).text).libraries;
  assert.equal(removed[0].products.length, 0);
  assert.deepEqual(JSON.parse((await request('api/biblioteca.php', { csrf: secondToken, library_id: ownLibrary.id, action: 'delete' })).text).libraries, []);
  assert.deepEqual(JSON.parse((await request('api/escaneos.php', { ...scan, csrf: secondToken })).text), { unique_codes: 1, total_scans: 1 });
  const secondTop = await request('tops.php');
  assert.match(secondTop.text, /Otro usuario \(vos\)/);
  assert.match(secondTop.text, /<td>2<\/td><td>3<\/td>/);
  await request('salir.php', { csrf: secondToken });
  const newForm = await request('cuenta.php');
  for (let i = 0; i < 10; i++) await request('cuenta.php', { csrf: token(newForm.text), email: data.email, password: 'mal' });
  assert.match((await request('cuenta.php', { csrf: token(newForm.text), email: data.email, password: data.password })).text, /Demasiados intentos/);
  console.log('OK: autenticación, TOPs, conteos, Biblioteca, EAN, colecciones privadas y protección de cambios.');
})().catch(e => { console.error(e); process.exitCode = 1; }).finally(() => {
  server.on('exit', () => fs.rmSync(dir, { recursive: true, force: true }));
  server.kill();
});
