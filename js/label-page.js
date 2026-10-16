(async()=>{
 const status=document.getElementById('labelStatus'),sheets=document.getElementById('labelSheets'),print=document.getElementById('printLabels'),pdf=document.getElementById('pdfLabels');
 if(!window.repotoolsLabels.length)return;
 let pdfBlob;
 const openPDF=async()=>{
   // Open synchronously during the tap: Safari blocks popups created after await.
   const viewer=window.open('about:blank','_blank');
   if(viewer){viewer.document.title='Etiquetas de palets';viewer.document.body.textContent='Preparando PDF A4 vertical…';}
   pdf.disabled=true;print.disabled=true;
   try{
     pdfBlob??=await RepoPalletLabelPDF.create(window.repotoolsLabels);
     const url=URL.createObjectURL(pdfBlob);
     if(viewer)viewer.location.replace(url);
     else {const link=document.createElement('a');link.href=url;link.download='Repotools-etiquetas-A4-vertical.pdf';link.click();}
     // Keep the URL alive for the PDF viewer and its print/share controls.
     status.textContent='PDF A4 vertical listo: una hoja por palet.';
   }catch(e){viewer?.close();status.textContent='No pudimos preparar el PDF. Intentá nuevamente.';}
   finally{pdf.disabled=false;print.disabled=false;}
 };
 try{
   for(const label of window.repotoolsLabels){
     const sheet=document.createElement('section'),image=new Image();sheet.className='label-sheet';image.alt='Etiqueta Repotools del palet '+label.code;image.src=URL.createObjectURL(new Blob([RepoPalletLabel.vector(label)],{type:'image/svg+xml'}));image.decoding='async';image.loading='eager';sheet.append(image);
     const share=document.createElement('button');share.type='button';share.className='label-share';share.setAttribute('aria-label','Compartir enlace');share.title='Compartir enlace';share.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M5 13v8h14v-8"/></svg>';share.addEventListener('click',async()=>{try{status.textContent=await RepoPalletLabel.shareLink([label]);}catch(e){if(e.name!=='AbortError')status.textContent=e.message;}});sheet.append(share);sheets.append(sheet);
   }
   await Promise.all([...sheets.querySelectorAll('img')].map(image=>image.decode()));
   print.disabled=false;pdf.disabled=false;status.textContent='A4 vertical · una hoja por palet. También podés abrir el PDF.';
   pdf.addEventListener('click',openPDF);
   print.addEventListener('click',async()=>{
     print.disabled=true;
     try{await Promise.all([...sheets.querySelectorAll('img')].map(image=>image.decode()));window.print();}
     catch(e){status.textContent='No pudimos preparar la impresión. Probá abrir el PDF.';}
     finally{print.disabled=false;}
   });
 }catch(e){status.textContent='No pudimos cargar las etiquetas. Intentá nuevamente.';}
})();
