<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/pro-subscriptions.php';
if(!current_user()){header('Location: cuenta.php');exit;}
try{pro_require_tester(database(),(int)current_user()['id']);$product=billing_product($_GET['product']??'pro');}catch(CatalogException $e){http_response_code($e->status);exit($e->status===422?'Plan no válido.':'La suscripción está en pruebas privadas.');}
$ownedTeams=$product==='supervision'?supervision_owned(database(),(int)current_user()['id']):[];
$teamId=0;if($product==='supervision'){try{$rawTeam=$_GET['team_id']??'';if(!is_string($rawTeam)||($rawTeam!==''&&!preg_match('/^[1-9][0-9]{0,14}$/D',$rawTeam)))throw new CatalogException('Equipo no válido.',422);$teamId=supervision_billing_team(database(),(int)current_user()['id'],$rawTeam!==''?(int)$rawTeam:(int)($ownedTeams[0]['id']??0));}catch(CatalogException $e){http_response_code($e->status);exit(escape($e->getMessage()));}}
$planLabel=$product==='supervision'?'Supervisión':'PRO';
$paginaPro=true;
$publicPageTitle='Mi plan';
include __DIR__.'/vistas/header.vista.php';
?>
<main class="library-main pro-main" id="proSubscription" data-team-id="<?= $teamId ?>" data-product="<?= escape($product) ?>" data-csrf="<?= escape(csrf_token()) ?>">
  <h1><?= stock_icon('crown') ?>Mi plan</h1>
  <?php if($product==='supervision'): ?><div class="pro-team-context team-switcher"><?php if($ownedTeams): ?><label><span class="team-switcher-label">Equipo de trabajo</span><span class="team-switcher-field"><?= stock_icon('users') ?><select id="proTeamSelect"><?php foreach($ownedTeams as $team): ?><option value="<?= (int)$team['id'] ?>"<?= (int)$team['id']===$teamId?' selected':'' ?>><?= escape($team['name']) ?></option><?php endforeach; ?></select><?= stock_icon('chevron') ?></span></label><?php endif; ?><?php if($ownedTeams): ?><div class="team-switcher-actions"><button id="proNewTeam" type="button" class="library-secondary team-switcher-action"><?= stock_icon('plus') ?><span>Contratar otro equipo</span></button></div><?php endif; ?><p class="stock-muted">Cada equipo tiene su propia suscripción y hasta 10 integrantes, además del supervisor.</p></div><?php endif; ?>
  <section class="pro-account-plan" aria-label="Detalle de tu plan">
    <div class="pro-account-heading"><span class="pro-account-emblem"><?= stock_icon('crown') ?></span><div><span class="pro-account-eyebrow">Tu plan</span><h2 id="proPlanName">Consultando…</h2></div><span id="proActiveBadge" class="pro-account-active" hidden><?= stock_icon('check') ?>Activo</span></div>
    <div class="pro-account-body">
      <p class="pro-description"><?= $product==='supervision'?'Consultá el stock y los movimientos de tu equipo, con accesos autorizados.':'Compartí stock, transferí palets y gestioná accesos.' ?></p>
      <p id="proStatus" class="pro-state" role="status">Consultando suscripción…</p>
      <dl class="pro-account-details"><div id="proValidityRow" hidden><dt>Acceso incluido hasta</dt><dd id="proValidity"></dd></div><div id="proPriceRow" hidden><dt>Importe mensual</dt><dd id="proPrice"></dd></div></dl>
      <p id="proTerms" class="pro-account-terms" hidden></p>
      <form id="proTeamNameForm" hidden><label for="proTeamName">¿Cómo se llama tu equipo?</label><input id="proTeamName" name="name" type="text" maxlength="100" autocomplete="organization" required placeholder="Ej.: Equipo Centro"><p class="stock-muted">El pago está confirmado. Elegí el nombre que verán tus integrantes.</p><button class="library-primary" type="submit">Guardar y abrir mi equipo</button></form>
      <div class="pro-actions"><a id="proOpenTeam" href="supervision.php" class="library-primary" hidden>Ir a mi equipo</a><button id="proRefresh" class="library-secondary" type="button" hidden>Comprobar pago</button><button id="proCheckout" class="library-primary" type="button" hidden>Unirme a <?= escape($planLabel) ?></button><button id="proCancel" class="library-secondary" type="button" hidden>Cancelar renovación</button></div>
    </div>
  </section>
  <p class="pro-payment-note"><?= stock_icon('shield') ?><span>Pagos gestionados por Mercado Pago. Los datos de tu tarjeta se ingresan en sus campos seguros; RepoTools no recibe el número ni el código de seguridad.</span></p>
  <nav class="pro-plan-links" aria-label="Mis planes"><a href="pro.php">PRO</a><a href="pro.php?product=supervision">Supervisión</a></nav><a class="help-back pro-account-back" href="cuenta.php"><?= stock_icon('chevron') ?>Volver a Ajustes</a>
</main>
<dialog id="proPaymentDialog" class="library-dialog pro-payment-dialog" aria-labelledby="proPaymentTitle">
<div class="library-heading"><h2 id="proPaymentTitle"><?= stock_icon('crown') ?>Unirme a <span class="pro-badge"><?= escape($planLabel) ?></span></h2><button type="button" class="app-close" id="closeProPayment" aria-label="Cerrar"><?= stock_icon('close') ?></button></div>
<div class="pro-payment-content">
<div class="pro-checkout-summary"><div><span>Plan <?= escape($planLabel) ?> · mensual</span><p id="proPaymentPrice" class="pro-payment-price"></p></div><span class="pro-checkout-provider"><?= stock_icon('shield') ?>Mercado Pago</span></div><p class="pro-test-note">Pago de prueba · usá una tarjeta ficticia</p><p class="pro-payment-terms">Renovación mensual. Cancelá cuando quieras y conservá el período vigente.</p>
<div id="proPaymentSummary"><ul class="pro-benefits"><?php if($product==='supervision'): ?><li>Un equipo con hasta 10 integrantes.</li><li>Stock y movimientos del equipo.</li><li>Cada integrante autoriza el acceso a su stock.</li><?php else: ?><li>Compartí palets y stock.</li><li>Transferí a otro palet o usuario.</li><li>Gestioná accesos para colaborar.</li><?php endif; ?></ul><button id="proContinueMercadoPago" type="button" class="library-primary" hidden>Pagar en Mercado Pago</button><button id="proContinueCard" type="button" class="library-primary">Continuar con tarjeta</button></div>
<p class="pro-payment-note"><?= stock_icon('shield') ?>Ingresás la tarjeta en campos de Mercado Pago. RepoTools no recibe tu número ni código de seguridad.</p>
<p id="proPaymentStatus" class="stock-muted" role="status"></p><div id="proCardSlot"></div>

</div>
</dialog>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
