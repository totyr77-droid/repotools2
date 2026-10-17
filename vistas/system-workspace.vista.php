<section id="systemWorkspace" class="system-workspace" hidden aria-labelledby="systemTitle">
  <h2 id="systemTitle"><?= stock_icon('clock') ?>Sistema</h2>
  <p>Rendimiento del escáner</p>
  <form id="systemFilters" class="system-filters">
    <label>Período<select name="days"><option value="1">Últimas 24 horas</option><option value="7" selected>Últimos 7 días</option><option value="30">Últimos 30 días</option></select></label>
    <label>Lector<select name="mode"><option value="">QR y EAN</option><option value="qr">QR</option><option value="ean">EAN</option></select></label>
    <label>Versión<select name="version"><option value="">Todas las versiones</option></select></label>
    <details><summary>Más filtros</summary><label>Navegador<select name="browser"><option value="">Todos</option><option value="safari">Safari</option><option value="chrome">Chrome</option><option value="edge">Edge</option><option value="firefox">Firefox</option><option value="other">Otro</option></select></label><label>Método<select name="source"><option value="">Todos</option><option value="camera">Cámara</option><option value="background">Análisis de imagen</option><option value="ocr">Números por OCR</option><option value="none">Sin lectura</option></select></label></details>
    <button type="button" id="refreshSystem" class="library-secondary"><?= stock_icon('refresh') ?>Actualizar</button>
  </form>
  <p id="systemStatus" role="status" class="library-status"></p>
  <div id="systemSummary" class="system-summary"></div>
  <p class="system-hint">Lectura: desde cámara lista hasta confirmar, con pantalla activa; incluye apuntar. Apertura: desde pedir cámara hasta que está lista. Los promedios de lectura usan solo lecturas confirmadas. QR incluye OCR; podés separarlo por método.</p>
  <h3>Registros recibidos</h3>
  <div id="systemRecords"></div>
  <div class="system-pagination"><button id="systemPrevious" type="button" class="library-secondary">Anterior</button><span id="systemPage"></span><button id="systemNext" type="button" class="library-secondary">Siguiente</button></div>
</section>
