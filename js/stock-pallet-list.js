(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  // Only the visible presentation is incremental. The source inventory stays complete.
  const createList=(box,makeCard,user)=>{
    const batch=32,key='repotools:pallet-render:'+Number(user)+':'+box.id;
    let source=[],rows=[],limit=batch,filterKey='',options={},disposed=false,stored;
    try{stored=JSON.parse(sessionStorage.getItem(key)||'null');}catch{}
    const more=doc.createElement('button');more.type='button';more.className='library-secondary stock-pallet-more';
    more.textContent='Ver más palets';more.setAttribute('aria-controls',box.id);more.hidden=true;box.after(more);
    function remember(){try{sessionStorage.setItem(key,JSON.stringify({filterKey,limit:Math.min(limit,10000)}));}catch{}}
    function paint(reset=false){
      if(disposed)return;
      const focused=box.contains(doc.activeElement)?doc.activeElement?.closest('[data-code]')?.dataset.code:null;
      if(reset)box.replaceChildren();
      const fragment=doc.createDocumentFragment();
      for(let i=box.children.length;i<Math.min(limit,rows.length);i++){
        const card=makeCard(rows[i],fragment);options.decorate?.(card,rows[i]);
      }
      box.append(fragment);more.hidden=box.children.length>=rows.length;
      more.setAttribute('aria-label','Mostrar más palets · '+box.children.length+' de '+rows.length);
      if(focused)box.querySelector('[data-code="'+focused+'"]')?.focus({preventScroll:true});
    }
    function update(){rows=source.filter(options.filter||(()=>true));if(options.compare)rows.sort(options.compare);paint(true);}
    function advance(event){if(disposed||more.hidden)return;const firstNew=box.children.length;limit+=batch;paint();remember();if(event?.type==='click'&&event.detail===0)box.children[firstNew]?.focus({preventScroll:true});}
    more.addEventListener('click',advance);
    const observer=typeof IntersectionObserver==='function'?new IntersectionObserver(entries=>{
      if(entries.some(e=>e.isIntersecting)&&!more.hidden&&doc.activeElement!==more&&!box.closest('[hidden]'))advance();
    },{rootMargin:'400px 0px'}):null;
    observer?.observe(more);
    const list={set(items){if(disposed)return;source=items;update();},configure(next){
      if(disposed)return;
      const changed=next.key!==filterKey||next.tagVersion!==options.tagVersion||next.branchVersion!==options.branchVersion;
      if(next.key!==filterKey){filterKey=next.key;limit=stored?.filterKey===filterKey?Math.max(batch,Math.min(10000,Number(stored.limit)||batch)):batch;}
      options=next;if(changed)update();
    },get count(){return rows.length;},get total(){return source.length;},dispose(){disposed=true;observer?.disconnect();more.remove();source=[];rows=[];options={};}};
    page?.onLeave(list.dispose);return list;
  };
  window.RepoPalletList=createList;
  page?.onLeave(()=>{if(window.RepoPalletList===createList)delete window.RepoPalletList;});
})();
