(() => {
  'use strict';
  const labels=window.RepoPalletLabel;if(!labels)return;
  // Prebuilt single PDF preserves the native share gesture and includes every label.
  labels.shareFiles=async files=>{
    if(files.length!==1||files[0].type!=='application/pdf')throw Error('Prepará un PDF con las etiquetas para compartir.');
    let supported=false;try{supported=!!navigator.canShare?.({files});}catch{}
    if(supported){try{await navigator.share({files,title:'Etiquetas de palets · Repotools'});return 'PDF compartido.';}catch(error){if(error.name==='AbortError')throw error;}}
    const link=document.createElement('a'),url=URL.createObjectURL(files[0]);link.href=url;link.download=files[0].name;document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),60000);return 'PDF descargado.';
  };
})();
