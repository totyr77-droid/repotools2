const fs=require('fs'),path=require('path'),os=require('os'),assert=require('assert/strict'),{spawn,spawnSync}=require('child_process'),{chromium}=require('../tmp/ui-check/node_modules/playwright');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'repo-expiry-')),php='C:/xampp/php/php.exe',env={...process.env,DATABASE_URL:'',REPOTOOLS_REQUIRE_DATABASE_URL:'0',REPOTOOLS_DB_PATH:path.join(dir,'db.sqlite')},base='http://127.0.0.1:18829';
const seed=spawnSync(php,['-r',`require 'includes/database.php';require 'includes/stock.php';$db=database();$db->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute(['Prueba','alerts@example.invalid',password_hash('test-password-123',PASSWORD_DEFAULT)]);$db->prepare("INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES('12345678',1,?)")->execute([time()+1000]);$p=stock_claim($db,'12345678',1);$today=new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires'));foreach([-3,0,1] as $offset)stock_save_product($db,1,['pallet_id'=>$p['id'],'ean'=>'96385074','name'=>'Prueba '.$offset,'amount'=>2,'kind'=>'units','pack_size'=>1,'expires'=>$today->modify(($offset>=0?'+':'').$offset.' days')->format('Y-m-d')]);`],{env,encoding:'utf8'});assert.equal(seed.status,0,seed.stderr);
const server=spawn(php,['-S','127.0.0.1:18829','-t',process.cwd()],{env,stdio:'ignore'});let browser;
(async()=>{
 for(let i=0;i<40;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}
 browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const context=await browser.newContext({viewport:{width:390,height:850}}),page=await context.newPage();
 await context.route('https://**/*',r=>r.fulfill({contentType:'text/javascript',body:''}));
 await page.goto(base+'/cuenta.php');await page.locator('[name=email]').fill('alerts@example.invalid');await page.locator('[name=password]').fill('test-password-123');await page.locator('form[method=post] button[type=submit]').click();await page.waitForURL('**/index.php');
 await page.locator('#openNotifications').click();await page.locator('.notification-item[data-state=expired]').waitFor();
 for(const [state,label] of [['expired','Vencido hace 3 días'],['today','Vence hoy'],['warning','Vence en 1 día']])assert.equal(await page.locator(`.notification-item[data-state=${state}] .notification-expiry-label`).innerText(),label);
 for(const width of [320,390,1366]){await page.setViewportSize({width,height:850});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));assert(await page.locator('#notificationPanel').evaluate(el=>el.getBoundingClientRect().right<=innerWidth+1));}
 await page.setViewportSize({width:390,height:850});await page.screenshot({path:'tmp/expiry-notifications-390.png'});
 const link=page.locator('.notification-item[data-state=expired]'),href=await link.getAttribute('href'),id=new URL(href,base).searchParams.get('producto');
 await link.click();await page.waitForURL('**/stock.php?*');await page.locator('#palletDialog[open]').waitFor();await page.locator(`[data-product-id="${id}"].stock-product-highlight`).waitFor();
 assert.match(await page.locator(`[data-product-id="${id}"]`).innerText(),/Vencido hace 3 días/);
 await page.locator('#palletDialog [data-stock-close]').first().click();
 await page.locator('#stockExpiryView').click();await page.locator('.stock-expiry-group').first().waitFor();assert.equal(await page.locator('.stock-expiry-group').count(),3);
 assert.deepEqual(await page.locator('.stock-expiry-group>h3').allTextContents(),['Vencidos · 1','Vencen hoy · 1','Próximos a vencer · 1']);
 console.log('OK: avisos rojo/naranja/amarillo, etiquetas, acceso AJAX a lote resaltado, grupos y anchos 320/390/1366.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(async()=>{await browser?.close();server.kill();fs.rmSync(dir,{recursive:true,force:true});});
