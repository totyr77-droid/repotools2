(() => {
  "use strict";

  const STORAGE_KEY = `repotools_palets_v1_user_${window.repotoolsUserId}`;
  const EAN = /^(?:\d{8}|\d{13})$/;
  const els = {
    escanearPalet: document.getElementById("escanearPalet"),
    codigoPaletManual: document.getElementById("codigoPaletManual"),
    abrirPaletManual: document.getElementById("abrirPaletManual"),
    lista: document.getElementById("lista-palets-contenido"),
    scanner: document.getElementById("stockScanner"),
    scannerTitulo: document.getElementById("stockScannerTitulo"),
    reader: document.getElementById("stockReader"),
    paletManualScanner: document.getElementById("paletManualScanner"),
    cerrarScanner: document.getElementById("cerrarStockScanner"),
    editor: document.getElementById("paletEditor"),
    cerrarEditor: document.getElementById("cerrarPaletEditor"),
    paletCodigo: document.getElementById("paletCodigo"),
    escanearProducto: document.getElementById("escanearProducto"),
    eanManual: document.getElementById("eanManual"),
    agregarEanManual: document.getElementById("agregarEanManual"),
    productos: document.getElementById("productosPalet"),
    eliminarPalet: document.getElementById("eliminarPalet"),
    template: document.getElementById("productoFormTemplate")
  };

  let palets = cargarPalets();
  let paletActivo = null;
  let lector = null;
  let modoEscaneo = "palet";
  let procesandoLectura = false;

  enlazarEventos();
  renderPalets();

  function enlazarEventos() {
    els.escanearPalet.addEventListener("click", () => abrirScanner("palet"));
    els.abrirPaletManual.addEventListener("click", abrirPaletDesdeInput);
    els.codigoPaletManual.addEventListener("keydown", (e) => {
      if (e.key === "Enter") abrirPaletDesdeInput();
    });
    els.cerrarScanner.addEventListener("click", cerrarScanner);
    els.cerrarEditor.addEventListener("click", cerrarEditor);
    els.escanearProducto.addEventListener("click", () => abrirScanner("producto"));
    els.agregarEanManual.addEventListener("click", agregarProductoDesdeInput);
    els.eanManual.addEventListener("keydown", (e) => {
      if (e.key === "Enter") agregarProductoDesdeInput();
    });
    els.eliminarPalet.addEventListener("click", eliminarPaletActivo);
  }

  function cargarPalets() {
    try {
      const datos = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");
      return datos && typeof datos === "object" && !Array.isArray(datos) ? datos : {};
    } catch {
      return {};
    }
  }

  function guardarPalets() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(palets));
  }

  function normalizarCodigo(valor) {
    return String(valor || "").trim().replace(/\s+/g, " ").slice(0, 80);
  }

  async function abrirPaletDesdeInput() {
    const codigo = normalizarCodigo(els.codigoPaletManual.value);
    if (!codigo) return avisar("Ingresa o escanea un código de palet.");
    els.codigoPaletManual.value = "";
    await cerrarScanner();
    abrirPalet(codigo);
  }

  function abrirPalet(codigo) {
    if (!palets[codigo]) {
      palets[codigo] = {
        codigo,
        creado: new Date().toISOString(),
        actualizado: new Date().toISOString(),
        productos: []
      };
      guardarPalets();
    }
    paletActivo = codigo;
    els.paletCodigo.textContent = codigo;
    els.editor.classList.add("activo");
    els.editor.setAttribute("aria-hidden", "false");
    document.body.classList.add("no-scroll");
    renderProductos();
    renderPalets();
  }

  function cerrarEditor() {
    els.editor.classList.remove("activo");
    els.editor.setAttribute("aria-hidden", "true");
    document.body.classList.remove("no-scroll");
    paletActivo = null;
    renderPalets();
  }

  async function abrirScanner(modo) {
    if (!window.Html5Qrcode) {
      avisar("No se pudo cargar el lector. Puedes ingresar el código manualmente.");
      return;
    }
    modoEscaneo = modo;
    procesandoLectura = false;
    els.scannerTitulo.textContent = modo === "palet"
      ? "Escanea el QR del palet"
      : "Escanea el código EAN del producto";
    els.paletManualScanner.hidden = modo !== "palet";
    els.scanner.classList.add("activo");
    els.scanner.setAttribute("aria-hidden", "false");

    lector = new Html5Qrcode("stockReader");
    const formatos = modo === "palet"
      ? [Html5QrcodeSupportedFormats.QR_CODE]
      : [Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8];

    try {
      await lector.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 260, height: modo === "palet" ? 260 : 140 }, formatsToSupport: formatos },
        procesarLectura,
        () => {}
      );
    } catch (error) {
      console.error(error);
      await cerrarScanner();
      avisar("No se pudo iniciar la cámara. Revisa el permiso o ingresa el código manualmente.");
    }
  }

  async function procesarLectura(texto) {
    if (procesandoLectura) return;
    procesandoLectura = true;
    const codigo = normalizarCodigo(texto);
    await cerrarScanner();

    if (modoEscaneo === "palet") {
      if (!codigo) return avisar("El QR no contiene un código de palet válido.");
      abrirPalet(codigo);
      return;
    }
    agregarProducto(codigo);
  }

  async function cerrarScanner() {
    if (lector) {
      try {
        if (lector.isScanning) await lector.stop();
        await lector.clear();
      } catch (error) {
        console.warn("No se pudo cerrar el lector", error);
      }
      lector = null;
    }
    els.scanner.classList.remove("activo");
    els.scanner.setAttribute("aria-hidden", "true");
    els.reader.innerHTML = "";
  }

  function agregarProductoDesdeInput() {
    const ean = els.eanManual.value.trim();
    els.eanManual.value = "";
    agregarProducto(ean);
  }

  async function agregarProducto(ean) {
    if (!paletActivo) return;
    if (!EAN.test(ean)) return avisar("El EAN debe contener 8 o 13 números.");

    const palet = palets[paletActivo];
    const existente = palet.productos.find((producto) => producto.ean === ean);
    if (existente) {
      renderProductos();
      document.querySelector(`[data-ean="${ean}"]`)?.scrollIntoView({ behavior: "smooth", block: "center" });
      return avisar("Ese producto ya está en el palet. Puedes editar sus datos.");
    }

    const producto = {
      ean,
      nombre: "",
      bultos: 1,
      vencimiento: ""
    };
    palet.productos.unshift(producto);
    palet.actualizado = new Date().toISOString();
    guardarPalets();
    renderProductos();

    const nombre = await buscarNombreProducto(ean);
    if (nombre && paletActivo && palets[paletActivo]?.productos.includes(producto)) {
      producto.nombre = nombre;
      guardarPalets();
      renderProductos();
    }
  }

  async function buscarNombreProducto(ean) {
    try {
      const respuesta = await fetch(`https://world.openfoodfacts.org/api/v2/product/${ean}.json`);
      if (!respuesta.ok) return "";
      const datos = await respuesta.json();
      return datos.status === 1 ? (datos.product?.product_name || datos.product?.generic_name || "") : "";
    } catch {
      return "";
    }
  }

  function renderPalets() {
    const lista = Object.values(palets).sort((a, b) => b.actualizado.localeCompare(a.actualizado));
    els.lista.innerHTML = "";
    if (!lista.length) {
      els.lista.innerHTML = '<p class="lista-palets__empty">No hay palets guardados todavía. Escanea uno para comenzar.</p>';
      return;
    }
    lista.forEach((palet) => {
      const boton = document.createElement("button");
      boton.type = "button";
      boton.className = "lista-palets__item";
      boton.innerHTML = `<span class="lista-palets__icon"><i class="fa-solid fa-pallet"></i></span>
        <span><strong></strong><small></small><small class="lista-palets__detalle"></small></span>
        <i class="fa-solid fa-chevron-right"></i>`;
      boton.querySelector("strong").textContent = palet.codigo;
      boton.querySelector("small").textContent = `${palet.productos.length} producto${palet.productos.length === 1 ? "" : "s"}`;
      boton.querySelector(".lista-palets__detalle").textContent = palet.productos.length
        ? palet.productos.map((producto) => `${producto.nombre || "Sin nombre"} · EAN ${producto.ean} · Cant. ${producto.bultos}${producto.vencimiento ? ` · Vence ${formatearFecha(producto.vencimiento)}` : ""}`).join(" | ")
        : "Palet vacío";
      boton.addEventListener("click", () => abrirPalet(palet.codigo));
      els.lista.appendChild(boton);
    });
  }

  function renderProductos() {
    if (!paletActivo) return;
    const productos = palets[paletActivo].productos;
    els.productos.innerHTML = "";
    if (!productos.length) {
      els.productos.innerHTML = '<p class="productos-palet__empty">Este palet está vacío. Escanea un EAN para agregar el primer producto.</p>';
      return;
    }

    productos.forEach((producto) => {
      const fragmento = els.template.content.cloneNode(true);
      const form = fragmento.querySelector("form");
      form.dataset.ean = producto.ean;
      form.querySelector(".producto-ean").textContent = `EAN ${producto.ean}`;
      form.querySelector(".producto-nombre").value = producto.nombre;
      form.querySelector(".producto-bultos").value = producto.bultos;
      form.querySelector(".producto-vencimiento").value = producto.vencimiento;
      form.addEventListener("submit", (e) => guardarProducto(e, producto));
      form.querySelector(".producto-eliminar").addEventListener("click", () => eliminarProducto(producto.ean));
      els.productos.appendChild(fragmento);
    });
  }

  function guardarProducto(evento, producto) {
    evento.preventDefault();
    const form = evento.currentTarget;
    const bultos = Number.parseInt(form.querySelector(".producto-bultos").value, 10);
    const vencimiento = form.querySelector(".producto-vencimiento").value;
    if (!Number.isInteger(bultos) || bultos < 1) {
      return avisar("Completa la cantidad con un número mayor a cero.");
    }
    producto.nombre = form.querySelector(".producto-nombre").value.trim();
    producto.bultos = bultos;
    producto.vencimiento = vencimiento;
    palets[paletActivo].actualizado = new Date().toISOString();
    guardarPalets();
    renderPalets();
    avisar("Producto guardado.");
  }

  function eliminarProducto(ean) {
    if (!confirm(`¿Eliminar el producto ${ean} de este palet?`)) return;
    const palet = palets[paletActivo];
    palet.productos = palet.productos.filter((producto) => producto.ean !== ean);
    palet.actualizado = new Date().toISOString();
    guardarPalets();
    renderProductos();
    renderPalets();
  }

  function eliminarPaletActivo() {
    if (!paletActivo || !confirm(`¿Eliminar el palet ${paletActivo} y todos sus productos?`)) return;
    delete palets[paletActivo];
    guardarPalets();
    cerrarEditor();
  }

  function avisar(mensaje) {
    if (window.alertaGeneral) window.alertaGeneral("INFORMACIÓN", mensaje);
    else alert(mensaje);
  }

  function formatearFecha(fecha) {
    const [anio, mes, dia] = fecha.split("-");
    return anio && mes && dia ? `${dia}/${mes}/${anio}` : fecha;
  }
})();
