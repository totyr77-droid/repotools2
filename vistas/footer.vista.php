<!-- GR FOOTER -->
<footer class="footer" id="contacto">
  <div class="footer__texto">
    <p>© 2023 - 2026</p>
    <p>Derechos reservados para GR Diseño y Repotools</p>

    <div class="footer__texto__redes">
      <a target="_blank" rel="noopener" href="https://www.instagram.com/greeyes_/" aria-label="Instagram">
        <i class="fab fa-instagram"></i>
      </a>
      <a target="_blank" rel="noopener" href="https://wa.me/message/CFL7IWQ6XWPRM1" aria-label="WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
      </a>
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

<!-- COOKIES -->
<div class="cookies" id="cookies">
  <div class="cookies-box">
    <div class="cookies-img">
      <img src="https://www.svgrepo.com/show/30963/cookie.svg" alt="Galleta">
    </div>
    <p>Usamos almacenamiento local para guardar tus escaneos.</p>
    <p>Al continuar, aceptas su uso.</p>
    <p id="aceptarCookies">Aceptar</p>
  </div>
</div>

<!-- SCRIPTS -->
<?php if (!empty($paginaStock)): ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/stock.js"></script>
<?php else: ?>
<script src="https://unpkg.com/quagga@0.12.1/dist/quagga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lz-string@1.4.4/libs/lz-string.min.js"></script>
<script src="js/script.js"></script>
<?php endif; ?>
<script src="js/botones.js"></script>
</body>
</html>
