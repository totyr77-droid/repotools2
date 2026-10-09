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
<div class="verb" id="verb">
  <button class="verc" id="verc" type="button" aria-label="Cerrar resultados">
    <i class="fa-solid fa-circle-xmark"></i>
  </button>

  <div class="verb-contenedor">
    <div class="ver-logoD">
      <p>REPOTOOLS</p>
    </div>
    <div class="ver-resultado">
      <p>RESULTADOS</p>
      <svg id="barcode"></svg>
      <div id="veri"></div>
      <section id="resultProductDetails" class="product-details" aria-label="Características del producto"></section>
    </div>

    <button class="compartir" id="compartirD" type="button" aria-label="Compartir resultados">
      <i class="fa-solid fa-share-from-square"></i>
    </button>

    <div class="verpag">
      <button id="vera" class="vera" type="button" aria-label="Codigo anterior">
        <i class="fa-solid fa-arrow-left"></i>
      </button>
      <button id="vers" class="vers" type="button" aria-label="Codigo siguiente">
        <i class="fa-solid fa-arrow-right"></i>
      </button>
    </div>
  </div>
</div>
