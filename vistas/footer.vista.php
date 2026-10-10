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
  <p class="alerta-cerrar"><i class="fa-solid fa-xmark"></i> CERRAR</p>
</div>

<?php if (!empty($paginaInicio) || !empty($paginaBiblioteca)): ?>
<?php include __DIR__ . '/product-editor.vista.php'; ?>
<?php include __DIR__ . '/product-report.vista.php'; ?>
<?php endif; ?>
<!-- SCRIPTS -->
<?php if (!empty($paginaBiblioteca)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="js/ean-scanner.js?v=20261010-scan2"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/product-info.js"></script>
<script src="js/product-editor.js"></script>
<script src="js/product-reports.js"></script>
<script src="js/biblioteca.js"></script>
<?php elseif (!empty($paginaReportes)): ?>
<script src="js/product-reports.js"></script>
<script src="js/reports-page.js"></script>
<?php elseif (!empty($paginaStock)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/ean-scanner.js?v=20261010-scan2"></script>
<script src="js/product-info.js"></script>
<script src="js/stock.js"></script>
<?php elseif (empty($paginaCuenta)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lz-string@1.4.4/libs/lz-string.min.js"></script>
<script src="js/product-info.js"></script>
<script src="js/product-editor.js"></script>
<script src="js/product-reports.js"></script>
<script src="js/ean-scanner.js?v=20261010-scan2"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/script.js?v=20261010-home3"></script>
<?php endif; ?>
<script src="js/botones.js"></script>
<script src="js/report-notifications.js"></script>
<script src="js/notifications.js"></script>
</body>
</html>
