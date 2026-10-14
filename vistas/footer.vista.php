<?php if (!empty($paginaInicio) || !empty($mostrarCreditos)): ?>
<footer class="footer site-footer" id="contacto">
  <small class="site-footer__credits"><span>&copy; 2026 Repotools</span><span aria-hidden="true">&middot;</span><span>Hecho por <a href="https://www.instagram.com/greeyes_/" target="_blank" rel="noopener noreferrer">GR Dise&ntilde;o&nbsp;<span aria-hidden="true">&#8599;</span></a></span></small>
</footer>
<?php endif; ?>

<!-- ALERTA GENERAL -->
<div class="alerta">
  <div class="alerta-contenedor">
    <div class="alertabox">
      <div class="alerta-titulo">
        <i class="fa-solid fa-circle-info"></i><p id="alertaTitulo"></p>
      </div>
      <div class="alerta-info">
        <div id="alertaInfo"></div>
      </div>
    </div>
  </div>
  <button class="alerta-cerrar app-close" type="button" aria-label="Cerrar aviso" title="Cerrar aviso"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
</div>

<?php if (!empty($paginaInicio) || !empty($paginaBiblioteca)): ?>
<?php include __DIR__ . '/product-editor.vista.php'; ?>
<?php include __DIR__ . '/product-report.vista.php'; ?>
<?php endif; ?>
<!-- SCRIPTS -->
<?php if(!empty($paginaDemo)): ?><script src="js/guest-demo.js?v=20261013-demo1"></script><?php endif; ?>
<script src="js/loading.js?v=20261013-loading1"></script>
<script>window.repotoolsOfflineUser=<?= (int)(current_user()['id']??0) ?>;</script><script src="js/stock-offline.js?v=20261014-toasts"></script><script src="js/connectivity.js?v=20261014-silent1"></script><script src="js/offline-cache.js?v=20261014-silent1"></script><script src="js/offline-sync.js?v=20261014-sync-stable"></script>
<?php if (!empty($paginaStock) || !empty($paginaBiblioteca)): ?><link rel="stylesheet" href="css/sync-status.css?v=20261014-silent1"><script src="js/sync-status.js?v=20261014-silent1"></script><script src="js/library-offline.js?v=20261014-toasts"></script><?php endif; ?>
<script src="js/date-time.js?v=20261011-dates1"></script>
<script src="js/navigation.js?v=20261013-motion1"></script>
<script src="js/device-connection.js?v=20261013-identification1"></script>
<script src="js/action-notices.js?v=20261014-entry-details"></script>
<?php if (!empty($paginaBiblioteca)): ?>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="js/ean-scanner.js?v=20261014-single-guide"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/product-info.js?v=20261013-loading1"></script>
<script src="js/product-editor.js?v=20261010-ajax1"></script>
<script src="js/product-reports.js?v=20261013-access1"></script>
<script src="js/biblioteca.js?v=20261013-loading1"></script>
<?php elseif (!empty($paginaReportes)): ?>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="js/product-reports.js?v=20261013-access1"></script>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="js/ean-scanner.js?v=20261014-single-guide"></script>
<script src="js/branch-maps.js?v=20261013-map-compact"></script>
<script src="js/branch-management.js?v=20261013-map-compact"></script>
<script src="js/reports-page.js?v=20261013-loading1"></script>
<?php elseif (!empty($paginaStock)): ?>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/ean-scanner.js?v=20261014-single-guide"></script>
<script src="js/product-info.js?v=20261013-loading1"></script>
<script src="js/vendor/qrcode.min.js"></script>
<script src="js/stock-utils.js?v=20261012-reserved-label"></script>
<script src="js/pallet-label.js?v=20261013-portrait1"></script>

<script src="js/stock.js?v=20261014-sync-stable"></script>
<script src="js/stock-summary.js?v=20261012-branch-refresh"></script>
<script src="js/public-pallet.js?v=20261013-loading1"></script>
<script src="js/branch-maps.js?v=20261013-map-compact"></script>
<script src="js/stock-branches.js?v=20261014-toolbar"></script>
<?php elseif (empty($paginaCuenta)): ?>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lz-string@1.4.4/libs/lz-string.min.js"></script>
<script src="js/product-info.js?v=20261013-loading1"></script>
<script src="js/product-editor.js?v=20261010-ajax1"></script>
<script src="js/product-reports.js?v=20261013-access1"></script>
<script src="js/ean-scanner.js?v=20261014-single-guide"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/script.js?v=20261013-zoom2"></script>
<?php if (current_user()): ?><script src="js/scan-library.js?v=20261010-result2"></script><?php endif; ?>
<?php endif; ?>
<script src="js/scan-photo.js?v=20261011-orientation1"></script>
<script src="js/dialog-scroll.js?v=20261014-offline1"></script>
<script src="js/title-icons.js?v=20261011-icons1"></script>

<script src="js/botones.js?v=20261014-branch-choice"></script>
<script src="js/report-notifications.js?v=20261011-pendingcounts1"></script>
<script src="js/notifications.js?v=20261011-compactpalets17"></script>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/stock-preferences.js?v=20261013-zoom2"></script><script src="js/public-name.js?v=20261013-public1"></script><script src="js/account-edit.js?v=20261011-dialog2"></script><script src="js/account-security.js?v=20261011-security2"></script><?php endif; ?>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/vendor/qrcode.min.js"></script><script src="js/user-profiles.js?v=20261013-identification1"></script><?php endif; ?>
<?php if (!empty($paginaInicio) || (!empty($paginaCuenta) && empty($paginaTops) && current_user())): ?>
<?php include __DIR__ . '/onboarding.vista.php'; ?>
<script src="js/onboarding.js?v=20261013-transition1"></script>
<?php endif; ?>
<?php if (!empty($paginaTops)): ?><script src="js/tops.js?v=20261013-tops2"></script><?php endif; ?>
<?php if (!empty($paginaStock)): ?><script src="js/pallet-activity.js?v=20261014-toasts"></script><link rel="stylesheet" href="css/stock-export.css?v=20261013-export1"><script src="js/stock-export.js?v=20261013-export1"></script><link rel="stylesheet" href="css/stock-overview.css?v=20261014-toasts"><script src="js/stock-overview.js?v=20261014-pallet2"></script><?php endif; ?>
<script src="js/motion.js?v=20261014-offline1"></script>
</body>
</html>
