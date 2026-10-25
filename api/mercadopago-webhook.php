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
    $service=new ProSubscriptions(database(),$config);
    if(($body['type']??'')==='subscription_preapproval')$service->subscription($id);
    elseif(($body['type']??'')==='subscription_authorized_payment')$service->invoice($id);
    else{http_response_code(400);exit;}
    echo '{"received":true}';
}catch(CatalogException $e){http_response_code($e->status===401?401:503);echo '{"received":false}';}
catch(Throwable $e){error_log('RepoTools Pro webhook: '.get_class($e));http_response_code(503);echo '{"received":false}';}
