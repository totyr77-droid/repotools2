(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document,menu=doc.getElementById('stockExport');if(!menu)return;
  let busy=false,left=false;page?.onLeave(()=>{left=true;});
  menu.querySelectorAll('[data-export]').forEach(button=>button.addEventListener('click',async()=>{
    if(busy)return;busy=true;const stop=window.RepotoolsLoader?.start(button,{type:'button',label:'Preparando archivo…'});menu.querySelectorAll('button').forEach(b=>b.disabled=true);
    try{const response=await(page?.fetch||fetch.bind(window))('api/stock-export.php?type='+button.dataset.export,{cache:'no-store'});if(!response.ok){const error=await response.json();throw Error(error.error||'No pudimos generar el archivo');}
    const blob=await response.blob();if(left)return;const link=doc.createElement('a'),url=URL.createObjectURL(blob);link.href=url;link.download=response.headers.get('Content-Disposition')?.match(/filename="([^"]+)"/)?.[1]||'repotools.csv';link.dataset.fullNavigation='';doc.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);menu.open=false;window.RepoToast?.('Exportación lista');
    }catch(error){if(!left)window.RepoToast?.(error.message,{kind:'error'});}finally{stop?.();busy=false;if(!left)menu.querySelectorAll('button').forEach(b=>b.disabled=false);}
  }));
})();
