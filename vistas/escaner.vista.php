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
        <?php if (current_user()): ?>
        <button id="saveScanLibrary" class="library-secondary" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 3h5v18H4ZM11 3h4v18h-4ZM17 4l3-1 4 17-3 1Z"/></svg>Biblioteca</button>
        <p id="scanLibraryStatus" class="scanner-status" role="status"></p>
        <?php endif; ?>
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

<?php if (current_user()): ?>
<dialog id="scanLibraryDialog" class="library-dialog" aria-labelledby="scanLibraryTitle">
  <form id="scanLibraryForm">
    <h2 id="scanLibraryTitle">Guardar en Biblioteca</h2>
    <p id="scanLibraryEan" class="product-edit-ean"></p>
    <label id="scanLibraryChoice">Biblioteca<select id="scanLibrarySelect"></select></label>
    <label id="scanLibraryNew">Nombre de la nueva biblioteca<input id="scanLibraryName" maxlength="80" autocomplete="off" placeholder="Por ejemplo, Productos"></label>
    <p id="scanLibraryError" class="library-status" role="alert"></p>
    <div class="library-dialog-actions"><button id="cancelScanLibrary" class="library-secondary" type="button">Cancelar</button><button id="confirmScanLibrary" class="library-primary" type="submit">Guardar</button></div>
  </form>
</dialog>
<?php endif; ?>

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
