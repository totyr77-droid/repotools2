(() => {
  function calculate(text) {
    const source=String(text).replace(/[×xX]/g,'*').replace(/−/g,'-').replace(/\s/g,'');
    if (!/^[0-9()+*/.\-]+$/.test(source)||source.length>80) throw Error('Escribe una cantidad o una cuenta, por ejemplo 8 × 5.');
    const tokens=source.match(/\d+(?:\.\d+)?|[()+*/-]/g); if(tokens.join('')!==source)throw Error('Revisa la cuenta.'); let i=0;
    function factor(){if(tokens[i]==='('){i++;const n=sum();if(tokens[i++]!==')')throw Error('Revisa los paréntesis.');return n;}if(!/^\d/.test(tokens[i]||''))throw Error('Completa la cuenta.');return Number(tokens[i++]);}
    function product(){let n=factor();while(['*','/'].includes(tokens[i])){const op=tokens[i++],v=factor();n=op==='*'?n*v:n/v;}return n;}
    function sum(){let n=product();while(['+','-'].includes(tokens[i])){const op=tokens[i++],v=product();n=op==='+'?n+v:n-v;}return n;}
    const result=sum();if(i!==tokens.length||!Number.isSafeInteger(result)||result<1||result>1000000)throw Error('El resultado debe ser un número entero entre 1 y 1.000.000.');return result;
  }
  function dateISO(value){if(!value.trim())return '';const m=/^(\d{2})\/(\d{2})\/(\d{2})$/.exec(value);if(!m)throw Error('Escribe el vencimiento completo: DD/MM/AA.');const year=2000+Number(m[3]),month=Number(m[2]),day=Number(m[1]),date=new Date(Date.UTC(year,month-1,day));if(date.getUTCFullYear()!==year||date.getUTCMonth()!==month-1||date.getUTCDate()!==day)throw Error('La fecha de vencimiento no es válida.');return `${year}-${m[2]}-${m[1]}`;}
  function dateDisplay(value){return value?`${value.slice(8,10)}/${value.slice(5,7)}/${value.slice(2,4)}`:'';}
  function expiryText(value,warningDays=30){if(!value)return {text:'Sin vencimiento',className:''};const today=new Intl.DateTimeFormat('en-CA',{timeZone:'America/Buenos_Aires',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());const days=Math.round((Date.parse(value+'T00:00:00Z')-Date.parse(today+'T00:00:00Z'))/86400000);return {days,text:days<0?`Vencido hace ${-days} día${days===-1?'':'s'}`:days===0?'Vence hoy':`Vence en ${days} día${days===1?'':'s'}`,className:days<0?'stock-expiry-past':days===0?'stock-expiry-today':days<=7?'stock-expiry-urgent':days<=warningDays?'stock-expiry-near':''};}
  function palletCode(value){if(window.RepoEANScanner?.palletIdentifier)return window.RepoEANScanner.palletIdentifier(value);const text=String(value).trim();if(/^\d{8}$/.test(text))return text;try{const url=new URL(text);const own=['repotools.online','www.repotools.online','repotools2.onrender.com'];const same=url.origin===location.origin,legacy=own.includes(location.hostname)&&own.includes(url.hostname)&&!url.port;if((!same&&!legacy)||!['http:','https:'].includes(url.protocol)||url.username||url.password||(!url.pathname.endsWith('/palet.php')&&!/\/q\/\d{8}\/?$/.test(url.pathname)))return null;const code=/\/q\/(\d{8})\/?$/.exec(url.pathname)?.[1]||url.searchParams.get('codigo');return /^\d{8}$/.test(code||'')?code:null;}catch{return null;}}
  function movementDate(value){return new Date(/Z$|\+\d\d:?\d\d$/.test(value)?value:value.replace(' ','T')+'Z').toLocaleString('es-AR',{timeZone:'America/Buenos_Aires',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'});}
  function ownerName(p){return p.owner_name+(p.owner_company?' ('+p.owner_company+')':'');}
  function expirySummary(p){const total=Number(p.expiry_count)||0;if(!total)return {text:'',className:''};if(p.expired_count==null||p.today_count==null)return {text:total+' con aviso de vencimiento',className:'stock-expiry-near'};const expired=Number(p.expired_count)||0,today=Number(p.today_count)||0,near=Math.max(0,total-expired-today);return {text:[expired?expired+(expired===1?' vencido':' vencidos'):'',today?today+(today===1?' vence hoy':' vencen hoy'):'',near?near+' por vencer':''].filter(Boolean).join(' · '),className:expired?'stock-expiry-past':today?'stock-expiry-today':'stock-expiry-near'};}
  window.RepoStockUtils={expirySummary,ownerName,movementDate,calculate,dateISO,dateDisplay,expiryText,palletCode};
})();
// Quantity editing is shared by connected/offline forms; identifiers keep leading zeros.
(()=>{if(typeof document==='undefined'||window.RepoQuantityInputs)return;window.RepoQuantityInputs=true;
const editable=node=>node?.matches?.('#stockAmount,#deductAmount,#offlineAmount,#stockPackSize,#deductPackSize,#offlinePackSize,#deductPack,#offlinePack,#labelCount')&&!node.readOnly&&!node.disabled;
document.addEventListener('focusin',event=>{if(editable(event.target)&&event.target.value==='0'){try{event.target.select();}catch{}};});
document.addEventListener('input',event=>{const field=event.target;if(editable(field)&&!event.isComposing&&/^0+\d+$/.test(field.value))field.value=field.value.replace(/^0+(?=\d)/,'');},true);
})();
