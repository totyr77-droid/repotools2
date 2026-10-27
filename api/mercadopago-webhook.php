<?php
// No user sessions, no trusted status/URLs from the request body.
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/pro-subscriptions.php';
header('Content-Type: application/json');header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit;}
if((int)($_SERVER['CONTENT_LENGTH']??0)>16384){http_response_code(413);exit;}
try{
    $config=pro_config();if(!$config['enabled']){http_response_code(503);exit;}
    $id=pro_verify_webhook($_SERVER['QUERY_STRING']??'',$_SERVER['HTTP_X_SIGNATURE']??'',$_SERVER['HTTP_X_REQUEST_ID']??'',$config['secret'],time());
    $body=json_decode(file_get_contents('php://input',false,null,0,16385),true);
    if(!is_array($body)||(string)($body['data']['id']??'')!==$id){http_response_code(400);exit;}
    $type=$body['type']??'';if(!in_array($type,['subscription_preapproval','subscription_authorized_payment','subscription_preapproval_plan'],true)){http_response_code(400);exit;}
    $db=database();if($type!=='subscription_preapproval_plan')foreach(['pro','supervision'] as $product){$settings=pro_config($product);if(!$settings['enabled'])continue;$service=new ProSubscriptions($db,$settings);if($type==='subscription_preapproval'){$service->subscription($id);$service->reconcileProvider($id);}else $service->invoice($id);}
    stock_query($db,'INSERT INTO billing_webhook_receipts(request_key,event_type,resource_key,received_at) VALUES(?,?,?,?) ON CONFLICT(request_key) DO NOTHING',[hash('sha256',$_SERVER['HTTP_X_REQUEST_ID']),$type,hash('sha256',$type.':'.$id),time()]);
    echo '{"received":true}';
}catch(CatalogException $e){error_log('RepoTools billing webhook category='.($e->status===401?'signature':'resource').' status='.$e->status);http_response_code($e->status===401?401:503);echo '{"received":false}';}
catch(Throwable $e){error_log('RepoTools Pro webhook: '.get_class($e));http_response_code(503);echo '{"received":false}';}
