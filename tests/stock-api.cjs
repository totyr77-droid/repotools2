const {spawn,spawnSync}=require('node:child_process'),fs=require('node:fs'),os=require('node:os'),path=require('node:path'),assert=require('node:assert/strict'),{randomUUID}=require('node:crypto');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'repotools-stock-api-')),env={...process.env,DATABASE_URL:'',REPOTOOLS_REQUIRE_DATABASE_URL:'0',REPOTOOLS_DB_PATH:path.join(dir,'accounts.sqlite')};
const server=spawn('C:/xampp/php/php.exe',['-d','session.save_path='+dir,'-S','127.0.0.1:18771','-t',path.resolve(__dirname,'..')],{env,stdio:'ignore'}),base='http://127.0.0.1:18771/';
function client(owner = 'a'.repeat(64)) {
  let cookie = '';
  return async (url, data) => {
    const response = await fetch(base + url, { redirect: 'manual', headers: { Cookie: cookie, 'X-Report-Owner': owner }, ...(data ? { method: 'POST', body: data instanceof FormData ? data : new URLSearchParams(data) } : {}) });
    const next = response.headers.get('set-cookie'); if (next) cookie = next.split(';')[0];
    const bytes = Buffer.from(await response.arrayBuffer()); const text = bytes.toString();
    return { status: response.status, text, bytes, json: () => JSON.parse(text) };
  };
}

const a=client(),b=client(),guest=client();let csrfA,csrfB;
async function register(request,name,company=''){const form=await request('cuenta.php?mode=registro');const csrf=form.text.match(/name="csrf" value="([^"]+)"/)[1];assert.equal((await request('cuenta.php?mode=registro',{name,company,email:name+'@example.invalid',password:'stock-test-password',csrf})).status,302);return (await request('api/stock.php')).json().csrf;}
async function post(request,csrf,data,key=randomUUID()){const r=await request('api/stock.php',{csrf,operation_key:key,...data});return r;}
(async()=>{
 for(let i=0;i<30;i++){try{await guest('index.php');break;}catch{await new Promise(r=>setTimeout(r,100));}}
 csrfA=await register(a,'Juan','Agencia <Norte>');csrfB=await register(b,'Pedro');assert.equal((await a('api/stock.php')).json().warning_days,30);
 assert.equal((await post(guest,csrfA,{action:'generate'})).status,401);assert.equal((await post(a,'wrong',{action:'generate'})).status,403);
 const key=randomUUID(),generated=await post(a,csrfA,{action:'generate',count:2},key);assert.equal(generated.status,200,generated.text);const labels=generated.json().labels;assert.equal((await post(a,csrfA,{action:'generate',count:2},key)).json().labels[0].code,labels[0].code);assert.equal((await a('api/stock.php')).json().labels.length,2);
 const unused=labels[1].code;assert.equal((await post(b,csrfB,{action:'delete_label',code:unused})).status,403);assert.equal((await post(a,csrfA,{action:'delete_label',code:unused})).status,200);assert.equal((await guest('api/stock.php?code='+unused)).json().pallet.can_claim,false);assert.equal((await a('api/stock.php')).json().labels.length,1);
 const code=labels[0].code;assert.equal((await guest('api/stock.php?code='+code)).json().pallet.available,true);assert.equal((await b('etiqueta.php?codigo='+code)).status,403);
 const claimed=await post(a,csrfA,{action:'claim',code});assert.equal(claimed.status,200,claimed.text);const id=claimed.json().id;assert.equal((await guest('api/stock.php?code='+code)).json().pallet.owner_company,'Agencia <Norte>');assert.ok((await a('cuenta.php')).text.includes('Agencia &lt;Norte&gt;'));
 assert.equal((await post(b,csrfB,{action:'delete',pallet_id:id})).status,403);
 assert.equal((await post(a,csrfA,{action:'delete_label',code})).status,409);const rev=(await a('api/stock.php?code='+code)).json().pallet.revision;await post(a,csrfA,{action:'description',pallet_id:id,revision:rev,description:'Ubicacion privada'});assert.equal((await a('api/stock.php?code='+code)).json().pallet.description,'Ubicacion privada');assert.ok(!('description' in (await guest('api/stock.php?code='+code)).json().pallet));assert.ok(!('description' in (await b('api/stock.php?code='+code)).json().pallet));
 const product={action:'save_product',pallet_id:id,ean:'96385074',name:'Agua',amount:8,kind:'boxes',pack_size:5,expires:new Date(Date.now()+30*86400000).toLocaleDateString('en-CA',{timeZone:'America/Buenos_Aires'})};
 const productKey=randomUUID();assert.equal((await post(a,csrfA,product,productKey)).status,200);assert.equal((await post(a,csrfA,product,productKey)).status,200);let detail=(await a('api/stock.php?code='+code)).json().pallet;assert.equal(detail.products[0].units,40);
 assert.equal((await guest('api/stock.php?ean=96385074')).status,401);assert.equal((await b('api/stock.php?ean=96385074')).json().references[0].pack_size,5);assert.equal((await b('api/stock.php?ean=55007732')).status,422);
 assert.equal(Number((await a('api/stock.php')).json().pallets.find(p=>p.code===code).expiry_count),1);await post(a,csrfA,{action:'preferences',expiry_days:29});assert.equal(Number((await a('api/stock.php')).json().pallets.find(p=>p.code===code).expiry_count),0);await post(a,csrfA,{action:'preferences',expiry_days:30});
 const raced=await post(b,csrfB,{...product,pallet_id:0,code});assert.equal(raced.status,200,raced.text);assert.equal(raced.json().relocated,true);assert.notEqual(raced.json().code,code);assert.equal((await b('api/stock.php?code='+raced.json().code)).json().pallet.products[0].units,40);
 assert.equal((await post(a,csrfA,{action:'invite',pallet_id:id,email:'Pedro@example.invalid'.toLowerCase()})).status,200);const invited=(await b('api/notificaciones.php')).json();assert.ok(invited.notifications.some(n=>n.type==='stock_invitation'));
 assert.equal((await post(b,csrfB,{...product,pallet_id:id})).status,403);assert.equal((await post(b,csrfB,{action:'accept',pallet_id:id})).status,200);assert.equal((await post(b,csrfB,{...product,amount:2,kind:'units'})).status,200);
 assert.equal((await b('api/stock.php?code='+code)).json().pallet.description,'Ubicacion privada');detail=(await a('api/stock.php?code='+code)).json().pallet;assert.equal(detail.products[0].units,42);assert.equal((await post(b,csrfB,{action:'invite',pallet_id:id,email:'Juan@example.invalid'.toLowerCase()})).status,403);assert.equal((await b('etiqueta.php?codigo='+code)).status,200);
 assert.equal((await post(b,csrfB,{action:'leave',pallet_id:id})).status,200);assert.equal((await post(b,csrfB,product)).status,403);
 const admin=client(),csrfAdmin=await register(admin,'Admin');
 function adminRole(role){const r=spawnSync('C:/xampp/php/php.exe',['-r',"require 'includes/database.php'; database()->prepare('UPDATE users SET role=? WHERE email=?')->execute([$argv[1],'admin@example.invalid']);",role],{env,encoding:'utf8'});assert.equal(r.status,0,r.stderr);}
 adminRole('administrador');const overview=(await admin('api/stock.php')).json();assert.equal(overview.is_admin,true);assert.ok(overview.admin_pallets.some(p=>p.code===code));const adminDetail=(await admin('api/stock.php?code='+code)).json().pallet;assert.equal(adminDetail.can_manage,true);assert.equal(adminDetail.description,'Ubicacion privada');assert.equal((await admin('etiqueta.php?codigo='+code)).status,200);assert.equal((await post(admin,csrfAdmin,{action:'description',pallet_id:id,revision:adminDetail.revision,description:'Revisado por admin'})).status,200);assert.equal((await admin('api/stock.php?code='+code)).json().pallet.owner_name,'Juan');
 const audit=spawnSync('C:/xampp/php/php.exe',['-r',"require 'includes/database.php';echo database()->query('SELECT COUNT(*) FROM pallet_admin_events')->fetchColumn();"],{env,encoding:'utf8'});assert.ok(Number(audit.stdout)>0);
 adminRole('moderador');assert.equal((await admin('api/stock.php')).json().is_admin,false);assert.ok(!('description' in (await admin('api/stock.php?code='+code)).json().pallet));assert.equal((await post(admin,csrfAdmin,{action:'delete',pallet_id:id})).status,403);
 assert.equal((await post(a,csrfA,{action:'preferences',expiry_days:365})).status,200);assert.equal(Number((await a('api/stock.php')).json().pallets.find(p=>p.code===code).expiry_count),1);assert.equal((await post(a,csrfA,{action:'preferences',expiry_days:0})).status,200);assert.equal(Number((await a('api/stock.php')).json().pallets.find(p=>p.code===code).expiry_count),0);
 assert.equal((await post(a,csrfA,{action:'delete',pallet_id:id})).status,200);assert.equal((await guest('api/stock.php?code='+code)).json().pallet.available,true);
 const reused=await post(b,csrfB,{action:'claim',code});assert.equal(reused.status,200);assert.notEqual(reused.json().id,id);assert.equal((await a('api/stock.php?code='+code)).json().pallet.can_edit,false);
 assert.equal((await post(b,csrfB,{action:'preferences',expiry_days:0})).status,200);assert.equal((await b('api/stock.php')).json().expiry_days,0);
 console.log('OK: Stock API authentication, CSRF, idempotent retries, race relocation with products, invitations, permissions, printing, leaving and code reuse.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(()=>server.kill());
