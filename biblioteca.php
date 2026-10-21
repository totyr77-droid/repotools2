<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__.'/includes/stock-icons.php';
if (is_string($_GET['share'] ?? null) && preg_match('/^[a-f0-9]{64}$/D', $_GET['share'])) $_SESSION['library_share'] = $_GET['share'];
if(!current_user()){
    require_once __DIR__.'/includes/stock-icons.php';
    $demoSection='biblioteca';$paginaDemo=true;$paginaBiblioteca=true;
    include __DIR__.'/vistas/header.vista.php';include __DIR__.'/vistas/guest-demo.vista.php';
    $paginaBiblioteca=false;include __DIR__.'/vistas/footer.vista.php';exit;
}
require_account('biblioteca');
$pendingShare = $_SESSION['library_share'] ?? null;
unset($_SESSION['library_share']);
$paginaBiblioteca = true;
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="library-main">
  <section class="library-app">
    <div class="library-heading"><div><h1>Biblioteca</h1><p>Tus productos, a mano.</p></div><button id="newCollection" class="library-primary" type="button">+ Crear</button></div>
    <p id="libraryStatus" class="library-status" role="status"></p>
    <?php if (!getenv('DATABASE_URL')): ?><p class="library-status">Versión de prueba: las colecciones pueden perderse si el servidor reinicia.</p><?php endif; ?>
    <aside id="libraryEmpty" class="library-empty-notice" role="status" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v1"/></svg><div><h2>Crea tu primera colección</h2><p>Usá «+ Crear» para darle un nombre y empezar a guardar productos.</p></div></aside>
    <nav id="collectionList" class="library-collections" aria-label="Tus colecciones" hidden></nav>
    <section id="collectionPanel" hidden>
      <div class="library-collection-header">
        <div class="library-collection-name"><h2 id="collectionTitle"></h2>
          <details id="collectionSettings" class="library-settings">
            <summary aria-label="Ajustes de colección" title="Ajustes de colección"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 3 1-1h4l1 1 .5 2 2 1 2-.5 2 3-.5 2-1.5 1v2l1.5 1 .5 2-2 3-2-.5-2 1-.5 2-1 1h-4l-1-1-.5-2-2-1-2 .5-2-3 .5-2 1.5-1v-2L3 11l-.5-2 2-3 2 .5 2-1Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="library-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
            <div class="library-settings-menu"><button id="renameCollection" class="library-secondary" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6ZM13 6l5 5"/></svg> Renombrar</button><button id="deleteCollection" class="library-danger" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg> Eliminar colección</button></div>
          </details>
        </div>
        <div class="library-add-actions"><button id="scanLibraryProduct" class="library-primary library-icon-button" type="button" aria-label="Escanear producto" title="Escanear producto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v10l9 5V13m9-5v5M7.5 5.5l9 5M17 18h6m-3-3v6"/></svg></button><button id="manualLibraryProduct" class="library-secondary library-icon-button" type="button" aria-label="Escribir código" title="Escribir código"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M6 9h.01M10 9h.01M14 9h.01M18 9h.01M6 12h.01M10 12h.01M14 12h.01M18 12h.01M7 15h10"/></svg></button><button id="shareLibrary" class="library-secondary library-icon-button" type="button" aria-label="Compartir colección" title="Compartir colección" hidden disabled><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M5 13v7h14v-7"/></svg></button></div>
      </div>
      <div id="libraryProducts" class="library-products"></div>
    </section>
  </section>
</main>
<dialog id="collectionDialog" class="library-dialog">
  <form id="collectionForm"><h2 id="collectionFormTitle">Nueva colección</h2><label>Nombre<input id="collectionName" maxlength="80" required autocomplete="off" placeholder="Ej.: Bebidas"></label><div class="library-dialog-actions"><button type="button" data-close="collectionDialog" class="library-secondary">Cancelar</button><button type="submit" class="library-primary">Guardar</button></div></form>
</dialog>
<dialog id="libraryProductDialog" class="library-dialog">
  <form id="libraryProductForm">
    <h2 id="libraryProductFormTitle">Guardar producto</h2>
    <div class="library-save-card">
      <p id="libraryScannedEan" class="library-scanned-ean" hidden></p>
      <label id="libraryManualEan">Código EAN<input id="libraryProductEan" type="text" inputmode="numeric" autocomplete="off" required placeholder="8 o 13 números"></label>
      <div id="libraryProductReference"><label>Nombre de referencia <small>(opcional)</small><input id="libraryProductName" maxlength="120" placeholder="Ej.: Red Bull Energizante Lata 250 ml" aria-describedby="libraryScanProductInfo"></label>
      <section id="libraryScanProductInfo" class="library-save-hint" role="status" aria-label="Información del producto"></section></div>
    </div>
    <div class="library-dialog-actions"><button type="button" data-close="libraryProductDialog" class="library-secondary">Cancelar</button><button id="libraryProductSubmit" type="submit" class="library-primary">Guardar</button></div>
  </form>
</dialog>
<dialog id="libraryScannerDialog" class="library-dialog library-scanner-dialog" aria-labelledby="libraryScannerTitle">
  <div class="library-heading"><h2 id="libraryScannerTitle">Escanea el producto</h2><button id="closeLibraryScanner" class="library-secondary app-close" type="button" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <div id="libraryReader" class="ean-reader"></div><p id="libraryScannerStatus" class="scanner-status" role="status">Centra un solo código dentro del recuadro.</p>
</dialog>
<dialog id="libraryEanDialog" class="library-dialog library-ean-dialog">
  <div class="library-heading"><h2>Código EAN</h2><button type="button" data-close="libraryEanDialog" class="library-secondary app-close" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <label>Colección<select id="eanCollection"></select></label>
  <div id="libraryEanSwipeArea" class="library-ean-swipe-area"><h3 id="eanProductName"></h3><p id="eanCatalogName" class="library-catalog-name" hidden></p><svg id="libraryBarcode" role="img" aria-label="Código de barras del producto"></svg><p id="eanValue"></p>
  <p id="eanPosition" role="status"></p><p id="librarySwipeHint" class="library-swipe-hint" hidden>Desliza para cambiar de producto</p></div>
  <div class="library-preview-actions"><button id="openLibraryProductInfo" type="button" class="library-secondary library-info-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v2"/></svg> Información</button><button id="copyLibraryEan" type="button" class="library-secondary library-info-button" aria-label="Copiar código EAN"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 8h12v13H8zM16 8V3H3v13h5"/></svg><span>Copiar código</span></button><button id="reportLibraryProduct" type="button" class="library-secondary library-info-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 21V3m0 1h14l-3 5 3 5H5"/></svg> Reportar</button></div>
  <div class="library-dialog-actions ean-navigation"><button id="previousLibraryEan" class="library-secondary" type="button">← Anterior</button><button id="nextLibraryEan" class="library-primary" type="button">Siguiente →</button></div>
</dialog>
<dialog id="libraryInfoDialog" class="library-dialog" aria-labelledby="libraryInfoTitle">
  <div class="library-heading"><h2 id="libraryInfoTitle">Información del producto</h2><button type="button" data-close="libraryInfoDialog" class="library-secondary app-close" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <p id="libraryInfoEan" class="library-scanned-ean"></p><section id="libraryProductDetails" class="product-details" aria-label="Características del producto"></section>
</dialog>
<dialog id="libraryImportDialog" class="library-dialog" aria-labelledby="libraryImportTitle">
  <div class="library-heading"><h2 id="libraryImportTitle"><?= stock_icon('book') ?>Colección compartida</h2><button type="button" data-close="libraryImportDialog" class="library-secondary app-close" aria-label="Cerrar colección compartida"><?= stock_icon('close') ?></button></div><p id="libraryImportQuestion" class="library-import-summary"></p><p id="libraryImportHint" class="library-share-hint">Se guardará una copia en tu biblioteca.</p>
  <ul id="libraryImportProducts" class="library-import-products"></ul>
  <p id="libraryImportStatus" role="status"></p>
  <div class="library-dialog-actions"><button type="button" data-close="libraryImportDialog" class="library-secondary">Cancelar</button><button id="confirmLibraryImport" type="button" class="library-primary">Agregar a mi biblioteca</button></div>
</dialog>
<dialog id="libraryShareDialog" class="library-dialog" aria-labelledby="libraryShareTitle">
  <div class="library-heading"><h2 id="libraryShareTitle"><?= stock_icon('share') ?>Compartir colección</h2><button type="button" data-close="libraryShareDialog" class="library-secondary app-close" aria-label="Cerrar compartir colección"><?= stock_icon('close') ?></button></div>
  <p id="libraryShareName" class="library-import-summary"></p>

  <form id="libraryShareUserPanel"><label for="libraryShareEmail">Correo del usuario</label><input id="libraryShareEmail" name="email" type="email" maxlength="254" autocomplete="off" required><p class="library-share-hint">La persona podrá revisar y agregar la colección.</p><button type="submit" class="library-primary">Enviar colección</button></form>
  <section id="libraryShareLinkPanel" class="library-share-link-section"><label>Enlace de la colección<input id="libraryShareUrl" readonly></label><div class="library-dialog-actions"><button id="copyLibraryShare" type="button" class="library-secondary"><?= stock_icon('copy') ?>Copiar enlace</button><button id="sendLibraryShareLink" type="button" class="library-secondary"><?= stock_icon('share') ?>Compartir</button></div></section>
  <p id="libraryShareStatus" class="library-share-status" role="status"></p>
</dialog>
<script>window.repotoolsLibrary = <?= json_encode(['userId' => current_user() ? (int) current_user()['id'] : null, 'csrf' => csrf_token(), 'share' => $pendingShare], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
