<?php
require_once __DIR__ . '/includes/auth.php';
require_account('biblioteca');
$paginaBiblioteca = true;
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="library-main">
  <section class="library-app">
    <div class="library-heading"><div><h1>Biblioteca</h1><p>Organiza productos en colecciones.</p></div><button id="newCollection" class="library-primary" type="button">+ Crear</button></div>
    <p id="libraryStatus" class="library-status" role="status"></p>
    <?php if (!getenv('DATABASE_URL')): ?><p class="library-status">Versión de prueba: las colecciones pueden perderse si el servidor reinicia.</p><?php endif; ?>
    <div id="collectionList" class="library-collections" aria-label="Tus colecciones"></div>
    <section id="collectionPanel" hidden>
      <div class="library-heading"><h2 id="collectionTitle"></h2><button id="renameCollection" class="library-secondary" type="button">Renombrar</button></div>
      <button id="scanLibraryProduct" class="library-primary library-scan" type="button"><i class="fa-solid fa-barcode" aria-hidden="true"></i> Escanear producto</button>
      <button id="manualLibraryProduct" class="library-secondary" type="button">Escribir código</button>
      <div id="libraryProducts" class="library-products"></div>
      <button id="deleteCollection" class="library-danger" type="button">Eliminar colección</button>
    </section>
  </section>
</main>
<dialog id="collectionDialog" class="library-dialog">
  <form id="collectionForm"><h2 id="collectionFormTitle">Nueva colección</h2><label>Nombre<input id="collectionName" maxlength="80" required autocomplete="off" placeholder="Ej.: Bebidas"></label><div class="library-dialog-actions"><button type="button" data-close="collectionDialog" class="library-secondary">Cancelar</button><button type="submit" class="library-primary">Guardar</button></div></form>
</dialog>
<dialog id="libraryProductDialog" class="library-dialog">
  <form id="libraryProductForm">
    <h2>Guardar producto</h2>
    <div class="library-save-card">
      <p id="libraryScannedEan" class="library-scanned-ean" hidden></p>
      <label id="libraryManualEan">Código EAN<input id="libraryProductEan" inputmode="numeric" pattern="(?:[0-9]{8}|[0-9]{13})" maxlength="13" required placeholder="8 o 13 números"></label>
      <label>Nombre de referencia <small>(opcional)</small><input id="libraryProductName" maxlength="120" placeholder="Ej.: Red Bull Energizante Lata 250 ml" aria-describedby="libraryScanProductInfo"></label>
      <section id="libraryScanProductInfo" class="library-save-hint" role="status" aria-label="Información del producto"></section>
    </div>
    <div class="library-dialog-actions"><button type="button" data-close="libraryProductDialog" class="library-secondary">Cancelar</button><button type="submit" class="library-primary">Guardar</button></div>
  </form>
</dialog>
<dialog id="libraryScannerDialog" class="library-dialog library-scanner-dialog" aria-labelledby="libraryScannerTitle">
  <div class="library-heading"><h2 id="libraryScannerTitle">Escanea el producto</h2><button id="closeLibraryScanner" class="library-secondary" type="button">Cerrar</button></div>
  <div id="libraryReader" class="ean-reader"></div><p id="libraryScannerStatus" class="scanner-status" role="status">Centra un solo código dentro del recuadro.</p>
</dialog>
<dialog id="libraryEanDialog" class="library-dialog library-ean-dialog">
  <div class="library-heading"><h2>Código EAN</h2><button type="button" data-close="libraryEanDialog" class="library-secondary">Cerrar</button></div>
  <label>Colección<select id="eanCollection"></select></label>
  <h3 id="eanProductName"></h3><svg id="libraryBarcode" role="img" aria-label="Código de barras del producto"></svg><p id="eanValue"></p>
  <p id="eanPosition" role="status"></p>
  <section id="libraryProductDetails" class="product-details" aria-label="Características del producto"></section>
  <div class="library-dialog-actions"><button id="previousLibraryEan" class="library-secondary" type="button">← Anterior</button><button id="nextLibraryEan" class="library-primary" type="button">Siguiente →</button></div>
</dialog>
<script>window.repotoolsLibrary = <?= json_encode(['userId' => current_user() ? (int) current_user()['id'] : null, 'csrf' => csrf_token()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
