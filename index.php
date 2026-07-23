<?php
include 'vistas/header.vista.php';
?>
<main>
  <div class="contenedor-main">
    <div class="app-resumen">
      <p class="app-resumen__eyebrow">Scanner rapido para reposicion</p>
      <h1>Escanea, guarda y comparte codigos EAN</h1>
      <p>Tu lista queda en este dispositivo y se puede compartir cuando termines el recorrido.</p>
    </div>

    <!--BOTON SCANER-->
    <button id="boton" type="button"><i class="fa-solid fa-barcode"></i>ESCANEAR</button>

    <!--BOTON VER-->
    <div class="box-tools" id="resultados">
      <button class="resultados-box" id="ver" type="button" aria-label="Ver codigos escaneados"> 
        <p><i class="fa-solid fa-box-open"></i><span id="cantidad"></span></p>
      </button>
      <button class="compartir" id="compartir" type="button" aria-label="Compartir codigos"> 
        <i class="fa-solid fa-share-from-square"></i>
      </button>
    </div>

    <!--BOTON BORRAR-->
    <button class="borrar-box" id="borrarD" type="button"><i class="fa-solid fa-trash"></i>BORRAR TODOS</button>
  </div>

  <!-- BOX INFO -->
  <div class="info">
    <div class="info-contenedor">
      <div class="infotitulo">
        <p><i class="fa-solid fa-circle-info"></i>INFORMACION DE ESCANEOS</p>
      </div>
      <div class="infobox">
        <div class="infocontador">
          <p><span id="contadorGlobal">0</span> TOTALES</p>
        </div>
        <div class="infocontador">
          <p><span id="sM">0</span> TUYOS</p>
        </div>
      </div>
    </div>
  </div>
</main>
<?php
include 'vistas/escaner.vista.php';
include 'vistas/footer.vista.php';
?>
