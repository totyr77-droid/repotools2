const snd = new Audio("sonido/sonido.mp3");

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

const scanner = window.RepoEANScanner.create('contenedor', {
  onCode: codigo => {
    const result = mostrarResultadoEscaneo(codigo);
    snd.play().catch(() => {});
    return result;
  },
  onStatus: text => { document.getElementById('mainScannerStatus').textContent = text; },
  onError: mostrarErrorScanner
});

inicializar();

function inicializar() {
  cargarListaCompartida();
  actualizarContadores();
  bindEventos();
}

function bindEventos() {
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

  document.getElementById('retryMainScanner')?.addEventListener('click', iniciarScanner);
  els.contenedormax.addEventListener('cancel', event => { event.preventDefault(); cerrarScanner(); });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && els.contenedormax.open) cerrarScanner();
  });
  window.addEventListener('pagehide', cerrarScanner);
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
    .filter((codigo, index, arr) => EAN.test(codigo) && arr.indexOf(codigo) === index);
}

function cargarListaCompartida() {
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

function borrarTodo() {
  if (!lista.length) {
    alertaGeneral("ERROR", "No hay codigos para borrar.");
    return;
  }

  lista = [];
  indiceActual = 1;
  ultimoAgregado = null;
  resultadoActual = "";
  guardarLista();
  actualizarContadores();
  alertaGeneral("INFORMACION", "Productos borrados.");
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
  JsBarcode("#barcode", lista[indiceActual - 1], {
    format: lista[indiceActual - 1].length === 8 ? "EAN8" : "EAN13",
    displayValue: true,
    margin: 10
  });
  els.veri.textContent = `${indiceActual} / ${lista.length}`;
  els.vers.className = indiceActual < lista.length ? "vers si" : "vers no";
  els.vera.className = indiceActual > 1 ? "vera si" : "vera no";
  els.vers.disabled = indiceActual === lista.length;
  els.vera.disabled = indiceActual === 1;
  window.RepoProductInfo?.show(document.getElementById("resultProductDetails"), lista[indiceActual - 1]);
}

function moverResultado(direccion) {
  const nuevoIndice = indiceActual + direccion;
  if (nuevoIndice < 1 || nuevoIndice > lista.length) return;
  verCodigo(nuevoIndice);
}

function cerrarResultados() {
  els.verb.style.display = "none";
  document.body.classList.remove("no-scroll");
  quitarOverlay();

  if (esListaCompartida) {
    window.history.replaceState({}, document.title, urlOriginal);
    esListaCompartida = false;
  }
}

function abrirScanner() {
  if (!els.contenedormax.open) els.contenedormax.showModal();
  iniciarScanner();
}

async function iniciarScanner() {
  const revision = ++scannerRevision;
  scanResultRevision++;
  resultadoActual = '';
  ultimoAgregado = null;
  els.copy.disabled = true;
  els.cartelScaner.style.display = 'none';
  els.contenedor.hidden = false;
  document.getElementById('mainScannerStatus').hidden = false;
  document.getElementById('retryMainScanner').hidden = true;
  document.getElementById('scanCopyStatus').textContent = '';
  els.resultado.textContent = '';
  try { await scanner.start(); }
  catch { if (revision === scannerRevision && els.contenedormax.open) mostrarErrorScanner(); }
}

function mostrarErrorScanner() {
  document.getElementById('mainScannerStatus').textContent = 'No pudimos abrir la cámara. Revisa el permiso e intenta de nuevo.';
  document.getElementById('retryMainScanner').hidden = false;
}

function cerrarScanner() {
  scannerRevision++;
  scanResultRevision++;
  resultadoActual = '';
  els.copy.disabled = true;
  els.contenedormax.close();
  scanner.stop().catch(() => {});
}

async function mostrarResultadoEscaneo(codigo) {
  if (!els.contenedormax.open || !window.RepoEANScanner.validEAN(codigo)) return;
  const revision = ++scanResultRevision;
  registrarEscaneoTop(codigo);
  const esDuplicado = lista.includes(codigo);
  resultadoActual = codigo;
  ultimoAgregado = esDuplicado ? null : codigo;

  if (!esDuplicado) {
    lista.push(codigo);
    guardarLista();
  }
  indiceActual = lista.indexOf(codigo) + 1;

  els.contenedor.hidden = true;
  document.getElementById('mainScannerStatus').hidden = true;
  els.copy.disabled = false;
  els.cartelScaner.style.display = 'flex';
  els.resultado.textContent = codigo;
  const heading = document.getElementById("scanResultHeading");
  if (heading) heading.textContent = esDuplicado ? "Ya está en tu lista" : "Producto guardado";
  els.rescancelar.style.display = esDuplicado ? "none" : "block";
  actualizarContadores();

  els.resultadoApi.textContent = "Buscando producto…";
  const resultadoAPI = await buscarProductoPorEAN(codigo);
  if (revision !== scanResultRevision) return;
  els.resultadoApi.textContent = resultadoAPI;
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
  if (heading) heading.textContent = "Producto quitado";
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

  const link = crearLinkCompartido();

  if (navigator.share) {
    try {
      await navigator.share({
        title: "RepoTools - Codigos escaneados",
        text: "Mira los resultados que escanee con RepoTools:",
        url: link
      });
      return;
    } catch (error) {
      console.warn("No se pudo compartir con Web Share", error);
    }
  }

  fallbackCompartir(link);
}

function crearLinkCompartido() {
  const json = JSON.stringify(lista);
  const comprimido = LZString.compressToBase64(json);
  return `${urlOriginal}?${comprimido}`;
}

async function fallbackCompartir(link) {
  try {
    await navigator.clipboard.writeText(link);
  } catch (error) {
    console.warn("No se pudo copiar el enlace", error);
  }

  const mensaje = encodeURIComponent(`Mira este resultado de RepoTools: ${link}`);
  const whatsapp = `https://wa.me/?text=${mensaje}`;
  const telegram = `https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent("Mira este resultado de RepoTools")}`;
  const email = `mailto:?subject=Resultados%20RepoTools&body=${mensaje}`;

  alertaGeneral("ENLACE LISTO", `
    <p>Copiamos el enlace. Tambien puedes enviarlo desde aca:</p>
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
    return window.RepoProductInfo.summary(product);
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
