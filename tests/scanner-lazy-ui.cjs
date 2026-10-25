const fs=require('fs'),assert=require('assert/strict'),pw=require('../tmp/ui-check/node_modules/playwright');
const mock=`window.Html5QrcodeSupportedFormats={EAN_13:9,EAN_8:10,QR_CODE:0};window.Html5Qrcode=class{constructor(id){this.id=id;window.readersMade=(window.readersMade||0)+1;}async start(){this.isScanning=true;window.cameraStarts=(window.cameraStarts||0)+1;}async stop(){this.isScanning=false;}clear(){}};`;
(async()=>{const engine=process.env.RT_BROWSER||'chromium',browser=await pw[engine].launch({headless:true,...(engine==='chromium'?{executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'}:{})});try{
for(const failFirst of [false,true]){
  const c=await browser.newContext();let primary=0,alternate=0;
  await c.route('http://lazy.test/**',async r=>{
    const url=r.request().url();
    if(url.endsWith('html5-qrcode-2.3.8.min.js')){primary++;await new Promise(r=>setTimeout(r,250));if(failFirst&&primary===1)return r.abort();return r.fulfill({contentType:'text/javascript',body:mock});}
    if(url.endsWith('quagga-1.12.1.min.js')){alternate++;return r.fulfill({contentType:'text/javascript',body:'window.Quagga={decodeSingle:async()=>null};'});}
    return r.fulfill({contentType:'text/html',body:'<div id="reader"></div><p id="status"></p>'});
  });
  const p=await c.newPage();await p.goto('http://lazy.test/');await p.addScriptTag({path:'js/ean-scanner.js'});
  assert.equal(primary,0);assert.equal(alternate,0);
  await p.evaluate(()=>{window.reader=RepoEANScanner.rawCreate('reader',{onCode(){},onStatus:m=>document.getElementById('status').textContent=m,feedbackPreferences:()=>({sound:false,vibration:false})});window.pending=reader.start('qr').catch(e=>{window.failure=e.message;});});
  await p.locator('#status').getByText('Preparando cámara…',{exact:true}).waitFor();
  if(failFirst){await p.evaluate(()=>pending);assert.match(await p.evaluate(()=>failure),/cargar el lector/);await p.evaluate(()=>reader.start('qr'));}
  else{const stopMs=await p.evaluate(async()=>{const at=performance.now();await reader.stop();return performance.now()-at;});assert(stopMs<150,'Stop must not wait for the engine download');await p.evaluate(()=>pending);assert.equal(await p.evaluate(()=>window.cameraStarts||0),0,'Closing during load must not request camera');await p.evaluate(()=>reader.start('qr'));}
  assert.equal(await p.evaluate(()=>cameraStarts),1);assert.equal(primary,failFirst?2:1);assert.equal(alternate,0,'QR does not need EAN fallback');
  await p.evaluate(()=>reader.stop());await p.evaluate(()=>reader.start('ean'));await p.waitForFunction(()=>Boolean(window.Quagga));assert.equal(alternate,1);await p.evaluate(()=>reader.stop());
  await p.evaluate(()=>reader.start('ean'));assert.equal(primary,failFirst?2:1);assert.equal(alternate,1);await p.evaluate(()=>reader.stop());await c.close();
}
console.log('OK '+engine+': no engines before use, close during load never starts camera, failure retries, QR avoids alternate, EAN loads fallback once.');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
