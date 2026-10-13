const fs=require('fs'),os=require('os'),path=require('path'),assert=require('assert/strict'),{spawn,spawnSync}=require('child_process');
const apache=process.env.APACHE_BIN||'C:/xampp/apache/bin/httpd.exe';
if(!fs.existsSync(apache)){console.log('SKIP: definir APACHE_BIN para validar cabeceras con Apache.');process.exit(0);}
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'repo-headers-')),root=dir.replaceAll('\\','/'),serverRoot=path.resolve(path.dirname(apache),'..').replaceAll('\\','/'),config=path.join(dir,'httpd.conf'),base='http://127.0.0.1:18850';let server;
(async()=>{
 fs.copyFileSync('.htaccess',path.join(dir,'.htaccess'));for(const file of ['index.php','recuperar.php','sw.js','.env.test','neon.ts'])fs.writeFileSync(path.join(dir,file),'fixture');fs.copyFileSync('manifest.webmanifest',path.join(dir,'manifest.webmanifest'));
 const modules=['authz_core','authz_host','dir','mime','headers','rewrite'];fs.writeFileSync(config,`ServerRoot "${serverRoot}"
Listen 127.0.0.1:18850
ServerName localhost
PidFile "${root}/httpd.pid"
ErrorLog "${root}/error.log"
${modules.map(m=>`LoadModule ${m}_module "${serverRoot}/modules/mod_${m}.so"`).join('\n')}
TypesConfig "${serverRoot}/conf/mime.types"
DocumentRoot "${root}"
<Directory "${root}">
AllowOverride All
Require all granted
</Directory>
`);
 const syntax=spawnSync(apache,['-t','-f',config],{encoding:'utf8'});assert.equal(syntax.status,0,syntax.stderr);
 server=spawn(apache,['-X','-f',config],{stdio:'ignore'});let ready=false;for(let i=0;i<60;i++){try{await fetch(base);ready=true;break;}catch{await new Promise(r=>setTimeout(r,100));}}assert.ok(ready,'Apache starts');
 const response=await fetch(base);assert.equal(response.status,200);assert.equal(await response.text(),'fixture');assert.equal(response.headers.get('x-content-type-options'),'nosniff');assert.equal(response.headers.get('referrer-policy'),'strict-origin-when-cross-origin');assert.equal(response.headers.get('x-frame-options'),'SAMEORIGIN');
 assert.equal((await fetch(base+'/recuperar.php')).headers.get('referrer-policy'),'no-referrer');assert.ok((await fetch(base+'/sw.js')).headers.get('cache-control').includes('no-cache'));assert.ok((await fetch(base+'/manifest.webmanifest')).headers.get('content-type').includes('application/manifest+json'));
 for(const file of ['.env.test','neon.ts'])assert.equal((await fetch(base+'/'+file)).status,403);
 const redirect=await new Promise((resolve,reject)=>require('http').get(base+'/stock.php?test=1',{headers:{Host:'repotools2.onrender.com'}},response=>{response.resume();resolve(response);}).on('error',reject));assert.equal(redirect.statusCode,308);assert.equal(redirect.headers.location,'https://repotools.online/stock.php?test=1');
 console.log('OK: Apache headers, recuperacion no-referrer, DirectoryIndex, redireccion, archivos protegidos y manifest MIME.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(async()=>{if(server&&!server.killed){server.kill();await new Promise(resolve=>{server.once('exit',resolve);setTimeout(resolve,2000);});}fs.rmSync(dir,{recursive:true,force:true});});
