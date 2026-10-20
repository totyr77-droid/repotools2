const fs=require('fs'),vm=require('vm'),assert=require('assert/strict'),source=fs.readFileSync('js/ean-scanner.js','utf8');const extract=(from,to)=>source.slice(source.indexOf(from),source.indexOf(to,source.indexOf(from)));const context={performance:{now:()=>0},validEAN:()=>false};vm.createContext(context);vm.runInContext(extract('  function confirmation(','  // Shared identification')+extract('  function ocrConsensus(','  function ocrCandidate(')+';this.create=ocrConsensus;',context);
let c=context.create();assert.equal(c('00001234',1,0),false);assert.equal(c(null,2,100),false);assert.equal(c('00001234',3,200),false);assert.equal(c('00001234',4,300),true,'A missed frame preserves 1/3 briefly');
c=context.create();c('17309735',1,0);c(null,2,1500);assert.equal(c('17309735',3,2001),false);assert.equal(c('17309735',4,2100),false);assert.equal(c('17309735',5,2200),true,'Expired detection restarts at 1/3');
c=context.create();c('17309735',1,0);c('00001234',2,100);assert.equal(c('17309735',3,200),false);assert.equal(c('17309735',4,300),false);assert.equal(c('17309735',5,400),true,'Different number resets consensus');
c=context.create();c('17309735',1,0);assert.equal(c('17309735',1,100),false);assert.equal(c('17309735',2,150),false);assert.equal(c('17309735',3,160),false);assert.equal(c('17309735',4,250),true,'Distinct frames and minimum spacing remain required');
console.log('OK: OCR retains brief misses, expires after2000ms, resets for another number and requires3 distinct spaced frames.');

c=context.create();c('00001234',1,0);assert.equal(c.progress(0),1);c(null,2,100);assert.equal(c.progress(100),1);c('00001234',3,200);assert.equal(c.progress(200),2);c('00001234',4,300);assert.equal(c.progress(300),3);assert.equal(c.progress(2301),0);

c=context.create();c('17309735',1,0);c(null,2,900);c('17309735',3,1800);c(null,4,2500);assert.equal(c('17309735',5,3700),true,'Sway preserves three clear reads through unreadable frames');
c=context.create();c('17309735',1,0);c('17309735',2,1900);c('17309735',3,3800);assert.equal(c('17309735',4,5100),false,'Total window expires even if each gap is acceptable');assert.equal(c.progress(5100),1);
c=context.create();c('17309735',1,0);c('17309735',2,1700);c('17309736',3,1800);assert.equal(c.progress(1800),1,'Conflicting digit resets immediately');assert.equal(c('17309735',4,1900),false);assert.equal(c.progress(1900),1);assert.equal(c('17309735',5,2100),false);assert.equal(c('17309735',6,2300),true);
console.log('OK: shaky OCR gaps up to2s, total5s cap, no digit approximation, conflict resets and three separate frames.');

let zoom=1;c=context.create(()=>zoom);assert.equal(c.tolerance(),2000);zoom=2;assert.equal(c.tolerance(),2500);zoom=3;assert.equal(c.tolerance(),3000);zoom=4;assert.equal(c.tolerance(),3500);zoom=5;assert.equal(c.tolerance(),4000);zoom=8;assert.equal(c.tolerance(),4000);zoom=NaN;assert.equal(c.tolerance(),2000);
zoom=3;c=context.create(()=>zoom);c('17309735',1,0);assert.equal(c('17309735',2,2800),false);assert.equal(c('17309735',3,5600),true,'Intermediate zoom3x already increases OCR tolerance');
zoom=2;c=context.create(()=>zoom);c('17309735',1,0);assert.equal(c('17309735',2,2501),false);assert.equal(c.progress(2501),1,'Zoom2x has bounded tolerance');
zoom=5;c=context.create(()=>zoom);c('17309735',1,0);c('17309735',2,3500);zoom=1;assert.equal(c.progress(5600),0,'Lower zoom restores shorter retention');
zoom=4;c=context.create(()=>zoom);c('17309735',1,0);c('17309736',2,2000);assert.equal(c.progress(2000),1,'Zoom never tolerates a conflicting digit');
console.log('OK: applied zoom1/2/3/4/5/8 progressive tolerance, unknown fallback, bounds, zoom-down and conflicting codes.');
