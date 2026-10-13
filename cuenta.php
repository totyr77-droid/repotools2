<?php
require_once __DIR__ . '/includes/auth.php';
$paginaCuenta = true;
require_once __DIR__.'/includes/stock-icons.php';
$mostrarCreditos = true;
$register = ($_GET['mode'] ?? '') === 'registro';
$next = in_array($_GET['next'] ?? '', ['stock', 'tops', 'biblioteca', 'reportes', 'moderacion'], true) ? $_GET['next'] : '';
$error = $_SESSION['logout_error'] ?? '';
unset($_SESSION['logout_error']);
$name = '';
$email = '';
$company = '';
$deviceConflict=false;
function finish_account_login(array $user,string $token,string $kind,string $next): void { session_regenerate_id(true);unset($_SESSION['pending_device_login']);$_SESSION['user']=$user;$_SESSION['device_token']=$token;$_SESSION['device_kind']=$kind;$_SESSION['csrf']=bin2hex(random_bytes(32));header('Location: '.account_destination($next));exit; }
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['device_decision'])&&!current_user()){
    $pending=$_SESSION['pending_device_login']??null;
    if(!valid_csrf()||!$pending||(int)$pending['expires']<time()){$error='La confirmación venció. Ingresa nuevamente.';unset($_SESSION['pending_device_login']);}
    elseif($_POST['device_decision']==='cancel'){unset($_SESSION['pending_device_login']);}
    elseif($_POST['device_decision']==='replace'){if(device_claim(database(),$pending['user'],$pending['kind'],$pending['token'],true))finish_account_login($pending['user'],$pending['token'],$pending['kind'],$pending['next']);else{$error='Tu acceso cambió. Ingresa nuevamente.';unset($_SESSION['pending_device_login']);}}
}

function account_icon(string $name): string {
    $paths=['user'=>'<circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3"/>','mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>','lock'=>'<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>','company'=>'<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-4h6v4M8 7h2m4 0h2M8 11h2m4 0h2"/>'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$name].'</svg>';
}
function account_destination(string $next): string {
    $target=$next==='moderacion'?'reportes.php?queue=1':($next?$next.'.php':'index.php');
    if ($next==='stock' && preg_match('/^[0-9]{8}$/D',$_SESSION['pending_stock_code']??'')) {
        $target.='?codigo='.$_SESSION['pending_stock_code']; unset($_SESSION['pending_stock_code']);
    }
    return $target;
}
if (current_user() && $next) {
    header('Location: ' . account_destination($next));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['device_decision']) && !current_user()) {
    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    if ($register) $name = preg_replace('/\s+/u', ' ', $name) ?? '';
    $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));
    $company = trim(is_string($_POST['company'] ?? null) ? $_POST['company'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!valid_csrf()) {
        $error = 'La sesión del formulario venció. Intenta nuevamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Ingresa un correo válido.';
    } elseif ($register && (mb_strlen($name) < 3 || mb_strlen($name) > 80 || !preg_match('/^\S+(?: +\S+)+$/u', $name))) {
        $error = 'Ingresá tu nombre y apellido (hasta 80 caracteres).';
    } elseif ($register && (strlen($password) < 10 || strlen($password) > 72)) {
        $error = 'La contraseña debe tener entre 10 y 72 bytes.';
    } elseif ($register && mb_strlen($company)>100) {
        $error = 'La empresa o agencia admite hasta 100 caracteres.';
    } else {
        try {
            $db = database();
            $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $email);
            $stmt = $db->prepare('SELECT attempts, started FROM login_attempts WHERE key = ?');
            $stmt->execute([$key]);
            $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($attempt && (int)$attempt['started'] > time() - 900 && (int)$attempt['attempts'] >= 10) {
                $error = 'Demasiados intentos. Espera 15 minutos antes de volver a intentar.';
            } else {
                $db->prepare('DELETE FROM login_attempts WHERE started < ?')->execute([time() - 900]);
                $db->prepare('INSERT INTO login_attempts (key, attempts, started) VALUES (?, 1, ?) ON CONFLICT(key) DO UPDATE SET attempts = login_attempts.attempts + 1')->execute([$key, time()]);
                if ($register) {
                    $initialPublicName=generate_public_user_name($name,0);
                    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, company, public_name) VALUES (?, ?, ?, ?, ?) RETURNING id');
                    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $company, $initialPublicName]);
                    $newUserId=(int)$stmt->fetchColumn();$stmt->closeCursor();
                    if($initialPublicName==='Usuario #0') $db->prepare('UPDATE users SET public_name=? WHERE id=?')->execute([generate_public_user_name($name,$newUserId),$newUserId]);
                }
                $stmt = $db->prepare('SELECT id, name, public_name, email, role, company, password_hash, auth_version FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password_hash'])) {
                    $db->prepare('DELETE FROM login_attempts WHERE key = ?')->execute([$key]);
                    unset($user['password_hash']);$token=bin2hex(random_bytes(32));$kind=device_kind();
                    if(device_claim($db,$user,$kind,$token))finish_account_login($user,$token,$kind,$next);
                    $_SESSION['pending_device_login']=['user'=>$user,'token'=>$token,'kind'=>$kind,'expires'=>time()+300,'next'=>$next];$deviceConflict=true;
                } else {
                    $error='El correo o la contraseña no son correctos.';
                }

            }
        } catch (PDOException $exception) {
            $error = is_unique_violation($exception) ? 'No se pudo crear la cuenta con ese correo. Intenta iniciar sesión.' : 'No pudimos acceder a las cuentas. Intenta más tarde.';
        }
    }
}
$deviceConflict=$deviceConflict||isset($_SESSION['pending_device_login']);
$accountPreferences=null;
if(current_user()){require_once __DIR__.'/includes/preferences.php';$accountPreferences=account_preferences(database(),(int)current_user()['id']);}
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="account-main">
  <section class="account-panel<?= current_user() ? ' account-panel-profile' : ' account-panel-auth' ?>">
    <?php if (current_user() && $error): ?><p class="account-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
    <?php if (current_user()): ?>
    <div class="account-profile-heading"><h1 class="account-title"><?= stock_icon('settings') ?>Configuración<span class="profile-pending-dot" data-profile-pending aria-label="Perfil por completar"<?= $userHand !== '' ? ' hidden' : '' ?>></span></h1></div>
    <div class="account-profile-contact"><div class="account-mail-tools"><p class="account-email"><?= escape(current_user()['email']) ?></p><button id="editAccount" type="button" aria-label="Editar información de la cuenta" title="Editar información de la cuenta"><?= stock_icon('settings') ?></button></div><p id="accountCompany" class="account-company"<?= empty(current_user()['company']) ? ' hidden' : '' ?>><?= account_icon('company') ?><span><?= escape(current_user()['company'] ?? '') ?></span></p></div>
    <dialog id="accountEditDialog" class="account-edit-dialog"><div class="account-edit-heading"><h2>Información de la cuenta</h2><button type="button" class="app-close" aria-label="Cerrar" title="Cerrar"><?= stock_icon('close') ?></button></div><form id="accountEditForm"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><label><span class="account-field-title"><?= account_icon('user') ?>Nombre</span><input name="name" autocomplete="name" minlength="2" maxlength="80" placeholder="Tu nombre" required value="<?= escape(current_user()['name']) ?>"></label><label><span class="account-field-title"><?= account_icon('company') ?>Empresa o agencia <small>(opcional)</small></span><input name="company" autocomplete="organization" maxlength="100" placeholder="Marca, empresa o agencia" value="<?= escape(current_user()['company'] ?? '') ?>"></label><p id="accountEditStatus" role="status"></p><button type="submit">Guardar cambios</button></form></dialog><p id="accountSavedStatus" class="account-saved-status" role="status" hidden><?= stock_icon('check') ?><span>Información actualizada.</span></p>
    <div class="settings-layout"><section class="settings-group settings-profile" aria-labelledby="settingsProfileTitle"><h2 id="settingsProfileTitle">Perfil</h2>
    <details id="myProfileQR" class="profile-qr"><summary><?= stock_icon('qr') ?>Mi QR de perfil<span class="profile-chevron"><?= stock_icon('chevron') ?></span></summary><div class="profile-qr-image"></div><p role="status"></p></details>

    <?php include __DIR__.'/vistas/public-name.vista.php'; ?>
    </section>
    <section id="accountPreferences" class="settings-group settings-preferences" aria-labelledby="settingsPreferencesTitle" data-csrf="<?= escape(csrf_token()) ?>"><h2 id="settingsPreferencesTitle">Preferencias</h2>
    <section class="stock-preferences"><h3><?= stock_icon('clock') ?>Avisos de vencimiento</h3><form id="stockPreferences"><label for="stockExpiryDays">Días de anticipación</label><input id="stockExpiryDays" type="number" inputmode="numeric" min="0" max="365" placeholder="30" value="<?= (int)($accountPreferences['expiry_days']??30) ?>" required aria-describedby="expiryPreferenceHint"></form><p id="expiryPreferenceHint" class="settings-hint">Para tus palets y los compartidos.</p><p id="stockPreferenceStatus" role="status" hidden></p></section>
    <fieldset class="settings-feedback"><legend>Sonido y vibración</legend>
    <?php foreach(['sound'=>['Sonido','fa-volume-high'],'vibration'=>['Vibración','fa-mobile-screen-button']] as $key=>$setting): ?>
    <label class="settings-toggle"><span class="settings-toggle-name"><i class="fa-solid <?= $setting[1] ?>" aria-hidden="true"></i><?= $setting[0] ?></span><span class="settings-toggle-control"><small data-preference-state="<?= $key ?>"><?= ($accountPreferences[$key]??true)?'Activado':'Desactivado' ?></small><input id="preference-<?= $key ?>" type="checkbox" role="switch" name="<?= $key ?>" aria-label="<?= $setting[0] ?>"<?= ($accountPreferences[$key]??true)?' checked':'' ?>><span class="settings-toggle-track" aria-hidden="true"></span></span></label>
    <?php endforeach; ?>
    </fieldset>
    <?php include __DIR__ . '/includes/hand-preference-view.php'; ?>
    </section>
    <section class="settings-group settings-access" aria-labelledby="settingsAccessTitle"><h2 id="settingsAccessTitle">Accesos</h2>
    <nav class="account-shortcuts" aria-label="Accesos de mi cuenta"><a href="tops.php"><?= stock_icon('chart') ?>Estadísticas y TOPs</a>
    <a href="biblioteca.php"><?= stock_icon('book') ?>Biblioteca</a>
    <a href="reportes.php"><?= stock_icon('report') ?>Mis reportes</a>
    <a href="stock.php"><?= stock_icon('qr') ?>Palets</a>
    <a href="index.php"><?= stock_icon('barcode') ?>Escáner</a></nav>
    <button id="replayOnboarding" type="button" class="onboarding-replay"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4m0 3h.01"/></svg>Ver introducción</button>
    </section></div>
    <?php include __DIR__.'/includes/account-security-view.php'; ?>
    <?php else: ?>
    <h1><?= $register ? 'Crear cuenta' : 'Iniciar sesión' ?></h1>
    <p class="account-intro"><?= $register ? 'Guarda tus productos y organiza tus palets.' : 'Continúa con tus productos y palets.' ?></p>
    <?php if ($error): ?><p class="account-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
    <?php if($deviceConflict): ?><aside class="account-device-notice" role="status"><?= account_icon('lock') ?><div><strong>Ya tienes otra <?= ($_SESSION['pending_device_login']['kind']??'desktop')==='desktop'?'computadora':'sesión de celular' ?> conectada</strong><p>¿Quieres cerrar esa sesión y entrar aquí? Las cargas pendientes se conservarán en el equipo anterior.</p></div></aside><form method="post"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><div class="account-device-actions"><button type="submit" name="device_decision" value="cancel" class="library-secondary">Cancelar</button><button type="submit" name="device_decision" value="replace" class="library-primary">Entrar aquí</button></div></form><?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">
      <?php if ($register): ?><label><span class="account-field-title"><?= account_icon('user') ?>Nombre y apellido</span><input name="name" autocomplete="name" minlength="3" maxlength="80" placeholder="Ej.: Juan Pérez" required value="<?= escape($name) ?>"></label>
      <label><span class="account-field-title"><?= account_icon('company') ?>Empresa o agencia <small>(opcional)</small></span><input name="company" autocomplete="organization" maxlength="100" placeholder="Marca, empresa o agencia" value="<?= escape($company) ?>"></label><p class="account-field-hint">Se mostrará junto a tu nombre en los palets.</p><?php endif; ?>
      <label><span class="account-field-title"><?= account_icon('mail') ?>Correo electrónico</span><input type="email" name="email" autocomplete="email" maxlength="254" placeholder="nombre@correo.com" required value="<?= escape($email) ?>"></label>
      <label><span class="account-field-title"><?= account_icon('lock') ?>Contraseña</span><span class="account-password-field"><input id="accountPassword" type="password" name="password" autocomplete="<?= $register ? 'new-password' : 'current-password' ?>" placeholder="<?= $register ? 'Al menos 10 caracteres' : 'Tu contraseña' ?>" <?= $register ? 'minlength="10" maxlength="72"' : '' ?> required><button class="password-toggle" type="button" aria-label="Mostrar contraseña" title="Mostrar contraseña" aria-controls="accountPassword" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-eye-slash" d="m3 3 18 18"/></svg></button></span></label>
      <button type="submit"><?= $register ? 'CREAR CUENTA' : 'INGRESAR' ?></button>
    </form><?php endif; ?>
    <?php if (!$register): ?><a class="account-create-button" href="cuenta.php?mode=registro&amp;next=<?= escape($next) ?>"><?= account_icon('user') ?><span>Crear cuenta</span></a><?php endif; ?>
    <nav class="account-auth-links" aria-label="Opciones de acceso"><?php if ($register): ?><a href="cuenta.php?mode=login&amp;next=<?= escape($next) ?>">Ya tengo cuenta</a><?php endif; ?>
    <?php if (!$register): ?><a href="recuperar.php">Olvidé mi contraseña</a><?php endif; ?>
    <a href="index.php">Continuar sin cuenta</a>
    <a href="tops.php">Ver TOPs</a>
    <a href="reportes.php">Mis reportes</a>
    </nav><p class="account-notice">Usamos una cookie para mantener tu sesión.</p>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
