(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document,form=doc.getElementById('topsFilters'),result=doc.getElementById('topsRanking');if(!form)return;
  let request;const controller=new AbortController();page?.onLeave(()=>{controller.abort();request?.abort();});
  async function load(event){event.preventDefault();request?.abort();const active=request=new AbortController(),url=new URL(form.action,location.href);new FormData(form).forEach((v,k)=>url.searchParams.set(k,v));
    const publicURL=url.href;url.searchParams.set('fragment','1');const stop=window.RepotoolsLoader?.start(result,{type:'local',label:'Cargando ranking…'})||(()=>{});
    try{const response=await fetch(url,{signal:active.signal,headers:{Accept:'text/html'}});if(!response.ok)throw Error();const html=await response.text();if(active!==request)return;result.innerHTML=html;history.replaceState(history.state,'',publicURL);doc.getElementById('topsDescription').textContent=form.querySelector('[name=categoria]:checked').dataset.description;}
    catch(e){if(e.name!=='AbortError')window.RepoToast?.('No pudimos cargar el ranking. Intentá nuevamente.',{kind:'error'});}finally{stop();}
  }
  const link=doc.getElementById('topsReferralLink');
  async function copy(){try{await navigator.clipboard.writeText(link.value);window.RepoToast?.('Enlace copiado.');}catch{link.focus();link.select();window.RepoToast?.('Copiá tu enlace de invitación.',{kind:'info'});}}
  doc.getElementById('copyTopsReferral')?.addEventListener('click',copy,{signal:controller.signal});doc.getElementById('shareTopsReferral')?.addEventListener('click',async()=>{if(navigator.share){try{await navigator.share({title:'RepoTools',text:'Te invito a usar RepoTools',url:link.value});return;}catch(e){if(e.name==='AbortError')return;}}await copy();},{signal:controller.signal});
  form.addEventListener('change',load,{signal:controller.signal});form.addEventListener('submit',load,{signal:controller.signal});
})();
