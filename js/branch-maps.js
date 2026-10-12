(() => {
  'use strict';
  const page=window.RepoNavigation?.page,doc=page?.document||document;
  if(!doc.getElementById('palletBranchDialog')&&!doc.getElementById('manageBranchDialog'))return;
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

  function vectorLoad(){
    let css=document.querySelector('link[data-maplibre]');
    if(!css){css=document.createElement('link');css.rel='stylesheet';css.href='js/vendor/maplibre/maplibre-gl.css';css.dataset.maplibre='true';document.head.append(css);}
    if(window.L.maplibreGL&&window.maplibregl)return Promise.resolve();
    if(window.repoVectorLoad)return window.repoVectorLoad;
    const script=src=>new Promise((resolve,reject)=>{const el=document.createElement('script');el.src=src;el.onload=resolve;el.onerror=()=>{el.remove();reject(Error('Mapa vectorial no disponible'));};document.head.append(el);});
    window.repoVectorLoad=script('js/vendor/maplibre/maplibre-gl.js').then(()=>script('js/vendor/maplibre/leaflet-maplibre-gl.js')).catch(e=>{window.repoVectorLoad=null;throw e;});
    return window.repoVectorLoad;
  }
  const coords=b=>b?.latitude!==null&&b?.latitude!==undefined&&b?.latitude!==''&&b?.longitude!==null&&b?.longitude!==undefined&&b?.longitude!==''&&Number.isFinite(Number(b.latitude))&&Number.isFinite(Number(b.longitude));
  async function create(container,status,{branches=[],onSelect,onPoint,onBrowse,point=null}={}){
    const L=await load();if(left||!container.isConnected||!container.closest('dialog')?.open||container.closest('[hidden]'))return null;
    const map=L.map(container,{scrollWheelZoom:window.matchMedia('(any-hover: hover) and (any-pointer: fine)').matches,dragging:true,doubleClickZoom:true,boxZoom:true,keyboard:true,zoomAnimation:false,fadeAnimation:false}).setView([-34.6037,-58.3816],12),group=L.layerGroup().addTo(map);
    const markers=new Map();let pin,userMarker,dead=false,geoVersion=0;
    const dispose=()=>{dead=true;geoVersion++;clearTimeout(timer);instances.delete(dispose);map.remove();};instances.add(dispose);
    let vector,timer,raster=false;
    function fallback(){if(dead||left||raster)return;raster=true;clearTimeout(timer);if(vector&&map.hasLayer(vector))map.removeLayer(vector);
      container.dataset.mapRenderer='raster';
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,detectRetina:true,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'}).addTo(map).on('tileerror',()=>{if(!dead)status.textContent='No pudimos cargar parte del mapa. Puedes buscar por nombre.';});
    }
    vectorLoad().then(()=>{
      if(dead||left)return;
      if(window.maplibregl.supported&&!window.maplibregl.supported({failIfMajorPerformanceCaveat:false})){fallback();return;}
      try{
        vector=L.maplibreGL({style:'https://tiles.openfreemap.org/styles/liberty',interactive:false}).addTo(map);
        const gl=vector.getMaplibreMap();timer=setTimeout(fallback,12000);
        gl.once('load',()=>{if(dead||raster)return;clearTimeout(timer);container.dataset.mapRenderer='vector';});
        gl.on('error',()=>{if(!gl.isStyleLoaded())fallback();});
        gl.getCanvas().addEventListener('webglcontextlost',fallback,{once:true});
      }catch(e){fallback();}
    }).catch(fallback);

    function setPoint(lat,lng){if(pin)map.removeLayer(pin);pin=L.circleMarker([lat,lng],{radius:9,color:'#27187e',fillColor:'#7963d5',fillOpacity:1}).addTo(map);onPoint?.(lat,lng);status.textContent='Ubicación marcada.';}
    function update(list){group.clearLayers();markers.clear();const located=list.filter(coords);for(const b of located){const popup=document.createElement('div');const title=document.createElement('strong');title.textContent=b.name;popup.append(title);const address=document.createElement('p');address.textContent=[b.address,b.locality,b.province].filter(Boolean).join(', ');popup.append(address);if(onSelect){const button=document.createElement('button');button.type='button';button.className='library-primary';button.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><span>Seleccionar sucursal</span>';button.addEventListener('click',()=>onSelect(b));popup.append(button);}const marker=L.marker([Number(b.latitude),Number(b.longitude)],{title:b.name,icon:L.divIcon({className:'branch-store-marker',html:'<span class="branch-store-pin"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 10h18l-2-6H5zM4 10v11h16V10M9 21v-7h6v7"/></svg></span>',iconSize:[40,44],iconAnchor:[20,40],popupAnchor:[0,-34]})}).addTo(group).bindPopup(popup);markers.set(String(b.id),marker);}return located;}
    const located=update(branches);if(coords(point)){setPoint(Number(point.latitude),Number(point.longitude));map.setView([Number(point.latitude),Number(point.longitude)],16);}else if(located.length)map.fitBounds(located.map(b=>[Number(b.latitude),Number(b.longitude)]),{padding:[25,25],maxZoom:15});
    if(onPoint||onBrowse)map.on('click',e=>{setPoint(e.latlng.lat,e.latlng.lng);onBrowse?.(e.latlng.lat,e.latlng.lng);});
    async function locate(){const request=++geoVersion;status.textContent='Buscando tu ubicación…';if(!navigator.geolocation){status.textContent='Tu navegador no ofrece ubicación. Puedes recorrer el mapa.';return;}
      navigator.geolocation.getCurrentPosition(pos=>{if(left||dead||request!==geoVersion)return;const center=[pos.coords.latitude,pos.coords.longitude];if(userMarker)map.removeLayer(userMarker);userMarker=L.circleMarker(center,{radius:6,color:'#fff',weight:2,fillColor:'#2787d9',fillOpacity:1,interactive:false}).addTo(map);map.setView(center,16);status.textContent=onPoint?'Toca el mapa para marcar el local.':'Toca una sucursal para seleccionarla.';},()=>{if(!left&&!dead&&request===geoVersion)status.textContent='No pudimos obtener tu ubicación. Puedes recorrer el mapa o buscar por nombre.';},{timeout:10000,maximumAge:60000,enableHighAccuracy:true});
    }
    status.textContent=onPoint?'Toca el mapa para marcar el local.':located.length?'Toca una sucursal para seleccionarla.':'Todavía no hay sucursales con ubicación en el mapa.';
    setTimeout(()=>{if(!dead&&!left)map.invalidateSize();},0);
    return {locate,update,openBranch:id=>markers.get(String(id))?.openPopup(),resize:()=>map.invalidateSize(),destroy:dispose};
  }
  window.RepoBranchMaps={create,coords};
  page?.onLeave(()=>{left=true;for(const dispose of instances)dispose();instances.clear();});
})();
