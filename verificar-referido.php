<?php
require_once __DIR__.'/includes/auth.php';require_once __DIR__.'/includes/referrals.php';require_once __DIR__.'/includes/stock-icons.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$token=is_string($_GET['token']??null)?$_GET['token']:'';$message='';$verified=false;
if(!referrals_enabled()){http_response_code(503);exit('La verificación todavía no está habilitada en este entorno.');}
if($_SERVER['REQUEST_METHOD']==='POST')try{
    if(!valid_csrf())throw new ReferralError('Recargá la página para confirmar.');referral_limit(database(),'verify:'.hash('sha256',session_id()),30,900);
    $verified=referral_verify(database(),is_string($_POST['token']??null)?$_POST['token']:'');$message=$verified?'Correo confirmado. Gracias por sumarte a Repotools.':'El enlace venció o ya fue utilizado. Podés pedir otro desde Referidos.';
}catch(ReferralError $e){$message=$e->getMessage();}
$paginaCuenta=true;include __DIR__.'/vistas/header.vista.php';
?>
<main class="account-main"><section class="account-panel"><h1><?= stock_icon('check') ?>Confirmar correo</h1><p><?= escape($message?:'Confirmá esta dirección para validar tu invitación.') ?></p>
<?php if(!$verified&&preg_match('/^[a-f0-9]{64}$/D',$token)): ?><form method="post"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="token" value="<?= escape($token) ?>"><button class="library-primary" type="submit">Confirmar correo</button></form><?php endif; ?><a href="referidos.php">Ir a Referidos</a></section></main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
