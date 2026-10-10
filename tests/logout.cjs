const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const assert = require('node:assert/strict');

const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'repotools-test-'));
const server = spawn(process.env.PHP_BIN || 'C:/xampp/php/php.exe', ['-d', `session.save_path=${dir}`, '-S', '127.0.0.1:18743', '-t', path.resolve(__dirname, '..')], {
  env: { ...process.env, DATABASE_URL: '', REPOTOOLS_REQUIRE_DATABASE_URL: '0', REPOTOOLS_DB_PATH: path.join(dir, 'accounts.sqlite') }, stdio: 'ignore'
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
(async()=>{
 for(let i=0;i<30;i++){try{await request('index.php');break;}catch{await new Promise(r=>setTimeout(r,100));}}
 assert.equal((await request('salir.php')).location,'index.php');
 const form=await request('cuenta.php?mode=registro');
 assert.equal((await request('cuenta.php?mode=registro',{csrf:token(form.text),name:'Prueba',email:'logout@example.invalid',password:'test-password-logout'})).status,302);
 const home=await request('index.php');
 assert.equal((await request('salir.php')).location,'cuenta.php');
 assert.equal((await request('biblioteca.php')).status,200);
 assert.equal((await request('salir.php',{csrf:'wrong'})).location,'cuenta.php');
 const retry=await request('cuenta.php');assert.match(retry.text,/Pulsa Salir nuevamente/);
 assert.ok(!(await request('cuenta.php')).text.includes('Pulsa Salir nuevamente'));
 const logout=await request('salir.php',{csrf:token(retry.text)});assert.equal(logout.status,303);assert.equal(logout.location,'index.php');
 assert.equal((await request('biblioteca.php')).location,'cuenta.php?next=biblioteca');
 assert.equal((await request('salir.php',{csrf:token(home.text)})).location,'index.php');
 console.log('OK: direct logout links, invalid CSRF, retry, valid logout and repeated logout.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(()=>server.kill());
