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
function section(image,x,y,width,height){
  const data=new Uint8ClampedArray(width*height*4);
  for(let row=0;row<height;row++)data.set(image.data.subarray(((y+row)*image.width+x)*4,((y+row)*image.width+x+width)*4),row*width*4);
  return new ImageData(data,width,height);
}
self.onmessage=async event=>{
  const request=event.data;if(request?.type!=='decode')return;
  try{
    await ready;const {width,height,buffer}=request;
    if(!Number.isInteger(width)||!Number.isInteger(height)||width<1||height<1||width*height>6000000||!(buffer instanceof ArrayBuffer)||buffer.byteLength!==width*height*4)throw Error('Invalid frame');
    const original=new ImageData(new Uint8ClampedArray(buffer),width,height),codes=new Set(),symbols=new Map();
    const collect=(rows,scale=1,x=0,y=0)=>{for(const row of rows)if(row.isValid&&typeof row.text==='string'&&row.text.length<=2048){codes.add(row.text);const points=Object.values(row.position||{}).filter(p=>Number.isFinite(p?.x)&&Number.isFinite(p?.y));if(points.length>=4&&!symbols.has(row.text))symbols.set(row.text,{text:row.text,x:(points.reduce((n,p)=>n+p.x,0)/points.length/scale+x)/width,y:(points.reduce((n,p)=>n+p.y,0)/points.length/scale+y)/height});}};
    for(const [binarizer,scale,denoise] of [['LocalAverage',1,true],['GlobalHistogram',1,true],['LocalAverage',1.5,true]]){
      const image=scale===1?original:enlarge(original,scale);if(!image)continue;
      collect(await ZXingWASM.readBarcodes(image,{formats:['QRCode'],tryHarder:true,tryRotate:true,tryInvert:true,tryDenoise:denoise,binarizer,maxNumberOfSymbols:4,returnErrors:false}),scale);
      if(codes.size>=(request.multiple?2:1))break;
    }
    // A readable label must not hide its neighbor. Search overlapping halves
    // before declaring that a pallet target contains only one code.
    if(request.multiple&&codes.size<2){
      const w=Math.ceil(width*.65),h=Math.ceil(height*.65);
      for(const [x,y,cw,ch]of [[0,0,w,height],[width-w,0,w,height],[0,0,width,h],[0,height-h,width,h]]){
        collect(await ZXingWASM.readBarcodes(section(original,x,y,cw,ch),{formats:['QRCode'],tryHarder:true,tryRotate:true,tryInvert:true,tryDenoise:true,binarizer:'LocalAverage',maxNumberOfSymbols:4,returnErrors:false}),1,x,y);
        if(codes.size>=2)break;
      }
    }
    self.postMessage({type:'result',id:request.id,codes:[...codes],symbols:[...symbols.values()],verified:request.multiple===true});
  }catch{self.postMessage({type:'result',id:request.id,codes:[],verified:false});}
};
