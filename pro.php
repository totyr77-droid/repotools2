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
  <p class="pro-description">Compartí palets y stock, transferí entre palets o usuarios y gestioná accesos.</p>
  <p id="proStatus" class="pro-state" role="status">Consultando suscripción…</p>
  <p id="proPrice" hidden></p>
  <p id="proTerms" hidden>Renovación automática mensual. Podés cancelarla y conservar el período ya pagado.</p>
  <div class="pro-actions"><button id="proCheckout" class="library-primary" type="button" hidden>Probar suscripción</button><button id="proCancel" class="library-secondary" type="button" hidden>Cancelar renovación</button></div>
  <p class="pro-payment-note">El formulario de tarjeta lo procesa Mercado Pago. RepoTools no recibe el número ni el código de seguridad de tu tarjeta.</p>
  <a class="help-back" href="cuenta.php">Volver a Ajustes</a>
</main>
<dialog id="proPaymentDialog" class="library-dialog pro-payment-dialog" aria-labelledby="proPaymentTitle">
<div class="library-heading"><h2 id="proPaymentTitle"><?= stock_icon('crown') ?>Unirme a <span class="pro-badge">PRO</span></h2><button type="button" class="app-close" id="closeProPayment" aria-label="Cerrar"><?= stock_icon('close') ?></button></div>
<p class="pro-test-note">Pago de prueba · usá una tarjeta ficticia</p><p id="proPaymentPrice" class="pro-payment-price"></p><p class="pro-payment-terms">Renovación mensual automática. Podés cancelarla y conservar el período vigente.</p>
<div id="proPaymentSummary"><ul class="pro-benefits"><li>Compartí palets y stock.</li><li>Transferí a otro palet o usuario.</li><li>Gestioná accesos para colaborar.</li></ul><button id="proContinueCard" type="button" class="library-primary">Continuar con tarjeta</button></div>
<p class="pro-payment-note"><?= stock_icon('shield') ?>Ingresás la tarjeta en campos de Mercado Pago. RepoTools no recibe tu número ni código de seguridad.</p>
<p id="proPaymentStatus" class="stock-muted" role="status"></p><div id="proCardSlot"></div>

</dialog>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
