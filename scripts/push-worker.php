<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/push.php';require_once __DIR__.'/../includes/push-health.php';
push_health_write(['status'=>'starting','at'=>time()]);
$phase='dependency';
try{
    if(!push_library()){push_health_write(['status'=>'error','at'=>time(),'error'=>'dependency_unavailable']);error_log('Repotools push: delivery dependency unavailable');exit(1);}
    $phase='database';$db=database();
    $phase='prepare';push_prepare($db);
    $phase='dispatch';$sent=push_dispatch($db);
    push_health_write(['status'=>'ok','at'=>time(),'sent'=>$sent]);
}catch(Throwable $e){
    $error=$e instanceof PDOException?'database_unavailable':'worker_error';
    $rawCode=$e instanceof PDOException?($e->errorInfo[0]??$e->getCode()):$e->getCode();
    $code=preg_match('/^[A-Z0-9]{5}$/D',(string)$rawCode)?(string)$rawCode:'unavailable';
    $reason=null;
    if($phase==='database'){
        $url=trim(getenv('DATABASE_URL')?:'');
        if($url===''&&getenv('REPOTOOLS_REQUIRE_DATABASE_URL')==='1')$reason='configuration_missing';
        elseif($url!==''){
            try{postgres_connection($url);}catch(PDOException $ignored){$reason='configuration_invalid';}
            if(!$reason&&!in_array('pgsql',PDO::getAvailableDrivers(),true))$reason='driver_missing';
        }
        $reason=$reason??push_connection_reason($e);
    }
    push_health_write(['status'=>'error','at'=>time(),'error'=>$error,'phase'=>$phase,'code'=>$code,'reason'=>$reason,'failure'=>$e instanceof PDOException?'query':($e instanceof Error?'runtime':'application')]);
    error_log('Repotools push: '.$error.' phase='.$phase.' class='.get_class($e).' code='.$code.' reason='.($reason??'unknown'));exit(1);
}
