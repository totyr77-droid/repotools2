const fs=require('fs'),vm=require('vm'),assert=require('assert/strict');const requests=[];
const context={window:{},document:{},navigator:{onLine:true},AbortController,setTimeout,clearTimeout,fetch:()=>new Promise(resolve=>requests.push(resolve))};vm.createContext(context);vm.runInContext(fs.readFileSync('js/product-info.js','utf8'),context);
(async()=>{
const info=context.window.RepoProductInfo,ean='96385074',old=info.get(ean);assert.equal(requests.length,1);info.invalidate(ean);const fresh=info.get(ean);assert.equal(requests.length,2);
requests[1]({ok:true,json:async()=>({product:{name:'Referencia confirmada'},can_edit:true,csrf:'fresh'})});await fresh;
requests[0]({ok:true,json:async()=>({product:null,can_edit:false,csrf:'old'})});assert.equal(await old,null);
assert.equal((await info.get(ean)).name,'Referencia confirmada','Late missing result cannot replace confirmed cache');assert.equal(info.permission(ean).canEdit,true,'Late missing result cannot replace current permissions');assert.equal(requests.length,2);
info.invalidate(ean);assert.equal(info.permission(ean),undefined);console.log('OK: invalidation refetches, late missing lookup cannot overwrite newer product or permission.');
})().catch(error=>{console.error(error);process.exitCode=1;});
