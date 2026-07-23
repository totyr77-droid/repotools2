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
const EAN_13 = /^\d{13}$/;
const REPETICIONES_NECESARIAS = 3;
const urlActual = new URL(window.location.href);
const urlOriginal = `${urlActual.origin}${urlActual.pathname}`;

let lista = cargarLista();
let indiceActual = 1;
let esListaCompartida = false;
let scannerActivo = false;
let ultimoCodigo = null;
let contadorCoincidencias = 0;
let ultimoAgregado = null;
let resultadoActual = "";

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

  if (window.Quagga) {
    Quagga.onDetected(procesarDeteccion);
    Quagga.onProcessed(dibujarGuiasScanner);
  }
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
    .filter((codigo, index, arr) => EAN_13.test(codigo) && arr.indexOf(codigo) === index);
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
    format: "EAN13",
    displayValue: true,
    margin: 10
  });
  els.veri.textContent = `${indiceActual} / ${lista.length}`;
  els.vers.className = indiceActual < lista.length ? "vers si" : "vers no";
  els.vera.className = indiceActual > 1 ? "vera si" : "vera no";
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
  mostrarOverlay();
  els.contenedormax.style.display = "block";
  els.contenedor.style.display = "block";
  iniciarScanner();
}

function iniciarScanner() {
  if (!window.Quagga) {
    alertaGeneral("ERROR", "No se pudo cargar el lector de codigos.");
    return;
  }

  detenerScanner();
  resetDeteccion();
  els.cartelScaner.style.display = "none";
  els.rescancelar.style.display = "block";
  els.resultado.textContent = "";
  els.resultadoApi.textContent = "Apunta la camara al codigo.";

  Quagga.init({
    inputStream: {
      name: "Live",
      type: "LiveStream",
      target: els.contenedor,
      constraints: {
        facingMode: "environment",
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    },
    locate: true,
    decoder: {
      readers: ["ean_reader"]
    }
  }, (err) => {
    if (err) {
      console.error(err);
      cerrarScanner();
      alertaGeneral("ERROR", "No se pudo iniciar la camara. Revisa los permisos.");
      return;
    }

    scannerActivo = true;
    Quagga.start();
  });
}

function detenerScanner() {
  if (!window.Quagga || !scannerActivo) return;

  try {
    Quagga.stop();
  } catch (error) {
    console.warn("No se pudo detener Quagga", error);
  }

  scannerActivo = false;
}

function cerrarScanner() {
  detenerScanner();
  els.contenedor.style.display = "none";
  els.contenedormax.style.display = "none";
  quitarOverlay();
}

async function procesarDeteccion(data) {
  const codigoActual = data?.codeResult?.code;
  if (!EAN_13.test(codigoActual)) return;

  if (codigoActual !== ultimoCodigo) {
    ultimoCodigo = codigoActual;
    contadorCoincidencias = 1;
    return;
  }

  contadorCoincidencias++;
  if (contadorCoincidencias < REPETICIONES_NECESARIAS) return;

  detenerScanner();
  resetDeteccion();
  await snd.play().catch(() => {});
  mostrarResultadoEscaneo(codigoActual);
}

async function mostrarResultadoEscaneo(codigo) {
  const esDuplicado = lista.includes(codigo);
  resultadoActual = codigo;
  ultimoAgregado = esDuplicado ? null : codigo;

  if (!esDuplicado) {
    lista.push(codigo);
    guardarLista();
  }

  els.cartelScaner.style.display = "flex";
  els.resultado.textContent = esDuplicado ? `${codigo} DUPLICADO` : codigo;
  els.rescancelar.style.display = esDuplicado ? "none" : "block";
  actualizarContadores();

  const loading = iniciarTextoCarga(els.resultadoApi);
  const resultadoAPI = await buscarProductoPorEAN(codigo);
  loading();
  els.resultadoApi.textContent = resultadoAPI;
}

function iniciarTextoCarga(elemento) {
  let dots = 0;
  elemento.textContent = "Buscando";

  const intervalo = setInterval(() => {
    dots = (dots + 1) % 4;
    elemento.textContent = `Buscando${".".repeat(dots)}`;
  }, 400);

  return () => clearInterval(intervalo);
}

function resetDeteccion() {
  ultimoCodigo = null;
  contadorCoincidencias = 0;
}

function cancelarUltimoEscaneo() {
  if (!ultimoAgregado) return;

  lista = lista.filter((codigo) => codigo !== ultimoAgregado);
  ultimoAgregado = null;
  guardarLista();
  actualizarContadores();
  els.resultado.textContent = "ELIMINADO";
  els.resultadoApi.textContent = "El codigo se quito de tu lista.";
  els.rescancelar.style.display = "none";
}

async function copiarResultado() {
  if (!resultadoActual) {
    alertaGeneral("INFORMACION", "Todavia no hay un codigo para copiar.");
    return;
  }

  try {
    await navigator.clipboard.writeText(resultadoActual);
    alertaGeneral("INFORMACION", `Codigo copiado: ${resultadoActual}`);
  } catch (error) {
    alertaGeneral("ERROR", "No se pudo copiar el codigo.");
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
  const endpoint = `https://world.openfoodfacts.org/api/v0/product/${ean}.json`;

  try {
    const res = await fetch(endpoint);
    if (!res.ok) throw new Error("Respuesta invalida");

    const data = await res.json();
    if (data.status !== 1) return "Producto no encontrado en la API.";

    const nombre = (data.product.product_name || "Sin nombre").toUpperCase();
    const marca = (data.product.brands || "Marca desconocida").toUpperCase();
    const volumen = (data.product.quantity || "Cantidad desconocida").toUpperCase();

    return `${nombre} | ${marca} | ${volumen}`;
  } catch (error) {
    console.error("Error al consultar la API", error);
    return "No se pudo consultar la informacion del producto.";
  }
}

function dibujarGuiasScanner(result) {
  if (!window.Quagga?.canvas?.ctx?.overlay || !window.Quagga?.canvas?.dom?.overlay) return;

  const drawingCtx = Quagga.canvas.ctx.overlay;
  const drawingCanvas = Quagga.canvas.dom.overlay;

  if (!result) return;

  if (result.boxes) {
    drawingCtx.clearRect(0, 0, Number(drawingCanvas.getAttribute("width")), Number(drawingCanvas.getAttribute("height")));
    result.boxes
      .filter((box) => box !== result.box)
      .forEach((box) => Quagga.ImageDebug.drawPath(box, { x: 0, y: 1 }, drawingCtx, { color: "#23c55e", lineWidth: 2 }));
  }

  if (result.box) {
    Quagga.ImageDebug.drawPath(result.box, { x: 0, y: 1 }, drawingCtx, { color: "#3D8FE1", lineWidth: 2 });
  }

  if (result.codeResult?.code && result.codeResult.format !== "ean_13") {
    Quagga.ImageDebug.drawPath(result.line, { x: "x", y: "y" }, drawingCtx, { color: "#e74c3c", lineWidth: 3 });
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
