<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/sessions.php';
require_once __DIR__.'/account-devices.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)DatabaseSessions::LIFETIME);
    session_name('repotools_session');
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (getenv('RENDER') === 'true');
    session_set_cookie_params(['lifetime' => DatabaseSessions::LIFETIME, 'path' => '/', 'httponly' => true, 'secure' => $secureCookie, 'samesite' => 'Lax']);
    try {
        session_set_save_handler(new DatabaseSessions(database()), true);
        if (!session_start()) throw new RuntimeException('Session unavailable');
    } catch (Throwable $error) {
        http_response_code(503);
        header('Retry-After: 30');
        header('Content-Type: text/plain; charset=UTF-8');
        exit('No pudimos acceder a tu sesión. Intenta nuevamente en unos momentos.');
    }
}

require_once __DIR__ . '/database.php';

function current_user(): ?array {
    $user=$_SESSION['user']??null;
    if (!$user) return null;
    static $versions=[];
    if(!array_key_exists($user['id'],$versions)){$q=database()->prepare('SELECT auth_version,name,public_name,email,company FROM users WHERE id=?');$q->execute([$user['id']]);$versions[$user['id']]=$q->fetch(PDO::FETCH_ASSOC);}
    $fresh=$versions[$user['id']];
    if($fresh===false||(int)$fresh['auth_version']!==(int)($user['auth_version']??1)){unset($_SESSION['user']);return null;}
    $user=array_replace($user,$fresh);
    if(!isset($_SESSION['device_token'])){
        $token=bin2hex(random_bytes(32));$kind=device_kind();
        if(!device_claim(database(),$user,$kind,$token)){unset($_SESSION['user']);$_SESSION['logout_error']='Esta cuenta ya tiene una sesión abierta en otro equipo. Ingresa para cambiarla.';return null;}
        $_SESSION['device_token']=$token;$_SESSION['device_kind']=$kind;
    }
    if(!device_valid(database(),$user,$_SESSION['device_token'],$_SESSION['device_kind'])){unset($_SESSION['user']);$_SESSION['logout_error']='Tu sesión se abrió en otro equipo. Puedes volver a ingresar aquí.';return null;}
    if((int)($_SESSION['device_renewed']??0)<time()-600){database()->prepare('UPDATE account_devices SET expires_at=? WHERE user_id=? AND kind=? AND token_hash=?')->execute([time()+30*86400,$user['id'],$_SESSION['device_kind'],hash('sha256',$_SESSION['device_token'])]);$_SESSION['device_renewed']=time();}
    return $_SESSION['user']=$user;
}

function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function valid_csrf(): bool {
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals(csrf_token(), $_POST['csrf']);
}

function require_account(string $next = 'stock'): void {
    if (!current_user()) {
        $base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php')),'/');
        header('Location: '.$base.'/cuenta.php?next=' . (in_array($next, ['stock', 'tops', 'biblioteca', 'reportes', 'moderacion'], true) ? $next : 'stock'));
        exit;
    }
}
