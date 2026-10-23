<?php
declare(strict_types=1);
// Immutable events and daily counters are written in the business transaction.
function audit_q(PDO $db,string $sql,array $params=[]): PDOStatement {$q=$db->prepare($sql);$q->execute($params);return $q;}
function audit_rank(string $role): int {return ['usuario'=>0,'moderador'=>1,'administrador'=>2,'manager'=>3][$role]??-1;}
function audit_clean($value){if(!is_array($value))return is_string($value)?mb_substr($value,0,500):$value;$out=[];foreach($value as $k=>$v)if(!preg_match('/password|token|secret|csrf|credential|email|ip_signal|photo|image/i',(string)$k))$out[$k]=audit_clean($v);return $out;}
function audit_time(?int $millis=null): string {
    $now=time();$at=$millis===null?$now:(int)floor($millis/1000);
    if($at>$now+300||$at<$now-30*86400)throw new InvalidArgumentException('La fecha original del movimiento no es válida.');
    return gmdate('Y-m-d H:i:s',$at);
}
function audit_event(PDO $db,int $actor,string $action,string $operation,array $context=[],array $before=[],array $after=[],string $result='confirmed',?string $original=null,int $activity=0,int $units=0): void {
    if(!$db->inTransaction())throw new LogicException('Audit requires the business transaction.');
    $businessActor=$actor;
    $supportActor=$GLOBALS['repo_support_actor']??null;$supportTarget=$GLOBALS['repo_support_target']??null;
    if($supportActor&&$supportTarget){$actor=(int)$supportActor['id'];$after['support_account']=['id'=>(int)$supportTarget['id'],'name'=>get_public_user_name($supportTarget)];}
    $u=audit_q($db,'SELECT id,name,role,public_name FROM users WHERE id=?',[$actor])->fetch();
    if($supportActor&&$supportTarget)$u['public_name']=get_public_user_name($u).' · como '.get_public_user_name($supportTarget);
    $id=hash('sha256',$actor.'|'.$action.'|'.$operation.'|'.$result);
    $at=$original??gmdate('Y-m-d H:i:s');
    $insert=audit_q($db,'INSERT INTO audit_events(event_id,actor_id,actor_name,actor_role,actor_rank,action,category,result,occurred_at,synced_at,operation_id,branch_id,pallet_id,pallet_code,ean,target_user,before_data,after_data) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(event_id) DO NOTHING',[$id,$actor,$u?get_public_user_name($u):'Usuario eliminado',$u['role']??'usuario',audit_rank($u['role']??'usuario'),$action,$context['category']??'movements',$result,$at,gmdate('Y-m-d H:i:s'),$operation,$context['branch_id']??null,$context['pallet_id']??null,$context['code']??'',$context['ean']??'',$context['target_user']??null,json_encode(audit_clean($before),JSON_UNESCAPED_UNICODE),json_encode(audit_clean($after),JSON_UNESCAPED_UNICODE)])->rowCount();
    if(!$insert||$result!=='confirmed')return;
    $actor=$businessActor;
    require_once __DIR__.'/achievements.php';
    if(in_array($action,['save_product','deduct_product','transfer_commit','edited'],true))achievement_day($db,$actor);
    if($action==='referral_credited'){
        achievement_record($db,$actor,'referidos',(string)($context['target_user']??$operation));
        $joined=audit_q($db,"SELECT r.id AS referral_id,u.id,u.public_name FROM referrals r JOIN users u ON u.id=r.invitee_id WHERE r.referrer_id=? AND r.invitee_id=? AND r.state='validated' AND r.email_verified_at IS NOT NULL",[$actor,(int)($context['target_user']??0)])->fetch();
        if($joined)audit_q($db,"INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?) ON CONFLICT(event_key) DO NOTHING",[$actor,'referral-joined:'.$joined['referral_id'],'referral_joined','¡Se sumó tu referido!',get_public_user_name($joined).' se unió a RepoTools con tu invitación. ¡Gracias por ayudarnos a crecer!']);
    }
    if($action==='transfer_commit')achievement_record($db,$actor,'transferencias',$operation);
    if($action==='deduct_product')achievement_record($db,$actor,'retiros',$operation);
    if($action==='save_product'){
        $oldUnits=array_sum(array_column($before['products']??[],'units'));$newUnits=array_sum(array_column($after['products']??[],'units'));
        if($newUnits>$oldUnits&&!empty($context['new_load']))achievement_record($db,$actor,'productos_agregados',$operation);
        if(empty($before['activated'])&&!empty($after['products'])&&(int)($after['owner_id']??0)===$actor)achievement_record($db,$actor,'pallets',(string)$after['id']);
    }
    if($action==='approved'&&!empty($context['target_user'])&&(int)$context['target_user']!==$actor&&array_intersect_key($before['product']??[],array_flip(['name','brand','quantity','category','packaging','image']))!==array_intersect_key($after['product']??[],array_flip(['name','brand','quantity','category','packaging','image'])))achievement_record($db,(int)$context['target_user'],'ediciones',$operation);
    $day=(new DateTimeImmutable($at,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'))->format('Y-m-d');
    foreach([['activity',$actor,$activity],['branches',(int)($context['branch_id']??0),$units]] as [$kind,$subject,$value])if($subject>0&&$value>0)audit_q($db,'INSERT INTO ranking_daily(kind,subject_id,day,value) VALUES(?,?,?,?) ON CONFLICT(kind,subject_id,day) DO UPDATE SET value=ranking_daily.value+excluded.value',[$kind,$subject,$day,$value]);
}
function audit_pallet(PDO $db,int $id): array {
    if(!$id)return [];$p=audit_q($db,'SELECT id,code,branch_id,description,released,activated,owner_id FROM pallets WHERE id=?',[$id])->fetch();if(!$p)return [];
    $p['products']=audit_q($db,'SELECT id,ean,name,expires,units,revision FROM pallet_products WHERE pallet_id=? ORDER BY id',[$id])->fetchAll();
    $p['accesses']=audit_q($db,'SELECT user_id,status FROM pallet_access WHERE pallet_id=? ORDER BY user_id',[$id])->fetchAll();return $p;
}
function audit_stock(PDO $db,int $actor,string $action,string $key,array $before,array $after,array $input): void {
    $comparableBefore=$before;$comparableAfter=$after;foreach($comparableBefore['products']??[] as $i=>$row)unset($comparableBefore['products'][$i]['revision']);foreach($comparableAfter['products']??[] as $i=>$row)unset($comparableAfter['products'][$i]['revision']);if($comparableBefore===$comparableAfter&&$before)return;
    $units=0;$ean=(string)($input['ean']??'');$old=[];$new=[];
    foreach($before['products']??[] as $p)$old[$p['ean']]=($old[$p['ean']]??0)+(int)$p['units'];
    if(empty($after['released']))foreach($after['products']??[] as $p)$new[$p['ean']]=($new[$p['ean']]??0)+(int)$p['units'];
    foreach(array_unique([...array_keys($old),...array_keys($new)]) as $code){$delta=abs(($new[$code]??0)-($old[$code]??0));$units+=$delta;if($delta)$ean=$code;}
    audit_event($db,$actor,$action,$key,['new_load'=>$action==='save_product'&&(int)($input['product_id']??0)===0,'category'=>in_array($action,['invite','accept','decline','revoke','leave'],true)?'roles':'movements','branch_id'=>$after['branch_id']??$before['branch_id']??null,'pallet_id'=>$after['id']??$before['id']??null,'code'=>$after['code']??$before['code']??($input['code']??''),'ean'=>$ean,'target_user'=>$input['user_id']??null],$before,$after,'confirmed',isset($input['offline_created'])?audit_time((int)$input['offline_created']):null,1,$units);
}
function audit_denied(PDO $db,int $actor,string $action,string $key,int $status,array $input=[]): void {
    if(!in_array($status,[403,409,422],true)||!$actor)return;
    $db->beginTransaction();try{$at=null;if(isset($input['offline_created']))try{$at=audit_time((int)$input['offline_created']);}catch(InvalidArgumentException $e){}audit_event($db,$actor,$action,$key,['category'=>$status===403?'roles':'movements','pallet_id'=>(int)($input['pallet_id']??0)?:null,'code'=>is_string($input['code']??null)?mb_substr($input['code'],0,8):'','ean'=>is_string($input['ean']??null)?mb_substr($input['ean'],0,20):'','target_user'=>(int)($input['user_id']??0)?:null],[],['http_status'=>$status,'request'=>array_intersect_key($input,array_flip(['amount','kind','pack_size','product_id','revision','offline_revision','expires']))],$status===403?'denied':($status===409?'conflict':'rejected'),$at);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('Repotools audit rejection unavailable');}
}

function audit_from_activity(?array $snapshot): array {
    if(!$snapshot)return [];$p=array_intersect_key($snapshot,array_flip(['id','code','branch_id','description','released','activated','owner_id']));$p['products']=[];$products=$snapshot['products']??[];ksort($products);foreach($products as $id=>$row)$p['products'][]=['id'=>(int)$id,...$row];$p['accesses']=[];$accesses=$snapshot['accesses']??[];ksort($accesses);foreach($accesses as $id=>$status)$p['accesses'][]=['user_id'=>(int)$id,'status'=>$status];return $p;
}

function audit_session(PDO $db,int $user,string $action,string $kind): void {
    // No cookies, session identifiers, credentials, IPs or device tokens are stored.
    $own=!$db->inTransaction();try{if($own)$db->beginTransaction();audit_event($db,$user,$action,bin2hex(random_bytes(16)),['category'=>'users'],[],['device_kind'=>$kind==='mobile'?'mobile':'desktop']);if($own)$db->commit();}catch(Throwable $e){if($own&&$db->inTransaction())$db->rollBack();error_log('Repotools session audit unavailable');}
}
