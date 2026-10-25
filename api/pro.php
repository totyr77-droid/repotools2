<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/pro-subscriptions.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function pro_response(int $status,array $body): never {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;}
$user=current_user();if(!$user)pro_response(401,['error'=>'Iniciá sesión para continuar.']);
$method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','POST'],true))pro_response(405,['error'=>'Solicitud no válida.']);
if($method==='POST'&&!valid_csrf())pro_response(403,['error'=>'La sesión venció. Volvé a ingresar.']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>2048)pro_response(413,['error'=>'Solicitud demasiado grande.']);
$csrf=csrf_token();session_write_close();
try{
    $db=database();pro_require_tester($db,(int)$user['id']);
    if($method==='POST'){
        $action=$_POST['action']??'';$service=new ProSubscriptions($db,pro_config());
        if($action==='checkout')pro_response(200,['checkout_url'=>$service->checkout((int)$user['id'])]);
        elseif($action==='cancel')$service->cancel((int)$user['id']);
        else pro_response(422,['error'=>'Acción no válida.']);
    }
    pro_response(200,['subscription'=>pro_state($db,(int)$user['id']),'csrf'=>$csrf]);
}catch(CatalogException $e){pro_response($e->status,['error'=>$e->getMessage()]);}
catch(Throwable $e){error_log('RepoTools Pro: '.get_class($e));pro_response(503,['error'=>'No pudimos consultar tu suscripción. Volvé a intentar.']);}
