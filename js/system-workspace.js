(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document,root=doc.getElementById('systemWorkspace');if(!root)return;
  const form=doc.getElementById('systemFilters'),status=doc.getElementById('systemStatus'),summary=doc.getElementById('systemSummary'),records=doc.getElementById('systemRecords');
  let index=0,revision=0,controller;
  const seconds=value=>value==null?'—':(Number(value)/1000).toLocaleString('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2})+' s';
  const node=(tag,text,parent,className)=>{const n=doc.createElement(tag);if(text!=null)n.textContent=text;if(className)n.className=className;parent.append(n);return n;};
  const contexts={home:'Inicio',stock:'Stock',library:'Biblioteca',users:'Usuarios',relay:'Celular conectado',offline:'Sin conexión',other:'Otro'},devices={mobile:'Celular',tablet:'Tablet',desktop:'PC'},browsers={safari:'Safari',chrome:'Chrome',edge:'Edge',firefox:'Firefox',other:'Otro'};
  const source={camera:'Cámara',background:'Análisis de imagen',ocr:'OCR',none:'Sin lectura'},outcome={confirmed:'Confirmado',cancelled:'Cerrado sin lectura',error:'Error de cámara'};
  async function load(){
    controller?.abort();controller=new AbortController();const current=++revision;
    status.textContent='Consultando rendimiento…';doc.getElementById('refreshSystem').disabled=true;
    try{
      const params=new URLSearchParams(new FormData(form));params.set('page',String(index));
      const response=await fetch(new URL('api/scanner-metrics.php?'+params,doc.baseURI),{credentials:'same-origin',cache:'no-store',signal:controller.signal});const data=await response.json();if(!response.ok)throw Error(data.error||'No pudimos consultar Sistema.');if(current!==revision||root.hidden)return;
      summary.replaceChildren();records.replaceChildren();
      const selected=form.elements.version.value;form.elements.version.replaceChildren(new Option('Todas las versiones',''));for(const version of data.versions)form.elements.version.add(new Option('v'+version,version));if(selected&&!data.versions.includes(selected))form.elements.version.add(new Option('v'+selected,selected));form.elements.version.value=selected;
      const stats=data.summary;
      if(!Number(stats.attempts)){status.textContent='Todavía no hay registros para estos filtros. Escaneá un QR o EAN para empezar.';}
      else{
        status.textContent='';
        const totals=node('p',null,summary);node('strong',(stats.confirmed||0)+' lecturas confirmadas',totals);node('span',' · '+(stats.cancelled||0)+' cerrados sin lectura · '+(stats.errors||0)+' errores de cámara',totals);
        node('p','Apertura promedio: '+seconds(stats.avg_open_ms)+' · Confirmación desde primera detección: '+seconds(stats.avg_confirm_ms),summary,'system-hint');
        for(const group of data.groups){const row=node('div',null,summary,'system-group');node('strong',group.mode.toUpperCase()+' · v'+group.app_version,row);const detail=node('span',null,row);node('strong',seconds(group.avg_scan_ms),detail);node('small',' promedio · '+(group.confirmed||0)+' lecturas',detail);}
      }
      for(const item of data.items){
        const row=node('article',null,records,'system-record'),heading=node('div',null,row,'system-record-heading');
        node('strong',item.mode.toUpperCase()+' · '+outcome[item.outcome],heading);node('span',seconds(item.scan_ms),heading);
        node('p',source[item.source]+' · '+(item.rapid?'Rápido':'Normal')+' · '+contexts[item.context]+' · '+browsers[item.browser]+' / '+devices[item.device]+' · v'+item.app_version,row);
        const time=window.RepoDateTime?.stamp(item.recorded_at)||'Fecha no disponible';
        node('small',time+' · Apertura '+seconds(item.open_ms)+' · Confirmación '+seconds(item.confirm_ms)+(item.width?' · '+item.width+'×'+item.height:''),row);
      }
      doc.getElementById('systemPrevious').disabled=index===0;doc.getElementById('systemNext').disabled=!data.more;doc.getElementById('systemPage').textContent=Number(stats.attempts)?String(index+1):'';
      root.querySelector('.system-pagination').hidden=!Number(stats.attempts);
    }catch(error){if(error.name!=='AbortError'&&current===revision){status.textContent=error.message;summary.replaceChildren();records.replaceChildren();root.querySelector('.system-pagination').hidden=true;}}
    finally{if(current===revision)doc.getElementById('refreshSystem').disabled=false;}
  }
  form.addEventListener('submit',e=>e.preventDefault());form.addEventListener('change',()=>{index=0;load();});
  doc.getElementById('refreshSystem').addEventListener('click',load);
  doc.getElementById('systemPrevious').addEventListener('click',()=>{if(index){index--;load();}});doc.getElementById('systemNext').addEventListener('click',()=>{index++;load();});
  const toggle=e=>{if(e.detail){index=0;load();}else{++revision;controller?.abort();}};
  const synced=()=>{if(!root.hidden)load();};window.addEventListener('repotools:scanner-metrics-synced',synced);
  doc.addEventListener('moderation:system',toggle);page?.onLeave(()=>{++revision;controller?.abort();doc.removeEventListener('moderation:system',toggle);window.removeEventListener('repotools:scanner-metrics-synced',synced);});
  if(!root.hidden)load();
})();
