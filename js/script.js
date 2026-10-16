(() => {
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;
  const fetch = page?.fetch || globalThis.fetch?.bind(globalThis);

const els = {
  boton: document.getElementById("boton"),
  cantidad: document.getElementById("cantidad"),
  ver: document.getElementById("ver"),
  verb: document.getElementById("verb"),
  vers: document.getElementById("vers"),
  vera: document.getElementById("vera"),
  verc: document.getElementById("verc"),
  borrarD: document.getElementById("borrarD"),
  conterdormaxc: document.getElementById("conterdormaxc"),
  contenedormax: document.getElementById("contenedormax"),
  contenedor: document.getElementById("contenedor"),
  cartelScaner: document.getElementById("cartelScaner"),
  resultado: document.getElementById("resultado"),
  resultadoApi: document.querySelector(".resultado-api"),
  cantidadScan: document.getElementById("cantidadScan"),
  rescancelar: document.getElementById("rescancelar"),
  rescanear: document.getElementById("rescanear"),
  copy: document.getElementById("copy"),
  compartir: document.getElementById("compartir"),
  compartirD: document.getElementById("compartirD"),
  alerta: document.querySelector(".alerta"),
  alertaTitulo: document.getElementById("alertaTitulo"),
  alertaInfo: document.getElementById("alertaInfo"),
  barcode: document.getElementById("barcode"),
  veri: document.getElementById("veri"),
  cantidadBox: document.querySelector(".cantidadbox")
};

const STORAGE_KEY = "lista";
const EAN = /^(?:\d{8}|\d{13})$/;
const urlActual = new URL(window.location.href);
const urlOriginal = `${urlActual.origin}${urlActual.pathname}`;

let lista = cargarLista();
let indiceActual = 1;
let esListaCompartida = false;
let scannerRevision = 0;
let ultimoAgregado = null;
let resultadoActual = "";
let scanResultRevision = 0;
let viewerRevision = 0;
let rapidActive = false, rapidCount = 0;

const scanner = window.RepoEANScanner.create('contenedor', {
  onManual: abrirEntradaManual,
  keepOpen: () => rapidActive,
  scanContext: () => rapidActive ? 'Escaneo rápido de Inicio' : 'Escaneo normal de Inicio',
  feedbackPreferences: () => ({sound:document.body.dataset?.scanSound!=='0',vibration:document.body.dataset?.scanVibration!=='0'}),
  repeatCooldown: 1500,
  rearmRepeated: true,
  canAccept: () => !rapidActive || !document.querySelector('dialog[open]:not(#contenedormax)'),
  onCode: codigo => {
    const result = mostrarResultadoEscaneo(codigo);
    if (rapidActive) { result.catch(() => {}); return; }
    return result;
  },
  onStatus: text => { document.getElementById('mainScannerStatus').textContent = rapidActive && text === 'Código confirmado.' ? 'Centrá el próximo EAN.' : text; },
  onError: mostrarErrorScanner
});

inicializar();
page?.onLeave(() => { rapidActive = false; ++scannerRevision; ++scanResultRevision; return scanner.stop(); });

function inicializar() {
  cargarListaCompartida();
  actualizarContadores();
  bindEventos();
}

function bindEventos() {
  document.getElementById('mainScanNormal')?.addEventListener('click', () => cambiarModoRapido(false));
  document.getElementById('mainScanRapid')?.addEventListener('click', () => cambiarModoRapido(true));
  document.getElementById('mainRapidFinish')?.addEventListener('click', () => {cerrarScanner();if(lista.length)abrirResultados();});
  els.borrarD?.addEventListener("click", borrarTodo);
  els.ver?.addEventListener("click", abrirResultados);
  els.cantidadBox?.addEventListener("click", () => {
    cerrarScanner();
    abrirResultados();
  });
  els.conterdormaxc?.addEventListener("click", cerrarScanner);
  els.vers?.addEventListener("click", () => moverResultado(1));
  els.vera?.addEventListener("click", () => moverResultado(-1));
  els.verc?.addEventListener("click", cerrarResultados);
  els.boton?.addEventListener("click", abrirScanner);
  els.rescancelar?.addEventListener("click", cancelarUltimoEscaneo);
  els.rescanear?.addEventListener("click", iniciarScanner);
  els.copy?.addEventListener("click", copiarResultado);
  els.compartir?.addEventListener("click", compartirLista);
  els.compartirD?.addEventListener("click", compartirLista);
  document.getElementById('copyCurrentEan')?.addEventListener('click', async () => { const ean=lista[indiceActual-1]; if(!ean)return; try { await navigator.clipboard.writeText(ean); window.RepoToast?.('EAN copiado.'); } catch { window.RepoToast?.('No se pudo copiar el EAN.',{kind:'error'}); } });
  document.getElementById('shareCurrentEan')?.addEventListener('click', () => { const ean = lista[indiceActual - 1]; if (ean) compartirCodigos([ean]); });
  document.getElementById('openResultInfo')?.addEventListener('click', () => {
    const ean = lista[indiceActual - 1]; if (!ean) return;
    const dialog = document.getElementById('resultInfoDialog'); document.getElementById('resultInfoEan').textContent = `EAN ${ean}`;
    if (!dialog.open) dialog.showModal(); window.RepoProductInfo.show(document.getElementById('resultProductDetails'), ean);
  });
  document.getElementById('closeResultInfo')?.addEventListener('click', () => document.getElementById('resultInfoDialog').close());
  els.verb.addEventListener('cancel', event => { event.preventDefault(); cerrarResultados(); });
  (page ? page.listen : window.addEventListener.bind(window))('keydown', event => {
    if (!els.verb.open || document.getElementById('resultInfoDialog').open || /INPUT|TEXTAREA|SELECT/.test(event.target.tagName)) return;
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); moverResultado(event.key === 'ArrowLeft' ? -1 : 1); }
  });
  const swipeArea = document.getElementById('resultSwipeArea'); let touchStart;
  swipeArea?.addEventListener('touchstart', event => { touchStart = event.touches.length === 1 ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null; }, { passive: true });
  swipeArea?.addEventListener('touchend', event => {
    if (!touchStart || event.changedTouches.length !== 1) return;
    const dx = event.changedTouches[0].clientX - touchStart.x, dy = event.changedTouches[0].clientY - touchStart.y; touchStart = null;
    if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) moverResultado(dx < 0 ? 1 : -1);
  }, { passive: true });
  swipeArea?.addEventListener('touchcancel', () => { touchStart = null; }, { passive: true });

  document.getElementById('retryMainScanner')?.addEventListener('click', iniciarScanner);
  els.contenedormax.addEventListener('cancel', event => { event.preventDefault(); cerrarScanner(); });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && els.contenedormax.open) cerrarScanner();
  });
  (page ? page.listen : window.addEventListener.bind(window))('pagehide', cerrarScanner);
}

function cargarLista() {
  const guardado = localStorage.getItem(STORAGE_KEY);

  if (!guardado) return [];

  try {
    const parseado = JSON.parse(guardado);
    if (Array.isArray(parseado)) return limpiarLista(parseado);
  } catch (error) {
    return limpiarLista(guardado.split(","));
  }

  return [];
}

function guardarLista() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(lista));
}

function limpiarLista(valores) {
  return valores
    .map((codigo) => String(codigo).trim())
    .filter((codigo, index, arr) => window.RepoEANScanner.validEAN(codigo) && arr.indexOf(codigo) === index);
}

function cargarListaCompartida() {
  if(Array.isArray(window.repotoolsSharedCodes)){lista=limpiarLista(window.repotoolsSharedCodes);delete window.repotoolsSharedCodes;if(lista.length){esListaCompartida=true;guardarLista();abrirResultados();}return;}
  if (!urlActual.search || !window.LZString) return;

  try {
    const comprimido = urlActual.search.substring(1);
    const descomprimido = LZString.decompressFromBase64(comprimido);
    const compartida = JSON.parse(descomprimido);
    const listaLimpia = limpiarLista(Array.isArray(compartida) ? compartida : []);

    if (!listaLimpia.length) throw new Error("Lista compartida vacia");

    lista = listaLimpia;
    esListaCompartida = true;
    guardarLista();
    abrirResultados();
  } catch (error) {
    alertaGeneral("ERROR", "El enlace compartido no se pudo leer.");
  }
}

function actualizarContadores() {
  if (els.cantidad) els.cantidad.textContent = lista.length;
  if (els.cantidadScan) els.cantidadScan.textContent = lista.length;
  if (els.ver) els.ver.disabled = lista.length === 0;
  if (els.compartir) els.compartir.disabled = lista.length === 0;
  if (els.compartirD) els.compartirD.disabled = lista.length === 0;
  if (els.borrarD) els.borrarD.disabled = lista.length === 0;
  renderListaPalets();
}

function renderListaPalets() {
  const listaContainer = document.getElementById("lista-palets-contenido");
  if (!listaContainer) return;

  listaContainer.innerHTML = lista.length
    ? lista
        .map(
          (codigo, index) =>
            `<div class="lista-palets__item"><span class="lista-palets__index">${index + 1}</span><span>${codigo}</span></div>`
        )
        .join("")
    : `<p class="lista-palets__empty">No hay palets creados aun. Escanea uno para comenzar.</p>`;
}

async function borrarTodo() {
  if (!lista.length) return;
  const confirmed = await window.RepoConfirm({
    title: '¿Borrar la lista?',
    message: 'Se quitarán todos los códigos de esta lista.',
    confirmLabel: 'Borrar lista'
  });
  if (!confirmed) return;

  lista = [];
  indiceActual = 1;
  ultimoAgregado = null;
  resultadoActual = "";
  guardarLista();
  actualizarContadores();
}

function abrirResultados() {
  if (!lista.length) {
    alertaGeneral("INFORMACION", "Primero escanea algun producto.");
    return;
  }

  indiceActual = Math.min(Math.max(indiceActual, 1), lista.length);
  mostrarOverlay();
  verCodigo(indiceActual);
  document.body.classList.add("no-scroll");
}

function verCodigo(indice) {
  if (!lista.length) return;

  indiceActual = Math.min(Math.max(indice, 1), lista.length);
  els.verb.style.display = "flex";
  if (!els.verb.open) els.verb.showModal();
  JsBarcode("#barcode", lista[indiceActual - 1], {
    format: lista[indiceActual - 1].length === 8 ? "EAN8" : "EAN13",
    displayValue: true,
    margin: 10
  });
  els.veri.textContent = `${indiceActual} / ${lista.length}`;els.veri.hidden=lista.length<2;document.getElementById('resultSwipeHint').hidden=lista.length<2;document.getElementById('resultNavigation').hidden=lista.length<2;
  els.vers.className = "library-primary";
  els.vera.className = "library-secondary";
  els.vers.disabled = indiceActual === lista.length;
  els.vera.disabled = indiceActual === 1;
  document.getElementById('resultShareStatus').textContent = '';
  els.compartirD.textContent = `Compartir lista completa (${lista.length})`;
  const ean = lista[indiceActual - 1], version = ++viewerRevision, summary = document.getElementById('resultProductSummary'); summary.textContent = 'Buscando producto…';
  window.RepoProductInfo.get(ean).then(product => {
    if (version === viewerRevision && els.verb.open) window.RepoProductInfo.renderSummary(summary, product);
  }).catch(() => { if (version === viewerRevision && els.verb.open) summary.textContent = 'Nombre y contenido no disponibles'; });
}

function moverResultado(direccion) {
  const nuevoIndice = indiceActual + direccion;
  if (nuevoIndice < 1 || nuevoIndice > lista.length) return;
  verCodigo(nuevoIndice);
}

function cerrarResultados() {
  ++viewerRevision;
  if (els.verb.open) els.verb.close();
  const info = document.getElementById('resultInfoDialog'); if (info.open) info.close();
  window.RepoProductInfo.clear(document.getElementById('resultProductDetails'));
  els.verb.style.display = "none";
  document.body.classList.remove("no-scroll");
  quitarOverlay();
  els.ver?.focus?.();

  if (esListaCompartida) {
    window.history.replaceState({}, document.title, urlOriginal);
    esListaCompartida = false;
  }
}

function abrirScanner() {
  const oldNotice=document.getElementById('scanLibraryStatus');if(oldNotice)oldNotice.textContent='';
  rapidActive = false;
  actualizarModoRapido();
  if (!els.contenedormax.open) els.contenedormax.showModal();
  iniciarScanner();
}

function actualizarModoRapido() {
  els.contenedormax.dataset.scanMode = rapidActive ? 'rapid' : 'normal';
  document.getElementById('mainScanNormal')?.setAttribute('aria-pressed', String(!rapidActive));
  document.getElementById('mainScanRapid')?.setAttribute('aria-pressed', String(rapidActive));
  const panel = document.getElementById('mainRapidPanel'); if (panel) panel.hidden = !rapidActive;
}
function cambiarModoRapido(enabled) {
  if (enabled === rapidActive) return;
  rapidActive = enabled;
  if (enabled) {
    rapidCount = 0;
    document.getElementById('mainRapidCount').textContent = 'Escaneados: 0';document.getElementById('mainRapidOutcome').textContent='';
    document.getElementById('mainRapidLast').textContent = 'Listo para escanear';
    document.getElementById('mainRapidCode').textContent = '';
    document.getElementById('mainRapidActions').replaceChildren();
    document.getElementById('mainRapidPanel').dataset.state = 'idle';
    document.getElementById('mainScannerTitle').textContent = 'Escaneo rápido';
  }
  actualizarModoRapido();
  // The existing live stream can switch to keepOpen without restarting it.
  if (!enabled || els.contenedor.hidden || window.RepoScanMethods?.isMobile === false) iniciarScanner();
}
function mostrarProductoRapido(product) {
  const host=document.getElementById('mainRapidLast');host.replaceChildren();
  if(product&&typeof product==='object')host.append(camposProducto(product));
  else {host.textContent=typeof product==='string'?'No pudimos consultar el producto':'Sin información. Tocá Reportar para completar los datos.';document.getElementById('mainRapidPanel').dataset.state='warning';}
  host.title=host.textContent;
}

async function iniciarScanner() {
  const revision = ++scannerRevision;
  delete els.contenedormax.dataset.hasResult;
  scanResultRevision++;
  resultadoActual = '';
  ultimoAgregado = null;
  els.copy.disabled = true;
  document.getElementById('mainScannerTitle').textContent = rapidActive ? 'Escaneo rápido' : 'Escanea el producto';
  document.getElementById('scanProductActions')?.replaceChildren();
  els.cartelScaner.hidden = true;
  document.getElementById('scanResultHeading').textContent = '';
  els.cartelScaner.style.display = 'none';
  els.contenedor.hidden = false;
  document.getElementById('mainScannerStatus').hidden = false;
  document.getElementById('retryMainScanner').hidden = true;
  document.getElementById('scanCopyStatus').textContent = '';
  window.RepoProductInfo.clear(els.resultadoApi);
  els.resultado.textContent = '';
  try { await scanner.start(); }
  catch { if (revision === scannerRevision && els.contenedormax.open) mostrarErrorScanner(); }
}

function mostrarErrorScanner() {
  document.getElementById('mainScannerStatus').textContent = 'No pudimos abrir la cámara. Revisa el permiso e intenta de nuevo.';
  document.getElementById('retryMainScanner').hidden = false;
}

function cerrarScanner() {
  const total = rapidActive ? rapidCount : null;
  rapidActive = false; actualizarModoRapido();
  scannerRevision++;
  scanResultRevision++;
  resultadoActual = '';
  els.copy.disabled = true;
  els.contenedormax.close();
  scanner.stop().catch(() => {});
  if (total !== null) window.RepoToast?.(`Escaneados: ${total}`);
}

function camposProducto(product){
    const fields=document.createElement('dl');fields.className='scan-product-fields';
    for(const [label,value] of [['Marca',product.brand],['Producto',product.name],['Contenido / gramaje',product.quantity]]){const term=document.createElement('dt'),detail=document.createElement('dd');term.textContent=label;detail.textContent=typeof value==='string'&&value.trim()?value.trim():'Sin información';if(detail.textContent==='Sin información')detail.className='scan-field-missing';fields.append(term,detail);}return fields;
}

function mostrarFichaEscaneada(product,ean=resultadoActual,revision=scanResultRevision) {
  const host=els.resultadoApi;host.replaceChildren();host.dataset.lookupState=product&&typeof product==='object'?'found':typeof product==='string'?'error':'missing';
  if(host.dataset.lookupState==='found'){
    host.append(camposProducto(product));
  }else{
    const title=document.createElement('strong'),hint=document.createElement('p');title.textContent=host.dataset.lookupState==='missing'?'No tenemos información de este producto':'No pudimos consultar la información del producto';hint.textContent=host.dataset.lookupState==='missing'?'Podés ayudarnos a completarla tocando Reportar.':'Intentá consultar nuevamente cuando tengas conexión.';host.append(title,hint);
    if(host.dataset.lookupState==='error'){const retry=document.createElement('button');retry.type='button';retry.className='library-secondary';retry.textContent='Volver a consultar';retry.addEventListener('click',async()=>{retry.disabled=true;let result;try{result=await window.RepoProductInfo.get(ean,{fresh:true});}catch{result='error';}if(revision===scanResultRevision&&els.contenedormax.open&&resultadoActual===ean)mostrarFichaEscaneada(result,ean,revision);});host.append(retry);}
  }
}

async function mostrarResultadoEscaneo(codigo,lectura=true) {
  if (!els.contenedormax.open || !window.RepoEANScanner.validEAN(codigo)) return;
  const revision = ++scanResultRevision;
  if(lectura)registrarEscaneoTop(codigo);
  const esDuplicado = lista.includes(codigo);
  resultadoActual = codigo;
  ultimoAgregado = esDuplicado ? null : codigo;

  if (!esDuplicado) {
    lista.push(codigo);
    guardarLista();
  }
  indiceActual = lista.indexOf(codigo) + 1;

  if (rapidActive) {
    rapidCount++;document.getElementById('mainRapidOutcome').textContent=esDuplicado?'Ya está en tu lista. No se agregó otra vez.':'Producto agregado a tu lista.';
    document.getElementById('mainRapidCount').textContent = `Escaneados: ${rapidCount}`;
    document.getElementById('mainRapidCode').textContent = codigo;
    document.getElementById('mainRapidLast').textContent = esDuplicado ? 'Ya está en tu lista' : 'Producto agregado';
    document.getElementById('mainRapidPanel').dataset.state = esDuplicado ? 'warning' : 'success';
    const actions = document.getElementById('mainRapidActions'); actions.replaceChildren();
    window.RepoProductInfo.editButton(actions, codigo);
    actualizarContadores();
    const input = els.contenedor.querySelector?.('input'); if (input) { input.value = ''; input.focus(); }
    const product = await buscarProductoPorEAN(codigo);
    if (revision !== scanResultRevision || !rapidActive || !els.contenedormax.open) return;
    mostrarProductoRapido(product);
    actions.replaceChildren(); window.RepoProductInfo.editButton(actions, codigo);
    return;
  }

  els.contenedormax.dataset.hasResult='true';
  els.contenedor.hidden = true;
  document.getElementById('mainScannerStatus').textContent='Lectura confirmada.';
  document.getElementById('mainScannerStatus').hidden = false;
  els.copy.disabled = false;
  els.cartelScaner.hidden = false;
  els.cartelScaner.style.display = 'flex';
  els.resultado.textContent = codigo;
  const heading = document.getElementById("scanResultHeading");
  document.getElementById('mainScannerTitle').textContent = esDuplicado ? 'Ya está en tu lista' : 'Producto escaneado';
  if (heading) {heading.parentElement.hidden=!esDuplicado;heading.textContent='Este código ya está en tu lista. No lo agregamos otra vez.';heading.parentElement.dataset.state='warning';}
  document.getElementById('scanProductActions')?.replaceChildren();
  els.rescancelar.style.display = esDuplicado ? "none" : "block";
  actualizarContadores();

  els.resultadoApi.dataset.lookupState='loading';els.resultadoApi.textContent = "Buscando producto…";
  const resultadoAPI = await buscarProductoPorEAN(codigo);
  if (revision !== scanResultRevision) return;
  mostrarFichaEscaneada(resultadoAPI,codigo,revision);
  window.RepoProductInfo.editButton(document.getElementById('scanProductActions'), codigo);
}

async function registrarEscaneoTop(codigo) {
  const account = window.repotoolsStats;
  if (!account) return;
  const status = document.getElementById("scanStatsStatus");
  const event = crypto.randomUUID();
  // Reutilizar el evento en el reintento evita contar dos veces una misma lectura.
  for (let attempt = 0; attempt < 2; attempt++) {
    try {
      const response = await fetch("api/escaneos.php", {
        method: "POST",
        body: new URLSearchParams({ csrf: account.csrf, code: codigo, event }),
        credentials: "same-origin"
      });
      const data = await response.json();
      if (!response.ok) {
        if (response.status >= 500 && attempt === 0) continue;
        throw new Error(data.error || "No se pudo registrar el escaneo en TOPs.");
      }
      if (status) status.textContent = `${data.unique_codes} códigos únicos · ${data.total_scans} escaneos totales`;
      return;
    } catch (error) {
      if (attempt === 1) {
        if (status) status.textContent = `${error.message} El código sigue guardado en tu lista local.`;
      }
    }
  }
}

function cancelarUltimoEscaneo() {
  if (!ultimoAgregado) return;
  scanResultRevision++;

  lista = lista.filter((codigo) => codigo !== ultimoAgregado);
  ultimoAgregado = null;
  guardarLista();
  actualizarContadores();
  const heading = document.getElementById("scanResultHeading");
  document.getElementById('mainScannerTitle').textContent = 'Producto quitado';
  if (heading) {heading.textContent = 'Producto quitado de tu lista.';heading.parentElement.dataset.state='warning';}
  els.resultadoApi.textContent = "El código se quitó de tu lista.";
  els.rescancelar.style.display = "none";
}

async function copiarResultado() {
  // Captura el EAN visible antes de esperar a que termine el portapapeles.
  const code = els.resultado.textContent.trim();
  const revision = scanResultRevision;
  if (!els.contenedormax.open || code !== resultadoActual || !window.RepoEANScanner.validEAN(code)) return;
  const status = document.getElementById('scanCopyStatus');
  try {
    await navigator.clipboard.writeText(code);
    if (revision === scanResultRevision) status.textContent = 'EAN copiado: ' + code;
  } catch {
    if (revision === scanResultRevision) status.textContent = 'No se pudo copiar. Mantén pulsado el EAN para seleccionarlo.';
  }
}

async function compartirLista() {
  if (!lista.length) {
    alertaGeneral("ERROR", "No hay codigos para compartir.");
    return;
  }

  return compartirCodigos([...lista]);
}
async function compartirCodigos(codigos) {
  let link = crearLinkCompartido(codigos);
  if(navigator.onLine){try{const response=await fetch('api/listas.php',{method:'POST',body:new URLSearchParams({csrf:window.repotoolsListCSRF,codes:JSON.stringify(codigos)})}),data=await response.json();if(!response.ok||!data.url)throw Error(data.error||'No pudimos crear el enlace.');link=data.url;}catch{window.RepoToast?.('Usamos el enlace completo. Tu lista se conserva.',{kind:'info'});}}


  if (navigator.share) {
    try {
      await navigator.share({
        title: codigos.length === 1 ? `EAN ${codigos[0]}` : 'Repotools - Lista de códigos',
        text: codigos.length === 1 ? `Código EAN: ${codigos[0]}` : `${codigos.length} códigos escaneados`,
        url: link
      });
      return;
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.warn("No se pudo compartir con Web Share", error);
    }
  }

  return fallbackCompartir(link);
}

function crearLinkCompartido(codigos = lista) {
  const json = JSON.stringify(codigos);
  const comprimido = LZString.compressToBase64(json);
  return new URL('index.php',document.baseURI).href+'?'+comprimido;
}

async function fallbackCompartir(link) {
  let copied = false;
  try {
    await navigator.clipboard.writeText(link);
    copied = true;
  } catch (error) {
    console.warn("No se pudo copiar el enlace", error);
  }
  if (els.verb.open) { document.getElementById('resultShareStatus').textContent = copied ? 'Enlace copiado. Puedes pegarlo para compartir.' : `Copia este enlace para compartir: ${link}`; return; }

  const mensaje = encodeURIComponent(`Mira este resultado de RepoTools: ${link}`);
  const whatsapp = `https://wa.me/?text=${mensaje}`;
  const telegram = `https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent("Mira este resultado de RepoTools")}`;
  const email = `mailto:?subject=Resultados%20RepoTools&body=${mensaje}`;

  alertaGeneral("ENLACE LISTO", `
    <p>${copied ? 'Copiamos el enlace. También puedes enviarlo desde acá:' : 'Elige cómo compartir el enlace:'}</p>
    <div class="social">
      <a href="${whatsapp}" target="_blank" rel="noopener" aria-label="Compartir por WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
      <a href="${telegram}" target="_blank" rel="noopener" aria-label="Compartir por Telegram"><i class="fa-brands fa-telegram"></i></a>
      <a href="${email}" aria-label="Compartir por email"><i class="fa-solid fa-envelope"></i></a>
    </div>
  `, true);
}

async function buscarProductoPorEAN(ean) {
  try {
    const product = await window.RepoProductInfo.get(ean);
    return product;
  } catch (error) {
    return "No pudimos cargar la información del producto.";
  }
}

function mostrarOverlay() {
  if (!document.getElementById("overlayModal")) {
    const overlay = document.createElement("div");
    overlay.id = "overlayModal";
    document.body.appendChild(overlay);
  }

  document.body.classList.add("no-scroll-overlay");
}

function quitarOverlay() {
  const overlay = document.getElementById("overlayModal");
  if (overlay) overlay.remove();
  document.body.classList.remove("no-scroll-overlay");
}

function alertaGeneral(titulo, descripcion, esHtml = false) {
  mostrarOverlay();
  els.alertaTitulo.textContent = titulo || "INFORMACION";

  if (esHtml) {
    els.alertaInfo.innerHTML = descripcion;
  } else {
    els.alertaInfo.textContent = descripcion;
  }

  els.alerta.classList.add("activo");
}

function cerrarAlerta() {
  els.alerta.classList.remove("activo");
  els.alertaInfo.textContent = "";
  quitarOverlay();
}

window.alertaGeneral = alertaGeneral;
window.cerrarAlerta = cerrarAlerta;
window.quitarOverlay = quitarOverlay;
(page ? page.listen : window.addEventListener.bind(window))('repotools:product-updated', event => {
  const { ean, product } = event.detail;
  if (els.contenedormax.open && resultadoActual === ean) {
    if (rapidActive) { mostrarProductoRapido(product); return; }
    mostrarFichaEscaneada(product);
    document.getElementById('scanProductActions')?.replaceChildren();
    window.RepoProductInfo.editButton(document.getElementById('scanProductActions'), ean);
  }
  if (els.verb.style.display === 'flex' && lista[indiceActual - 1] === ean) {
    window.RepoProductInfo.renderSummary(document.getElementById('resultProductSummary'), product);
    if (document.getElementById('resultInfoDialog').open) window.RepoProductInfo.show(document.getElementById('resultProductDetails'), ean);
  }
});

async function abrirEntradaManual(){
  await scanner.stop();
  const dialog=document.createElement('dialog');dialog.className='library-dialog';
  dialog.innerHTML='<div class="library-heading"><h2>Ingresar EAN</h2><button class="app-close" type="button" aria-label="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div><form><label>Código EAN<input inputmode="numeric" maxlength="13" autocomplete="off" placeholder="EAN de 8 o 13 números" required></label><p role="status" class="library-status"></p><button class="library-primary" type="submit">Agregar producto</button></form>';
  document.body.append(dialog);let accepted=false;
  dialog.querySelector('.app-close').addEventListener('click',()=>dialog.close());
  dialog.addEventListener('close',()=>{dialog.remove();if(!accepted&&els.contenedormax.open)iniciarScanner();},{once:true});
  dialog.querySelector('form').addEventListener('submit',e=>{e.preventDefault();const code=dialog.querySelector('input').value.trim();if(!window.RepoEANScanner.validEAN(code)){dialog.querySelector('[role=status]').textContent='Ingresa un EAN válido de 8 o 13 números.';return;}accepted=true;dialog.close();mostrarResultadoEscaneo(code,false);if(rapidActive)scanner.start().catch(mostrarErrorScanner);});dialog.showModal();
}

})();
