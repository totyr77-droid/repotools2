const fs=require('fs'),os=require('os'),path=require('path'),assert=require('assert/strict'),{spawn,execFileSync}=require('child_process'),pw=require('../tmp/ui-check/node_modules/playwright'),{setStockQuantity}=require('./helpers/stock-quantity.cjs');
const root=path.resolve(__dirname,'..'),dir=fs.mkdtempSync(path.join(os.tmpdir(),'rt-inner-ui-')),base='http://127.0.0.1:18936',php='C:/xampp/php/php.exe',env={...process.env,DATABASE_URL:'',REPOTOOLS_REQUIRE_DATABASE_URL:'0',REPOTOOLS_DB_PATH:path.join(dir,'test.sqlite')};let browser,server;const errors=[];
const sql=q=>JSON.parse(execFileSync(php,['-r',`require 'includes/database.php';echo json_encode(database()->query(${JSON.stringify(q)})->fetchAll());`],{cwd:root,env}).toString());
(async()=>{
execFileSync(php,['-r',`require 'includes/database.php';require 'includes/stock.php';$db=database();stock_query($db,'INSERT INTO users(name,email,password_hash,handedness) VALUES(?,?,?,?)',['Test','test@example.invalid',password_hash('test-password-123',PASSWORD_DEFAULT),'right']);stock_query($db,'INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES(?,1,?)',['12345678',time()+86400]);stock_claim($db,'12345678',1);`],{cwd:root,env});
server=spawn(php,['-S','127.0.0.1:18936','-t',root],{cwd:root,env,stdio:'ignore'});for(let i=0;i<40;i++){try{await fetch(base+'/app-version.php');break;}catch{await new Promise(r=>setTimeout(r,100));}}
const engine=process.env.RT_BROWSER==='webkit'?'webkit':'chromium';browser=await pw[engine].launch(engine==='chromium'?{executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true}:{headless:true});const context=await browser.newContext({viewport:{width:390,height:844}});await context.addInitScript(()=>{delete Navigator.prototype.serviceWorker;for(let i=0;i<10;i++)localStorage.setItem('repotools:onboarding:seen:'+i,'1');});await context.route('https://**/*',r=>r.fulfill({body:''}));await context.route('**/js/ean-scanner.js*',r=>r.fulfill({contentType:'text/javascript',body:fs.readFileSync(path.join(root,'js/ean-scanner.js'),'utf8')+`;{const create=RepoEANScanner.create;RepoEANScanner.create=(id,options)=>id==='stockReader'?{start:async()=>options.onManual(),stop:async()=>{}}:create(id,options);}`}));await context.route('**/api/productos.php?ean=*',r=>r.fulfill({contentType:'application/json',body:JSON.stringify({product:null,can_edit:false})}));
let navigating=false;const p=await context.newPage();p.on('pageerror',e=>{if(!(engine==='webkit'&&e.message.includes('due to access control checks.')&&(navigating||e.message.includes('/api/conectividad.php'))))errors.push(e.message);});await p.goto(base+'/cuenta.php');await p.locator('[name=email]').fill('test@example.invalid');await p.locator('[name=password]').fill('test-password-123');await p.locator('form[method=post] button[type=submit]').click();await p.waitForURL('**/index.php');await p.goto(base+'/stock.php?codigo=12345678');await p.locator('#palletDialog').waitFor({state:'visible'});
await p.evaluate(()=>document.getElementById('palletDialog').close());
for(const panel of ['ownedPanel','goodsPanel']){
  await p.evaluate(id=>document.querySelector('[data-stock-tab="'+id+'"]').click(),panel);
  for(const previous of ['modified','expiry_desc']){
    await p.locator('#stockSort').evaluate((n,value)=>{n.value=value;n.dispatchEvent(new Event('change',{bubbles:true}));},previous);
    await p.locator('#stockExpiryView').click();assert.equal(await p.locator('#stockSort').inputValue(),'expiry_asc');
    await p.locator('#stockExpiryView').click();assert.equal(await p.locator('#stockSort').inputValue(),previous,'Restores order in '+panel);assert.equal(await p.locator('#stockExpiryView').getAttribute('aria-pressed'),'false');
    if(previous==='modified')await p.waitForFunction(()=>document.getElementById('stockActiveFilters').hidden);
  }
}
console.log('OK '+engine+': expiration toggle restores previous order in pallets and goods, clears automatic filter and keeps panel.');
})().catch(e=>{console.error(e);process.exitCode=1;}).finally(async()=>{await browser?.close();server?.kill();fs.rmSync(dir,{recursive:true,force:true});});

