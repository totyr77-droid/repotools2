/* Small PDF 1.4 writer for the existing label: vector QR/text and embedded logo.
   Fixed A4 landscape MediaBox; no browser print pagination or remote dependency. */
(() => {
  const bytes=text=>Uint8Array.from(text,c=>c.charCodeAt(0)&255);
  const join=parts=>{const result=new Uint8Array(parts.reduce((n,p)=>n+p.length,0));let offset=0;for(const p of parts){result.set(p,offset);offset+=p.length;}return result;};
  const literal=text=>'('+Array.from(String(text),c=>{const n=c.charCodeAt(0);if(c==='('||c===')'||c==='\\')return '\\'+c;if(n>126&&n<=255)return '\\'+n.toString(8).padStart(3,'0');return n>255?' ':c;}).join('')+')';
  let logoPromise;
  function logoImage(source){return logoPromise??=(async()=>{const image=new Image();image.src=source;await image.decode();const canvas=document.createElement('canvas');canvas.width=image.naturalWidth;canvas.height=image.naturalHeight;const ctx=canvas.getContext('2d');ctx.drawImage(image,0,0);ctx.globalCompositeOperation='source-in';ctx.fillStyle='black';ctx.fillRect(0,0,canvas.width,canvas.height);ctx.globalCompositeOperation='destination-over';ctx.fillStyle='white';ctx.fillRect(0,0,canvas.width,canvas.height);return {width:canvas.width,height:canvas.height,data:bytes(atob(canvas.toDataURL('image/jpeg',1).split(',')[1]))};})();}
  async function create(labels){
    if(!labels.length||labels.length>100)throw Error('Elegí entre 1 y 100 etiquetas.');
    const first=RepoPalletLabel.layout(labels[0]),logo=await logoImage(first.logo),objects=[];
    const add=body=>{objects.push(typeof body==='string'?bytes(body):body);return objects.length;};
    const stream=(dictionary,data)=>join([bytes('<< '+dictionary+' /Length '+data.length+' >>\nstream\n'),data,bytes('\nendstream')]);
    const regular=add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'),bold=add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
    const imageId=add(stream('/Type /XObject /Subtype /Image /Width '+logo.width+' /Height '+logo.height+' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode',logo.data));
    const pagesId=add(''),catalogId=add('<< /Type /Catalog /Pages '+pagesId+' 0 R >>'),pages=[];
    const W=297*72/25.4,H=210*72/25.4,margin=8*72/25.4,scale=Math.min((W-margin*2)/3508,(H-margin*2)/2480),left=(W-3508*scale)/2,bottom=(H-2480*scale)/2;
    const measure=document.createElement('canvas').getContext('2d');
    for(const label of labels){
      const l=RepoPalletLabel.layout(label),cell=l.qr.size/l.n,ops=['q',`${scale} 0 0 ${scale} ${left} ${bottom} cm`,'0 g'];
      const text=(x,y,size,value,isBold=false,width)=>{measure.font=(isBold?'bold ':'')+size+'px Arial';const natural=width?String(value).length*size*.556:measure.measureText(value).width;const target=width||natural;ops.push(`BT /${isBold?'B':'F'} ${size} Tf ${width?width/natural*100:100} Tz 1 0 0 1 ${x-target/2} ${2480-y} Tm ${literal(value)} Tj ET`);};
      ops.push(`q ${l.brand.width} 0 0 ${l.brand.height} ${l.brand.x} ${2480-l.brand.y-l.brand.height} cm /Logo Do Q`);
      text(l.qr.x+l.qr.size/2,250,64,'Escaneá para consultar este palet');
      for(let y=0;y<l.n;y++)for(let x=0;x<l.n;x++)if(l.matrix.isDark(y,x)){let end=x+1;while(end<l.n&&l.matrix.isDark(y,end))end++;ops.push(`${l.qr.x+x*cell} ${2480-l.qr.y-(y+1)*cell} ${(end-x)*cell} ${cell} re`);x=end-1;}
      ops.push('f');text(l.code.x,820,56,'CÓDIGO DEL PALET');text(l.code.x,l.code.y,l.code.size,label.code,true,l.code.width);l.footer.forEach((line,i)=>text(l.code.x,1570+i*90,48,line));ops.push('Q');
      const content=add(stream('',bytes(ops.join('\n'))));
      pages.push(add(`<< /Type /Page /Parent ${pagesId} 0 R /MediaBox [0 0 ${W} ${H}] /Resources << /Font << /F ${regular} 0 R /B ${bold} 0 R >> /XObject << /Logo ${imageId} 0 R >> >> /Contents ${content} 0 R >>`));
    }
    objects[pagesId-1]=bytes('<< /Type /Pages /Count '+pages.length+' /Kids ['+pages.map(id=>id+' 0 R').join(' ')+'] >>');
    const parts=[bytes('%PDF-1.4\n%\xE2\xE3\xCF\xD3\n')],offsets=[0];let length=parts[0].length;
    objects.forEach((body,i)=>{offsets.push(length);const part=join([bytes((i+1)+' 0 obj\n'),body,bytes('\nendobj\n')]);parts.push(part);length+=part.length;});
    const xref=length;parts.push(bytes('xref\n0 '+(objects.length+1)+'\n0000000000 65535 f \n'+offsets.slice(1).map(n=>String(n).padStart(10,'0')+' 00000 n \n').join('')+'trailer\n<< /Size '+(objects.length+1)+' /Root '+catalogId+' 0 R >>\nstartxref\n'+xref+'\n%%EOF\n'));
    return new Blob(parts,{type:'application/pdf'});
  }
  window.RepoPalletLabelPDF={create};
})();
