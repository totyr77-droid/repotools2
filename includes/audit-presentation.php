<?php
declare(strict_types=1);
// Presentation only: immutable audit snapshots remain untouched.
function audit_action_label(string $action): string {
    return ['role_changed'=>'Rango actualizado','account_updated'=>'Datos de la cuenta actualizados','public_name_updated'=>'Nombre público actualizado','profile_updated'=>'Perfil actualizado','save_product'=>'Mercadería incorporada o actualizada','deduct_product'=>'Mercadería retirada','delete_product'=>'Producto eliminado','delete'=>'Palet eliminado','claim'=>'Palet activado','generate'=>'Etiquetas generadas','delete_label'=>'Etiqueta eliminada','description'=>'Descripción actualizada','branch_assign'=>'Sucursal del palet modificada','branch_created'=>'Sucursal creada','branch_updated'=>'Datos de sucursal actualizados','branch_deleted'=>'Sucursal eliminada','branch_shared'=>'Enlace de sucursal creado','branch_share'=>'Enlace de sucursal creado','branch_revoke'=>'Enlace revocado','library_shared'=>'Colección compartida','stock_shared'=>'Stock compartido','share'=>'Enlace creado','revoke_share'=>'Enlace revocado','invite'=>'Invitación enviada','accept'=>'Invitación aceptada','decline'=>'Invitación rechazada','revoke'=>'Acceso revocado','leave'=>'Acceso abandonado','edited'=>'Ficha de producto actualizada','approved'=>'Reporte aprobado','rejected'=>'Reporte rechazado','blocked'=>'Reportes bloqueados','unblocked'=>'Reportes habilitados','account_created'=>'Cuenta creada','email_verified'=>'Correo verificado','referral_credited'=>'Referido acreditado','referral_decision'=>'Estado de referido actualizado','password_changed'=>'Contraseña actualizada','sessions_revoked'=>'Sesiones revocadas'][$action]??'Acción registrada: '.str_replace('_',' ',$action);
}
function audit_changes(array $before,array $after): array {
    $labels=['role'=>'Rango','name'=>'Nombre','public_name'=>'Nombre público','company'=>'Empresa','description'=>'Descripción','branch_id'=>'Sucursal','owner_id'=>'Propietario','released'=>'Palet liberado','activated'=>'Palet activado','units'=>'Cantidad de unidades','ean'=>'Código EAN','expires'=>'Vencimiento','status'=>'Estado','blocked'=>'Reportes bloqueados','verified'=>'Correo verificado','state'=>'Estado','address'=>'Dirección','street'=>'Calle','number'=>'Altura','city'=>'Localidad','province'=>'Provincia','latitude'=>'Latitud','longitude'=>'Longitud','quantity'=>'Contenido neto','brand'=>'Marca','category'=>'Categoría','packaging'=>'Envase','note'=>'Nota','reason'=>'Motivo','collection'=>'Colección','amount'=>'Cantidad','kind'=>'Presentación','pack_size'=>'Unidades por presentación','http_status'=>'Respuesta del servidor'];
    $ignored=['id','revision','auth_version','historical_role_unknown','legacy','actor','changes','created_at','updated_at'];$changes=[];
    $walk=function(array $old,array $new,string $prefix='')use(&$walk,&$changes,$labels,$ignored):void{
        foreach(array_unique([...array_keys($old),...array_keys($new)]) as $key){
            if(in_array((string)$key,$ignored,true)||preg_match('/password|token|secret|csrf|credential|email|photo|image/i',(string)$key))continue;
            $a=$old[$key]??null;$b=$new[$key]??null;if($a===$b)continue;
            if(in_array($key,['products','accesses'],true)&&is_array($a??[])&&is_array($b??[])){
                $index=function(array $rows)use($key):array{$out=[];foreach($rows as $i=>$row){if(!is_array($row))continue;$id=$row[$key==='products'?'id':'user_id']??($row['ean']??$i);$out[(string)$id]=$row;}return $out;};
                $aa=$index($a??[]);$bb=$index($b??[]);foreach(array_unique([...array_keys($aa),...array_keys($bb)]) as $id){$row=$bb[$id]??$aa[$id];$title=$key==='products'?('Producto '.($row['name']??'').(!empty($row['ean'])?' · '.$row['ean']:'')):'Acceso de usuario #'.$id;$walk($aa[$id]??[],$bb[$id]??[],$title.' · ');}continue;
            }
            if(is_array($a)||is_array($b)){if(is_array($a??[])&&is_array($b??[]))$walk($a??[],$b??[],$prefix.(['product'=>'Producto','request'=>'Solicitud','proposal'=>'Propuesta'][$key]??($labels[$key]??'Información')).' · ');continue;}
            if(!isset($labels[$key]))continue;
            // DB drivers may represent unchanged numeric values as strings.
            if($a!==null&&$b!==null&&(string)$a===(string)$b)continue;
            $changes[]=['field'=>$key,'label'=>$prefix.$labels[$key],'before'=>$a,'after'=>$b,'delta'=>$key==='units'?(int)($b??0)-(int)($a??0):null];
        }
    };$walk($before,$after);return $changes;
}
function audit_present(PDO $db,array $event,int $rank): array {
    $before=json_decode($event['before_data']??'{}',true)?:[];$after=json_decode($event['after_data']??'{}',true)?:[];
    $changes=audit_changes($before,$after);$names=[];
    foreach($changes as $change)if(in_array($change['field'],['branch_id','owner_id'],true))foreach(['before','after'] as $side){$id=$change[$side];if(!$id)continue;$table=$change['field']==='branch_id'?'stock_branches':'users';$column=$table==='users'?'public_name':'name';$key=$table.':'.$id;if(!array_key_exists($key,$names)){try{$names[$key]=audit_q($db,'SELECT '.$column.' FROM '.$table.' WHERE id=?',[$id])->fetchColumn()?:null;}catch(PDOException $e){$names[$key]=null;}}}
    foreach($changes as &$change)if(in_array($change['field'],['branch_id','owner_id'],true))foreach(['before','after'] as $side){$id=$change[$side];$table=$change['field']==='branch_id'?'stock_branches':'users';$change[$side]=!$id?($table==='users'?'Sin propietario':'Sin sucursal'):($names[$table.':'.$id]??(($table==='users'?'Usuario':'Sucursal').' #'.$id));}unset($change);
    $event['action_label']=audit_action_label($event['action']);$event['changes']=$changes;$event['can_technical']=$rank>=2;
    if($rank>=2){$event['before']=audit_clean($before);$event['after']=audit_clean($after);}
    unset($event['before_data'],$event['after_data']);return $event;
}
