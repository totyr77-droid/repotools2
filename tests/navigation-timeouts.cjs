const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const window=new EventTarget(),document=new EventTarget(),classes=new Set(),timers=new Map();let serial=0,stopped=0,closed=0,redirect;
document.body={querySelectorAll:selector=>selector==='video'?[{srcObject:{getTracks:()=>[{stop(){stopped++;}}]}}]:selector==='dialog[open]'?[{close(){closed++;}}]:[],querySelector:()=>null};document.scripts=[];
document.documentElement={dataset:{},classList:{add:value=>classes.add(value),remove:value=>classes.delete(value)}};
const context={window,document,location:{href:'https://example.invalid/index.php',origin:'https://example.invalid',assign:url=>redirect=url},history:{},URL,AbortController,AbortSignal,CSS:{escape:v=>v},setTimeout:(callback,delay)=>{const id=++serial;timers.set(id,{callback,delay});return id;},clearTimeout:id=>timers.delete(id),setInterval,clearInterval,fetch:()=>new Promise(()=>{})};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname,'../js/navigation.js'),'utf8'),context);
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function expire(delay){for(const [id,timer] of timers)if(timer.delay===delay){timers.delete(id);timer.callback();}}
(async()=>{const page=window.RepoNavigation.page;page.onLeave(()=>new Promise(()=>{}));const disposing=page.dispose();await tick();expire(3000);await disposing;assert.equal(stopped,1);assert.equal(closed,1);
const navigating=window.RepoNavigation.navigate('https://example.invalid/cuenta.php');assert.ok(classes.has('app-navigating'));await tick();expire(15000);await navigating;assert.equal(redirect,'https://example.invalid/cuenta.php');assert.equal(classes.has('app-navigating'),false);assert.equal(timers.size,0);console.log('OK: stalled cleanup stops cameras; stalled navigation falls back and clears the loading state');})().catch(e=>{console.error(e);process.exitCode=1;});
