<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/push.php';require_once __DIR__.'/../includes/push-health.php';
push_health_write(['status'=>'starting','at'=>time()]);
try{
    if(!push_library()){push_health_write(['status'=>'error','at'=>time(),'error'=>'dependency_unavailable']);error_log('Repotools push: delivery dependency unavailable');exit(1);}
    $db=database();push_prepare($db);$sent=push_dispatch($db);
    push_health_write(['status'=>'ok','at'=>time(),'sent'=>$sent]);
}catch(Throwable $e){
    $error=$e instanceof PDOException?'database_unavailable':'worker_error';push_health_write(['status'=>'error','at'=>time(),'error'=>$error]);
    $code=preg_match('/^[A-Z0-9]{5}$/D',(string)$e->getCode())?(string)$e->getCode():'unavailable';
    error_log('Repotools push: '.$error.' class='.get_class($e).' code='.$code);exit(1);
}
