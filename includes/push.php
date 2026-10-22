<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';require_once __DIR__.'/notification-context.php';
function push_library():bool {static $loaded=false;if(!$loaded&&is_file(__DIR__.'/../vendor/autoload.php')){require_once __DIR__.'/../vendor/autoload.php';$loaded=true;}return class_exists(\Minishlink\WebPush\WebPush::class);}
function push_keys(PDO $db):array {
    if(!push_library())throw new CatalogException('Las notificaciones del celular todavía no están disponibles.',503);
    $row=stock_query($db,'SELECT public_key,private_key FROM push_app_keys WHERE id=1')->fetch();
    if(!$row){$keys=\Minishlink\WebPush\VAPID::createVapidKeys();stock_query($db,'INSERT INTO push_app_keys(id,public_key,private_key) VALUES(1,?,?) ON CONFLICT(id) DO NOTHING',[$keys['publicKey'],$keys['privateKey']]);$row=stock_query($db,'SELECT public_key,private_key FROM push_app_keys WHERE id=1')->fetch();}
    return $row;
}
function push_active_sql(string $alias='s'):string {return "$alias.enabled=1 AND EXISTS(SELECT 1 FROM account_devices d JOIN users u ON u.id=d.user_id WHERE d.user_id=$alias.user_id AND d.token_hash=$alias.device_hash AND d.auth_version=u.auth_version AND d.expires_at>".time().')';}
function push_validate(array $input):array {
    $endpoint=$input['endpoint']??null;$keys=$input['keys']??null;
    if(!is_string($endpoint)||strlen($endpoint)>2048||preg_match('/[\x00-\x20\x7f]/',$endpoint)||!is_array($keys))throw new CatalogException('La suscripción no es válida.',422);
    $url=parse_url($endpoint);$host=strtolower($url['host']??'');
    $allowed=in_array($host,['fcm.googleapis.com','updates.push.services.mozilla.com'],true)||str_ends_with($host,'.push.apple.com')||str_ends_with($host,'.notify.windows.com');
    if(!$allowed||($url['scheme']??'')!=='https'||isset($url['user'])||isset($url['pass'])||isset($url['fragment'])||isset($url['port'])&&$url['port']!==443||empty($url['path']))throw new CatalogException('El servicio de notificaciones no es válido.',422);
    foreach(['p256dh'=>65,'auth'=>16] as $key=>$length){$value=$keys[$key]??'';if(!is_string($value)||!preg_match('/^[A-Za-z0-9_-]+={0,2}$/D',$value)||strlen($value)>100)throw new CatalogException('Las claves de suscripción no son válidas.',422);$raw=base64_decode(strtr($value,'-_','+/'),true);if($raw===false||strlen($raw)!==$length||$key==='p256dh'&&$raw[0]!=="\x04")throw new CatalogException('Las claves de suscripción no son válidas.',422);}
    return ['endpoint'=>$endpoint,'p256dh'=>$keys['p256dh'],'auth'=>$keys['auth']];
}
function push_subscribe(PDO $db,int $user,string $device,array $input):int {
    $data=push_validate($input);push_keys($db);
    if((int)stock_query($db,'SELECT COUNT(*) FROM push_subscriptions WHERE user_id=? AND updated_at>?',[$user,time()-3600])->fetchColumn()>20)throw new CatalogException('Se hicieron muchos cambios. Volvé a intentar más tarde.',429);
$hash=hash('sha256',$data['endpoint']);$own=!$db->inTransaction();if($own)$db->beginTransaction();
    try{
        $current=stock_query($db,'SELECT id,user_id,device_hash,enabled FROM push_subscriptions WHERE endpoint_hash=?',[$hash])->fetch();
        $cursor=(int)stock_query($db,'SELECT COALESCE(MAX(id),0) FROM notifications')->fetchColumn();
        if($current&&(int)$current['user_id']===$user&&hash_equals($current['device_hash'],$device)&&(int)$current['enabled']===1){stock_query($db,'UPDATE push_subscriptions SET endpoint=?,p256dh=?,auth=?,updated_at=? WHERE id=?',[$data['endpoint'],$data['p256dh'],$data['auth'],time(),$current['id']]);$id=(int)$current['id'];}
        else{stock_query($db,"INSERT INTO push_subscriptions(endpoint_hash,user_id,device_hash,endpoint,p256dh,auth,last_notification_id,updated_at) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(endpoint_hash) DO UPDATE SET user_id=excluded.user_id,device_hash=excluded.device_hash,endpoint=excluded.endpoint,p256dh=excluded.p256dh,auth=excluded.auth,enabled=1,last_notification_id=excluded.last_notification_id,expiry_day='',updated_at=excluded.updated_at",[$hash,$user,$device,$data['endpoint'],$data['p256dh'],$data['auth'],$cursor,time()]);$id=(int)stock_query($db,'SELECT id FROM push_subscriptions WHERE endpoint_hash=?',[$hash])->fetchColumn();stock_query($db,"UPDATE push_jobs SET status='cancelled' WHERE subscription_id=? AND status IN ('pending','sending')",[$id]);}
        stock_query($db,'UPDATE push_subscriptions SET enabled=0,updated_at=? WHERE user_id=? AND device_hash=? AND id<>?',[time(),$user,$device,$id]);
        if($own)$db->commit();return $id;
    }catch(Throwable $e){if($own&&$db->inTransaction())$db->rollBack();throw $e;}
}
function push_event_payload(PDO $db,int $user,array $n):?array {
    if(!in_array($n['type'],['stock_invitation','team_invitation','supervisor_invitation','library_invitation','team_pallet_request'],true)||$n['read_at'])return null;
    if(!stock_query($db,'SELECT 1 FROM notifications n WHERE n.id=? AND n.user_id=? AND '.notification_visibility_sql($db,$user),[$n['id'],$user])->fetchColumn())return null;
    $url='cuenta.php';$key=$n['event_key'];
    if($n['type']==='stock_invitation'){
        if(!preg_match('/^pallet-invite:([0-9]+):/',$key,$m))return null;
        $p=stock_query($db,"SELECT p.code FROM pallet_access a JOIN pallets p ON p.id=a.pallet_id WHERE a.user_id=? AND a.pallet_id=? AND a.status='pending' AND p.released=0",[$user,$m[1]])->fetch();if(!$p)return null;$url='stock.php?invitacion='.rawurlencode($p['code']);
    }elseif($n['type']==='team_pallet_request'){
        if(!preg_match('/^team-pallet-request:([0-9]+)$/D',$key,$m))return null;
        if(!stock_query($db,"SELECT 1 FROM team_pallet_requests WHERE id=? AND owner_id=? AND status='pending'",[$m[1],$user])->fetchColumn())return null;$url='stock.php?solicitud_equipo='.$m[1];
    }elseif($n['type']==='team_invitation'){
        if(!preg_match('/^team-invite:([a-f0-9]{64})$/D',$key,$m))return null;
        if(!stock_query($db,'SELECT 1 FROM branch_team_invites WHERE token_hash=? AND revoked=0 AND used_by IS NULL AND expires>?',[$m[1],time()])->fetchColumn())return null;$url='sucursales.php';
    }elseif($n['type']==='supervisor_invitation'){
        if(!preg_match('/^supervisor-invite:([0-9]+):/',$key,$m)||!stock_query($db,"SELECT 1 FROM supervisor_members m JOIN supervisor_teams t ON t.id=m.team_id WHERE m.team_id=? AND m.user_id=? AND m.status='pending' AND t.active=1",[$m[1],$user])->fetchColumn())return null;$url='supervision.php';
    }elseif($n['type']==='library_invitation'){$url='biblioteca.php';if(preg_match('/^library-share:([0-9]+):/',$key,$m)){$token=stock_query($db,'SELECT token FROM library_shares WHERE source_library_id=?',[$m[1]])->fetchColumn();if(!$token)return null;$url.='?share='.rawurlencode($token);}}
    $items=notification_branch_context($db,[$n],$user);if(!$items)return null;$n=$items[0];
    return ['title'=>mb_substr($n['title'],0,100),'body'=>mb_substr($n['body'],0,220),'url'=>$url];
}
function push_expiry_payload(PDO $db,int $user):?array {
    $today=new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires'));$date=$today->format('Y-m-d');$limit=$today->modify('+'.stock_warning_days($db,$user).' days')->format('Y-m-d');
    $counts=stock_query($db,"SELECT COALESCE(SUM(CASE WHEN b.expires<? THEN 1 ELSE 0 END),0) AS expired,COALESCE(SUM(CASE WHEN b.expires=? THEN 1 ELSE 0 END),0) AS today,COALESCE(SUM(CASE WHEN b.expires>? THEN 1 ELSE 0 END),0) AS upcoming FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id WHERE p.released=0 AND p.activated=1 AND b.units>0 AND b.expires<>'' AND b.expires<=? AND (p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access a WHERE a.pallet_id=p.id AND a.user_id=? AND a.status='accepted'))",[$date,$date,$date,$limit,$user,$user])->fetch();
    $parts=[];foreach(['expired'=>'vencidos','today'=>'vencen hoy','upcoming'=>'próximos a vencer'] as $key=>$label)if((int)$counts[$key])$parts[]=(int)$counts[$key].' '.($key==='expired'?'lotes ':'').$label;
    if(!$parts)return null;return ['title'=>'Vencimientos de tu stock','body'=>implode(' · ',$parts).'. Revisá tu mercadería.','url'=>'stock.php?avisos=vencimientos'];
}
function push_enqueue(PDO $db,int $sub,string $key,string $kind,array $payload,?int $notification=null,?int $expires=null):void {
    stock_query($db,"INSERT INTO push_jobs(id,subscription_id,event_key,notification_id,kind,payload,available_at,expires_at) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(subscription_id,event_key) DO NOTHING",[bin2hex(random_bytes(16)),$sub,$key,$notification,$kind,json_encode($payload,JSON_UNESCAPED_UNICODE),time(),$expires??time()+86400]);
}
function push_prepare(PDO $db,?DateTimeImmutable $now=null):void {
    $now=$now??new DateTimeImmutable('now',new DateTimeZone('America/Buenos_Aires'));$today=$now->format('Y-m-d');
    $ids=stock_query($db,'SELECT s.id FROM push_subscriptions s WHERE '.push_active_sql().' ORDER BY s.checked_at,s.id LIMIT 100')->fetchAll(PDO::FETCH_COLUMN);
    foreach($ids as $id){$db->beginTransaction();try{
        $s=stock_query($db,'SELECT s.* FROM push_subscriptions s WHERE s.id=? AND '.push_active_sql().($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?' FOR UPDATE':''),[$id])->fetch();if(!$s){$db->commit();continue;}
        $cursor=(int)$s['last_notification_id'];$notifications=stock_query($db,'SELECT * FROM notifications WHERE user_id=? AND id>? ORDER BY id LIMIT 50',[$s['user_id'],$cursor])->fetchAll();
        foreach($notifications as $n){if($payload=push_event_payload($db,(int)$s['user_id'],$n))push_enqueue($db,(int)$s['id'],'notice:'.$n['id'],'notice',$payload,(int)$n['id']);$cursor=(int)$n['id'];}
        $day=$s['expiry_day'];if($day!==$today&&(int)$now->format('H')>=9){if($payload=push_expiry_payload($db,(int)$s['user_id']))push_enqueue($db,(int)$s['id'],'expiry:'.$today,'expiry',$payload,null,(int)$now->setTime(23,59,59)->format('U'));$day=$today;}
        stock_query($db,'UPDATE push_subscriptions SET last_notification_id=?,expiry_day=?,checked_at=? WHERE id=?',[$cursor,$day,time(),$id]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}}
}
function push_send(PDO $db,array $s,array $payload):array {
    $keys=push_keys($db);$sender=new \Minishlink\WebPush\WebPush(['VAPID'=>['subject'=>'https://repotools.online','publicKey'=>$keys['public_key'],'privateKey'=>$keys['private_key']]],['TTL'=>3600,'urgency'=>'normal'],10,['allow_redirects'=>false,'connect_timeout'=>5]);
    $report=$sender->sendOneNotification(\Minishlink\WebPush\Subscription::create(['endpoint'=>$s['endpoint'],'keys'=>['p256dh'=>$s['p256dh'],'auth'=>$s['auth']]]),json_encode($payload,JSON_UNESCAPED_UNICODE));
    return ['success'=>$report->isSuccess(),'status'=>$report->getResponse()?->getStatusCode()??0];
}
function push_dispatch(PDO $db,?callable $send=null):int {
    $now=time();$sent=0;$jobs=stock_query($db,"SELECT id FROM push_jobs WHERE (status='pending' AND available_at<=? OR status='sending' AND lease_until<=?) ORDER BY available_at,id LIMIT 20",[$now,$now])->fetchAll(PDO::FETCH_COLUMN);
    foreach($jobs as $id){$now=time();if(!stock_query($db,"UPDATE push_jobs SET status='sending',attempts=attempts+1,lease_until=? WHERE id=? AND (status='pending' AND available_at<=? OR status='sending' AND lease_until<=?)",[$now+120,$id,$now,$now])->rowCount())continue;
        $job=stock_query($db,'SELECT * FROM push_jobs WHERE id=?',[$id])->fetch();$s=stock_query($db,'SELECT s.* FROM push_subscriptions s WHERE s.id=? AND '.push_active_sql(),[$job['subscription_id']])->fetch();$payload=null;
        if($s&&(int)$job['expires_at']>$now){if($job['kind']==='notice'){$n=stock_query($db,'SELECT * FROM notifications WHERE id=? AND user_id=?',[$job['notification_id'],$s['user_id']])->fetch();if($n)$payload=push_event_payload($db,(int)$s['user_id'],$n);}elseif($job['kind']==='expiry')$payload=push_expiry_payload($db,(int)$s['user_id']);else $payload=json_decode($job['payload'],true);}
        if(!$payload){stock_query($db,"UPDATE push_jobs SET status='cancelled' WHERE id=?",[$id]);continue;}
        $payload+=[ 'user'=>(int)$s['user_id'],'receipt'=>$id,'tag'=>$job['kind']==='expiry'?'stock-expiry':'notice-'.($job['notification_id']??$id) ];
        try{$result=($send??'push_send')($db,$s,$payload);$code=(int)$result['status'];if($result['success']){stock_query($db,"UPDATE push_jobs SET status='sent',sent_at=?,error_code=NULL WHERE id=?",[time(),$id]);$sent++;}elseif(in_array($code,[404,410],true)){stock_query($db,'UPDATE push_subscriptions SET enabled=0,updated_at=? WHERE id=?',[time(),$s['id']]);stock_query($db,"UPDATE push_jobs SET status='cancelled',error_code=? WHERE id=?",['HTTP_'.$code,$id]);}else throw new RuntimeException('HTTP_'.$code);}catch(Throwable $e){$terminal=(int)$job['attempts']>=5;stock_query($db,"UPDATE push_jobs SET status=?,available_at=?,lease_until=0,error_code=? WHERE id=?",[$terminal?'failed':'pending',time()+min(3600,60*(2**min(5,(int)$job['attempts']))),'DELIVERY_FAILED',$id]);}
    }
    stock_query($db,"DELETE FROM push_jobs WHERE status IN ('sent','cancelled','failed') AND expires_at<?",[time()-7*86400]);return $sent;
}
