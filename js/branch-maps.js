(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  if(!doc.getElementById('palletBranchDialog'))return;
  const instances=new Set();let left=false;
  function load(){
    let css=document.querySelector('link[data-leaflet]');
    if(!css){css=document.createElement('link');css.rel='stylesheet';css.href='js/vendor/leaflet/leaflet.css';css.dataset.leaflet='true';css.repoReady=new Promise((resolve,reject)=>{css.onload=resolve;css.onerror=()=>{css.remove();reject(Error('No pudimos abrir el mapa. Puedes buscar por nombre.'));};});document.head.append(css);}
    const ready=css.repoReady||Promise.resolve();
    if(window.L)return ready.then(()=>window.L);
    if(window.repoLeafletLoad)return Promise.all([ready,window.repoLeafletLoad]).then(([,L])=>L);
    window.repoLeafletLoad=new Promise((resolve,reject)=>{
      const script=document.createElement('script');script.src='js/vendor/leaflet/leaflet.js';script.onload=()=>resolve(window.L);script.onerror=()=>{script.remove();window.repoLeafletLoad=null;reject(Error('No pudimos abrir el mapa. Puedes buscar por nombre.'));};document.head.append(script);
    });return Promise.all([ready,window.repoLeafletLoad]).then(([,L])=>L);
  }
  const coords=b=>b?.latitude!==null&&b?.latitude!==undefined&&b?.latitude!==''&&b?.longitude!==null&&b?.longitude!==undefined&&b?.longitude!==''&&Number.isFinite(Number(b.latitude))&&Number.isFinite(Number(b.longitude));
  async function create(container,status,{branches=[],onSelect,onPoint,onBrowse,point=null}={}){
    const L=await load();if(left||!container.isConnected||!container.closest('dialog')?.open||container.closest('[hidden]'))return null;
    const map=L.map(container,{scrollWheelZoom:false,zoomAnimation:false,fadeAnimation:false}).setView([-34.6037,-58.3816],12),group=L.layerGroup().addTo(map);
    const markers=new Map();instances.add(map);let pin,userMarker,dead=false,geoVersion=0;
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,detectRetina:true,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'}).addTo(map).on('tileerror',()=>{if(!dead)status.textContent='No pudimos cargar parte del mapa. Puedes seguir buscando por nombre.';});
    function setPoint(lat,lng){if(pin)map.removeLayer(pin);pin=L.circleMarker([lat,lng],{radius:9,color:'#27187e',fillColor:'#7963d5',fillOpacity:1}).addTo(map);onPoint?.(lat,lng);status.textContent='Ubicación marcada.';}
    function update(list){group.clearLayers();markers.clear();const located=list.filter(coords);for(const b of located){const popup=document.createElement('div');const title=document.createElement('strong');title.textContent=b.name;popup.append(title);const address=document.createElement('p');address.textContent=[b.address,b.locality,b.province].filter(Boolean).join(', ');popup.append(address);if(onSelect){const button=document.createElement('button');button.type='button';button.className='library-primary';button.textContent='Seleccionar sucursal';button.addEventListener('click',()=>onSelect(b));popup.append(button);}const marker=L.circleMarker([Number(b.latitude),Number(b.longitude)],{radius:8,color:'#27187e',fillColor:'#7963d5',fillOpacity:.9}).addTo(group).bindPopup(popup);markers.set(String(b.id),marker);}return located;}
    const located=update(branches);if(coords(point)){setPoint(Number(point.latitude),Number(point.longitude));map.setView([Number(point.latitude),Number(point.longitude)],16);}else if(located.length)map.fitBounds(located.map(b=>[Number(b.latitude),Number(b.longitude)]),{padding:[25,25],maxZoom:15});
    if(onPoint||onBrowse)map.on('click',e=>{setPoint(e.latlng.lat,e.latlng.lng);onBrowse?.(e.latlng.lat,e.latlng.lng);});
    async function locate(){const request=++geoVersion;status.textContent='Buscando tu ubicación…';if(!navigator.geolocation){status.textContent='Tu navegador no ofrece ubicación. Puedes recorrer el mapa.';return;}
      navigator.geolocation.getCurrentPosition(pos=>{if(left||dead||request!==geoVersion)return;const center=[pos.coords.latitude,pos.coords.longitude];if(userMarker)map.removeLayer(userMarker);userMarker=L.circleMarker(center,{radius:6,color:'#fff',weight:2,fillColor:'#2787d9',fillOpacity:1,interactive:false}).addTo(map);map.setView(center,16);status.textContent=onPoint?'Toca el mapa para marcar el local.':'Toca una sucursal para seleccionarla.';},()=>{if(!left&&!dead&&request===geoVersion)status.textContent='No pudimos obtener tu ubicación. Puedes recorrer el mapa o buscar por nombre.';},{timeout:10000,maximumAge:60000,enableHighAccuracy:true});
    }
    status.textContent=onPoint?'Toca el mapa para marcar el local.':located.length?'Toca una sucursal para seleccionarla.':'Todavía no hay sucursales con ubicación en el mapa.';
    setTimeout(()=>{if(!dead&&!left)map.invalidateSize();},0);
    return {locate,update,openBranch:id=>markers.get(String(id))?.openPopup(),resize:()=>map.invalidateSize(),destroy:()=>{dead=true;geoVersion++;instances.delete(map);map.remove();}};
  }
  window.RepoBranchMaps={create,coords};
  page?.onLeave(()=>{left=true;for(const map of instances)map.remove();instances.clear();});
})();
