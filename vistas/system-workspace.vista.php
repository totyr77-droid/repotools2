<section id="systemWorkspace" class="system-workspace" hidden aria-labelledby="systemTitle">
  <h2 id="systemTitle"><?= stock_icon('clock') ?>Sistema</h2>
  <?php include __DIR__.'/commercial-plans.vista.php'; ?>
  <p id="systemPushHealth" class="system-push-health system-hint" role="status"><?= stock_icon('bell') ?><span>Consultando envío de avisos…</span></p>
  <p>Rendimiento del escáner</p>
  <form id="systemFilters" class="system-filters">
    <label>Período<select name="days"><option value="1">Últimas 24 horas</option><option value="7" selected>Últimos 7 días</option><option value="30">Últimos 30 días</option></select></label>
    <label>Lector<select name="mode"><option value="">QR y EAN</option><option value="qr">QR</option><option value="ean">EAN</option></select></label>
    <label>Versión<select name="version"><option value="">Todas las versiones</option></select></label>
    <details><summary>Más filtros</summary><label>Navegador<select name="browser"><option value="">Todos</option><option value="safari">Safari</option><option value="chrome">Chrome</option><option value="edge">Edge</option><option value="firefox">Firefox</option><option value="other">Otro</option></select></label><label>Dispositivo<select name="device"><option value="">Todos</option><option value="mobile">Celular</option><option value="tablet">Tablet</option><option value="desktop">PC</option></select></label><label>Modo<select name="rapid"><option value="">Normal y rápido</option><option value="0">Normal</option><option value="1">Rápido</option></select></label><label>Método<select name="source"><option value="">Todos</option><option value="camera">Cámara</option><option value="background">Análisis de imagen</option><option value="ocr">Números por OCR</option><option value="none">Sin lectura</option></select></label></details>
    <button type="button" id="refreshSystem" class="library-secondary"><?= stock_icon('refresh') ?>Actualizar</button>
  </form>
  <p id="systemStatus" role="status" class="library-status"></p>
  <p id="systemDelivery" class="system-hint" hidden></p><div id="systemSummary" class="system-summary"></div>
  <p class="system-hint">Lectura: cámara lista → confirmación; incluye apuntar. Apertura se mide aparte. Mediana y P90 usan confirmados: compará el mismo lector, dispositivo y modo. QR puede incluir OCR.</p>
  <h3>Registros recibidos</h3>
  <div id="systemRecords"></div>
  <div class="system-pagination"><button id="systemPrevious" type="button" class="library-secondary">Anterior</button><span id="systemPage"></span><button id="systemNext" type="button" class="library-secondary">Siguiente</button></div>
</section>
