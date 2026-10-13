<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/preferences.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function preference_response(int $code,array $data): void {http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
$user=current_user();if(!$user)preference_response(401,['error'=>'Inicia sesión para configurar tu cuenta.']);
$method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','POST'],true))preference_response(405,['error'=>'Solicitud no válida.']);
if($method==='POST'&&!valid_csrf())preference_response(403,['error'=>'Recarga la página para continuar.']);
$csrf=csrf_token();session_write_close();
try{$db=database();if($method==='POST')save_account_preferences($db,(int)$user['id'],$_POST);
$preferences=account_preferences($db,(int)$user['id']);if(!$preferences)preference_response(401,['error'=>'La cuenta ya no está disponible.']);preference_response(200,$preferences+['csrf'=>$csrf]);}
catch(InvalidArgumentException $e){preference_response(422,['error'=>$e->getMessage()]);}
catch(Throwable $e){error_log('Repotools preferences: '.get_class($e));preference_response(503,['error'=>'No pudimos guardar tus preferencias. Intenta nuevamente.']);}
