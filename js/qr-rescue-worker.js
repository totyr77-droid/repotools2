/* Local QR-only rescue. Frames never leave this worker or the device. */
'use strict';
importScripts('vendor/zxing-wasm-3.1.5/reader.js');
const ready=ZXingWASM.prepareZXingModule({overrides:{locateFile:path=>new URL('vendor/zxing-wasm-3.1.5/'+path,self.location.href).href},fireImmediately:true});
ready.then(()=>self.postMessage({type:'ready'})).catch(()=>self.postMessage({type:'unavailable'}));
function enlarge(image,scale){
  const width=Math.round(image.width*scale),height=Math.round(image.height*scale);
  if(width*height>6000000)return null;
  const output=new Uint8ClampedArray(width*height*4),input=image.data;
  for(let y=0;y<height;y++)for(let x=0;x<width;x++){
    const from=(Math.min(image.height-1,Math.floor(y/scale))*image.width+Math.min(image.width-1,Math.floor(x/scale)))*4,to=(y*width+x)*4;
    for(let channel=0;channel<4;channel++)output[to+channel]=input[from+channel];
  }
  return new ImageData(output,width,height);
}
self.onmessage=async event=>{
  const request=event.data;if(request?.type!=='decode')return;
  try{
    await ready;const {width,height,buffer}=request;
    if(!Number.isInteger(width)||!Number.isInteger(height)||width<1||height<1||width*height>6000000||!(buffer instanceof ArrayBuffer)||buffer.byteLength!==width*height*4)throw Error('Invalid frame');
    const original=new ImageData(new Uint8ClampedArray(buffer),width,height);let results=[];
    for(const [binarizer,scale,denoise] of [['LocalAverage',1,true],['GlobalHistogram',1,true],['LocalAverage',1.5,true]]){
      const image=scale===1?original:enlarge(original,scale);if(!image)continue;
      results=await ZXingWASM.readBarcodes(image,{formats:['QRCode'],tryHarder:true,tryRotate:true,tryInvert:true,tryDenoise:denoise,binarizer,maxNumberOfSymbols:4,returnErrors:false});
      if(results.some(row=>row.isValid))break;
    }
    self.postMessage({type:'result',id:request.id,codes:[...new Set(results.filter(row=>row.isValid&&typeof row.text==='string'&&row.text.length<=2048).map(row=>row.text))]});
  }catch{self.postMessage({type:'result',id:request.id,codes:[]});}
};
