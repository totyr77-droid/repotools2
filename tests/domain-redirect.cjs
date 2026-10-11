const fs=require('fs'),os=require('os'),path=require('path'),assert=require('assert/strict'),{spawn,spawnSync}=require('child_process');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'repotools-redirect-')),root=dir.replaceAll('\\','/'),apache='C:/xampp/apache/bin/httpd.exe',base='http://127.0.0.1:18831';
fs.copyFileSync('.htaccess',path.join(dir,'.htaccess'));fs.writeFileSync(path.join(dir,'index.html'),'OK');fs.writeFileSync(path.join(dir,'.env'),'test');
const config=path.join(dir,'httpd.conf');fs.writeFileSync(config,`ServerRoot "C:/xampp/apache"
Listen 127.0.0.1:18831
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule dir_module modules/mod_dir.so
LoadModule rewrite_module modules/mod_rewrite.so
DocumentRoot "${root}"
ErrorLog "${root}/error.log"
PidFile "${root}/httpd.pid"
<Directory "${root}">
AllowOverride All
Require all granted
</Directory>
`);
const syntax=spawnSync(apache,['-t','-f',config],{encoding:'utf8'});assert.equal(syntax.status,0,syntax.stderr);
const server=spawn(apache,['-f',config],{stdio:'ignore',windowsHide:true});
function request(route,host,method='GET'){return new Promise((resolve,reject)=>{const req=require('http').request(base+route,{method,headers:{Host:host}},res=>{res.resume();resolve({status:res.statusCode,headers:{get:name=>res.headers[name]??null}})});req.on('error',reject);req.end(method==='POST'?'sample=1':undefined);});}
(async()=>{
 for(let i=0;i<50;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}
 for(const route of ['/','/biblioteca.php','/palet.php?codigo=12345678','/recuperar.php?token=sample%2Bvalue&mode=cambio','/a%20b?next=biblioteca%26stock']){
  const r=await request(route,'repotools2.onrender.com');assert.equal(r.status,308);assert.equal(r.headers.get('location'),'https://repotools.online'+route);
 }
 const post=await request('/api/stock.php?code=12345678','REPOTOOLS2.ONRENDER.COM:80','POST');assert.equal(post.status,308);assert.equal(post.headers.get('location'),'https://repotools.online/api/stock.php?code=12345678');
 for(const host of ['localhost','repotools.online','www.repotools.online','different.onrender.com','repotools2.onrender.com.evil.invalid']){const r=await request('/',host);assert.equal(r.status,200);assert.equal(r.headers.get('location'),null);}
 assert.equal((await request('/.env','localhost')).status,403);
 console.log('OK: Apache real, dominio Render → HTTPS propio, rutas/parámetros intactos, POST 308, sin bucles ni redirección local y secretos protegidos.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(()=>{server.kill();setTimeout(()=>fs.rmSync(dir,{recursive:true,force:true}),1000);});
