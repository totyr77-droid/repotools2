<?php
require_once __DIR__ . '/includes/auth.php';
$paginaCuenta = true; $paginaReportes = true;
include __DIR__ . '/vistas/header.vista.php';
$moderationView = $navModerator && ($_GET['queue'] ?? '') === '1';
?>
<main class="reports-main" data-report-view="<?= $moderationView ? 'queue' : 'own' ?>"><section class="reports-panel">
  <div class="library-heading"><div><p class="app-resumen__eyebrow"><?= $moderationView ? 'Moderación' : 'Mi cuenta' ?></p><h1><?= $moderationView ? 'Reportes pendientes' : 'Mis reportes' ?></h1></div><a href="cuenta.php" class="library-secondary">Mi cuenta</a></div>
  <p id="reportActionStatus" class="library-status" role="status"></p><p id="reportsStatus" class="library-status" role="status"></p><div id="reportsList" class="reports-list"></div>
</section></main>
<dialog id="reviewReportDialog" class="library-dialog product-edit-dialog" aria-labelledby="reviewReportTitle">
  <div class="library-heading"><h2 id="reviewReportTitle">Reporte</h2><button id="closeReviewReport" class="library-secondary app-close" type="button" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <p id="reviewReportStatus" class="library-status" role="status"></p><div id="reviewReportDetails"></div>
  <form id="reviewReportForm" hidden>
    <p class="product-edit-note">Revisa la propuesta y ajusta los datos si hace falta.</p>
    <label>Marca<input name="brand" maxlength="160" placeholder="Ej.: Red Bull"></label><label>Producto<input name="name" maxlength="120" placeholder="Ej.: Energizante Lata" required></label><label>Contenido neto<input name="quantity" maxlength="80" placeholder="Ej.: 250 ml, 500 g o 1 kg"></label><details class="library-disclosure report-more-data"><summary>Más datos<svg class="library-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary><label>Categoría<input name="category" maxlength="240" placeholder="Ej.: Bebidas, limpieza o alimentos"></label><label>Envase<input name="packaging" maxlength="240" placeholder="Ej.: Lata, botella o bolsa"></label></details>
    <label>Nota de revisión <small>(opcional)</small><textarea name="review_note" maxlength="1000" rows="3" placeholder="Ej.: La foto no permite confirmar el cambio"></textarea></label>
    <div class="library-dialog-actions"><button type="button" id="rejectReport" class="library-secondary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>Rechazar</button><button type="submit" id="approveReport" class="library-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Aprobar</button></div>
  </form>
</dialog>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
