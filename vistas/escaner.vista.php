<!-- ESCANER BLOQUE -->
<dialog class="library-dialog library-scanner-dialog" id="contenedormax" aria-labelledby="mainScannerTitle">
  <div class="library-heading">
    <h2 id="mainScannerTitle">Escanea el producto</h2>
    <button id="conterdormaxc" class="library-secondary" type="button">Cerrar</button>
  </div>
  <div id="contenedor" class="ean-reader"></div>
  <p id="mainScannerStatus" class="scanner-status" role="status">Centra un solo código dentro del recuadro.</p>
  <button id="retryMainScanner" class="library-primary" type="button" hidden>Intentar de nuevo</button>

  <div id="cartelScaner">
    <div class="cartelscaner-box">
      <div class="cartelscaner-box-titulo">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span id="scanResultHeading">Producto guardado</span>
      </div>

      <div class="cartelscaner-box-info">
        <div class="resultado-box">
          <div class="resultado-title">
            <i class="fa-solid fa-barcode"></i><p id="resultado"></p>
          </div>
          <div class="resultado-api" role="status"></div>
        </div>

        <div class="CsBH">
          <button class="cantidadbox" type="button" aria-label="Ver resultados">
            <i class="fa-solid fa-box-open" aria-hidden="true"></i>Ver lista (<span id="cantidadScan"></span>)
          </button>
          <button class="copybox" id="copy" type="button" aria-label="Copiar codigo" disabled>
            <i class="fa-solid fa-copy" aria-hidden="true"></i>Copiar EAN
          </button>
        </div>
        <p id="scanCopyStatus" class="scanner-status" role="status"></p>
        <button id="rescancelar" type="button"><i class="fa-solid fa-trash" aria-hidden="true"></i> Quitar de la lista</button>
      </div>

      <div class="cartelscaner-box-select">
        <button class="btnScaner" id="rescanear" type="button">
          <i class="fa-solid fa-barcode"></i> ESCANEAR OTRO
        </button>
      </div>
    </div>
  </div>
</dialog>

<!-- BLOQUE RESULTADOS -->
<dialog class="verb" id="verb" aria-labelledby="resultsTitle">
  <div class="verb-contenedor">
    <div class="results-heading"><h2 id="resultsTitle">Tu lista</h2><button id="verc" class="library-secondary" type="button">Cerrar</button></div>
    <div class="results-content">
      <div class="ver-resultado" id="resultSwipeArea">
        <p id="resultProductSummary" class="results-summary" role="status"></p>
        <svg id="barcode" role="img" aria-label="Código de barras del producto"></svg>
        <p class="results-swipe-hint">Desliza para cambiar de código</p>
      </div>
      <div class="results-actions">
        <button id="shareCurrentEan" class="library-primary" type="button">Compartir este EAN</button>
        <button id="openResultInfo" class="library-secondary" type="button">Ver información</button>
        <button id="compartirD" class="library-secondary" type="button">Compartir lista completa</button>
        <p id="resultShareStatus" class="library-status" role="status"></p>
      </div>
    </div>
    <div class="verpag"><button id="vera" class="vera" type="button" aria-label="Código anterior">← Anterior</button><div id="veri" role="status"></div><button id="vers" class="vers" type="button" aria-label="Código siguiente">Siguiente →</button></div>
  </div>
</dialog>
<dialog id="resultInfoDialog" class="library-dialog library-ean-dialog" aria-labelledby="resultInfoTitle">
  <div class="library-heading"><h2 id="resultInfoTitle">Información del producto</h2><button id="closeResultInfo" class="library-secondary" type="button">Cerrar</button></div>
  <p id="resultInfoEan" class="product-edit-ean"></p><section id="resultProductDetails" class="product-details" aria-label="Características del producto"></section>
</dialog>
