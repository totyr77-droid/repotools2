<!-- ESCANER BLOQUE -->
<dialog class="library-dialog library-scanner-dialog" id="contenedormax" aria-labelledby="mainScannerTitle">
  <div class="library-heading">
    <h2 id="mainScannerTitle">Escanea el producto</h2>
    <button id="conterdormaxc" class="library-secondary app-close" type="button" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
  </div>
  <div id="mainScanModes" class="main-scan-modes" role="group" aria-label="Modo de escaneo">
    <button id="mainScanNormal" class="library-secondary" type="button" aria-pressed="true">Normal</button>
    <button id="mainScanRapid" class="library-secondary" type="button" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 6 6 6-6 6m10-12 6 6-6 6"/></svg>Rápido</button>
  </div>
  <p id="mainScannerStatus" class="scanner-status camera-message-area" role="status">Centrá un solo código dentro del recuadro.</p>
  <div class="main-camera-slot"><div id="contenedor" class="ean-reader"></div><p class="main-camera-paused">Lectura lista. Podés consultar el producto abajo.</p></div>
  <button id="retryMainScanner" class="library-primary" type="button" hidden>Intentar de nuevo</button>
  <div class="main-scan-result-zone"><p class="main-scan-idle">Apuntá la cámara al código de barras. El resultado aparecerá acá.</p>
  <div id="mainRapidPanel" class="main-rapid-panel" hidden>
    <div class="main-rapid-feedback" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path class="rapid-check" d="m5 12 4 4 10-10"/><g class="rapid-warning"><circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 3h.01"/></g></svg><div class="main-rapid-info"><div id="mainRapidLast">Listo para escanear</div><span class="main-rapid-ean"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 4v16M6 4v16M10 4v16M14 4v16M17 4v16M21 4v16"/></svg><span id="mainRapidCode"></span></span></div></div>
    <div class="main-rapid-meta"><div class="main-rapid-status-text"><p id="mainRapidOutcome" class="main-rapid-outcome"></p><span id="mainRapidCount">Escaneados: 0</span></div><div id="mainRapidActions"></div></div>
    <button id="mainRapidFinish" class="library-primary" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4 10-10"/></svg>Finalizar</button>
  </div>

  <div id="cartelScaner" hidden>
    <div class="cartelscaner-box">
      <div class="cartelscaner-box-titulo" role="status" data-state="success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg><span id="scanResultHeading"></span>
      </div>

      <div class="cartelscaner-box-info">
        <div class="resultado-box">
          <div class="resultado-api" role="status"></div>
          <div class="resultado-title">
            <span class="scan-ean-code"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 4v16M6 4v16M10 4v16M14 4v16M17 4v16M21 4v16"/></svg><p id="resultado"></p></span><button class="library-secondary scan-ean-copy" id="copy" type="button" aria-label="Copiar EAN" title="Copiar EAN" disabled><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 8h12v13H8zM16 8V3H3v13h5"/></svg><span>Copiar</span></button>
          </div>
          <div id="scanProductActions" class="scan-product-actions" aria-label="Acciones del producto"></div>
        </div>

        <div class="CsBH">
          <button class="cantidadbox" type="button" aria-label="Ver resultados">
            <i class="fa-solid fa-box-open" aria-hidden="true"></i>Ver lista (<span id="cantidadScan"></span>)
          </button>
        </div>
        <p id="scanCopyStatus" class="scanner-status" role="status"></p>
        <?php if (current_user()): ?>
        <button id="saveScanLibrary" class="library-secondary" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 3h5v18H4ZM11 3h4v18h-4ZM17 4l3-1 4 17-3 1Z"/></svg>+ Biblioteca</button>
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
</div></dialog>

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
    <div class="results-heading"><h2 id="resultsTitle">Tu lista</h2><button id="verc" class="library-secondary app-close" type="button" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
    <div class="results-content">
      <div class="ver-resultado" id="resultSwipeArea">
        <p id="resultProductSummary" class="results-summary" role="status"></p>
        <svg id="barcode" role="img" aria-label="Código de barras del producto"></svg>
        <p id="veri" role="status"></p>
        <p id="resultSwipeHint" class="results-swipe-hint">Desliza para cambiar de código</p>
      </div>
      <div class="results-actions">
        <button id="copyCurrentEan" class="library-secondary" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V3H3v13h5"/></svg> Copiar EAN</button>
<button id="shareCurrentEan" class="library-primary" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M5 13v8h14v-8"/></svg><span>Compartir EAN</span></button>
        <button id="openResultInfo" class="library-secondary" type="button" aria-label="Ver información del producto" title="Ver información del producto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10h.01"/></svg><span>Información</span></button>
        <button id="compartirD" class="library-secondary" type="button">Compartir lista completa</button>
        <p id="resultShareStatus" class="library-status" role="status"></p>
      </div>
    </div>
    <div id="resultNavigation" class="verpag library-dialog-actions ean-navigation"><button id="vera" class="library-secondary" type="button" aria-label="Código anterior">← Anterior</button><button id="vers" class="library-primary" type="button" aria-label="Código siguiente">Siguiente →</button></div>
  </div>
</dialog>
<dialog id="resultInfoDialog" class="library-dialog library-ean-dialog" aria-labelledby="resultInfoTitle">
  <div class="library-heading"><h2 id="resultInfoTitle">Información del producto</h2><button id="closeResultInfo" class="library-secondary app-close" type="button" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <p id="resultInfoEan" class="product-edit-ean"></p><section id="resultProductDetails" class="product-details" aria-label="Características del producto"></section>
</dialog>
