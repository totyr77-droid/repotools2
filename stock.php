<?php
require_once __DIR__ . "/includes/auth.php";
require_account();
$paginaStock = true;
include 'vistas/header.vista.php';
?>
<main class="stock-main">
  <div class="contenedor-main stock-app">
    <div class="app-resumen">
      <h1>Tus palets</h1>
      <p>Escanea un QR para crear o abrir un palet.</p>
    </div>

    <p class="account-notice">En desarrollo. Tus palets se guardan solo en este dispositivo.</p>
    <script>window.repotoolsUserId = <?= (int) current_user()["id"] ?>;</script>
    <script>window.repotoolsStock = <?= json_encode(['csrf' => csrf_token()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
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

<dialog class="library-dialog library-scanner-dialog" id="stockScanner" aria-labelledby="stockScannerTitulo">
  <div class="library-heading"><h2 id="stockScannerTitulo">Escanea el QR del palet</h2><button id="cerrarStockScanner" class="library-secondary app-close" type="button" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <div id="stockReader" class="ean-reader"></div>
  <p id="stockScannerStatus" class="scanner-status" role="status">Centra un solo código dentro del recuadro.</p>
  <div class="stock-scanner__manual" id="paletManualScanner">
    <label for="codigoPaletManual">¿No puedes escanearlo? Ingresa el código</label>
    <div>
      <input id="codigoPaletManual" type="text" maxlength="80" placeholder="Ej: PALET-001">
      <button id="abrirPaletManual" type="button">CONTINUAR</button>
    </div>
  </div>
</dialog>

<div class="palet-editor" id="paletEditor" aria-hidden="true">
  <div class="palet-editor__panel">
    <button id="cerrarPaletEditor" class="palet-editor__close app-close" type="button" aria-label="Cerrar palet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
    <p class="palet-editor__logo">REPOTOOLS</p>
    <div class="palet-editor__header">
      <div>
        <span>PALET</span>
        <h2 id="paletCodigo"></h2>
      </div>
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
