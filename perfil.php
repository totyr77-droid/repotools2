<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/referrals.php';
require_once __DIR__.'/includes/public-names.php';
require_once __DIR__.'/includes/stock-icons.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
$publicShareBase=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/perfil.php')),'/').'/';
$code=$_GET['code']??'';$token=$_GET['user']??'';$profile=false;
if(is_string($code)&&preg_match('/^[A-Z0-9]{12}$/D',$code)){$q=database()->prepare('SELECT u.id,u.name,u.public_name,u.company FROM referral_codes t JOIN users u ON u.id=t.user_id WHERE t.code=?');$q->execute([$code]);$profile=$q->fetch();}
if(is_string($token)&&preg_match('/^[a-f0-9]{64}$/D',$token)){
    $q=database()->prepare('SELECT u.id,u.name,u.public_name,u.company FROM user_profile_tokens t JOIN users u ON u.id=t.user_id WHERE t.token=?');
    $q->execute([$token]);$profile=$q->fetch();
}
if(!$profile)http_response_code(404);
if($profile&&!current_user()&&referrals_enabled())try{referral_limit(database(),'capture:'.hash('sha256',session_id()),60,900);referral_capture(database(),referral_code(database(),(int)$profile['id']));}catch(ReferralError $e){}
$paginaCuenta=true;$paginaReferidos=true;$publicPageTitle='Perfil de usuario';
include __DIR__.'/vistas/header.vista.php';
?>
<main class="tops-main referral-workspace">
  <div class="referral-heading"><h1><?= stock_icon('user') ?>Perfil de usuario</h1><a class="library-secondary" href="index.php">Inicio</a></div>
  <?php if($profile): ?>
  <h2><?= escape(get_public_user_name($profile)) ?></h2>
  <?php if(trim((string)$profile['company'])!==''): ?><p><?= stock_icon('branch') ?><?= escape($profile['company']) ?></p><?php endif; ?>
  <?php if(!current_user()): ?><p class="referral-muted">Organizá tu mercadería con RepoTools.</p><a class="library-primary" href="cuenta.php?mode=registro">Crear cuenta</a><a class="library-secondary" href="cuenta.php">Ingresar</a><?php else: ?><p class="referral-muted">Compartí este perfil para que puedan encontrarte en RepoTools.</p><?php endif; ?>
  <?php else: ?><p>Este perfil no está disponible.</p><a class="library-primary" href="index.php">Volver al inicio</a><?php endif; ?>
</main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
