<?php
declare(strict_types=1);
// Return only fixed categories: never persist libpq messages, hostnames or credentials.
function push_connection_reason(Throwable $error): ?string {
    $message=strtolower($error->getMessage());
    foreach([
        'permission_denied'=>['permission denied','access is denied'],
        'authentication_failed'=>['password authentication failed','authentication failed'],
        'dns_failed'=>['could not translate host name','name or service not known','unknown host'],
        'connection_timeout'=>['timeout expired','connection timed out'],
        'connection_refused'=>['connection refused','could not connect to server'],
        'tls_failed'=>['ssl error','certificate verify failed','root certificate','ssl certificate'],
    ] as $reason=>$patterns)foreach($patterns as $pattern)if(str_contains($message,$pattern))return $reason;
    return null;
}
function push_health_path(): string {return getenv('REPOTOOLS_PUSH_HEALTH_PATH')?:'/var/lib/repotools/push-worker-health.json';}
function push_health_write(array $state): void {
    $path=push_health_path();$temp=$path.'.'.getmypid().'.tmp';
    if(@file_put_contents($temp,json_encode($state,JSON_UNESCAPED_UNICODE),LOCK_EX)===false||!@rename($temp,$path)){@unlink($temp);error_log('Repotools push: could not save worker status');}
}
function push_health_read(): array {
    $raw=@file_get_contents(push_health_path());$state=is_string($raw)?json_decode($raw,true):null;
    if(!is_array($state))return ['state'=>'unknown','last_run'=>null];
    $at=(int)($state['at']??0);$status=(string)($state['status']??'unknown');
    return ['state'=>$at<time()-300?'stale':($status==='ok'?'running':($status==='starting'?'starting':'error')),'last_run'=>$at?:null,'sent'=>(int)($state['sent']??0),'error'=>in_array($state['error']??'',['dependency_unavailable','database_unavailable','worker_error'],true)?$state['error']:null,
        'phase'=>in_array($state['phase']??'',['dependency','database','prepare','dispatch'],true)?$state['phase']:null,
        'reason'=>in_array($state['reason']??'',['configuration_missing','configuration_invalid','driver_missing','permission_denied','authentication_failed','dns_failed','connection_timeout','connection_refused','tls_failed'],true)?$state['reason']:null,
        'code'=>is_string($state['code']??null)&&preg_match('/^[A-Z0-9]{5}$/D',$state['code'])?$state['code']:null,
        'failure'=>in_array($state['failure']??'',['query','runtime','application'],true)?$state['failure']:null];
}
