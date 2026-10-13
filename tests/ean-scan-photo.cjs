const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
let success,now=0,pendingBlob;const timers=new Map();let timerId=0;
const video={videoWidth:1920,videoHeight:1080,clientWidth:390,clientHeight:320};
const element={classList:{add(){},remove(){}},querySelector:()=>video};
const image={size:100,type:'image/jpeg'};let drawn;
const document={getElementById:()=>element,createElement:()=>({getContext:()=>({drawImage(...args){drawn=args;}}),toBlob(cb){pendingBlob=()=>cb(image);}})};
class Reader{async start(a,b,cb){success=cb;this.isScanning=true;}async stop(){this.isScanning=false;}clear(){}}
const window={Html5Qrcode:Reader,Html5QrcodeSupportedFormats:{EAN_13:9,EAN_8:10,QR_CODE:0}};
vm.runInNewContext(fs.readFileSync('js/ean-scanner.js','utf8'),{window,document,performance:{now:()=>now},setTimeout(cb,delay){if(delay<=180)cb();else timers.set(++timerId,{cb,delay});return timerId;},clearTimeout(id){timers.delete(id);}});
const api=window.RepoEANScanner,scanner=api.create('reader',{onCode(){}}),ean='7791234567898';
const flush=async()=>{for(let i=0;i<30;i++)await Promise.resolve();};
async function scan(){await scanner.start();for(let i=0;i<3;i++){now+=100;success(ean);}await flush();}
(async()=>{
await scan();assert.ok(drawn,'Captura solo después de confirmar');assert.ok(drawn[3]<video.videoWidth,'Usa el encuadre visible');assert.equal(drawn[4],video.videoHeight,'Conserva altura para localizar códigos verticales antes de recortar');assert.ok(Math.abs(drawn[1]+drawn[3]/2-video.videoWidth/2)<1,'Encuadre centrado');pendingBlob();assert.equal(api.takeScanPhoto('96385074'),null,'No adjunta una imagen de otro EAN');assert.equal(api.takeScanPhoto(ean),null,'Descarta la imagen que no corresponde');
await scan();pendingBlob();assert.equal(api.takeScanPhoto(ean),image);assert.equal(api.takeScanPhoto(ean),null,'Se consume una sola vez');
await scan();const pending=api.takeScanPhoto(ean);assert.equal(typeof pending.then,'function');pendingBlob();assert.equal(await pending,image,'Puede consumir la captura antes de que termine toBlob');assert.equal(api.takeScanPhoto(ean),null);
await scan();const late=pendingBlob;await scanner.start();late();assert.equal(api.takeScanPhoto(ean),null,'No conserva una captura tardía del escaneo anterior');await scanner.stop();
await scan();pendingBlob();const expiry=[...timers.values()].find(t=>t.delay===300000);assert.ok(expiry);expiry.cb();assert.equal(api.takeScanPhoto(ean),null,'Caduca sin persistir');
console.log('OK: confirmed visible frame, EAN binding, one-use photo, stale capture rejection and five-minute expiry.');
})().catch(e=>{console.error(e);process.exitCode=1});
