const fs=require('fs'),os=require('os'),path=require('path'),assert=require('assert/strict'),{spawn,spawnSync}=require('child_process'),{chromium}=require('../tmp/ui-check/node_modules/playwright');
const dir=fs.mkdtempSync(path.join(os.tmpdir(),'repo-pwa-')),php='C:/xampp/php/php.exe',base='http://127.0.0.1:18849',env={...process.env,DATABASE_URL:'',REPOTOOLS_REQUIRE_DATABASE_URL:'0',REPOTOOLS_DB_PATH:path.join(dir,'db.sqlite')};
const server=spawn(php,['-S','127.0.0.1:18849','-t',process.cwd()],{env,stdio:'ignore'});let browser;
(async()=>{
 for(let i=0;i<40;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}
 const manifest=JSON.parse(fs.readFileSync('manifest.webmanifest','utf8'));assert.equal(manifest.display,'standalone');assert.equal(new URL(manifest.start_url,base+'/sub/manifest.webmanifest').pathname,'/sub/index.php');assert.equal(new URL(manifest.scope,base+'/sub/manifest.webmanifest').pathname,'/sub/');
 for(const icon of manifest.icons){const b=fs.readFileSync(icon.src);assert.equal(icon.sizes,b.readUInt32BE(16)+'x'+b.readUInt32BE(20));assert.equal((await fetch(base+'/'+icon.src)).status,200);}
 browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 for(const mobile of [false,true]){
  const context=await browser.newContext({viewport:{width:mobile?390:1366,height:900},isMobile:mobile,hasTouch:mobile}),page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));await context.route('https://**/*',r=>r.fulfill({body:''}));await context.addInitScript(()=>{localStorage.setItem('repotools_onboarding_seen_guest','true');localStorage.setItem('repotools_onboarding_version_guest','1');});await page.goto(base+'/index.php');
  assert.equal(await page.locator('h1').textContent(),'Herramientas para repositores');assert.equal(await page.locator('link[rel=manifest]').count(),1);assert.equal(await page.locator('meta[name=theme-color]').count(),1);assert.equal(await page.locator('link[rel=apple-touch-icon]').count(),1);
  await page.waitForFunction(()=>typeof Html5Qrcode==='function');assert.equal(await page.locator('script[src*="html5-qrcode"]').count(),1);assert.ok((await page.locator('script[src*="html5-qrcode"]').getAttribute('src')).startsWith('js/vendor/'));
  assert.ok(await page.locator('.logo img').evaluate(img=>img.complete&&img.naturalWidth===480));
  for(const hand of ['left','right'])for(const width of [320,390,1366]){await page.setViewportSize({width,height:900});await page.evaluate(hand=>document.body.dataset.hand=hand,hand);assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));await page.screenshot({path:'tmp/pwa-home-'+(mobile?'touch':'desktop')+'-'+hand+'-'+width+'.png'});}
  const client=await context.newCDPSession(page);const app=await client.send('Page.getAppManifest');assert.deepEqual(app.errors,[]);assert.ok(app.url.endsWith('/manifest.webmanifest'));
  await page.evaluate(()=>window.RepoOfflinePreparation);const registrations=await page.evaluate(async()=>(await navigator.serviceWorker.getRegistrations()).map(r=>r.active?.scriptURL));assert.equal(registrations.length,1);assert.ok(registrations[0].endsWith('/sw.js'));
  if(!mobile){await page.locator('#boton').click();await page.locator('.scan-method-picker input').first().waitFor({state:'visible'});}
  assert.deepEqual(errors,[]);await context.close();
 }
 console.log('OK: manifest/rutas/iconos reales, Html5Qrcode local, inicio y manos320/390/1366, apertura lector PC y un service worker.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(async()=>{await browser?.close();server.kill();fs.rmSync(dir,{recursive:true,force:true});});
