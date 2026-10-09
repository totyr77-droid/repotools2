<?php
require_once __DIR__ . '/includes/auth.php';
$paginaCuenta = true;
$register = ($_GET['mode'] ?? '') === 'registro';
$next = in_array($_GET['next'] ?? '', ['stock', 'tops'], true) ? $_GET['next'] : '';
$error = '';
$name = '';
$email = '';
if (current_user() && $next) {
    header('Location: ' . $next . '.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !current_user()) {
    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!valid_csrf()) {
        $error = 'La sesión del formulario venció. Intenta nuevamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Ingresa un correo válido.';
    } elseif ($register && (mb_strlen($name) < 2 || mb_strlen($name) > 80 || strlen($password) < 10 || strlen($password) > 72)) {
        $error = 'El nombre debe tener entre 2 y 80 caracteres y la contraseña entre 10 y 72 bytes.';
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
                $db->prepare('INSERT INTO login_attempts (key, attempts, started) VALUES (?, 1, ?) ON CONFLICT(key) DO UPDATE SET attempts = attempts + 1')->execute([$key, time()]);
                if ($register) {
                    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                }
                $stmt = $db->prepare('SELECT id, name, email, role, password_hash FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password_hash'])) {
                    $db->prepare('DELETE FROM login_attempts WHERE key = ?')->execute([$key]);
                    session_regenerate_id(true);
                    unset($user['password_hash']);
                    $_SESSION['user'] = $user;
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    header('Location: ' . ($next ? $next . '.php' : 'index.php'));
                    exit;
                }
                $error = 'El correo o la contraseña no son correctos.';
            }
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'No se pudo crear la cuenta con ese correo. Intenta iniciar sesión.' : 'No pudimos acceder a las cuentas. Intenta más tarde.';
        }
    }
}
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="account-main">
  <section class="account-panel">
    <?php if (current_user()): ?>
    <h1>Mi cuenta</h1>
    <p>Hola, <?= escape(current_user()['name']) ?>.</p>
    <p class="account-email"><?= escape(current_user()['email']) ?></p>
    <a href="tops.php">Ver mis estadísticas y TOPs</a>
    <a href="stock.php">Gestionar palets</a>
    <a href="index.php">Volver al escáner</a>
    <?php else: ?>
    <h1><?= $register ? 'Crear cuenta' : 'Iniciar sesión' ?></h1>
    <p>El escáner es libre. Para gestionar palets necesitas una cuenta.</p>
    <?php if ($error): ?><p class="account-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">
      <?php if ($register): ?><label>Nombre<input name="name" autocomplete="name" minlength="2" maxlength="80" required value="<?= escape($name) ?>"></label><?php endif; ?>
      <label>Correo electrónico<input type="email" name="email" autocomplete="email" maxlength="254" required value="<?= escape($email) ?>"></label>
      <label>Contraseña<input type="password" name="password" autocomplete="<?= $register ? 'new-password' : 'current-password' ?>" <?= $register ? 'minlength="10" maxlength="72"' : '' ?> required></label>
      <button class="password-toggle" type="button" aria-pressed="false">Mostrar contraseña</button>
      <?php if ($register): ?><p>Usa al menos 10 caracteres. Guardamos tu contraseña protegida mediante un hash.</p><?php endif; ?>
      <button type="submit"><?= $register ? 'CREAR CUENTA' : 'ENTRAR' ?></button>
    </form>
    <a href="cuenta.php?mode=<?= $register ? 'login' : 'registro' ?>&amp;next=<?= escape($next) ?>"><?= $register ? 'Ya tengo cuenta' : 'Crear una cuenta' ?></a>
    <a href="index.php">Continuar sin cuenta</a>
    <p class="account-notice">Los escaneos se guardan en este navegador. Al iniciar sesión usamos una cookie necesaria para mantener tu acceso.</p>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
