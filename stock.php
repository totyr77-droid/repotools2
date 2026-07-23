<?php
$paginaStock = true;
include 'vistas/header.vista.php';
?>
<main class="stock-main">
  <div class="contenedor-main stock-app">
    <div class="app-resumen">
      <p class="app-resumen__eyebrow">Stock / Palets</p>
      <h1>Gestiona el contenido de tus palets</h1>
      <p>Escanea el QR de un palet. Si existe podrás editarlo; si es nuevo, se creará automáticamente.</p>
    </div>

    <button id="escanearPalet" type="button" class="stock-primary">
      <i class="fa-solid fa-qrcode"></i> ESCANEAR PALET
    </button>

    <section class="lista-palets" aria-labelledby="tituloPalets">
      <p class="lista-palets__titulo" id="tituloPalets">
        <i class="fa-solid fa-boxes-stacked"></i> Palets guardados
      </p>
      <div id="lista-palets-contenido"></div>
    </section>
  </div>
</main>

<div class="stock-scanner" id="stockScanner" aria-hidden="true">
  <button id="cerrarStockScanner" class="stock-close" type="button" aria-label="Cerrar lector">
    <i class="fa-solid fa-xmark"></i>
  </button>
  <p id="stockScannerTitulo">Escanea el QR del palet</p>
  <div id="stockReader"></div>
  <div class="stock-scanner__manual" id="paletManualScanner">
    <label for="codigoPaletManual">¿No puedes escanearlo? Ingresa el código</label>
    <div>
      <input id="codigoPaletManual" type="text" maxlength="80" placeholder="Ej: PALET-001">
      <button id="abrirPaletManual" type="button">CONTINUAR</button>
    </div>
  </div>
</div>

<div class="palet-editor" id="paletEditor" aria-hidden="true">
  <div class="palet-editor__panel">
    <div class="palet-editor__header">
      <div>
        <span>PALET</span>
        <h2 id="paletCodigo"></h2>
      </div>
      <button id="cerrarPaletEditor" type="button" aria-label="Cerrar palet">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="palet-editor__actions">
      <button id="escanearProducto" type="button" class="stock-primary">
        <i class="fa-solid fa-barcode"></i> AGREGAR PRODUCTO POR EAN
      </button>
      <div class="stock-manual">
        <label for="eanManual">O ingresa el EAN manualmente</label>
        <div>
          <input id="eanManual" type="text" inputmode="numeric" maxlength="14" placeholder="EAN-8 o EAN-13">
          <button id="agregarEanManual" type="button">AGREGAR</button>
        </div>
      </div>
    </div>

    <p class="palet-editor__subtitle">Productos del palet</p>
    <div id="productosPalet" class="productos-palet"></div>
    <button id="eliminarPalet" class="stock-danger" type="button">
      <i class="fa-solid fa-trash"></i> ELIMINAR PALET
    </button>
  </div>
</div>

<template id="productoFormTemplate">
  <form class="producto-form">
    <div class="producto-form__title">
      <strong class="producto-ean"></strong>
      <button type="button" class="producto-eliminar" aria-label="Eliminar producto">
        <i class="fa-solid fa-trash"></i>
      </button>
    </div>
    <label>
      Producto
      <input class="producto-nombre" type="text" maxlength="120" placeholder="Nombre del producto">
    </label>
    <div class="producto-form__row">
      <label>
        Cantidad
        <input class="producto-bultos" type="number" min="1" step="1" required>
      </label>
      <label>
        Fecha de vencimiento <small>(opcional)</small>
        <input class="producto-vencimiento" type="date">
      </label>
    </div>
    <button class="producto-guardar" type="submit">GUARDAR CAMBIOS</button>
  </form>
</template>

<?php include 'vistas/footer.vista.php'; ?>
