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
<script>window.repotoolsOfflineUser=<?= (int)(current_user()['id']??0) ?>;</script><script src="js/offline-cache.js?v=20261011-camera3"></script>
<script src="js/date-time.js?v=20261011-dates1"></script>
<script src="js/navigation.js?v=20261011-branches1"></script>
<script src="js/action-notices.js?v=20261011-branches2"></script>
<?php if (!empty($paginaBiblioteca)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="js/ean-scanner.js?v=20261011-orientation4"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/product-info.js?v=20261011-fresh2"></script>
<script src="js/product-editor.js?v=20261010-ajax1"></script>
<script src="js/product-reports.js?v=20261011-reportphotofallback10"></script>
<script src="js/biblioteca.js?v=20261011-reference4"></script>
<?php elseif (!empty($paginaReportes)): ?>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="js/product-reports.js?v=20261011-orientation4"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/ean-scanner.js?v=20261011-orientation4"></script>
<script src="js/reports-page.js?v=20261011-profilelogs1"></script>
<?php elseif (!empty($paginaStock)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/ean-scanner.js?v=20261011-orientation4"></script>
<script src="js/product-info.js?v=20261011-fresh2"></script>
<script src="js/vendor/qrcode.min.js"></script>
<script src="js/stock-utils.js?v=20261011-compactpalets17"></script>
<script src="js/pallet-label.js?v=20261010-stock13"></script>
<script src="js/stock-offline.js?v=20261011-offline1"></script>
<script src="js/stock.js?v=20261011-branches2"></script>
<script src="js/public-pallet.js?v=20261010-stock13"></script>
<script src="js/stock-branches.js?v=20261012-preview"></script>
<?php elseif (empty($paginaCuenta)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lz-string@1.4.4/libs/lz-string.min.js"></script>
<script src="js/product-info.js?v=20261011-fresh2"></script>
<script src="js/product-editor.js?v=20261010-ajax1"></script>
<script src="js/product-reports.js?v=20261011-orientation4"></script>
<script src="js/ean-scanner.js?v=20261011-orientation4"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/script.js?v=20261011-result3"></script>
<?php if (current_user()): ?><script src="js/scan-library.js?v=20261010-result2"></script><?php endif; ?>
<?php endif; ?>
<script src="js/scan-photo.js?v=20261011-orientation1"></script>
<script src="js/dialog-scroll.js?v=20261011-lock1"></script>
<script src="js/title-icons.js?v=20261011-icons1"></script>
<script src="js/botones.js?v=20261011-password2"></script>
<script src="js/report-notifications.js?v=20261011-pendingcounts1"></script>
<script src="js/notifications.js?v=20261011-compactpalets17"></script>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/stock-preferences.js?v=20261010-stock13"></script><script src="js/account-edit.js?v=20261011-dialog2"></script><script src="js/account-security.js?v=20261011-security2"></script><?php endif; ?>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/vendor/qrcode.min.js"></script><script src="js/user-profiles.js?v=20261011-directory7"></script><?php endif; ?>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/hand-preferences.js?v=20261011-alert2"></script><?php endif; ?>
</body>
</html>
