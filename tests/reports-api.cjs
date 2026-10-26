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

const guest=client(),user=client(),mod=client(),admin=client();
const fields={ean:'7791234567898',name:'Agua corregida',quantity:'500 ml',brand:'Marca',description:'Presentación corregida'};
async function login(request,role){const form=await request('cuenta.php'),csrf=form.text.match(/name="csrf" value="([^"]+)"/)[1];assert.equal((await request('cuenta.php',{csrf,email:role+'@example.invalid',password:'test-password-123'})).status,302);}
async function config(request){return(await request('api/reportes.php?config=1')).json();}
async function create(request,more={}){const c=await config(request),data={...fields,submission_key:randomUUID(),csrf:c.csrf,...more},r=await request('api/reportes.php',data);assert.equal(r.status,200,r.text);return {data,report:r.json().report};}
(async()=>{
 for(let i=0;i<40;i++){try{await guest('index.php');break;}catch{await new Promise(r=>setTimeout(r,100));}}
 for(const url of ['api/reportes.php','api/reportes.php?queue=1','api/reportes.php?pending_count=1','api/reportes.php?id=1','api/reportes.php?id=1&photo=ean','api/reportes.php?view=history','api/reportes.php?view=logs'])assert.equal((await guest(url)).status,401,url);
 const gc=await config(guest);assert.deepEqual(Object.keys(gc).sort(),['can_submit','csrf','photos_enabled','registered']);
 const anon=await guest('api/reportes.php',{...fields,csrf:gc.csrf,submission_key:randomUUID(),owner_token:'a'.repeat(64)});assert.equal(anon.status,200,anon.text);assert.deepEqual(anon.json(),{submitted:true});
 await login(user,'usuario');await login(mod,'moderador');await login(admin,'administrador');const uc=await config(user),mc=(await mod('api/reportes.php?queue=1')).json().csrf,ac=(await admin('api/reportes.php?queue=1')).json().csrf;
 const registered=await create(user);assert.equal((await user('api/reportes.php',registered.data)).json().report.id,registered.report.id);
 assert.equal((await guest('api/reportes.php?id='+registered.report.id)).status,401);assert.equal((await user('api/reportes.php?queue=1')).status,403);
 assert.equal((await user('api/reportes.php',{...fields,csrf:'bad'})).status,403);
 const fake=new FormData();for(const [k,v] of Object.entries({...fields,csrf:uc.csrf,submission_key:randomUUID()}))fake.set(k,v);fake.set('photo_ean',new Blob(['<script>x</script>'],{type:'image/jpeg'}),'fake.jpg');assert.equal((await user('api/reportes.php',fake)).status,422);
 const privateReport=(await mod('api/reportes.php?id='+registered.report.id)).json().report;assert.equal(privateReport.submitter.email,'usuario@example.invalid');assert.equal(privateReport.submitter_ip,undefined);assert.equal((await user('api/reportes.php?id='+registered.report.id)).json().report.submitter,undefined);
 const block={action:'block_reports',id:registered.report.id,csrf:mc};assert.equal((await mod('api/reportes.php',{...block,csrf:'bad'})).status,403);assert.equal((await user('api/reportes.php',{...block,csrf:uc.csrf})).status,403);assert.equal((await mod('api/reportes.php',block)).status,403,'Only administrators and managers can block report senders');assert.equal((await admin('api/reportes.php',{...block,csrf:ac})).json().report.submitter.blocked,true);assert.equal((await user('api/reportes.php',registered.data)).status,403);assert.equal((await mod('api/reportes.php',{...block,action:'unblock_reports'})).status,403);assert.equal((await admin('api/reportes.php',{...block,csrf:ac,action:'unblock_reports'})).json().report.submitter.blocked,false);
 const review={...fields,action:'review',id:registered.report.id,csrf:mc,decision:'approved',revision:1};assert.equal((await mod('api/reportes.php',{...review,revision:999})).status,409);assert.equal((await mod('api/reportes.php',review)).status,200);assert.equal((await mod('api/reportes.php',review)).status,200);
 let notices=(await user('api/notificaciones.php')).json();assert.equal(notices.notifications.length,1);const notice=notices.notifications[0];assert.equal(Number(notice.report_id),registered.report.id);assert.equal((await user('api/notificaciones.php',{action:'read',id:notice.id,csrf:'bad'})).status,403);await admin('api/notificaciones.php',{action:'read',id:notice.id,csrf:ac});assert.equal((await user('api/notificaciones.php')).json().unread,1);assert.equal((await user('api/notificaciones.php',{action:'read',id:notice.id,csrf:uc.csrf})).json().unread,0);
 const proposal=await create(user,{quantity:'250 ml',description:''});const rejected={action:'review',id:proposal.report.id,decision:'rejected',review_note:'',csrf:mc};assert.equal((await mod('api/reportes.php',rejected)).status,200);await mod('api/reportes.php',rejected);notices=(await user('api/notificaciones.php')).json();assert.equal(notices.notifications.length,2);assert.match(notices.notifications[0].title,/rechazado/);
 const ownMod=await create(mod);assert.ok(!(await mod('api/reportes.php?queue=1')).json().reports.some(r=>r.id===ownMod.report.id));assert.equal((await mod('api/reportes.php',{...review,id:ownMod.report.id})).status,403);
 const ownAdmin=await create(admin);assert.ok(!(await admin('api/reportes.php?queue=1')).json().reports.some(r=>r.id===ownAdmin.report.id));assert.equal((await admin('api/reportes.php',{...review,id:ownAdmin.report.id,csrf:ac})).status,403);
 for(const c of [user,mod])for(const view of ['history','logs'])assert.equal((await c('api/reportes.php?view='+view)).status,c===mod&&view==='logs'?200:403);
 const history=(await admin('api/reportes.php?view=history')).json();assert.ok(history.items.some(r=>r.id===registered.report.id&&r.changes.some(c=>c.field==='quantity'&&c.after==='500 ml')));assert.ok(history.items.some(r=>r.status==='rejected'));assert.ok(history.items.every(r=>!('original_data'in r)&&!('proposed_data'in r)));
 const logs=(await admin('api/reportes.php?view=logs')).json();assert.ok(logs.items.some(r=>['approved','rejected'].includes(r.action)));assert.ok(logs.items.some(r=>['blocked','unblocked'].includes(r.action)));assert.ok(!logs.items.some(r=>r.action==='edited'));
 for(const filter of ['approved','rejected','blocked','unblocked'])assert.ok((await admin('api/reportes.php?view=logs&filter='+filter)).json().items.every(r=>r.action===filter));assert.equal((await admin('api/reportes.php?view=logs&filter[]=edited')).status,422);
 const upgraded=spawnSync(php,['-r',"require 'includes/database.php';database()->exec(\"UPDATE users SET role='manager' WHERE email='administrador@example.invalid'\");"],{env,encoding:'utf8'});assert.equal(upgraded.status,0,upgraded.stderr);const own=(await admin('api/reportes.php?id='+ownAdmin.report.id)).json().report;assert.equal((await admin('api/reportes.php',{...review,id:ownAdmin.report.id,csrf:ac,revision:own.current.revision})).status,200);
 assert.equal((await guest('tops.php?categoria=reportes')).status,200);const rewards=spawnSync(php,['-r',"require 'includes/database.php';echo database()->query('SELECT COUNT(*) FROM report_rewards')->fetchColumn();"],{env,encoding:'utf8'});assert.equal(rewards.stdout,'2');
 // Excel names are opt-in even for a direct API request.
 for(const replace of [false,true]){
   const proposal=await create(user,{name:'Nombre largo Excel',quantity:'750 ml',description:'Importado de Excel · Fixture'});
   const current=(await mod('api/reportes.php?id='+proposal.report.id)).json().report.current;
   const response=await mod('api/reportes.php',{...fields,name:'Nombre largo Excel',quantity:'750 ml',action:'review',id:proposal.report.id,csrf:mc,decision:'approved',revision:current.revision,...(replace?{replace_existing_name:'1'}:{})});
   assert.equal(response.status,200,response.text);
   assert.equal(response.json().report.current.name,replace?'Nombre largo Excel':current.name);
   assert.equal(response.json().report.current.quantity,'750 ml');
 }
 console.log('OK: guest receipt/private401, own reports, CSRF, false photo422, block/mod cannot unblock/admin can, approve/reject idempotency, notifications isolation, resolved changes/logs, manager own report and rewards. Object storage upload requires separate configured environment.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(()=>server.kill());
