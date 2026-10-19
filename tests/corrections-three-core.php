<?php
require __DIR__.'/unified-core.php';
$before=['branch_id'=>1,'revision'=>4,'products'=>[['id'=>4,'ean'=>'96385074','name'=>'Producto A','units'=>84,'revision'=>1],['id'=>9,'ean'=>'96385074','name'=>'Producto B','units'=>3]]];
$after=['branch_id'=>2,'revision'=>5,'products'=>[['id'=>9,'ean'=>'96385074','name'=>'Producto B','units'=>3],['id'=>4,'ean'=>'96385074','name'=>'Producto A','units'=>80,'revision'=>2]]];
$changes=audit_changes($before,$after);check(count($changes)===2,'only real nested changes, independent of array order');check($changes[1]['delta']===-4,'nested delta');check(str_contains($changes[1]['label'],'Producto A'),'product context');
check(audit_changes(['revision'=>1,'units'=>84],['revision'=>2,'units'=>'84'])===[],'metadata and numeric normalization');
check(count(audit_changes(['product'=>['name'=>'Antes','brand'=>'Misma']],['product'=>['name'=>'Después','brand'=>'Misma']]))===1,'nested product change');
$db->beginTransaction();foreach(['clear-action','independent-action','clear-action'] as $operation)audit_event($db,3,'role_changed',$operation,['category'=>'roles','target_user'=>1],['role'=>'usuario'],['role'=>'moderador']);$db->commit();
$items=audit_read($db,3,['action'=>'role_changed'])['items'];$matching=array_values(array_filter($items,fn($e)=>in_array($e['operation_id'],['clear-action','independent-action'],true)));check(count($matching)===2,'distinct operations preserved and replay deduplicated');
$event=audit_read($db,3,['event'=>$matching[0]['event_id']])['items'][0];check($event['action_label']==='Rango actualizado','specific title');check($event['can_technical']===false&&!isset($event['before'],$event['after'],$event['before_data'],$event['after_data']),'moderator has no raw JSON');check(count($event['changes'])===1&&$event['changes'][0]['field']==='role','readable role change');
$admin=audit_read($db,5,['event'=>$event['event_id']])['items'][0];check($admin['can_technical']&&isset($admin['before'],$admin['after']),'authorized admin original snapshots');check(audit_read($db,3,['event'=>hash('sha256','5|save_product|same-operation|confirmed')])['items']===[],'direct high-rank detail denied');
check(audit_action_label('account_updated')!==audit_action_label('role_changed'),'different action titles');
check(count(audit_changes([],[]))===0,'no invented changes');
$mail=welcome_email('Prueba','https://example.invalid/verify?token=safe');check(str_contains($mail['html'],'content="light only"'),'Gmail updated color-scheme');check(str_contains($mail['html'],'linear-gradient(#27197F,#27197F)'),'brand background fallback');check(str_contains($mail['html'],'gmail-difference'),'older Gmail white label fallback');check(str_contains($mail['html'],'https://example.invalid/verify?token=safe'),'verification link unchanged');
echo "OK: nested readable differences, product identity, role titles, distinct operations/replay, original snapshots, server raw-data permissions and color protection markup.\n";
