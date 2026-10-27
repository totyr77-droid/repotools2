<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/pro-subscriptions.php';
if(!current_user()){header('Location: cuenta.php');exit;}
try{pro_require_tester(database(),(int)current_user()['id']);}catch(CatalogException $e){http_response_code(403);exit('La suscripción está en pruebas privadas.');}
$paginaPro=true;
$publicPageTitle='Mi plan';
include __DIR__.'/vistas/header.vista.php';
?>
<main class="library-main pro-main" id="proSubscription" data-csrf="<?= escape(csrf_token()) ?>">
  <h1>Mi plan</h1>
  <section class="pro-account-plan" aria-label="Detalle de tu plan">
    <div class="pro-account-heading"><span class="pro-account-emblem"><?= stock_icon('crown') ?></span><div><span class="pro-account-eyebrow">Tu plan</span><h2 id="proPlanName">Consultando…</h2></div><span id="proActiveBadge" class="pro-account-active" hidden><?= stock_icon('check') ?>Activo</span></div>
    <div class="pro-account-body">
      <p class="pro-description">Compartí stock, transferí palets y gestioná accesos.</p>
      <p id="proStatus" class="pro-state" role="status">Consultando suscripción…</p>
      <dl class="pro-account-details"><div id="proValidityRow" hidden><dt>Acceso incluido hasta</dt><dd id="proValidity"></dd></div><div id="proPriceRow" hidden><dt>Importe mensual</dt><dd id="proPrice"></dd></div></dl>
      <p id="proTerms" class="pro-account-terms" hidden></p>
      <div class="pro-actions"><button id="proRefresh" class="library-secondary" type="button" hidden>Comprobar pago</button><button id="proCheckout" class="library-primary" type="button" hidden>Unirme a PRO</button><button id="proCancel" class="library-secondary" type="button" hidden>Cancelar renovación</button></div>
    </div>
  </section>
  <p class="pro-payment-note"><?= stock_icon('shield') ?><span>Pagos gestionados por Mercado Pago. Los datos de tu tarjeta se ingresan en sus campos seguros; RepoTools no recibe el número ni el código de seguridad.</span></p>
  <a class="help-back pro-account-back" href="cuenta.php"><?= stock_icon('chevron') ?>Volver a Ajustes</a>
</main>
<dialog id="proPaymentDialog" class="library-dialog pro-payment-dialog" aria-labelledby="proPaymentTitle">
<div class="library-heading"><h2 id="proPaymentTitle"><?= stock_icon('crown') ?>Unirme a <span class="pro-badge">PRO</span></h2><button type="button" class="app-close" id="closeProPayment" aria-label="Cerrar"><?= stock_icon('close') ?></button></div>
<div class="pro-checkout-summary"><div><span>Plan PRO · mensual</span><p id="proPaymentPrice" class="pro-payment-price"></p></div><span class="pro-checkout-provider"><?= stock_icon('shield') ?>Mercado Pago</span></div><p class="pro-test-note">Pago de prueba · usá una tarjeta ficticia</p><p class="pro-payment-terms">Renovación mensual. Cancelá cuando quieras y conservá el período vigente.</p>
<div id="proPaymentSummary"><ul class="pro-benefits"><li>Compartí palets y stock.</li><li>Transferí a otro palet o usuario.</li><li>Gestioná accesos para colaborar.</li></ul><button id="proContinueCard" type="button" class="library-primary">Continuar con tarjeta</button></div>
<p class="pro-payment-note"><?= stock_icon('shield') ?>Ingresás la tarjeta en campos de Mercado Pago. RepoTools no recibe tu número ni código de seguridad.</p>
<p id="proPaymentStatus" class="stock-muted" role="status"></p><div id="proCardSlot"></div>

</dialog>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
