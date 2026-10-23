<?php
declare(strict_types=1);
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
        'code'=>is_string($state['code']??null)&&preg_match('/^[A-Z0-9]{5}$/D',$state['code'])?$state['code']:null,
        'failure'=>in_array($state['failure']??'',['query','runtime','application'],true)?$state['failure']:null];
}
