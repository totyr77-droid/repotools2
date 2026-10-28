<?php
declare(strict_types=1);
// Presentation only: immutable audit snapshots remain untouched.
function audit_action_label(string $action): string {
    return ['plans_removed'=>'Planes quitados por manager','commercial_plan_saved'=>'Plan comercial configurado','support_action'=>'Acción desde acceso de manager','support_started'=>'Acceso como usuario iniciado','support_ended'=>'Acceso como usuario finalizado','session_started'=>'Sesión iniciada','session_ended'=>'Sesión cerrada','password_recovered'=>'Contraseña recuperada','transfer_commit'=>'Transferencia realizada','delete_many'=>'Palets eliminados','delete_labels'=>'Etiquetas eliminadas','transfer_preview'=>'Transferencia consultada','role_changed'=>'Rango actualizado','account_updated'=>'Datos de la cuenta actualizados','public_name_updated'=>'Nombre público actualizado','profile_updated'=>'Perfil actualizado','save_product'=>'Mercadería actualizada','deduct_product'=>'Mercadería retirada','delete_product'=>'Producto eliminado','delete'=>'Palet eliminado','claim'=>'Palet activado','generate'=>'Etiquetas generadas','delete_label'=>'Etiqueta eliminada','description'=>'Descripción actualizada','branch_assign'=>'Sucursal del palet modificada','branch_created'=>'Sucursal creada','branch_updated'=>'Datos de sucursal actualizados','branch_deleted'=>'Sucursal eliminada','branch_shared'=>'Palets compartidos','branch_share'=>'Palets compartidos','branch_revoke'=>'Enlace revocado','library_shared'=>'Colección compartida','stock_shared'=>'Stock compartido','share'=>'Enlace creado','revoke_share'=>'Enlace revocado','invite'=>'Invitación enviada','accept'=>'Invitación aceptada','decline'=>'Invitación rechazada','revoke'=>'Acceso revocado','leave'=>'Acceso abandonado','edited'=>'Ficha de producto actualizada','approved'=>'Reporte aprobado','rejected'=>'Reporte rechazado','blocked'=>'Reportes bloqueados','unblocked'=>'Reportes habilitados','account_created'=>'Cuenta creada','email_verified'=>'Correo verificado','referral_credited'=>'Referido acreditado','referral_decision'=>'Estado de referido actualizado','password_changed'=>'Contraseña actualizada','sessions_revoked'=>'Sesiones revocadas'][$action]??'Acción registrada: '.str_replace('_',' ',$action);
}
function audit_changes(array $before,array $after): array {
    $labels=['plans'=>'Planes','pro'=>'PRO','supervision'=>'Supervisión','active'=>'Acceso vigente','scope'=>'Alcance del plan','max_users'=>'Límite de usuarios','max_branches'=>'Límite de sucursales','max_pallets'=>'Límite de palets','max_products'=>'Límite de productos','role'=>'Rango','name'=>'Nombre','public_name'=>'Nombre público','company'=>'Empresa','description'=>'Descripción','branch_id'=>'Sucursal','owner_id'=>'Propietario','released'=>'Palet liberado','activated'=>'Palet activado','units'=>'Cantidad de unidades','ean'=>'Código EAN','expires'=>'Vencimiento','status'=>'Estado','blocked'=>'Reportes bloqueados','verified'=>'Correo verificado','state'=>'Estado','address'=>'Dirección','street'=>'Calle','number'=>'Altura','city'=>'Localidad','province'=>'Provincia','latitude'=>'Latitud','longitude'=>'Longitud','quantity'=>'Contenido neto','brand'=>'Marca','category'=>'Categoría','packaging'=>'Envase','note'=>'Nota','reason'=>'Motivo','collection'=>'Colección','amount'=>'Cantidad','kind'=>'Presentación','pack_size'=>'Unidades por presentación','inner_pack_size'=>'Unidades por paquete interno','http_status'=>'Respuesta del servidor'];
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
    $event=array_merge($event,audit_style($event));$event['action_label']=audit_action_label($event['action']);$event['changes']=$changes;$event['can_technical']=$rank>=2;
    if($rank>=2){$event['before']=audit_clean($before);$event['after']=audit_clean($after);}
    unset($event['before_data'],$event['after_data']);return $event;
}

function audit_style(array $event): array {
    $styles=[
        'save_product'=>['settings','info'],'deduct_product'=>['package-minus','warning'],'delete_product'=>['trash','danger'],'delete'=>['trash','danger'],'delete_many'=>['trash','danger'],
        'transfer_commit'=>['transfer','info'],'transfer_preview'=>['eye','neutral'],'claim'=>['pallet','success'],'generate'=>['qr','info'],'delete_label'=>['trash','danger'],'delete_labels'=>['trash','danger'],
        'approved'=>['check','success'],'rejected'=>['close','warning'],'edited'=>['settings','info'],'blocked'=>['lock','danger'],'unblocked'=>['check','success'],'role_changed'=>['crown','info'],
        'invite'=>['mail','info'],'accept'=>['check','success'],'decline'=>['close','warning'],'revoke'=>['lock','warning'],'leave'=>['logout','neutral'],'share'=>['share','info'],'revoke_share'=>['lock','warning'],
        'account_created'=>['user-plus','success'],'email_verified'=>['check','success'],'account_updated'=>['user','info'],'profile_updated'=>['user','info'],'public_name_updated'=>['user','info'],
        'support_started'=>['eye','info'],'support_ended'=>['logout','neutral'],'support_action'=>['settings','info'],
        'session_started'=>['user','info'],'session_ended'=>['logout','neutral'],'password_changed'=>['lock','info'],'password_recovered'=>['lock','warning'],'sessions_revoked'=>['lock','warning'],
        'branch_assign'=>['branch','info'],'branch_created'=>['branch','success'],'branch_updated'=>['branch','info'],'branch_deleted'=>['branch','danger'],'branch_shared'=>['share','info'],'branch_share'=>['share','info'],'branch_revoke'=>['lock','warning'],'description'=>['settings','info'],
        'library_shared'=>['share','info'],'stock_shared'=>['share','info'],'referral_credited'=>['user-plus','success'],'referral_decision'=>['users','info']
    ];
    [$icon,$tone]=$styles[$event['action']]??['info','neutral'];
    if(($event['result']??'confirmed')!=='confirmed'){$icon='lock';$tone='warning';}
    return ['action_icon'=>$icon,'action_tone'=>$tone];
}
function audit_navigation(PDO $db,array $event,int $viewer,int $rank): array {
    $links=[];
    foreach(['actor'=>'actor_id','target'=>'target_user']as$key=>$field){$id=(int)($event[$field]??0);if(!$id)continue;
        if($id===$viewer){$links[$key]=['label'=>'Mi cuenta','href'=>'cuenta.php'];continue;}
        if($rank<2)continue;
        $u=audit_q($db,'SELECT id,public_name,role FROM users WHERE id=?',[$id])->fetch();
        if($u&&($rank>=3||in_array($u['role'],['usuario','moderador'],true)))$links[$key]=['label'=>$u['public_name']?:'Usuario #'.$id,'href'=>'reportes.php?queue=1&view=users&user_id='.$id];
    }
    $codes=['pallet'=>[$event['pallet_code']??'',(int)($event['pallet_id']??0)]];
    if(!empty($event['transfer']))foreach(['source_code'=>'source','target_code'=>'destination']as$field=>$key)if(!empty($event['transfer'][$field]))$codes[$key]=[$event['transfer'][$field],0];
    foreach($codes as$key=>[$code,$id]){if(!preg_match('/^[0-9]{8}$/D',(string)$code))continue;
        $p=audit_q($db,'SELECT p.id,p.owner_id,p.branch_id FROM pallets p JOIN pallet_labels l ON l.pallet_id=p.id AND l.code=p.code WHERE p.code=? AND p.released=0'.($id?' AND p.id=?':''),$id?[$code,$id]:[$code])->fetch();
        if(!$p)continue;
        $allowed=$rank>=2||(int)$p['owner_id']===$viewer||(bool)audit_q($db,"SELECT 1 FROM pallet_access WHERE pallet_id=? AND user_id=? AND status='accepted'",[$p['id'],$viewer])->fetchColumn();
        if(!$allowed&&function_exists('team_role'))$allowed=team_role($db,$viewer,(int)($p['branch_id']??0))!=='';
        if($allowed)$links[$key]=['label'=>'Palet '.$code,'href'=>'stock.php?codigo='.$code];
    }
    return $links;
}
