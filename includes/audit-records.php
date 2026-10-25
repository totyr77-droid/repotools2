<?php
declare(strict_types=1);
require_once __DIR__.'/branch-visibility.php';
// Group only explicit operation identities. Immutable source events are never rewritten.
function audit_operation_sql(PDO $db): string {
    $explicit=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?"(e.after_data::jsonb->>'transaction_id')":"json_extract(e.after_data,'$.transaction_id')";
    // Older batch deletes used UUID:pallet_id. Never infer a batch from a timestamp.
    $legacy="CASE WHEN e.action='delete' AND e.pallet_id IS NOT NULL AND LENGTH(e.operation_id)=37+LENGTH(CAST(e.pallet_id AS TEXT)) AND SUBSTR(e.operation_id,9,1)='-' AND SUBSTR(e.operation_id,14,1)='-' AND SUBSTR(e.operation_id,19,1)='-' AND SUBSTR(e.operation_id,24,1)='-' AND SUBSTR(e.operation_id,37)=(':' || CAST(e.pallet_id AS TEXT)) THEN SUBSTR(e.operation_id,1,36) ELSE NULL END";
    return "COALESCE(NULLIF($explicit,''),$legacy,NULLIF(e.operation_id,''),e.event_id)";
}
function audit_section_sql(PDO $db): string {
    $owner=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?"(e.before_data::jsonb->>'owner_id')":"json_extract(e.before_data,'$.owner_id')";
    return "CASE WHEN e.event_id LIKE 'legacy:pallet_admin_events:%' OR e.result<>'confirmed' OR e.action IN ('role_changed','blocked','unblocked','approved','rejected','edited','referral_decision') OR (e.action='account_updated' AND e.target_user IS NOT NULL AND e.target_user<>e.actor_id) OR (e.actor_rank>=1 AND e.pallet_id IS NOT NULL AND $owner IS NOT NULL AND CAST($owner AS BIGINT)<>e.actor_id) THEN 'moderation' WHEN e.category='users' OR e.action IN ('session_started','session_ended','password_recovered') THEN 'users' WHEN e.action LIKE 'branch_%' OR e.category='links' OR e.action IN ('invite','accept','decline','revoke','leave') THEN 'management' WHEN e.category='roles' THEN 'moderation' ELSE 'goods' END";
}
function audit_grouped_read(PDO $db,int $viewer,array $input): array {
    [$where,$params,$rank,$detail]=audit_read_scope($db,$viewer,$input);
    $page=filter_var($input['page']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000]]);if($page===false)throw new CatalogException('Página no válida.',422);
    $operation=audit_operation_sql($db);$section=audit_section_sql($db);
    $branchVisible=$rank>=3?'1=1':'audit_branch.id NOT IN ('.implode(',',BRANCH_MANAGER_ONLY_IDS).')';
    $joins=' FROM audit_events e LEFT JOIN users current_actor ON current_actor.id=e.actor_id LEFT JOIN users audit_target ON audit_target.id=e.target_user LEFT JOIN stock_branches audit_branch ON audit_branch.id=e.branch_id AND '.$branchVisible;
    $base=$joins.($where?' WHERE '.implode(' AND ',$where):'');
    if($detail!==''){
        $seed=audit_q($db,"SELECT e.actor_id,e.result,$operation AS operation_group,$section AS section".$base,$params)->fetch();
        if(!$seed)return ['items'=>[],'actors'=>[],'has_more'=>false,'can_audit'=>true];
        // Fetch the entire authorized operation even if a list filter matched just one member.
        [$allowed,$args]=audit_read_scope($db,$viewer,[]);
        $allowed[]="e.actor_id=? AND e.result=? AND ($operation)=? AND ($section)=?";
        array_push($args,$seed['actor_id'],$seed['result'],$seed['operation_group'],$seed['section']);
        $rows=audit_q($db,"SELECT e.*,audit_target.public_name AS target_name,audit_branch.name AS branch_name".$joins.' WHERE '.implode(' AND ',$allowed).' ORDER BY e.synced_at,e.event_id LIMIT 201',$args)->fetchAll();
        if(count($rows)>200)throw new CatalogException('La operación es demasiado extensa. Consultá los registros por palet.',422);
        $events=[];foreach($rows as $row){$event=audit_present($db,$row,$rank);$event['products']=audit_product_changes($row);$after=json_decode($row['after_data'],true)?:[];$event['transfer']=$after['transfer']??null;$event['label_codes']=$after['labels']??[];$event['device_kind']=$after['device_kind']??null;$event['links']=audit_navigation($db,$event,$viewer,$rank);if($row['action']==='account_created'){$account=audit_q($db,'SELECT u.email_verified_at,a.public_name AS inviter FROM users u LEFT JOIN referrals r ON r.invitee_id=u.id LEFT JOIN users a ON a.id=r.referrer_id WHERE u.id=?',[$row['actor_id']])->fetch();$event['account']=$account?['verified'=>(bool)$account['email_verified_at'],'inviter'=>$account['inviter']?:'Sin invitador']:null;}$events[]=$event;}
        $first=$rows[0];return ['items'=>[['event_id'=>$detail,'action_label'=>count($rows)>1?'Operación con '.count($rows).' movimientos':audit_action_label($first['action']),'events'=>$events,'event_count'=>count($rows),'section'=>$seed['section']]],'actors'=>[],'has_more'=>false,'can_audit'=>true];
    }
    $columns='e.event_id,e.actor_id,e.actor_name,e.actor_role,e.actor_rank,e.action,e.category,e.result,e.occurred_at,e.synced_at,e.operation_id,e.branch_id,e.pallet_id,e.pallet_code,e.ean,e.target_user,audit_target.public_name AS target_name,audit_branch.name AS branch_name';
    $partition='actor_id,operation_group,result,section';
    $allowedInput=$input;foreach(['actor','branch','pallet','ean','action','role','category','result','family','q','from','to'] as $key)unset($allowedInput[$key]);
    [$allWhere,$allParams]=audit_read_scope($db,$viewer,$allowedInput);
    $allBase=$joins.($allWhere?' WHERE '.implode(' AND ',$allWhere):'');
    // DISTINCT keeps one match per operation; a join avoids re-scanning matched for every event.
    $visibleBase=$where===$allWhere&&$params===$allParams?$allBase:$joins." JOIN matched m ON m.actor_id=e.actor_id AND m.result=e.result AND m.operation_group=($operation) AND m.section=($section)".($allWhere?' WHERE '.implode(' AND ',$allWhere):'');
    $sql="WITH matched AS (SELECT DISTINCT e.actor_id,e.result,$operation AS operation_group,$section AS section$base), visible AS (SELECT $columns,$operation AS operation_group,$section AS section$visibleBase), ranked AS (SELECT visible.*,COUNT(*) OVER(PARTITION BY $partition) AS event_count,ROW_NUMBER() OVER(PARTITION BY $partition ORDER BY synced_at DESC,event_id DESC) AS position FROM visible) SELECT * FROM ranked WHERE position=1 ORDER BY synced_at DESC,event_id DESC LIMIT 51 OFFSET ".($page*50);
    $rows=audit_q($db,$sql,[...$params,...$allParams])->fetchAll();$more=count($rows)>50;$rows=array_slice($rows,0,50);
    foreach($rows as &$row){$row=array_merge($row,audit_style($row));$row['event_type']='audit';$row['actor']=$row['actor_name'];$row['created_at']=$row['occurred_at'];$row['action_label']=audit_action_label($row['action']);if((int)$row['event_count']>1)$row['action_label']=$row['action']==='delete'?'Palets eliminados':'Operación de mercadería';unset($row['position']);}unset($row);
    $actorInput=$input;unset($actorInput['actor']);[$actorWhere,$actorParams]=audit_read_scope($db,$viewer,$actorInput);
    $actors=audit_q($db,"SELECT e.actor_id AS id,COALESCE(NULLIF(MAX(current_actor.public_name),''),MAX(e.actor_name)) AS name".$joins.($actorWhere?' WHERE '.implode(' AND ',$actorWhere):'').' GROUP BY e.actor_id ORDER BY name,e.actor_id LIMIT 200',$actorParams)->fetchAll();
    $actions=audit_q($db,'SELECT DISTINCT e.action'.$allBase.' ORDER BY e.action LIMIT 100',$allParams)->fetchAll(PDO::FETCH_COLUMN);
    $branches=audit_q($db,'SELECT DISTINCT audit_branch.id,audit_branch.name'.$allBase.($allWhere?' AND ':' WHERE ').'audit_branch.id IS NOT NULL ORDER BY audit_branch.name,audit_branch.id LIMIT 200',$allParams)->fetchAll();
    return ['items'=>$rows,'actors'=>$actors,'branches'=>$branches,'actions'=>array_map(static fn($action)=>['value'=>$action,'label'=>audit_action_label($action)],$actions),'has_more'=>$more,'can_audit'=>true];
}
function audit_product_changes(array $event): array {
    $before=json_decode($event['before_data']??'{}',true)?:[];$after=json_decode($event['after_data']??'{}',true)?:[];
    $index=static function(array $data):array{$out=[];foreach($data['products']??[] as $row)$out[(string)($row['id']??($row['ean'].'|'.($row['expires']??'')))]=$row;return $out;};
    $old=$index($before);$new=empty($after['released'])?$index($after):[];$items=[];
    foreach(array_unique([...array_keys($old),...array_keys($new)]) as $id){$a=$old[$id]??[];$b=$new[$id]??[];if($a&&$b&&(int)$a['units']===(int)$b['units']&&($a['expires']??'')===($b['expires']??'')&&($a['name']??'')===($b['name']??''))continue;$row=$b?:$a;$items[]=['name'=>$row['name']??'Producto','name_before'=>$a['name']??'','name_after'=>$b['name']??'','ean'=>$row['ean']??'','expires_before'=>$a['expires']??'','expires_after'=>$b['expires']??'','before'=>(int)($a['units']??0),'after'=>(int)($b['units']??0),'delta'=>(int)($b['units']??0)-(int)($a['units']??0),'status'=>!$a?'added':(!$b?'removed':'updated')];}
    return $items;
}
