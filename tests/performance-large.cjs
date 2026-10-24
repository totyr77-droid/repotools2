const fs = require('fs'), os = require('os'), path = require('path'), assert = require('assert/strict');
const {spawn, execFileSync} = require('child_process');
const {chromium} = require('../tmp/ui-check/node_modules/playwright');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'rt-large-'));
const env = {...process.env, DATABASE_URL:'', REPOTOOLS_REQUIRE_DATABASE_URL:'0', REPOTOOLS_DB_PATH:path.join(dir,'db.sqlite')};
const php = 'C:/xampp/php/php.exe', base = 'http://127.0.0.1:18964';
let server, browser, proxy;

(async () => {
  fs.mkdirSync('tmp', {recursive:true});
  console.log(execFileSync(php, ['tests/performance-large-seed.php'], {env, encoding:'utf8'}));
  const log = fs.openSync('tmp/performance-server.log', 'w');
  server = spawn(php, ['-S','127.0.0.1:18966','-t',process.cwd()], {env, stdio:['ignore',log,log]});
  fs.closeSync(log);
  proxy = await require('./performance-proxy.cjs')(18964,18966);
  for (let i=0;i<40;i++) {
    try { const r=await fetch(base); await r.arrayBuffer(); if(r.ok)break; }
    catch { await new Promise(r=>setTimeout(r,100)); }
  }
  browser = await chromium.launch({headless:true, executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  const c = await browser.newContext({viewport:{width:390,height:844}});
  await c.route('https://**/*', r=>r.abort());
  await c.route('**/js/device-connection.js*', r=>r.fulfill({contentType:'text/javascript',body:''}));
  await c.addInitScript(() => {
    delete Navigator.prototype.serviceWorker;
    localStorage.setItem('repotools_onboarding_seen_1','true');
    localStorage.setItem('repotools_onboarding_version_1','1');
  });
  const p=await c.newPage(), errors=[];
  p.on('pageerror',e=>errors.push(e.message));
  await p.goto(base+'/cuenta.php');
  await p.locator('[name=email]').fill('bench1@example.invalid');
  await p.locator('[name=password]').fill('benchmark-only-password');
  await p.locator('form[method=post] button[type=submit]').click();
  await p.waitForURL('**/index.php');
  const http=[];
  for (const url of [
    '/api/stock.php', '/api/supervision.php?view=palets', '/api/supervision.php?view=mercaderia',
    '/api/reportes.php?view=logs&grouped=1&section=goods',
    '/api/reportes.php?view=logs&grouped=1&section=goods&action=save_product',
    '/api/notificaciones.php', '/api/reportes.php?view=general&period=7'
  ]) {
    const samples=[]; let bytes;
    for(let n=0;n<6;n++) {
      const start=performance.now(), r=await c.request.get(base+url), body=await r.body();
      assert.equal(r.status(),200,url+' '+body.toString().slice(0,200));
      const data=JSON.parse(body);
      if(url.includes('grouped=1')) {
        assert.equal(data.items.length,50); assert.equal(data.has_more,true);
        assert(data.items.every(item=>Number(item.event_count)===1));
      }
      if(url.includes('view=palets')||url.includes('view=mercaderia')) {
        assert.equal(data.inventory.items.length,30); assert.equal(data.inventory.has_more,true);
      }
      bytes=body.length; samples.push(Math.round(performance.now()-start));
    }
    const warm=samples.slice(1).sort((a,b)=>a-b);
    http.push({url,first_ms:samples[0],median_ms:warm[2],max_ms:warm[4],bytes});
  }
  console.log(JSON.stringify({http},null,2));
  const rendering=[], session=await c.newCDPSession(p);
  for(const slow of [false,true]) {
    await session.send('Network.enable');
    await session.send('Network.emulateNetworkConditions', {offline:false,latency:slow?150:0,downloadThroughput:slow?200000:-1,uploadThroughput:slow?100000:-1});
    await session.send('Network.setCacheDisabled',{cacheDisabled:true});
    for(const [name,url,ready] of [
      ['Stock','/stock.php','.stock-pallet-card'],
      ['Mi equipo','/supervision.php','.supervision-stock-row'],
      ['Registros','/reportes.php?queue=1&view=logs','#reportsList .audit-record-open']
    ]) {
      const start=performance.now();
      await p.goto(base+url,{timeout:60000,waitUntil:'domcontentloaded'});
      await p.locator(ready).first().waitFor({timeout:60000});
      await p.locator('#repotoolsPageLoader').waitFor({state:'hidden',timeout:60000});
      rendering.push({name,network:slow?'150ms RTT / 1.6Mbps / cold cache':'local / cold cache',ready_ms:Math.round(performance.now()-start),...await p.evaluate(()=>({nodes:document.querySelectorAll('*').length,overflow:document.documentElement.scrollWidth>innerWidth+1}))});
      assert.equal(rendering.at(-1).overflow,false,name+' should fit mobile');
      console.log('Rendered',name,slow);
    }
  }
  assert.deepEqual(errors,[]);
  const report={environment:'Local PHP/SQLite + Chromium 390x844, synthetic data. Serial local proxy; no production PostgreSQL or physical mobile CPU. HTTP: 1 first + 5 warm samples. Rendering: 1 per screen/network, cold cache, external CDN/fonts blocked, SW and device relay excluded.',fixture:{users:21,pallets:2000,lots:20000,eans:5000,events:50000},http,rendering};
  fs.writeFileSync('tmp/performance-large.json',JSON.stringify(report,null,2));
  console.log(JSON.stringify(report,null,2));
})().catch(e=>{console.error(e);process.exitCode=1;}).finally(async()=>{
  await browser?.close();
  if(server){const exited=new Promise(r=>server.once('exit',r));server.kill();await exited;}
  proxy?.closeAllConnections();proxy?.close();
  if(process.env.RT_KEEP_BENCHMARK!=='1'&&path.dirname(dir)===os.tmpdir()&&path.basename(dir).startsWith('rt-large-'))fs.rmSync(dir,{recursive:true,force:true});
});
