<dialog id="productReportDialog" class="library-dialog product-edit-dialog" aria-labelledby="productReportTitle">
  <div class="library-heading"><h2 id="productReportTitle">Reportar producto</h2><button id="closeProductReport" type="button" class="library-secondary app-close" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <p id="productReportStatus" class="library-status" role="status"></p>
  <form id="productReportForm" hidden>
    <p id="productReportEan" class="product-edit-ean"></p>
    <label id="reportProblem">¿Qué está mal? <small>(opcional)</small><textarea name="description" minlength="5" maxlength="1000" rows="3" placeholder="Ej.: la presentación es de 500 ml"></textarea></label>
    <p id="reportInstructions" class="product-edit-note">Propón los datos correctos. Un moderador los revisará.</p>
    <label>Marca<input name="brand" maxlength="160" placeholder="Ej.: Red Bull"></label>
    <label>Producto<input name="name" maxlength="120" placeholder="Ej.: Energizante Lata"></label>
    <label>Contenido neto<input name="quantity" maxlength="80" placeholder="Ej.: 250 ml, 500 g o 1 kg"></label>
    <details class="library-disclosure report-more-data"><summary>Más datos<svg class="library-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary><label>Categoría<input name="category" maxlength="240" placeholder="Ej.: Bebidas, limpieza o alimentos"></label><label>Envase<input name="packaging" maxlength="240" placeholder="Ej.: Lata, botella o bolsa"></label></details>
    <fieldset id="reportPhotos"><legend>Fotos opcionales</legend><p class="product-edit-note">Muestra el código y el frente del producto.</p>
      <div class="report-photo"><p>Foto del EAN</p><img id="reportPreviewEan" class="report-photo-preview" alt="Foto del código EAN" hidden><div class="report-photo-actions"><button type="button" class="library-secondary" data-report-camera="ean">Tomar foto del EAN</button><button type="button" class="library-secondary" data-report-remove="ean" hidden>Quitar foto</button></div></div>
      <div class="report-photo"><p>Foto del producto</p><img id="reportPreviewProduct" class="report-photo-preview" alt="Foto del producto" hidden><div class="report-photo-actions"><button type="button" class="library-secondary" data-report-camera="product">Tomar foto del producto</button><button type="button" class="library-secondary" data-report-remove="product" hidden>Quitar foto</button></div></div>
    </fieldset>
    <p id="reportPhotosNote" class="product-edit-note" role="status"></p>
    <div class="library-dialog-actions"><a href="reportes.php" class="library-secondary">Mis reportes</a><button id="sendProductReport" type="submit" class="library-primary">Enviar reporte</button></div>
  </form>
</dialog>
<dialog id="reportCameraDialog" class="library-dialog product-edit-dialog" aria-labelledby="reportCameraTitle">
  <div class="library-heading"><h2 id="reportCameraTitle">Tomar foto</h2><button id="closeReportCamera" class="library-secondary app-close" type="button" aria-label="Cerrar cámara" title="Cerrar cámara"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <p id="reportCameraStatus" class="library-status" role="status"></p>
  <div class="report-camera-frame"><video id="reportCameraVideo" autoplay muted playsinline></video><button id="reportCameraFlash" class="report-camera-flash" type="button" aria-label="Encender luz" title="Encender luz" aria-pressed="false" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-12h-7Z"/></svg></button></div>
  <div class="library-dialog-actions"><button id="retryReportCamera" class="library-secondary" type="button" hidden>Reintentar</button><button id="captureReportPhoto" class="library-primary" type="button" disabled>Tomar foto</button></div>
</dialog>
