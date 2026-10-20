(() => {
  'use strict';
  window.RepoStockLive={create({document:doc=globalThis.document,request,context,canRefresh,apply,onError,interval=4000}){
    let state=null,timer=null,controller=null,running=false,disposed=false,sequence=0,failures=0;
    function schedule(delay=interval){clearTimeout(timer);if(state&&!disposed&&!doc.hidden)timer=setTimeout(tick,delay);}
    function stop(){state=null;sequence++;clearTimeout(timer);controller?.abort();}
    function watch(stamp=''){stop();state={stamp};failures=0;schedule();}
    async function tick(){
      if(disposed||!state||doc.hidden)return;
      if(running||!canRefresh()){schedule();return;}
      const token=sequence,expected=context(),current=state;running=true;controller=new AbortController();const timeout=setTimeout(()=>controller?.abort(),12000);
      try{
        const data=await request('?watch='+encodeURIComponent(expected.code)+'&stamp='+encodeURIComponent(current.stamp),controller.signal);
        if(token!==sequence||disposed||doc.hidden||!canRefresh())return;
        if(!/^[a-f0-9]{64}$/.test(data.watch_stamp||'')||typeof data.unchanged!=='boolean')throw Error('No pudimos verificar la actualización del palet.');
        if(data.unchanged||await apply(data,expected)){if(token===sequence)current.stamp=data.watch_stamp;}
        failures=0;
      }catch(error){if(token===sequence&&error.name!=='AbortError'){failures++;if([401,403,404,409].includes(error.status)){stop();await onError?.(error,expected);}}}
      finally{clearTimeout(timeout);running=false;controller=null;if(state&&!disposed)schedule(Math.min(30000,interval*2**Math.min(failures,3)));}
    }
    function wake(){if(state&&!disposed&&!running)schedule(0);}
    function visibility(){if(doc.hidden){clearTimeout(timer);controller?.abort();}else wake();}
    doc.addEventListener('visibilitychange',visibility);doc.addEventListener('close',wake,true);doc.addEventListener('focusout',wake);
    const connectivity=event=>{if(event.detail?.kind==='online')wake();};window.addEventListener('repotools:connectivity',connectivity);
    window.addEventListener('online',wake);window.addEventListener('focus',wake);
    function dispose(){disposed=true;stop();doc.removeEventListener('visibilitychange',visibility);doc.removeEventListener('close',wake,true);doc.removeEventListener('focusout',wake);window.removeEventListener('repotools:connectivity',connectivity);window.removeEventListener('online',wake);window.removeEventListener('focus',wake);}
    return {watch,stop,wake,dispose};
  }};
})();
