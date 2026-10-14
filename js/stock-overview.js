(() => {
  const page=window.RepoNavigation?.page,doc=page?.document||document,$=id=>doc.getElementById(id),host=$('stockOverview');if(!host)return;
  const status=$('stockOverviewStatus'),retry=$('stockOverviewRetry'),history=$('stockHistoryDialog'),movement=$('stockMovementDialog'),list=$('stockHistoryList'),activity=window.RepoPalletActivity;
  let controller,timer,left=false,historyRequest,detailRequest,cursor=null,currentEvent=0;
  function node(tag,text,parent,cls){const n=doc.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;parent?.append(n);return n;}
  function icon(name,parent){const n=node('i',undefined,parent,'fa-solid fa-'+name);n.setAttribute('aria-hidden','true');return n;}
  async function load(){if(left)return;controller?.abort();const current=controller=new AbortController();status.textContent='';retry.hidden=true;
    const stop=window.RepotoolsLoader?.start(host.querySelector('.stock-overview-metrics'),{type:'skeleton',label:'Cargando resumen…'});
    try{if(!navigator.onLine)throw Error('El resumen se actualiza al conectar.');const response=await fetch('api/stock-overview.php',{cache:'no-store',signal:current.signal}),data=await response.json();if(!response.ok)throw Error(data.error);if(left||controller!==current)return;
      for(const [key,count] of Object.entries(data.counts))host.querySelector('[data-overview='+key+']').textContent=count;
      host.querySelector('[data-overview=products]').parentElement.title='Lotes de productos: cada EAN y vencimiento cuenta una vez';host.querySelector('[data-overview=upcoming]').parentElement.title='Vencen hoy o dentro de '+data.warning_days+' días';
    }catch(error){if(error.name!=='AbortError'&&!left&&controller===current){status.textContent=navigator.onLine?'No pudimos cargar el resumen':'El resumen se actualiza al conectar.';retry.hidden=!navigator.onLine;}}finally{stop?.();}
  }
  function eventTitle(item){return activity?.labels[item.action]||['clock','actualizó el palet'];}
  function timestamp(item){return activity?.when(item.created_at)||window.RepoStockUtils.movementDate(item.created_at);}
  async function loadHistory(append=false){historyRequest?.abort();const request=historyRequest=new AbortController();$('stockHistoryStatus').textContent='';$('stockHistoryRetry').hidden=true;$('stockHistoryMore').hidden=true;if(!append){list.replaceChildren();cursor=null;}
    const stop=window.RepotoolsLoader?.start(append?$('stockHistoryMore'):list,{type:append?'button':'local',label:'Cargando movimientos…'});
    try{if(!navigator.onLine)throw Error('Necesitás conexión para consultar el historial.');const response=await fetch('api/pallet-activity.php?scope=stock'+(append&&cursor?'&cursor='+cursor:''),{cache:'no-store',signal:request.signal}),data=await response.json();if(!response.ok)throw Error(data.error);if(left||request!==historyRequest||!history.open)return;
      for(const item of data.items){const row=node('li',undefined,list),button=node('button',undefined,row,'stock-history-ticket');button.type='button';button.setAttribute('aria-label','Ver movimiento #'+item.id);const [symbol,title]=eventTitle(item);icon(symbol,button);const body=node('span',undefined,button);node('strong',item.actor+' '+title,body);node('small','Palet '+item.code+' · '+timestamp(item),body);const access=node('span',undefined,button,'stock-history-access');icon('file-lines',access);node('small','#'+item.id,access);button.addEventListener('click',()=>openMovement(item.id));}
      cursor=data.next_cursor;$('stockHistoryMore').hidden=!cursor;if(!list.children.length)$('stockHistoryStatus').textContent='Todavía no hay movimientos en tus palets.';
    }catch(e){if(e.name!=='AbortError'&&!left&&request===historyRequest&&history.open){$('stockHistoryStatus').textContent=e.message||'No pudimos cargar los movimientos.';$('stockHistoryRetry').hidden=!navigator.onLine;}}
    finally{stop?.();}
  }
  async function openMovement(id){currentEvent=id;detailRequest?.abort();const request=detailRequest=new AbortController();$('stockMovementDetail').replaceChildren();$('stockMovementStatus').textContent='';$('stockMovementRetry').hidden=true;movement.querySelector('h2').textContent='Movimiento #'+id;if(!movement.open)movement.showModal();
    const stop=window.RepotoolsLoader?.start($('stockMovementDetail'),{type:'local',label:'Cargando movimiento…'});
    try{if(!navigator.onLine)throw Error('Necesitás conexión para ver este movimiento.');const response=await fetch('api/pallet-activity.php?scope=stock&event_id='+encodeURIComponent(id),{cache:'no-store',signal:request.signal}),data=await response.json();if(!response.ok)throw Error(data.error);if(left||request!==detailRequest||!movement.open)return;const item=data.item,meta=item.metadata,detail=$('stockMovementDetail'),[symbol,title]=eventTitle(item);
      const heading=node('p',undefined,detail,'stock-movement-action');icon(symbol,heading);node('strong',item.actor+' '+title,heading);node('p','Palet '+item.code+' · '+window.RepoStockUtils.movementDate(item.created_at.replace(/([+-]\d{2})$/,'$1:00')),detail,'stock-muted');
      const fields=node('dl',undefined,detail,'stock-movement-fields');for(const [label,value] of [['Producto',meta.reference],['EAN',meta.ean],['Colaborador',meta.member],['Cantidad',meta.units===undefined?undefined:meta.units+' unidades']])if(value!==undefined&&value!==''){node('dt',label,fields);node('dd',String(value),fields);}
      if('old' in meta){const comparison=node('div',undefined,detail,'stock-movement-comparison'),expiry=item.action.startsWith('expiry_');for(const [label,value] of [['Antes',meta.old],['Después',meta.new]]){const cell=node('div',undefined,comparison);node('small',label,cell);node('strong',expiry?activity.date(String(value??'')):String(value===''||value==null?'Sin datos':value),cell);}}
    }catch(e){if(e.name!=='AbortError'&&!left&&request===detailRequest&&movement.open){$('stockMovementStatus').textContent=e.message||'No pudimos cargar el movimiento.';$('stockMovementRetry').hidden=!navigator.onLine;}}
    finally{stop?.();}
  }
  $('openStockHistory').addEventListener('click',()=>{if(!history.open)history.showModal();loadHistory();});$('stockHistoryMore').addEventListener('click',()=>loadHistory(true));$('stockHistoryRetry').addEventListener('click',()=>loadHistory());$('stockMovementRetry').addEventListener('click',()=>openMovement(currentEvent));history.addEventListener('close',()=>historyRequest?.abort());movement.addEventListener('close',()=>detailRequest?.abort());
  retry.addEventListener('click',load);const listen=page?page.listen:window.addEventListener.bind(window);listen('repotools:stock-changed',()=>{clearTimeout(timer);timer=setTimeout(load,200);});listen('online',load);
  page?.onLeave(()=>{left=true;clearTimeout(timer);controller?.abort();historyRequest?.abort();detailRequest?.abort();history.close();movement.close();});timer=setTimeout(load,500);
})();
