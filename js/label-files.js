(() => {
  'use strict';
  const labels=window.RepoPalletLabel;if(!labels)return;
  labels.shareLink=async items=>{
    const codes=[...new Set(items.map(item=>item.code))];
    if(!codes.length||codes.length>100||codes.some(code=>!/^\d{8}$/.test(code)))throw Error('Seleccioná entre 1 y 100 etiquetas válidas.');
    const url=new URL('etiqueta.php',document.baseURI);url.searchParams.set('codigos',codes.join(','));
    if(['repotools.online','www.repotools.online'].includes(url.hostname)){url.hostname='repotools.online';url.protocol='https:';}
    if(navigator.share){try{await navigator.share({title:'Etiquetas de palets · Repotools',url:url.href});return 'Enlace de etiquetas compartido.';}catch(error){if(error.name==='AbortError')throw error;}}
    try{await navigator.clipboard.writeText(url.href);return 'Enlace de etiquetas copiado.';}catch{throw Error('No pudimos compartir el enlace. Abrí las etiquetas y copiá la dirección del navegador.');}
  };
  // Prebuilt single PDF preserves the native share gesture and includes every label.
  labels.shareFiles=async files=>{
    if(files.length!==1||files[0].type!=='application/pdf')throw Error('Prepará un PDF con las etiquetas para compartir.');
    let supported=false;try{supported=!!navigator.canShare?.({files});}catch{}
    if(supported){try{await navigator.share({files,title:'Etiquetas de palets · Repotools'});return 'PDF compartido.';}catch(error){if(error.name==='AbortError')throw error;}}
    const link=document.createElement('a'),url=URL.createObjectURL(files[0]);link.href=url;link.download=files[0].name;document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),60000);return 'PDF descargado.';
  };
})();
