<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/account-security.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function security_response(int $status,array $data): void {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
$user=current_user();if(!$user)security_response(401,['error'=>'Inicia sesión para continuar.']);
if($_SERVER['REQUEST_METHOD']!=='POST')security_response(405,['error'=>'Solicitud no válida.']);
if(!valid_csrf())security_response(403,['error'=>'Recarga la página para continuar.']);
$action=$_POST['action']??'';if(!in_array($action,['password','sessions'],true))security_response(422,['error'=>'Solicitud no válida.']);
$current=is_string($_POST['current_password']??null)?$_POST['current_password']:'';
$password=is_string($_POST['password']??null)?$_POST['password']:'';
if($action==='password' && (!is_string($_POST['confirmation']??null)||$password!==$_POST['confirmation']))security_response(422,['error'=>'Las contraseñas nuevas no coinciden.']);
try {
    $version=account_security_update(database(),$user,$current,$action==='password'?$password:null);
    $_SESSION['user']['auth_version']=$version;
    session_regenerate_id(true);session_write_close();
    $mail=null;
    if($action==='password'){try{$mail=account_security_email($user['email']);}catch(Throwable $e){$mail=false;}if(!$mail)error_log('Repotools: no se pudo enviar el aviso de cambio de contraseña.');}
    security_response(200,['message'=>$action==='password'?'Contraseña actualizada. Cerramos las otras sesiones.':'Las otras sesiones se cerraron. Este dispositivo sigue conectado.','mail_sent'=>$mail]);
}catch(InvalidArgumentException $e){security_response(422,['error'=>$e->getMessage()]);}
catch(PDOException $e){error_log('Repotools security: database error');security_response(503,['error'=>'No pudimos guardar el cambio. Intenta nuevamente.']);}
catch(RuntimeException $e){security_response(409,['error'=>'Tu sesión cambió. Vuelve a ingresar.']);}
catch(Throwable $e){error_log('Repotools security: '.get_class($e));security_response(503,['error'=>'No pudimos guardar el cambio. Intenta nuevamente.']);}
