const fs=require('fs'),assert=require('assert/strict'),{chromium}=require('../tmp/ui-check/node_modules/playwright');
let browser;
(async()=>{
  browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
  const page=await browser.newPage({viewport:{width:794,height:1200}});
  await page.goto('about:blank');
  await page.setContent('<nav class="label-toolbar">Toolbar</nav><p id="labelStatus">Status</p><main id="labelSheets"></main><div id="decoder"></div>');
  await page.addStyleTag({content:fs.readFileSync('css/pallet-label.css','utf8')});
  // A real location is required by URL generation, but no network asset is needed.
  await page.route('https://www.repotools.online/**',r=>r.fulfill({contentType:'text/html',body:'<nav class="label-toolbar">Toolbar</nav><p id="labelStatus">Status</p><main id="labelSheets"></main><div id="decoder"></div>'}));
  await page.goto('https://www.repotools.online/etiqueta.php');
  await page.addStyleTag({content:fs.readFileSync('css/pallet-label.css','utf8')});
  await page.addScriptTag({content:fs.readFileSync('js/vendor/qrcode.min.js','utf8')});
  await page.addScriptTag({content:fs.readFileSync('js/pallet-label.js','utf8')});
  for(const count of [1,2,5]){
    await page.evaluate(async count=>{
      labelSheets.replaceChildren();
      for(let i=0;i<count;i++){
        const label={code:String(12345678+i),activated:i%2,reserved_until:1792000000},section=document.createElement('section'),img=new Image();
        section.className='label-sheet';img.src=URL.createObjectURL(new Blob([RepoPalletLabel.vector(label)],{type:'image/svg+xml'}));
        section.append(img,Object.assign(document.createElement('button'),{className:'label-share',textContent:'Share'}));labelSheets.append(section);await img.decode();
      }
    },count);
    const pdf=await page.pdf({preferCSSPageSize:true,printBackground:true});
    assert.equal((pdf.toString('latin1').match(/\/Type\s*\/Page\b/g)||[]).length,count,'One PDF page per label, no trailing page');
    const bounds=pdf.toString('latin1').match(/\/MediaBox\s*\[0 0 ([\d.]+) ([\d.]+)/);
    assert.ok(bounds&&Math.abs(Number(bounds[1])*25.4/72-210)<1&&Math.abs(Number(bounds[2])*25.4/72-297)<1,'A4 portrait within browser rounding');
    fs.writeFileSync('tmp/pallet-a4-'+count+'.pdf',pdf);
  }
  const data=await page.evaluate(async()=>{
    const result=await RepoPalletLabel.image({code:'12345678',activated:0,reserved_until:1792000000}),img=new Image();img.src=result.dataURL;await img.decode();
    const canvas=document.createElement('canvas');canvas.width=img.width;canvas.height=img.height;const ctx=canvas.getContext('2d');ctx.drawImage(img,0,0);
    const pixels=ctx.getImageData(220,160,2040,350).data;let black=0;
    for(let i=0;i<pixels.length;i+=4){if(pixels[i]<30)black++;assertGray(pixels,i);}
    function assertGray(p,i){if(p[i]!==p[i+1]||p[i]!==p[i+2]||p[i+3]!==255)throw Error('Logo must print monochrome on opaque white');}
    return {width:img.width,height:img.height,black,dataURL:result.dataURL,svg:RepoPalletLabel.vector({code:'12345678',activated:1})};
  });
  assert.equal(data.width,2480);assert.equal(data.height,3508);assert.ok(data.black>100000,'Actual horizontal logo is visible in black');
  const logo=fs.readFileSync('img/Repotools_Logo_Pack_Blanco/06_horizontal_blanco_transparente/repotools-horizontal-blanco-2048w.png').toString('base64');
  assert.ok(data.svg.includes(logo),'Uses specified original logo, embedded in vector');
  fs.writeFileSync('tmp/pallet-a4-preview.png',Buffer.from(data.dataURL.split(',')[1],'base64'));
  await page.addScriptTag({content:fs.readFileSync('js/vendor/html5-qrcode-2.3.8.min.js','utf8')});
  const decoded=await page.evaluate(async src=>{const response=await fetch(src),blob=await response.blob(),reader=new Html5Qrcode('decoder');return reader.scanFile(new File([blob],'label.png',{type:'image/png'}),false);},data.dataURL);
  assert.equal(decoded,'https://www.repotools.online/palet.php?codigo=12345678','Printed/shared QR still points to its exact pallet');
  console.log('OK: A4 PDF 1/2/5 labels = 1/2/5 pages, embedded black logo, 2480x3508 shared PNG, QR decoded.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(async()=>{await browser?.close();});
