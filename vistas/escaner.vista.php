<!-- ESCANER BLOQUE -->
<div class="div" id="contenedormax">
  <div class="ver-logo">
    <p>REPOTOOLS</p>
  </div>

  <button id="conterdormaxc" type="button" aria-label="Cerrar scanner">
    <i class="fa-solid fa-circle-xmark"></i>
  </button>

  <div id="contenedor"></div>

  <div id="cartelScaner">
    <div class="cartelscaner-box">
      <div class="cartelscaner-box-titulo">
        <i class="fa-solid fa-circle-check"></i>ESCANEADO
      </div>

      <div class="cartelscaner-box-info">
        <div class="resultado-box">
          <div class="resultado-title">
            <i class="fa-solid fa-barcode"></i><p id="resultado"></p>
          </div>
          <div class="resultado-api"></div>
        </div>

        <button id="rescancelar" type="button"><i class="fa-solid fa-trash"></i> CANCELAR</button>

        <div class="CsBH">
          <button class="cantidadbox" type="button" aria-label="Ver resultados">
            <i class="fa-solid fa-box-open"></i><span id="cantidadScan"></span>
          </button>
          <button class="copybox" id="copy" type="button" aria-label="Copiar codigo">
            <i class="fa-solid fa-copy"></i>
          </button>
        </div>
      </div>

      <div class="cartelscaner-box-select">
        <button class="btnScaner" id="rescanear" type="button">
          <i class="fa-solid fa-barcode"></i> ESCANEAR OTRO
        </button>
      </div>
    </div>
  </div>
</div>

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
