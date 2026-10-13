(() => {
 const groups='.main-scan-modes,.hand-options,.stock-tabs,.reports-tabs,.reports-state-tabs,.library-collections,.branch-picker-tabs,.branch-shortcuts,.share-mode-tabs,.tops-periods,.offline-tabs,.nav,.nav-celular';
 const panels='#reportsList,#collectionPanel,#palletProducts,#ownedPanel,#pendingPanel,#sharedSection,#adminPanel,#stockShareResult,#palletBranchPreview,#mainRapidPanel,#userWorkspace,#userProfile,#userDirectoryList,#topsRanking,#offlineContent,#stockSearchResults,.library-status,.report-photo-request,.report-more-data,.report-sender-data,.library-empty-notice';
 const ignored='.ean-reader,video,canvas,.branch-map,.leaflet-container,.maplibregl-map';
 function mount(body,page){
   let frame=0,disposed=false;const tracked=new Map(),dirty=new Set(),animations=new Set(),lastReveal=new WeakMap(),reduced=matchMedia('(prefers-reduced-motion: reduce)');
   let entered=false;
   const enterPage=()=>{if(entered||disposed)return;entered=true;body.querySelector('main')?.classList.add('app-page-enter');};
   window.addEventListener('repotools:navigated',enterPage);window.addEventListener('repotools:page-ready',enterPage);
   const stopAnimation=animation=>{animation.cancel();animations.delete(animation);};
   function reveal(node){
     if(/ScannerStatus|mainScannerStatus/.test(node.id)||node.closest('.repotools-send-toast')||disposed||reduced.matches||!node.isConnected||!node.getClientRects().length||node.closest(ignored)||node.getAttribute('aria-busy')==='true'||typeof node.animate!=='function')return;
     const now=performance.now();if(now-(lastReveal.get(node)||-1000)<300)return;lastReveal.set(node,now);
     const animation=node.animate([{opacity:.65,translate:'0 4px'},{opacity:1,translate:'0 0'}],{duration:220,easing:'cubic-bezier(.2,.7,.2,1)'});animations.add(animation);animation.finished.catch(()=>{}).finally(()=>animations.delete(animation));
   }
   function selected(group){
     return [...group.children].find(node=>node.matches('[aria-selected=true],[aria-pressed=true],[aria-current=page]')||node.matches('label')&&node.querySelector('input:checked'));
   }
   function update(group){
     if(!group.isConnected){resize?.unobserve(group);tracked.delete(group);return;}
     const item=selected(group),target=item?.matches('label')?item.querySelector('span'):item;
     if(!target||!target.getClientRects().length){group.classList.remove('motion-selector','motion-ready');return;}
     const state=tracked.get(group),rect=target.getBoundingClientRect(),outer=group.getBoundingClientRect();
     if(!state.background){const color=getComputedStyle(target).backgroundColor;state.background=color==='rgba(0, 0, 0, 0)'?'#27187e':color;}
     group.style.setProperty('--motion-selected-bg',state.background);
     group.style.setProperty('--motion-x',(rect.left-outer.left+group.scrollLeft-group.clientLeft)+'px');
     group.style.setProperty('--motion-y',(rect.top-outer.top+group.scrollTop-group.clientTop)+'px');
     group.style.setProperty('--motion-width',rect.width+'px');group.style.setProperty('--motion-height',rect.height+'px');
     group.classList.add('motion-selector');
     if(!group.classList.contains('motion-ready'))requestAnimationFrame(()=>{if(!disposed&&group.isConnected)group.classList.add('motion-ready');});
   }
   function flush(){frame=0;for(const group of dirty)update(group);dirty.clear();}
   function schedule(group){if(group)dirty.add(group);if(!frame&&!disposed)frame=requestAnimationFrame(flush);}
   const resize=typeof ResizeObserver==='function'?new ResizeObserver(entries=>entries.forEach(entry=>schedule(entry.target))):null;
   function register(group){if(tracked.has(group))return;const position=group.style.position;if(getComputedStyle(group).position==='static')group.style.position='relative';tracked.set(group,{position,background:null});resize?.observe(group);schedule(group);}
   function discover(root){if(root.nodeType!==1||root.closest(ignored))return;if(root.matches(groups))register(root);root.querySelectorAll(groups).forEach(register);}
   discover(body);
   const observer=new MutationObserver(records=>{
     for(const record of records){const target=record.target;if(target.nodeType!==1||target.closest(ignored))continue;
       if(record.type==='childList'){
         record.addedNodes.forEach(discover);
         const container=target.closest(panels);if(container&&record.addedNodes.length)reveal(container);
       }
       if(record.attributeName==='data-hand'){tracked.forEach((_,group)=>schedule(group));continue;}
       const group=target.closest(groups);if(group)schedule(group);
       if(record.attributeName==='open'&&target.open){target.querySelectorAll(groups).forEach(schedule);}
       if(record.attributeName==='hidden'&&!target.hidden&&(target.matches(panels)||target.closest('form')&&target.matches('div,fieldset,label')))reveal(target);
     }
   });
   observer.observe(body,{subtree:true,childList:true,attributes:true,attributeFilter:['aria-pressed','aria-selected','aria-current','hidden','open','data-hand']});
   const change=event=>schedule(event.target.closest?.(groups));
   const resizeAll=()=>tracked.forEach((_,group)=>schedule(group));
   const motionChanged=()=>{if(reduced.matches)animations.forEach(stopAnimation);resizeAll();};
   body.addEventListener('change',change);body.addEventListener('click',change);window.addEventListener('resize',resizeAll);document.fonts?.addEventListener?.('loadingdone',resizeAll);reduced.addEventListener?.('change',motionChanged);
   function cleanup(){disposed=true;window.removeEventListener('repotools:navigated',enterPage);window.removeEventListener('repotools:page-ready',enterPage);observer.disconnect();resize?.disconnect();cancelAnimationFrame(frame);animations.forEach(stopAnimation);body.removeEventListener('change',change);body.removeEventListener('click',change);window.removeEventListener('resize',resizeAll);document.fonts?.removeEventListener?.('loadingdone',resizeAll);reduced.removeEventListener?.('change',motionChanged);tracked.forEach((state,group)=>{group.classList.remove('motion-selector','motion-ready');group.style.position=state.position;});tracked.clear();dirty.clear();}
   if(!document.documentElement.classList.contains('is-loading'))enterPage();
   page?.onLeave(cleanup);return cleanup;
 }
 window.RepoMotion??={mount};window.RepoMotion.mount(document.body,window.RepoNavigation?.page);
})();
