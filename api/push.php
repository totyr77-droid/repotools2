<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/push.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function push_response(int $status,array $data):never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
$user=current_user();if(!$user)push_response(401,['error'=>'Iniciá sesión para configurar los avisos.']);
$method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','POST'],true))push_response(405,['error'=>'Solicitud no válida.']);
if($method==='POST'&&!valid_csrf())push_response(403,['error'=>'La sesión venció. Volvé a ingresar.']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>8192)push_response(413,['error'=>'La suscripción es demasiado grande.']);
$uid=(int)$user['id'];$device=hash('sha256',$_SESSION['device_token']??'');$csrf=csrf_token();session_write_close();
try{
 $db=database();$action=$method==='GET'?($_GET['action']??'config'):($_POST['action']??'');if(!is_string($action))throw new CatalogException('Acción no válida.',422);
 if($method==='GET'&&$action==='verify'){
  $receipt=$_GET['receipt']??'';if(!is_string($receipt)||!preg_match('/^[a-f0-9]{32}$/D',$receipt))throw new CatalogException('Aviso no válido.',422);
  $allowed=(bool)stock_query($db,"SELECT 1 FROM push_jobs j JOIN push_subscriptions s ON s.id=j.subscription_id WHERE j.id=? AND s.user_id=? AND s.device_hash=? AND j.status IN ('sending','sent') AND j.expires_at>? AND ".push_active_sql(),[$receipt,$uid,$device,time()])->fetchColumn();
  push_response($allowed?200:403,['allowed'=>$allowed,'user'=>$uid]);
 }
 if($method==='GET'&&$action==='config'){
  $keys=push_keys($db);$s=stock_query($db,'SELECT id,enabled FROM push_subscriptions WHERE user_id=? AND device_hash=? ORDER BY enabled DESC,id DESC LIMIT 1',[$uid,$device])->fetch();
  push_response(200,['public_key'=>$keys['public_key'],'enabled'=>(bool)($s['enabled']??false),'csrf'=>$csrf,'user'=>$uid]);
 }
 if($method!=='POST')throw new CatalogException('Acción no válida.',422);
 if($action==='subscribe'){$raw=$_POST['subscription']??'';if(!is_string($raw)||strlen($raw)>6000||!is_array($input=json_decode($raw,true)))throw new CatalogException('La suscripción no es válida.',422);push_subscribe($db,$uid,$device,$input);}
 elseif($action==='disable')stock_query($db,'UPDATE push_subscriptions SET enabled=0,updated_at=? WHERE user_id=? AND device_hash=?',[time(),$uid,$device]);
 elseif($action==='test'){
  $s=stock_query($db,'SELECT s.id FROM push_subscriptions s WHERE s.user_id=? AND s.device_hash=? AND '.push_active_sql().' ORDER BY s.id DESC LIMIT 1',[$uid,$device])->fetch();if(!$s)throw new CatalogException('Activá los avisos en este dispositivo primero.',409);
  if(stock_query($db,"SELECT 1 FROM push_jobs WHERE subscription_id=? AND kind='test' AND available_at>?",[$s['id'],time()-60])->fetchColumn())throw new CatalogException('Esperá un minuto antes de repetir la prueba.',429);
  push_enqueue($db,(int)$s['id'],'test:'.floor(time()/60),'test',['title'=>'RepoTools','body'=>'Los avisos de este dispositivo están funcionando.','url'=>'cuenta.php#accountAlerts'],null,time()+600);
 }else throw new CatalogException('Acción no válida.',422);
 push_response(200,['ok'=>true,'csrf'=>$csrf]);
}catch(CatalogException $e){push_response($e->status,['error'=>$e->getMessage()]);}catch(Throwable $e){error_log('Repotools push settings unavailable: '.get_class($e));push_response(503,['error'=>'No pudimos configurar los avisos. Volvé a intentar.']);}
