(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document,host=doc.getElementById('stockOverview');if(!host)return;
  const status=doc.getElementById('stockOverviewStatus'),retry=doc.getElementById('stockOverviewRetry'),list=doc.getElementById('stockOverviewActivity');let controller,timer,left=false;
  const titles={pallet_created:'creó un palet',pallet_deleted:'eliminó un palet',description_updated:'cambió la descripción',product_added:'agregó un producto',product_removed:'quitó un producto',quantity_updated:'cambió una cantidad',expiry_added:'agregó un vencimiento',expiry_updated:'cambió un vencimiento',expiry_removed:'quitó un vencimiento',reference_updated:'cambió una referencia',member_added:'agregó un colaborador',member_removed:'quitó un colaborador',product_merged:'unificó dos lotes'};
  async function load(){if(left)return;controller?.abort();const current=controller=new AbortController();status.textContent='';retry.hidden=true;
    const stop=window.RepotoolsLoader?.start(host.querySelector('.stock-overview-metrics'),{type:'skeleton',label:'Cargando resumen…'});
    try{if(!navigator.onLine)throw Error('El resumen se actualiza al conectar.');const response=await fetch('api/stock-overview.php',{cache:'no-store',signal:current.signal}),data=await response.json();if(!response.ok)throw Error(data.error);if(left||controller!==current)return;
      for(const [key,count] of Object.entries(data.counts))host.querySelector('[data-overview='+key+']').textContent=count;
      host.querySelector('[data-overview=products]').parentElement.title='Lotes de productos: cada EAN y vencimiento cuenta una vez';host.querySelector('[data-overview=upcoming]').parentElement.title='Vencen hoy o dentro de '+data.warning_days+' días';list.replaceChildren();
      for(const event of data.activity){const item=doc.createElement('li'),icon=doc.createElement('i'),body=doc.createElement('span'),time=doc.createElement('small');icon.className='fa-solid '+(event.action.startsWith('expiry_')?'fa-calendar':event.action.includes('removed')||event.action.includes('deleted')?'fa-trash':'fa-pen');icon.setAttribute('aria-hidden','true');body.textContent=event.actor+' '+(titles[event.action]||'actualizó un palet');time.textContent='Palet '+event.code+' · '+window.RepoStockUtils.movementDate(event.created_at.replace(/([+-]\d{2})$/,'$1:00'));body.append(time);item.append(icon,body);list.append(item);}
      if(!data.activity.length){const empty=doc.createElement('li');empty.textContent='Tus próximos movimientos aparecerán acá.';list.append(empty);}
    }catch(error){if(error.name!=='AbortError'&&!left&&controller===current){status.textContent=navigator.onLine?'No pudimos cargar el resumen':'El resumen se actualiza al conectar.';retry.hidden=!navigator.onLine;}}finally{stop?.();}
  }
  retry.addEventListener('click',load);const listen=page?page.listen:window.addEventListener.bind(window);listen('repotools:stock-changed',()=>{clearTimeout(timer);timer=setTimeout(load,200);});listen('online',load);
  page?.onLeave(()=>{left=true;clearTimeout(timer);controller?.abort();});timer=setTimeout(load,500);
})();
