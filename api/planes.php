<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/commercial-plans.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function plans_response(int $status,array $data): never {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
$user=current_user();if(!$user)plans_response(401,['error'=>'Iniciá sesión para continuar.']);
$method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','POST'],true))plans_response(405,['error'=>'Solicitud no válida.']);
if($method==='POST'&&!valid_csrf())plans_response(403,['error'=>'La sesión venció. Volvé a ingresar.']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>8192)plans_response(413,['error'=>'Solicitud demasiado grande.']);
$csrf=csrf_token();session_write_close();
try{
    $db=database();commercial_require_manager($db,(int)$user['id']);
    if($method==='POST')commercial_save($db,(int)$user['id'],$_POST);
    plans_response(200,['plans'=>commercial_plans($db,(int)$user['id']),'csrf'=>$csrf,'mode'=>'draft','enforced'=>false]);
}catch(CatalogException $error){plans_response($error->status,['error'=>$error->getMessage()]);}
catch(Throwable $error){error_log('Repotools commercial plans unavailable: '.get_class($error));plans_response(503,['error'=>'No pudimos consultar los planes. Volvé a intentar.']);}
