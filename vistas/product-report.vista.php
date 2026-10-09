<dialog id="productReportDialog" class="library-dialog product-edit-dialog" aria-labelledby="productReportTitle">
  <div class="library-heading"><h2 id="productReportTitle">Reportar producto</h2><button id="closeProductReport" type="button" class="library-secondary">Cerrar</button></div>
  <p id="productReportStatus" class="library-status" role="status"></p>
  <form id="productReportForm" hidden>
    <p id="productReportEan" class="product-edit-ean"></p>
    <label id="reportProblem">¿Qué está mal?<textarea name="description" required minlength="5" maxlength="1000" rows="3" placeholder="Ej.: la presentación es de 500 ml"></textarea></label>
    <p id="reportInstructions" class="product-edit-note">Propón los datos correctos. Un moderador los revisará.</p>
    <label>Nombre<input name="name" maxlength="120"></label>
    <label>Presentación<input name="quantity" maxlength="80" placeholder="Ej.: 500 ml"></label>
    <details><summary>Más datos</summary><label>Marca<input name="brand" maxlength="160"></label><label>Categoría<input name="category" maxlength="240"></label><label>Envase<input name="packaging" maxlength="240"></label></details>
    <fieldset id="reportPhotos"><legend>Fotos opcionales</legend><p class="product-edit-note">Muestra el código y el frente del producto.</p>
      <div class="report-photo"><p>Foto del EAN</p><img id="reportPreviewEan" class="report-photo-preview" alt="Foto del código EAN" hidden><div class="report-photo-actions"><button type="button" class="library-secondary" data-report-camera="ean">Tomar foto del EAN</button><button type="button" class="library-secondary" data-report-remove="ean" hidden>Quitar foto</button></div></div>
      <div class="report-photo"><p>Foto del producto</p><img id="reportPreviewProduct" class="report-photo-preview" alt="Foto del producto" hidden><div class="report-photo-actions"><button type="button" class="library-secondary" data-report-camera="product">Tomar foto del producto</button><button type="button" class="library-secondary" data-report-remove="product" hidden>Quitar foto</button></div></div>
    </fieldset>
    <p id="reportPhotosNote" class="product-edit-note"></p>
    <div class="library-dialog-actions"><a href="reportes.php" class="library-secondary">Mis reportes</a><button id="sendProductReport" type="submit" class="library-primary">Enviar reporte</button></div>
  </form>
</dialog>
<dialog id="reportCameraDialog" class="library-dialog product-edit-dialog" aria-labelledby="reportCameraTitle">
  <div class="library-heading"><h2 id="reportCameraTitle">Tomar foto</h2><button id="closeReportCamera" class="library-secondary" type="button">Cancelar</button></div>
  <p id="reportCameraStatus" class="library-status" role="status"></p>
  <video id="reportCameraVideo" autoplay muted playsinline></video>
  <div class="library-dialog-actions"><button id="retryReportCamera" class="library-secondary" type="button" hidden>Reintentar</button><button id="captureReportPhoto" class="library-primary" type="button" disabled>Tomar foto</button></div>
</dialog>
