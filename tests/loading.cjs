const assert=require('assert/strict'),path=require('path'),{chromium}=require('../tmp/ui-check/node_modules/playwright');
(async()=>{const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});try{
 for(const width of [390,1366]){
  const page=await browser.newPage({viewport:{width,height:850}});
  await page.setContent('<base href="http://127.0.0.1:1/"><main><section id="list"><p>Contenido</p></section><button id="save">Guardar</button></main>');
  await page.addStyleTag({path:path.resolve('css/loading.css')});await page.addScriptTag({path:path.resolve('js/loading.js')});
  await page.evaluate(()=>{const stop=RepotoolsLoader.start(document.documentElement,{type:'fullscreen'});stop();});await page.waitForTimeout(210);assert.equal(await page.locator('.repotools-loader--fullscreen').count(),0);
  await page.evaluate(()=>window.full=RepotoolsLoader.start(document.documentElement,{type:'fullscreen'}));await page.waitForTimeout(210);assert.equal(await page.locator('.repotools-loader--fullscreen img').count(),1);await page.evaluate(()=>full.cancel());
  await page.evaluate(()=>{window.end=RepotoolsLoader.start(document.querySelector('#list'));end();});await page.waitForTimeout(240);assert.equal(await page.locator('.repotools-loader').count(),0);
  await page.evaluate(()=>window.end=RepotoolsLoader.start(document.querySelector('#list'),{type:'skeleton'}));await page.waitForTimeout(210);
  assert.equal(await page.locator('.repotools-loader--skeleton').count(),1);assert.equal(await page.locator('#list').getAttribute('aria-busy'),'true');
  await page.screenshot({path:path.resolve('tmp/loading-'+width+'.png')});
  assert.equal(await page.evaluate(()=>{end();return document.querySelectorAll('.repotools-loader').length}),1);assert.equal(await page.locator('#list').getAttribute('aria-busy'),null);await page.waitForTimeout(300);assert.equal(await page.locator('.repotools-loader').count(),0);
  await page.evaluate(()=>{window.old=RepotoolsLoader.start(document.querySelector('#list'));window.end=RepotoolsLoader.start(document.querySelector('#list'));old();});await page.waitForTimeout(210);assert.equal(await page.locator('.repotools-loader').count(),1);assert.equal(await page.locator('#list').getAttribute('aria-busy'),'true');await page.evaluate(()=>end.cancel());assert.equal(await page.locator('.repotools-loader').count(),0);
  await page.evaluate(()=>window.end=RepotoolsLoader.start(document.querySelector('#save'),{type:'button',label:'Guardando...'}));assert.equal(await page.locator('#save').isDisabled(),true);await page.waitForTimeout(210);await page.evaluate(()=>end());assert.equal(await page.locator('#save').isDisabled(),false);
  await page.waitForTimeout(310);await page.evaluate(()=>{RepotoolsLoader.start(document.querySelector('#list'));window.dispatchEvent(new Event('offline'));});await page.waitForTimeout(210);assert.equal(await page.locator('.repotools-loader').count(),0);
  await page.evaluate(async()=>{const stop=RepotoolsLoader.start(document.querySelector('#list'));try{throw Error('test');}catch{}finally{stop();}});await page.waitForTimeout(210);assert.equal(await page.locator('.repotools-loader').count(),0);
  await page.emulateMedia({reducedMotion:'reduce'});await page.evaluate(()=>RepotoolsLoader.start(document.querySelector('#list'),{type:'skeleton'}));await page.waitForTimeout(210);assert.equal(await page.locator('.repotools-loader-mark i').first().evaluate(n=>getComputedStyle(n).animationName),'none');
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);await page.evaluate(()=>RepotoolsLoader.clear());await page.close();
 }
 console.log('OK: delayed fast/slow loading, visual minimum, races, buttons, errors, offline, cleanup, mobile/desktop and reduced motion.');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
