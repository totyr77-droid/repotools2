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
<script src="js/navigation.js?v=20261010-stock11"></script>
<script src="js/action-notices.js?v=20261010-notices1"></script>
<?php if (!empty($paginaBiblioteca)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="js/ean-scanner.js?v=20261010-palletzoom1"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/product-info.js?v=20261010-result2"></script>
<script src="js/product-editor.js?v=20261010-ajax1"></script>
<script src="js/product-reports.js?v=20261010-report2"></script>
<script src="js/biblioteca.js?v=20261010-manual1"></script>
<?php elseif (!empty($paginaReportes)): ?>
<script src="js/product-reports.js?v=20261010-report2"></script>
<script src="js/reports-page.js?v=20261010-review1"></script>
<?php elseif (!empty($paginaStock)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/ean-scanner.js?v=20261010-palletzoom1"></script>
<script src="js/product-info.js?v=20261010-result2"></script>
<script src="js/vendor/qrcode.min.js"></script>
<script src="js/stock-utils.js?v=20261010-stock11"></script>
<script src="js/pallet-label.js?v=20261010-stock11"></script>
<script src="js/stock.js?v=20261010-stock11"></script>
<script src="js/public-pallet.js?v=20261010-stock11"></script>
<?php elseif (empty($paginaCuenta)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lz-string@1.4.4/libs/lz-string.min.js"></script>
<script src="js/product-info.js?v=20261010-result2"></script>
<script src="js/product-editor.js?v=20261010-ajax1"></script>
<script src="js/product-reports.js?v=20261010-report2"></script>
<script src="js/ean-scanner.js?v=20261010-palletzoom1"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/script.js?v=20261010-manual1"></script>
<?php if (current_user()): ?><script src="js/scan-library.js?v=20261010-result2"></script><?php endif; ?>
<?php endif; ?>
<script src="js/botones.js?v=20261010-notices1"></script>
<script src="js/report-notifications.js?v=20261010-ajax1"></script>
<script src="js/notifications.js?v=20261010-stock11"></script>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/stock-preferences.js?v=20261010-stock11"></script><?php endif; ?>
</body>
</html>
