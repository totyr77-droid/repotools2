<?php
require_once __DIR__.'/audit.php';
require_once __DIR__.'/audit-presentation.php';
require_once __DIR__.'/audit-records.php';
function audit_read_scope(PDO $db,int $viewer,array $input): array {
    $role=audit_q($db,'SELECT role FROM users WHERE id=?',[$viewer])->fetchColumn();$rank=audit_rank((string)$role);if($rank<1)throw new CatalogException('No tenés permiso para consultar registros.',403);
    $where=[];$params=[];
    if($rank<2){$where[]='(e.actor_id=? OR (e.actor_rank<? AND COALESCE(current_actor.role,\'usuario\') NOT IN (\'moderador\',\'administrador\',\'manager\') AND e.result<>\'denied\'))';$params[]=$viewer;$params[]=$rank;}
    foreach(['actor'=>'actor_id','branch'=>'branch_id','pallet'=>'pallet_code','ean'=>'ean','action'=>'action','role'=>'actor_role','category'=>'category','result'=>'result'] as $key=>$column){$value=$input[$key]??'';if(!is_string($value)||strlen($value)>100)throw new CatalogException('Filtro no válido.',422);if(in_array($key,['actor','branch'],true)&&$value!==''&&(!ctype_digit($value)||(int)$value<1))throw new CatalogException('Filtro numérico no válido.',422);if($value!==''){$where[]='e.'.$column.'=?';$params[]=$value;}}
    if(isset($input['family'])){if(!is_string($input['family'])||!preg_match('/^[a-z_]{1,30}$/D',$input['family']))throw new CatalogException('Acción no válida.',422);$where[]='e.action LIKE ?';$params[]=$input['family'].'%';}
    $q=$input['q']??'';if(!is_string($q)||mb_strlen($q)>100)throw new CatalogException('Búsqueda no válida.',422);if($q!==''){$where[]='(LOWER(e.actor_name) LIKE ? OR e.pallet_code LIKE ? OR e.ean LIKE ?)';array_push($params,'%'.mb_strtolower($q).'%','%'.$q.'%','%'.$q.'%');}
    foreach(['from'=>'>=','to'=>'<'] as $key=>$op)if(!empty($input[$key])){$v=$input[$key];if(!is_string($v)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)||!checkdate((int)substr($v,5,2),(int)substr($v,8,2),(int)substr($v,0,4)))throw new CatalogException('Fecha no válida.',422);$date=new DateTimeImmutable($v.' 00:00:00',new DateTimeZone('America/Argentina/Buenos_Aires'));if($key==='to')$date=$date->modify('+1 day');$where[]='e.occurred_at'.$op.'?';$params[]=$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
    $section=$input['section']??'';if(!is_string($section)||!in_array($section,['','users','goods','moderation','management'],true))throw new CatalogException('Sección no válida.',422);if($section!==''){$where[]='('.audit_section_sql($db).')=?';$params[]=$section;}
    $detail=$input['event']??'';if(!is_string($detail)||strlen($detail)>120)throw new CatalogException('Evento no válido.',422);if($detail!==''){$where[]='e.event_id=?';$params[]=$detail;}
    return [$where,$params,$rank,$detail];
}
function audit_read(PDO $db,int $viewer,array $input): array {
    if(($input['grouped']??'')==='1')return audit_grouped_read($db,$viewer,$input);
    [$where,$params,$rank,$detail]=audit_read_scope($db,$viewer,$input);
    $page=filter_var($input['page']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000]]);if($page===false)throw new CatalogException('Página no válida.',422);
    $base=' FROM audit_events e LEFT JOIN users current_actor ON current_actor.id=e.actor_id LEFT JOIN users audit_target ON audit_target.id=e.target_user'.($where?' WHERE '.implode(' AND ',$where):'');
    $columns=$detail!==''?'e.*,audit_target.public_name AS target_name':'audit_target.public_name AS target_name,e.event_id,e.actor_id,e.actor_name,e.actor_role,e.actor_rank,e.action,e.category,e.result,e.occurred_at,e.synced_at,e.operation_id,e.branch_id,e.pallet_id,e.pallet_code,e.ean,e.target_user';
    $items=audit_q($db,'SELECT '.$columns.$base.' ORDER BY e.synced_at DESC,e.event_id DESC LIMIT 51 OFFSET '.($page*50),$params)->fetchAll();$more=count($items)>50;$items=array_slice($items,0,50);
    $newAccounts=[];foreach($items as &$item){$item['event_type']='audit';$item['actor']=$item['actor_name'];$item['target']=$item['pallet_code']?:$item['ean'];$item['created_at']=$item['occurred_at'];$item['action_label']=audit_action_label($item['action']);if($detail!=='')$item=audit_present($db,$item,$rank);if($item['action']==='account_created')$newAccounts[]=(int)$item['actor_id'];unset($item['before_data'],$item['after_data']);}unset($item);
    if($newAccounts){$accounts=[];$rows=audit_q($db,'SELECT u.id,u.public_name,u.created_at,u.email_verified_at,a.public_name AS inviter FROM users u LEFT JOIN referrals r ON r.invitee_id=u.id LEFT JOIN users a ON a.id=r.referrer_id WHERE u.id IN ('.implode(',',array_fill(0,count($newAccounts),'?')).')',$newAccounts)->fetchAll();foreach($rows as $u)$accounts[$u['id']]=['name'=>$u['public_name'],'registered_at'=>$u['created_at'],'inviter'=>$u['inviter']?:'Sin invitador','verified'=>(bool)$u['email_verified_at']];foreach($items as &$item)if($item['action']==='account_created'&&isset($accounts[$item['actor_id']]))$item['account']=$accounts[$item['actor_id']];unset($item);}
    $actors=audit_q($db,"SELECT e.actor_id AS id,COALESCE(NULLIF(MAX(current_actor.public_name),''),MAX(e.actor_name)) AS name".$base.' GROUP BY e.actor_id ORDER BY name,id LIMIT 200',$params)->fetchAll();
    return ['items'=>$items,'actors'=>$actors,'has_more'=>$more,'can_audit'=>true];
}
