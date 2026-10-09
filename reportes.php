<?php
require_once __DIR__ . '/includes/auth.php';
$paginaCuenta = true; $paginaReportes = true;
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="reports-main"><section class="reports-panel">
  <div class="library-heading"><h1>Reportes</h1><a href="cuenta.php" class="library-secondary">Mi cuenta</a></div>
  <div class="reports-tabs"><button id="ownReports" class="library-secondary" type="button">Mis reportes</button><button id="queueReports" class="library-secondary" type="button" hidden>Pendientes de revisión</button></div>
  <p id="reportsStatus" class="library-status" role="status"></p><div id="reportsList" class="reports-list"></div>
</section></main>
<dialog id="reviewReportDialog" class="library-dialog product-edit-dialog" aria-labelledby="reviewReportTitle">
  <div class="library-heading"><h2 id="reviewReportTitle">Reporte</h2><button id="closeReviewReport" class="library-secondary" type="button">Cerrar</button></div>
  <p id="reviewReportStatus" class="library-status" role="status"></p><div id="reviewReportDetails"></div>
  <form id="reviewReportForm" hidden>
    <p class="product-edit-note">Revisa la propuesta y ajusta los datos si hace falta.</p>
    <label>Marca<input name="brand" maxlength="160" placeholder="Ej.: Red Bull"></label><label>Producto<input name="name" maxlength="120" placeholder="Ej.: Energizante Lata" required></label><label>Contenido neto<input name="quantity" maxlength="80" placeholder="Ej.: 250 ml, 500 g o 1 kg"></label><label>Categoría<input name="category" maxlength="240"></label><label>Envase<input name="packaging" maxlength="240"></label>
    <label>Nota de revisión<textarea name="review_note" maxlength="1000" rows="3" placeholder="Obligatoria si rechazas"></textarea></label>
    <div class="library-dialog-actions"><button type="button" id="rejectReport" class="library-secondary">Rechazar</button><button type="submit" id="approveReport" class="library-primary">Aprobar y actualizar</button></div>
  </form>
</dialog>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
