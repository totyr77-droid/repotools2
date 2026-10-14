(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document,form=doc.getElementById('topsFilters'),result=doc.getElementById('topsRanking');if(!form)return;
  let request;const controller=new AbortController();page?.onLeave(()=>{controller.abort();request?.abort();});
  async function load(event){event.preventDefault();request?.abort();const active=request=new AbortController(),url=new URL(form.action,location.href);new FormData(form).forEach((v,k)=>url.searchParams.set(k,v));
    const publicURL=url.href;url.searchParams.set('fragment','1');const stop=window.RepotoolsLoader?.start(result,{type:'local',label:'Cargando ranking…'})||(()=>{});
    try{const response=await fetch(url,{signal:active.signal,headers:{Accept:'text/html'}});if(!response.ok)throw Error();const html=await response.text();if(active!==request)return;result.innerHTML=html;history.replaceState(history.state,'',publicURL);doc.getElementById('topsDescription').textContent=form.categoria.selectedOptions[0].dataset.description;}
    catch(e){if(e.name!=='AbortError')window.RepoToast?.('No pudimos cargar el ranking. Intentá nuevamente.',{kind:'error'});}finally{stop();}
  }
  form.addEventListener('change',load,{signal:controller.signal});form.addEventListener('submit',load,{signal:controller.signal});
})();
