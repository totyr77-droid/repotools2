<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/pro-subscriptions.php';
if(!current_user()){header('Location: cuenta.php');exit;}
try{pro_require_tester(database(),(int)current_user()['id']);}catch(CatalogException $e){http_response_code(403);exit('La suscripción está en pruebas privadas.');}
$paginaPro=true;
$publicPageTitle='Mi suscripción Pro';
include __DIR__.'/vistas/header.vista.php';
?>
<main class="library-main pro-main" id="proSubscription" data-csrf="<?= escape(csrf_token()) ?>">
  <h1><?= stock_icon('user') ?> Mi suscripción <span class="pro-badge">PRO</span></h1>
  <p class="stock-muted">Pruebas de beta · sin cobros reales.</p>
  <p>Compartí palets y tu stock, transferí la propiedad y gestioná accesos.</p>
  <p id="proStatus" role="status">Consultando suscripción…</p>
  <p id="proPrice" hidden></p>
  <p id="proTerms" hidden>Renovación automática mensual. Podés cancelarla y conservar el período ya pagado.</p>
  <div class="pro-actions"><button id="proCheckout" class="library-primary" type="button" hidden>Probar suscripción</button><button id="proCancel" class="library-secondary" type="button" hidden>Cancelar renovación</button></div>
  <a class="library-secondary" href="cuenta.php">Volver a Ajustes</a>
</main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
