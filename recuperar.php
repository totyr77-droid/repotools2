<?php
require_once __DIR__.'/includes/auth.php';require_once __DIR__.'/includes/password-reset.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$paginaCuenta=true;$token=is_string($_GET['token']??null)?$_GET['token']:'';$notice='';$done=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if(!valid_csrf()) $notice='La sesión venció. Recargá e intentá nuevamente.';
    elseif($token) {
        $password=is_string($_POST['password']??null)?$_POST['password']:'';
        if(strlen($password)<10||strlen($password)>72)$notice='Usá una contraseña de 10 a 72 bytes.';
        else {$done=reset_password(database(),$token,$password);$notice=$done?'Contraseña actualizada. Ya podés ingresar.':'El enlace venció o ya fue utilizado. Pedí uno nuevo.';}
    } else {
        $email=strtolower(trim(is_string($_POST['email']??null)?$_POST['email']:''));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))$notice='Ingresá un correo válido.';
        elseif(!getenv('RESEND_API_KEY')||!getenv('MAIL_FROM')||!getenv('APP_URL'))$notice='La recuperación por correo todavía no está disponible.';
        else {
            $db=database();$key='reset:'.hash('sha256',$_SERVER['REMOTE_ADDR']??'');
            $q=$db->prepare('SELECT attempts,started FROM login_attempts WHERE key=?');$q->execute([$key]);$attempt=$q->fetch();
            if($attempt&&(int)$attempt['started']>time()-900&&(int)$attempt['attempts']>=5)$notice='Esperá 15 minutos antes de pedir otro enlace.';
            else {
                $db->prepare('DELETE FROM login_attempts WHERE key=? AND started<?')->execute([$key,time()-900]);
                $db->prepare('INSERT INTO login_attempts(key,attempts,started) VALUES(?,1,?) ON CONFLICT(key) DO UPDATE SET attempts=login_attempts.attempts+1')->execute([$key,time()]);
                $q=$db->prepare('SELECT id FROM users WHERE email=?');$q->execute([$email]);$uid=$q->fetchColumn();
                $notice='Si el correo tiene una cuenta, recibirás un enlace para recuperar el acceso.';
                if($uid){$new=reset_issue($db,(int)$uid);if(!reset_send($email,$new)){ $db->prepare('DELETE FROM password_resets WHERE token_hash=?')->execute([hash('sha256',$new)]);error_log('Repotools: falló el envío de recuperación.');}}
                $db->prepare('DELETE FROM password_resets WHERE expires_at<?')->execute([time()]);
            }
        }
    }
}
include __DIR__.'/vistas/header.vista.php'; ?>
<main class="account-main"><section class="account-panel"><h1>Recuperar acceso</h1>
<?php if($notice): ?><p class="library-status" role="status"><?= escape($notice) ?></p><?php endif; ?>
<?php if(!$done): ?><form method="post" class="account-form"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">
<?php if($token): ?><label>Nueva contraseña<input name="password" type="password" autocomplete="new-password" minlength="10" maxlength="72" placeholder="Al menos 10 caracteres" required></label>
<?php else: ?><label>Correo<input name="email" type="email" autocomplete="email" placeholder="tu@correo.com" required maxlength="254"></label><?php endif; ?>
<button type="submit" class="library-primary"><?= $token?'Guardar contraseña':'Enviar enlace' ?></button></form><?php endif; ?>
<nav class="account-auth-links"><a href="cuenta.php">Ingresar</a><?php if($token): ?><a href="recuperar.php">Pedir otro enlace</a><?php endif; ?></nav></section></main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
