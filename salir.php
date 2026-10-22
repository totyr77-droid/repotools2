<?php
require_once __DIR__ . '/includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user()) {
    header('Location: ' . (current_user() ? 'cuenta.php' : 'index.php'), true, 303);
    exit;
}
if (!valid_csrf()) {
    $_SESSION['logout_error'] = 'No se pudo cerrar la sesión. Pulsa Salir nuevamente.';
    header('Location: cuenta.php', true, 303);
    exit;
}
require_once __DIR__.'/includes/audit.php';audit_session(database(),(int)current_user()['id'],'session_ended',$_SESSION['device_kind']??device_kind());
device_release(database(),(int)current_user()['id'],$_SESSION['device_token']??'',$_SESSION['device_kind']??device_kind());
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: index.php', true, 303);
exit;
