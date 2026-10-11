const {spawn,spawnSync}=require('node:child_process');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'profiles-api-')),root=path.resolve(__dirname,'..'),php=process.env.PHP_BINARY||'C:/xampp/php/php.exe';
const env={...process.env,DATABASE_URL:'',REPOTOOLS_REQUIRE_DATABASE_URL:'0',REPOTOOLS_DB_PATH:path.join(dir,'accounts.sqlite')};
const seed=spawnSync(php,['-r',`require 'includes/database.php';$db=database();foreach(['usuario','moderador','administrador','manager'] as $role)$db->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)')->execute([$role,$role.'@example.invalid',password_hash('test-password-123',PASSWORD_DEFAULT),$role]);`],{env,cwd:root,encoding:'utf8'});assert.equal(seed.status,0,seed.stderr);
const server=spawn(php,['-d',`session.save_path=${dir}`,'-S','127.0.0.1:18773','-t',root],{env,stdio:'ignore'}),base='http://127.0.0.1:18773/';
function client(){let cookie='';return async(url,data)=>{const r=await fetch(base+url,{redirect:'manual',headers:{Cookie:cookie},...(data?{method:'POST',body:new URLSearchParams(data)}:{})});const next=r.headers.get('set-cookie');if(next)cookie=next.split(';')[0];return {status:r.status,text:await r.text()};};}
async function login(c,role){const form=await c('cuenta.php'),csrf=form.text.match(/name="csrf" value="([^"]+)"/)[1];assert.equal((await c('cuenta.php',{csrf,email:role+'@example.invalid',password:'test-password-123'})).status,302);}
(async()=>{const guest=client(),user=client(),admin=client(),manager=client(),mod=client();for(let i=0;i<30;i++){try{await guest('index.php');break;}catch{await new Promise(r=>setTimeout(r,100));}}
assert.equal((await guest('api/usuarios.php?email=usuario@example.invalid')).status,401);
await login(user,'usuario');const token=JSON.parse((await user('api/usuarios.php?mine=1')).text).token;assert.match(token,/^[a-f0-9]{64}$/);assert.equal((await user('api/usuarios.php?token='+token)).status,403);
await login(mod,'moderador');assert.equal((await mod('api/usuarios.php?token='+token)).status,403);assert.doesNotMatch((await mod('reportes.php?queue=1')).text,/id="userWorkspace"/);
await login(admin,'administrador');let r=await admin('api/usuarios.php?token='+token);assert.equal(r.status,200);const profile=JSON.parse(r.text),csrf=profile.csrf;assert.equal(profile.user.name,'usuario');assert.ok(!Object.hasOwn(profile.user,'report_ip'));assert.deepEqual(profile.roles,['usuario','moderador']);
assert.equal((await admin('api/usuarios.php',{action:'role',id:1,role:'moderador'})).status,403);
assert.equal((await admin('api/usuarios.php',{action:'role',id:1,role:'manager',csrf})).status,403);
assert.equal((await admin('api/usuarios.php',{action:'role',id:4,role:'usuario',csrf})).status,403);
assert.equal((await admin('api/usuarios.php?token=invalid')).status,422);
assert.equal((await admin('api/usuarios.php?email=missing@example.invalid')).status,404);
assert.equal((await admin('api/usuarios.php?token='+token+'&page=-1')).status,422);
r=await admin('api/usuarios.php',{action:'role',id:1,role:'moderador',csrf});assert.equal(r.status,200);assert.equal(JSON.parse(r.text).user.role,'moderador');assert.ok(JSON.parse(r.text).events.some(e=>e.kind==='role'&&e.detail.includes('administrador')));
assert.ok(JSON.parse((await admin('api/reportes.php?view=logs')).text).items.some(e=>e.event_type==='user_role'));
await login(manager,'manager');r=await manager('api/usuarios.php?email=USUARIO@example.invalid');assert.equal(r.status,200);const m=JSON.parse(r.text);assert.ok(Object.hasOwn(m.user,'report_ip'));assert.equal(m.roles.length,4);
assert.equal((await manager('api/usuarios.php',{action:'role',id:4,role:'usuario',csrf:m.csrf})).status,422);
// Existing sessions lose privileges immediately after demotion.
assert.equal((await manager('api/usuarios.php',{action:'role',id:3,role:'usuario',csrf:m.csrf})).status,200);assert.equal((await admin('api/usuarios.php?token='+token)).status,403);
console.log('OK: profile API authentication, QR privacy, CSRF, hierarchy, last manager, audit and immediate demotion');
const pref=JSON.parse((await user('api/preferencias.php')).text);assert.equal(pref.handedness,'');assert.equal(pref.profile_pending,true);
assert.equal((await guest('api/preferencias.php')).status,401);
assert.equal((await user('api/preferencias.php',{handedness:'left'})).status,403);
assert.equal((await user('api/preferencias.php',{handedness:'invalid',csrf:pref.csrf})).status,422);
assert.equal((await user('api/preferencias.php',{handedness:'left',csrf:pref.csrf})).status,200);
assert.equal(JSON.parse((await user('api/preferencias.php')).text).handedness,'left');assert.match((await user('biblioteca.php')).text,/data-hand="left"/);assert.doesNotMatch((await user('biblioteca.php')).text,/data-profile-pending/);
assert.equal((await user('api/preferencias.php',{handedness:'right',csrf:pref.csrf})).status,200);assert.match((await user('biblioteca.php')).text,/data-hand="right"/);
console.log('OK: handedness preferences, default reminder, persistence, authentication and CSRF');
})().finally(()=>{server.kill();fs.rmSync(dir,{recursive:true,force:true});}).catch(e=>{console.error(e);process.exitCode=1;});
