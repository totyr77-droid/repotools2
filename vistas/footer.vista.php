<!-- Créditos y contacto -->
<footer class="footer site-footer" id="contacto">
  <div class="site-footer__inner">
    <div class="site-footer__top">
      <div class="site-footer__identity">
        <a class="site-footer__brand" href="./" aria-label="Repotools, inicio">REPOTOOLS<span aria-hidden="true">.</span></a>
        <p>Herramientas para tu día a día.</p>
      </div>
      <nav class="site-footer__contact" aria-label="Contacto y redes sociales">
        <a target="_blank" rel="noopener noreferrer" href="https://www.instagram.com/greeyes_/"><i class="fab fa-instagram" aria-hidden="true"></i><span>Instagram</span></a>
        <a target="_blank" rel="noopener noreferrer" href="https://wa.me/message/CFL7IWQ6XWPRM1"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>Contacto</span></a>
      </nav>
    </div>
    <div class="site-footer__bottom">
      <small>&copy; 2023–2026 Repotools. Todos los derechos reservados.</small>
      <p class="site-footer__credit">Diseño y desarrollo por <a href="https://www.instagram.com/greeyes_/" target="_blank" rel="noopener noreferrer">GR Diseño <span aria-hidden="true">↗</span></a></p>
    </div>
  </div>
</footer>

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

<!-- SCRIPTS -->
<?php if (!empty($paginaStock)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/stock.js"></script>
<?php elseif (empty($paginaCuenta)): ?>
<script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lz-string@1.4.4/libs/lz-string.min.js"></script>
<script src="js/script.js"></script>
<?php endif; ?>
<script src="js/botones.js"></script>
</body>
</html>
