<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/sessions.php';
require_once __DIR__.'/account-devices.php';

require_once __DIR__.'/support-session.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)DatabaseSessions::LIFETIME);
    if(support_active()&&empty($_COOKIE['repotools_support_'.support_context()]))support_reject('El acceso como usuario terminó. Volvé a tu cuenta de manager.',401);
    session_name(support_active()?'repotools_support_'.support_context():'repotools_session');
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (getenv('RENDER') === 'true');
    session_set_cookie_params(['lifetime' => support_active()?1800:DatabaseSessions::LIFETIME, 'path' => '/', 'httponly' => true, 'secure' => $secureCookie, 'samesite' => 'Lax']);
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
    if(!array_key_exists($user['id'],$versions)){$q=database()->prepare('SELECT auth_version,name,public_name,email,company,role FROM users WHERE id=?');$q->execute([$user['id']]);$versions[$user['id']]=$q->fetch(PDO::FETCH_ASSOC);}
    $fresh=$versions[$user['id']];
    if($fresh===false||(int)$fresh['auth_version']!==(int)($user['auth_version']??1)){unset($_SESSION['user']);return null;}
    $user=array_replace($user,$fresh);
    if(!isset($_SESSION['device_token'])){
        $token=bin2hex(random_bytes(32));$kind=device_kind();
        if(!device_claim(database(),$user,$kind,$token)){unset($_SESSION['user']);$_SESSION['logout_error']='Esta cuenta ya tiene una sesión abierta en otro equipo. Ingresa para cambiarla.';return null;}
        $_SESSION['device_token']=$token;$_SESSION['device_kind']=$kind;
    }
    if(!device_valid(database(),$user,$_SESSION['device_token'],$_SESSION['device_kind'])){unset($_SESSION['user']);$_SESSION['logout_error']='Tu sesión se abrió en otro equipo. Puedes volver a ingresar aquí.';return null;}
    if((int)($_SESSION['device_renewed']??0)<time()-600){device_renew(database(),$user,$_SESSION['device_token'],$_SESSION['device_kind']);$_SESSION['device_renewed']=time();}
    $_SESSION['user']=$user;
    return support_effective_user($user);
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

// Fail closed: an expired support session must never become a normal user login.
if(support_active()){
    header('Cache-Control: no-store');header('Referrer-Policy: same-origin');
    if(!current_user())support_reject('El acceso como usuario terminó. Volvé a tu cuenta de manager.',401);
    $path=basename($_SERVER['SCRIPT_NAME']??'');
    if(in_array($path,['seguridad.php','push.php','dispositivos.php','scan-relay.php','scan-relay-photo.php','manager-cache.php','verificacion.php','recuperar.php','restablecer.php'],true)||($path==='cuenta.php'&&($_SERVER['REQUEST_METHOD']??'GET')!=='GET')||($path==='usuarios.php'&&($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&($_POST['action']??'')==='role'))support_reject('La seguridad y los rangos se gestionan desde tu cuenta de manager.');
}

if(support_active()&&($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&!in_array(basename($_SERVER['SCRIPT_NAME']??''),['ingresar-usuario.php','salir.php'],true)){
    $supportRoute=basename($_SERVER['SCRIPT_NAME']??'');$supportAction=is_string($_POST['action']??null)?mb_substr($_POST['action'],0,60):'guardar';
    register_shutdown_function(static function()use($supportRoute,$supportAction){
        try{require_once __DIR__.'/audit.php';$db=database();if($db->inTransaction())$db->rollBack();$status=http_response_code()?:200;$db->beginTransaction();audit_event($db,(int)$GLOBALS['repo_support_actor']['id'],'support_action',bin2hex(random_bytes(16)),['category'=>'users','target_user'=>$GLOBALS['repo_support_target']['id']],[],['route'=>$supportRoute,'operation'=>$supportAction,'http_status'=>$status],$status<400?'confirmed':'rejected');$db->commit();}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();error_log('Repotools: support audit unavailable');}
    });
}
