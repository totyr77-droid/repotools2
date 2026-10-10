(() => {
  const cache=new Map();
  function url(code){const link=new URL('palet.php',location.href);link.searchParams.set('codigo',code);return link.href;}
  function vector(label) {
    const holder=document.createElement('div'),qr=new QRCode(holder,{text:url(label.code),width:256,height:256,correctLevel:QRCode.CorrectLevel.M}),matrix=qr._oQRCode,n=matrix.getModuleCount(),cell=1920/n;
    let path='';for(let y=0;y<n;y++)for(let x=0;x<n;x++)if(matrix.isDark(y,x)){let end=x+1;while(end<n&&matrix.isDark(y,end))end++;path+=`M${x} ${y}h${end-x}v1h-${end-x}z`;x=end-1;}
    const escape=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[c]));
    const text=(y,size,value,bold=false)=>`<text x="1240" y="${y}" font-size="${size}"${bold?' font-weight="bold"':''}>${escape(value)}</text>`;
    let footer='';if(!Number(label.activated)){footer=text(3250,44,'Si no se cargan productos dentro de los 3 días desde su generación,')+text(3310,44,'este código quedará disponible para otra persona.');const deadline=Number(label.reserved_until||label.prepared_until);if(deadline)footer+=text(3380,44,'Reservado hasta '+new Date(deadline*1000).toLocaleString('es-AR'));}else footer=text(3290,44,'Consulta el contenido actualizado escaneando el QR.');
    return `<svg xmlns="http://www.w3.org/2000/svg" width="2480" height="3508" viewBox="0 0 2480 3508"><rect width="2480" height="3508" fill="white"/><g fill="black" font-family="Arial,sans-serif" text-anchor="middle">${text(360,210,'REPOTOOLS',true)}${text(510,64,'Escanea para consultar este palet')}<path d="${path}" transform="translate(280 770) scale(${cell})" shape-rendering="crispEdges"/>${text(2870,56,'CÓDIGO DEL PALET')}${text(3080,180,label.code,true)}${footer}</g></svg>`;
  }
  async function image(label) {
    const key=label.code+':'+(label.activated?'active':label.reserved_until||label.prepared_until||0);
    if(cache.has(key))return cache.get(key);
    const canvas=document.createElement('canvas');canvas.width=2480;canvas.height=3508;
    const ctx=canvas.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,2480,3508);ctx.fillStyle='#000';ctx.textAlign='center';ctx.font='bold 210px Arial';ctx.fillText('REPOTOOLS',1240,360);
    ctx.font='64px Arial';ctx.fillText('Escanea para consultar este palet',1240,510);
    const holder=document.createElement('div');new QRCode(holder,{text:url(label.code),width:1600,height:1600,colorDark:'#000000',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});
    const qr=holder.querySelector('canvas');if(!qr)throw Error('No pudimos generar el QR.');ctx.imageSmoothingEnabled=false;ctx.drawImage(qr,280,770,1920,1920);
    ctx.font='56px Arial';ctx.fillText('CÓDIGO DEL PALET',1240,2870);ctx.font='bold 180px Arial';ctx.fillText(label.code,1240,3080);
    ctx.font='44px Arial';
    if(!Number(label.activated)){
      ctx.fillText('Si no se cargan productos dentro de los 3 días desde su generación,',1240,3250);ctx.fillText('este código quedará disponible para otra persona.',1240,3310);
      const deadline=Number(label.reserved_until||label.prepared_until);if(deadline)ctx.fillText('Reservado hasta '+new Date(deadline*1000).toLocaleString('es-AR'),1240,3380);
    } else ctx.fillText('Consulta el contenido actualizado escaneando el QR.',1240,3290);
    const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));if(!blob)throw Error('No pudimos generar la etiqueta.');const result={blob,dataURL:canvas.toDataURL('image/png')};cache.set(key,result);return result;
  }
  async function share(label){const result=await image(label),file=new File([result.blob],`Repotools-palet-${label.code}.png`,{type:'image/png'});if(navigator.canShare?.({files:[file]})){await navigator.share({files:[file],title:`Palet ${label.code}`});return 'Etiqueta compartida.';}const link=document.createElement('a');link.href=result.dataURL;link.download=file.name;link.click();return 'Etiqueta descargada. Puedes enviarla como imagen.';}
  window.RepoPalletLabel={url,image,share,vector};
})();
