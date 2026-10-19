(async()=>{
 const status=document.getElementById('labelStatus'),sheets=document.getElementById('labelSheets'),print=document.getElementById('printLabels'),pdf=document.getElementById('pdfLabels');
 if(!window.repotoolsLabels.length)return;
 let pdfBlob;
 const openPDF=async()=>{
   pdf.disabled=true;
   try{
     const file=new File([pdfBlob],'Repotools-etiquetas-A4-vertical.pdf',{type:'application/pdf'});
     status.textContent=await RepoPalletLabel.shareFiles([file]);
   }catch(e){if(e.name!=='AbortError')status.textContent='No pudimos compartir el PDF. Intentá nuevamente.';}
   finally{pdf.disabled=false;}
 };
 const shareLink=document.getElementById('shareLabelsLink');
 shareLink?.addEventListener('click',async()=>{try{status.textContent=await RepoPalletLabel.shareLink(window.repotoolsLabels);}catch(e){if(e.name!=='AbortError')status.textContent=e.message;}});

 try{
   for(const label of window.repotoolsLabels){
     const sheet=document.createElement('section'),image=new Image();sheet.className='label-sheet';image.alt='Etiqueta Repotools del palet '+label.code;image.src=URL.createObjectURL(new Blob([RepoPalletLabel.vector(label)],{type:'image/svg+xml'}));image.decoding='async';image.loading='eager';sheet.append(image);
     const share=document.createElement('button');share.type='button';share.className='label-share';share.setAttribute('aria-label','Compartir enlace');share.title='Compartir enlace';share.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M5 13v8h14v-8"/></svg>';share.addEventListener('click',async()=>{try{status.textContent=await RepoPalletLabel.shareLink([label]);}catch(e){if(e.name!=='AbortError')status.textContent=e.message;}});sheet.append(share);sheets.append(sheet);
   }
   await Promise.all([...sheets.querySelectorAll('img')].map(image=>image.decode()));
   print.disabled=false;if(shareLink)shareLink.disabled=false;

   print.addEventListener('click',async()=>{
     print.disabled=true;
     try{await Promise.all([...sheets.querySelectorAll('img')].map(image=>image.decode()));window.print();}
     catch(e){status.textContent='No pudimos preparar la impresión. Probá descargar el PDF.';}
     finally{print.disabled=false;}
   });
   try{
   pdfBlob=await RepoPalletLabelPDF.create(window.repotoolsLabels);
   pdf.disabled=false;status.textContent='A4 vertical · una hoja por palet. Compartí el enlace o el archivo PDF.';
   pdf.addEventListener('click',openPDF);
   }catch{status.textContent='Podés imprimir o compartir el enlace. No pudimos preparar el PDF.';}
 }catch(e){status.textContent='No pudimos cargar las etiquetas. Intentá nuevamente.';}
})();
