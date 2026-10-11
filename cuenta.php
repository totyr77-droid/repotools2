<?php
require_once __DIR__ . '/includes/auth.php';
$paginaCuenta = true;
require_once __DIR__.'/includes/stock-icons.php';
$mostrarCreditos = true;
$register = ($_GET['mode'] ?? '') === 'registro';
$next = in_array($_GET['next'] ?? '', ['stock', 'tops', 'biblioteca'], true) ? $_GET['next'] : '';
$error = $_SESSION['logout_error'] ?? '';
unset($_SESSION['logout_error']);
$name = '';
$email = '';
$company = '';
function account_icon(string $name): string {
    $paths=['user'=>'<circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3"/>','mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>','lock'=>'<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>','company'=>'<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-4h6v4M8 7h2m4 0h2M8 11h2m4 0h2"/>'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$name].'</svg>';
}
function account_destination(string $next): string {
    $target=$next?$next.'.php':'index.php';
    if ($next==='stock' && preg_match('/^[0-9]{8}$/D',$_SESSION['pending_stock_code']??'')) {
        $target.='?codigo='.$_SESSION['pending_stock_code']; unset($_SESSION['pending_stock_code']);
    }
    return $target;
}
if (current_user() && $next) {
    header('Location: ' . account_destination($next));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !current_user()) {
    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));
    $company = trim(is_string($_POST['company'] ?? null) ? $_POST['company'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!valid_csrf()) {
        $error = 'La sesión del formulario venció. Intenta nuevamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Ingresa un correo válido.';
    } elseif ($register && (mb_strlen($name) < 2 || mb_strlen($name) > 80 || strlen($password) < 10 || strlen($password) > 72)) {
        $error = 'El nombre debe tener entre 2 y 80 caracteres y la contraseña entre 10 y 72 bytes.';
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
                    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, company) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $company]);
                }
                $stmt = $db->prepare('SELECT id, name, email, role, company, password_hash FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password_hash'])) {
                    $db->prepare('DELETE FROM login_attempts WHERE key = ?')->execute([$key]);
                    session_regenerate_id(true);
                    unset($user['password_hash']);
                    $_SESSION['user'] = $user;
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    header('Location: ' . account_destination($next));
                    exit;
                }
                $error = 'El correo o la contraseña no son correctos.';
            }
        } catch (PDOException $exception) {
            $error = is_unique_violation($exception) ? 'No se pudo crear la cuenta con ese correo. Intenta iniciar sesión.' : 'No pudimos acceder a las cuentas. Intenta más tarde.';
        }
    }
}
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="account-main">
  <section class="account-panel<?= current_user() ? ' account-panel-profile' : ' account-panel-auth' ?>">
    <?php if (current_user() && $error): ?><p class="account-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
    <?php if (current_user()): ?>
    <div class="account-profile-heading"><h1 class="account-title"><?= stock_icon('settings') ?>Configuración<span class="profile-pending-dot" data-profile-pending aria-label="Perfil por completar"<?= $userHand !== '' ? ' hidden' : '' ?>></span></h1></div>
    <div class="account-profile-contact"><div class="account-mail-tools"><p class="account-email"><?= escape(current_user()['email']) ?></p><button id="editAccount" type="button" aria-label="Editar información de la cuenta" title="Editar información de la cuenta"><?= stock_icon('settings') ?></button></div><p id="accountCompany" class="account-company"<?= empty(current_user()['company']) ? ' hidden' : '' ?>><?= account_icon('company') ?><span><?= escape(current_user()['company'] ?? '') ?></span></p></div>
    <dialog id="accountEditDialog" class="account-edit-dialog"><div class="account-edit-heading"><h2>Información de la cuenta</h2><button type="button" class="app-close" aria-label="Cerrar" title="Cerrar"><?= stock_icon('close') ?></button></div><form id="accountEditForm"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><label><span class="account-field-title"><?= account_icon('user') ?>Nombre</span><input name="name" autocomplete="name" minlength="2" maxlength="80" placeholder="Tu nombre" required value="<?= escape(current_user()['name']) ?>"></label><label><span class="account-field-title"><?= account_icon('company') ?>Empresa o agencia <small>(opcional)</small></span><input name="company" autocomplete="organization" maxlength="100" placeholder="Marca, empresa o agencia" value="<?= escape(current_user()['company'] ?? '') ?>"></label><p id="accountEditStatus" role="status"></p><button type="submit">Guardar cambios</button></form></dialog><p id="accountSavedStatus" class="account-saved-status" role="status" hidden><?= stock_icon('check') ?><span>Información actualizada.</span></p>
    <details id="myProfileQR" class="profile-qr"><summary><?= stock_icon('qr') ?>Mi QR de perfil<span class="profile-chevron"><?= stock_icon('chevron') ?></span></summary><div class="profile-qr-image"></div><p role="status"></p></details>

    <nav class="account-shortcuts" aria-label="Accesos de mi cuenta"><a href="tops.php"><?= stock_icon('chart') ?>Estadísticas y TOPs</a>
    <a href="biblioteca.php"><?= stock_icon('book') ?>Biblioteca</a>
    <a href="reportes.php"><?= stock_icon('report') ?>Mis reportes</a>
    <a href="stock.php"><?= stock_icon('qr') ?>Palets</a>
    <a href="index.php"><?= stock_icon('barcode') ?>Escáner</a></nav>
    <section class="stock-preferences"><h2><?= stock_icon('clock') ?>Avisos de vencimiento</h2><p>Te avisamos en la web cuando se acerca el vencimiento de productos en tus palets y los compartidos.</p><form id="stockPreferences"><label>Días de anticipación <input id="stockExpiryDays" type="number" inputmode="numeric" min="0" max="365" placeholder="30" value="30" required></label><button type="submit">Guardar preferencia</button><p id="stockPreferenceStatus" role="status"></p></form></section>
    <?php include __DIR__ . '/includes/hand-preference-view.php'; ?>
    <?php else: ?>
    <h1><?= $register ? 'Crear cuenta' : 'Iniciar sesión' ?></h1>
    <p class="account-intro"><?= $register ? 'Guarda tus productos y organiza tus palets.' : 'Continúa con tus productos y palets.' ?></p>
    <?php if ($error): ?><p class="account-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">
      <?php if ($register): ?><label><span class="account-field-title"><?= account_icon('user') ?>Nombre</span><input name="name" autocomplete="name" minlength="2" maxlength="80" placeholder="Tu nombre" required value="<?= escape($name) ?>"></label>
      <label><span class="account-field-title"><?= account_icon('company') ?>Empresa o agencia <small>(opcional)</small></span><input name="company" autocomplete="organization" maxlength="100" placeholder="Marca, empresa o agencia" value="<?= escape($company) ?>"></label><p class="account-field-hint">Se mostrará junto a tu nombre en los palets.</p><?php endif; ?>
      <label><span class="account-field-title"><?= account_icon('mail') ?>Correo electrónico</span><input type="email" name="email" autocomplete="email" maxlength="254" placeholder="nombre@correo.com" required value="<?= escape($email) ?>"></label>
      <label><span class="account-field-title"><?= account_icon('lock') ?>Contraseña</span><span class="account-password-field"><input id="accountPassword" type="password" name="password" autocomplete="<?= $register ? 'new-password' : 'current-password' ?>" placeholder="<?= $register ? 'Al menos 10 caracteres' : 'Tu contraseña' ?>" <?= $register ? 'minlength="10" maxlength="72"' : '' ?> required><button class="password-toggle" type="button" aria-label="Mostrar contraseña" title="Mostrar contraseña" aria-controls="accountPassword" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-eye-slash" d="m3 3 18 18"/></svg></button></span></label>
      <button type="submit"><?= $register ? 'CREAR CUENTA' : 'INGRESAR' ?></button>
    </form>
    <?php if (!$register): ?><a class="account-create-button" href="cuenta.php?mode=registro&amp;next=<?= escape($next) ?>"><?= account_icon('user') ?><span>Crear cuenta</span></a><?php endif; ?>
    <nav class="account-auth-links" aria-label="Opciones de acceso"><?php if ($register): ?><a href="cuenta.php?mode=login&amp;next=<?= escape($next) ?>">Ya tengo cuenta</a><?php endif; ?>
    <a href="index.php">Continuar sin cuenta</a>
    <a href="tops.php">Ver TOPs</a>
    <a href="reportes.php">Mis reportes</a>
    </nav><p class="account-notice">Usamos una cookie para mantener tu sesión.</p>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
