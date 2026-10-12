const {spawn,spawnSync}=require('child_process'),fs=require('fs'),os=require('os'),path=require('path'),assert=require('assert/strict');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'repo-reset-')),php='C:/xampp/php/php.exe',env={...process.env,DATABASE_URL:'',REPOTOOLS_REQUIRE_DATABASE_URL:'0',REPOTOOLS_DB_PATH:path.join(dir,'db.sqlite'),RESEND_API_KEY:'',MAIL_FROM:''},base='http://127.0.0.1:18824/';
const seed=spawnSync(php,['-r',"require 'includes/database.php';database()->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute(['User','user@example.invalid',password_hash('old-password-123',PASSWORD_DEFAULT)]);"],{env,encoding:'utf8'});assert.equal(seed.status,0,seed.stderr);
const server=spawn(php,['-S','127.0.0.1:18824','-t',process.cwd()],{env,stdio:'ignore'});
function client(agent='Windows Desktop'){let cookie='';return async(url,data)=>{const r=await fetch(base+url,{redirect:'manual',headers:{Cookie:cookie,'User-Agent':agent},...(data?{method:'POST',body:new URLSearchParams(data)}:{})});const c=r.headers.get('set-cookie');if(c)cookie=c.split(';')[0];return {status:r.status,text:await r.text()};};}
const logged=client(),reset=client(),fresh=client('iPhone Mobile'),csrf=html=>html.match(/name="csrf" value="([^"]+)"/)[1];
(async()=>{for(let i=0;i<40;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}let page=await logged('cuenta.php');assert.equal((await logged('cuenta.php',{csrf:csrf(page.text),email:'user@example.invalid',password:'old-password-123'})).status,302);

page=await fresh('cuenta.php');await fresh('cuenta.php',{csrf:csrf(page.text),email:'user@example.invalid',password:'old-password-123'});
page=await logged('cuenta.php');const securityCSRF=csrf(page.text);
assert.equal((await reset('api/seguridad.php',{action:'sessions',current_password:'old-password-123'})).status,401);
assert.equal((await logged('api/seguridad.php',{action:'sessions',current_password:'old-password-123'})).status,403);
assert.equal((await logged('api/seguridad.php',{csrf:securityCSRF,action:'password',current_password:'wrong',password:'new-password-123',confirmation:'new-password-123'})).status,422);
assert.equal((await logged('api/seguridad.php',{csrf:securityCSRF,action:'sessions',current_password:'old-password-123'})).status,200);
assert.equal((await fresh('api/biblioteca.php')).status,401);assert.equal((await logged('api/biblioteca.php')).status,200);
page=await fresh('cuenta.php');await fresh('cuenta.php',{csrf:csrf(page.text),email:'user@example.invalid',password:'old-password-123'});
const token=spawnSync(php,['-r',"require 'includes/database.php';require 'includes/password-reset.php';echo reset_issue(database(),1);"],{env,encoding:'utf8'}).stdout.trim();
assert.equal((await logged('api/seguridad.php',{csrf:securityCSRF,action:'password',current_password:'old-password-123',password:'new-password-123',confirmation:'different'})).status,422);
const changed=await logged('api/seguridad.php',{csrf:securityCSRF,action:'password',current_password:'old-password-123',password:'new-password-123',confirmation:'new-password-123'});assert.equal(changed.status,200,changed.text);assert.equal(JSON.parse(changed.text).mail_sent,false);
assert.equal((await fresh('api/biblioteca.php')).status,401);assert.equal((await logged('api/biblioteca.php')).status,200);
page=await reset('recuperar.php?token='+token);assert.match((await reset('recuperar.php?token='+token,{csrf:csrf(page.text),password:'another-password-123'})).text,/ya fue utilizado/);
page=await fresh('cuenta.php');assert.equal((await fresh('cuenta.php',{csrf:csrf(page.text),email:'user@example.invalid',password:'new-password-123'})).status,302);
page=await fresh('cuenta.php');const retryCSRF=csrf(page.text);for(let i=0;i<11;i++){const attempt=await fresh('api/seguridad.php',{csrf:retryCSRF,action:'sessions',current_password:'wrong-password'});assert.equal(attempt.status,422);if(i===10)assert.match(JSON.parse(attempt.text).error,/15 minutos/);}
console.log('OK: seguridad, CSRF, contrasena actual, cierre otros dispositivos, sesion actual intacta, nueva clave y token revocado');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(()=>{server.kill();fs.rmSync(dir,{recursive:true,force:true});});
