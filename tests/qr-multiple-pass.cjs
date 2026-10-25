const fs=require('fs'),vm=require('vm'),assert=require('assert/strict'),crypto=require('crypto');
const source=fs.readFileSync('js/qr-rescue-worker.js','utf8');
const version=crypto.createHash('sha1').update(source.replace(/\r\n/g,'\n')).digest('hex').slice(0,12);
assert(fs.readFileSync('js/ean-scanner.js','utf8').includes('qr-rescue-worker.js?v='+version),'Worker content hash must change with worker code');
async function run(multiple,answer){let calls=0,result;class ImageData{constructor(data,width,height){Object.assign(this,{data,width,height});}}
const self={location:{href:'http://local/js/qr-rescue-worker.js'},postMessage:value=>{if(value.type==='result')result=value;}};
vm.runInNewContext(source,{self,importScripts(){},ZXingWASM:{prepareZXingModule:()=>Promise.resolve(),readBarcodes:async(image,options)=>{calls++;return answer(calls,image,options);}},ImageData,Uint8ClampedArray,URL,ArrayBuffer,Set,Number,Math});
await self.onmessage({data:{type:'decode',id:1,multiple,width:100,height:100,buffer:new ArrayBuffer(40000)}});return {result,calls};}
(async()=>{const row=text=>({isValid:true,text});
const partial=await run(true,n=>[row(n===1?'left':'right')]);assert.deepEqual(Array.from(partial.result.codes),['left','right']);assert.equal(partial.calls,2,'Must not stop on first readable QR');assert.equal(partial.result.verified,true);
const halves=await run(true,(n,image)=>[row(image.width<100?'right':'left')]);assert.deepEqual(Array.from(halves.result.codes),['left','right']);assert(halves.calls>3,'Looks for neighbor in overlapping regions');
const single=await run(false,()=>[row('single')]);assert.equal(single.calls,1,'Generic QR rescue retains fast path');assert.equal(single.result.verified,false);
const empty=await run(true,()=>[]);assert.equal(empty.result.verified,true);assert.equal(empty.calls,7,'Empty pallet target is checked fully before OCR may proceed');
console.log('OK: partial first pass cannot hide neighbor, overlapping regions, verified empty target, generic fast path and cache-busting hash.');
})().catch(e=>{console.error(e);process.exitCode=1;});
