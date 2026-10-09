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
  await request('salir.php', { csrf: token(home.text) });
  const newForm = await request('cuenta.php');
  for (let i = 0; i < 10; i++) await request('cuenta.php', { csrf: token(newForm.text), email: data.email, password: 'mal' });
  assert.match((await request('cuenta.php', { csrf: token(newForm.text), email: data.email, password: data.password })).text, /Demasiados intentos/);
  console.log('OK: invitado, registro, login, logout, CSRF, acceso a palets y límite de intentos.');
})().catch(e => { console.error(e); process.exitCode = 1; }).finally(() => {
  server.on('exit', () => fs.rmSync(dir, { recursive: true, force: true }));
  server.kill();
});
