const fs=require('fs'),path=require('path'),os=require('os'),assert=require('assert/strict'),{spawn,spawnSync}=require('child_process'),{randomUUID}=require('crypto'),{chromium}=require('../tmp/ui-check/node_modules/playwright');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'repo-branches-')),php='C:/xampp/php/php.exe',env={...process.env,DATABASE_URL:'',REPOTOOLS_REQUIRE_DATABASE_URL:'0',REPOTOOLS_DB_PATH:path.join(dir,'db.sqlite')},base='http://127.0.0.1:18851';
const seed=spawnSync(php,['-r',`require 'includes/database.php';require 'includes/stock.php';$db=database();foreach(['Owner','Other'] as $n)$db->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute([$n,strtolower($n).'@example.invalid',password_hash('test-password-123',PASSWORD_DEFAULT)]);foreach(['12345678','87654321'] as $code){$db->prepare('INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES(?,1,?)')->execute([$code,time()+1000]);$p=stock_claim($db,$code,1);stock_save_product($db,1,['pallet_id'=>$p['id'],'ean'=>'96385074','name'=>'Referencia','amount'=>2,'expires'=>'']);}`],{env,encoding:'utf8'});assert.equal(seed.status,0,seed.stderr);
const server=spawn(php,['-S','127.0.0.1:18851','-t',process.cwd()],{env,stdio:'ignore'});let browser;

(async()=>{
 spawnSync(php,['-r',`require 'includes/database.php';database()->exec("UPDATE users SET role='manager' WHERE id=1");`],{env});
 for(let i=0;i<50;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}
 browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const context=await browser.newContext(),page=await context.newPage();
 await page.goto(base+'/cuenta.php');await page.locator('[name=email]').fill('owner@example.invalid');await page.locator('[name=password]').fill('test-password-123');await page.locator('form[method=post] button[type=submit]').click();await page.waitForURL('**/index.php');
 const paths=['/index.php','/stock.php','/api/stock.php','/api/stock.php?code=12345678','/biblioteca.php','/tops.php','/cuenta.php','/reportes.php?view=branches'];const results=[];
 for(const path of paths){const times=[];let cold;for(let i=0;i<11;i++){const start=performance.now();const r=await context.request.get(base+path);await r.body();assert.equal(r.status(),200,path);const ms=performance.now()-start;if(i===0)cold=ms;else times.push(ms);}times.sort((a,b)=>a-b);results.push({path,first_ms:+cold.toFixed(2),median_ms:+times[5].toFixed(2),p95_ms:+times[9].toFixed(2)});}
 const report={environment:'Local PHP/SQLite, 2 users, 2 pallets, authenticated manager; HTTP response only, not rendering or external APIs',samples:10,results};fs.writeFileSync('tmp/performance-baseline.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));
})().catch(e=>{console.error(e);process.exitCode=1}).finally(async()=>{await browser?.close();server.kill();fs.rmSync(dir,{recursive:true,force:true});});
