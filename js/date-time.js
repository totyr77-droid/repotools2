(()=>{'use strict';
function parse(value){
 if(typeof value!=='string')return null;
 const match=value.trim().match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(:\d{2})?(\.\d+)?(Z|[+-]\d{2}(?::?\d{2})?)?$/i);
 if(!match)return null;
 let zone=match[5]||'Z';if(zone.toUpperCase()==='Z')zone='Z';else{zone=zone.replace(':','');zone=zone.slice(0,3)+':'+(zone.slice(3)||'00');}
 const fraction=match[4]?match[4].slice(0,4):'';
 const date=new Date(match[1]+'T'+match[2]+(match[3]||':00')+fraction+zone);
 return Number.isFinite(date.getTime())?date:null;
}
function stamp(value){const date=parse(value);return date?date.toLocaleString('es-AR',{timeZone:'America/Buenos_Aires',dateStyle:'short',timeStyle:'short',hourCycle:'h23'}):'Fecha no disponible';}
window.RepoDateTime={parse,stamp};
})();
