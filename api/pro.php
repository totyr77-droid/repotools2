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
    $product=billing_product(($method==='POST'?$_POST:$_GET)['product']??'pro');$db=database();$teamId=0;$scopeReady=false;pro_require_tester($db,(int)$user['id']);
    $rawTeam=($method==='POST'?$_POST:$_GET)['team_id']??'0';if(!is_string($rawTeam)||!preg_match('/^(0|[1-9][0-9]{0,14})$/D',$rawTeam))throw new CatalogException('Equipo no válido.',422);
    if($product==='supervision'&&!(($method==='POST')&&($_POST['action']??'')==='prepare_team'))$teamId=supervision_billing_team($db,(int)$user['id'],(int)$rawTeam);$scopeReady=true;
    if($method==='POST'&&($_POST['action']??'')==='prepare_team'){
        if($product!=='supervision')throw new CatalogException('Plan no válido.',422);$scopeReady=false;if(!pro_config('supervision')['enabled'])throw new CatalogException('La contratación todavía no está disponible.',503);$teamId=supervision_prepare_team($db,(int)$user['id']);pro_response(200,['team_id'=>$teamId,'csrf'=>$csrf]);
    }
    if($method==='POST'){
        $action=$_POST['action']??'';$service=new ProSubscriptions($db,[...pro_config($product),'team_id'=>$teamId]);
        if(in_array($action,['checkout','checkout_hosted'],true)){if(!is_string($_POST['card_token']??''))throw new CatalogException('Token no válido.',422);$url=$service->checkout((int)$user['id'],$_POST['card_token']??'',$action==='checkout_hosted');pro_response(200,['checkout_url'=>$url,'subscription'=>pro_state($db,(int)$user['id'],$product,$teamId),'csrf'=>$csrf]);}
        elseif($action==='restart')$service->restart((int)$user['id']);
        elseif($action==='name_team'){if(!is_string($_POST['name']??null))throw new CatalogException('Nombre no válido.',422);$service->nameTeam((int)$user['id'],$_POST['name']);}
        elseif($action==='refresh')$service->reconcile((int)$user['id']);
        elseif($action==='cancel')$service->cancel((int)$user['id']);
        else pro_response(422,['error'=>'Acción no válida.']);
    }
    pro_response(200,['subscription'=>pro_state($db,(int)$user['id'],$product,$teamId),'csrf'=>$csrf]);
}catch(CatalogException $e){pro_response($e->status,['error'=>$e->getMessage(),...(!empty($scopeReady)?['subscription'=>pro_state($db,(int)$user['id'],$product,$teamId),'csrf'=>$csrf]:[])]);}
catch(Throwable $e){error_log('RepoTools Pro: '.get_class($e));pro_response(503,['error'=>'No pudimos consultar tu suscripción. Volvé a intentar.']);}
