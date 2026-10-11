(()=>{'use strict';const page=window.RepoNavigation?.page,doc=page?.document||document,body=doc.body,root=document.documentElement;let saved;
function unlock(){if(!saved)return;const previous=saved;saved=null;for(const key of ['position','top','left','width','paddingRight'])body.style[key]=previous.styles[key];root.classList.remove('dialog-scroll-locked');window.scrollTo({left:previous.x,top:previous.y,behavior:'instant'});}
function update(){const open=body.querySelector('dialog[open]');if(!open){unlock();return;}if(saved)return;const styles=Object.fromEntries(['position','top','left','width','paddingRight'].map(key=>[key,body.style[key]]));saved={styles,x:window.scrollX,y:window.scrollY};const gap=window.innerWidth-root.clientWidth;if(gap>0)body.style.paddingRight=(parseFloat(getComputedStyle(body).paddingRight)||0)+gap+'px';body.style.position='fixed';body.style.top=-saved.y+'px';body.style.left=-saved.x+'px';body.style.width='100%';root.classList.add('dialog-scroll-locked');}
const observer=new MutationObserver(update);observer.observe(body,{subtree:true,childList:true,attributes:true,attributeFilter:['open']});update();
doc.addEventListener('touchmove',event=>{if(saved&&!event.target.closest?.('dialog[open]'))event.preventDefault();},{passive:false});
page?.onLeave(()=>{observer.disconnect();unlock();});
})();
