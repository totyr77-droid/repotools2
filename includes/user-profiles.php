<?php
declare(strict_types=1);
require_once __DIR__ . '/reports.php';
require_once __DIR__.'/audit.php';

function profile_user(PDO $db, int $id, bool $lock = false): array {
    $s = $db->prepare('SELECT id,name,email,company,role,created_at FROM users WHERE id=?'.($lock && $db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?' FOR UPDATE':'')); $s->execute([$id]);
    $user = $s->fetch();
    if (!$user) throw new CatalogException('No encontramos esa cuenta.', 404);
    return $user;
}
function profile_token(PDO $db, int $id): string {
    $s = $db->prepare('INSERT INTO user_profile_tokens(user_id,token) VALUES(?,?) ON CONFLICT(user_id) DO NOTHING'); $s->execute([$id, bin2hex(random_bytes(32))]);
    $s = $db->prepare('SELECT token FROM user_profile_tokens WHERE user_id=?'); $s->execute([$id]); return $s->fetchColumn();
}
function profile_edit_fields(array $actor,array $target): array {
    if($actor['role']==='manager')return ['name','company','email'];
    if($actor['role']==='administrador' && ($actor['id']==$target['id'] || in_array($target['role'],['usuario','moderador'],true)))return ['name','company'];
    return [];
}
function profile_update_information(PDO $db,int $actorId,int $targetId,array $input): void {
    $name=is_string($input['name']??null)?trim($input['name']):'';$company=is_string($input['company']??null)?trim($input['company']):'';
    if(mb_strlen($name)<2 || mb_strlen($name)>80 || mb_strlen($company)>100) throw new CatalogException('Ingresa un nombre de 2 a 80 caracteres y una empresa de hasta 100.',422);
    $db->beginTransaction();
    try {
        report_lock($db);$actor=profile_user($db,$actorId);$target=profile_user($db,$targetId,true);
        $allowed=profile_edit_fields($actor,$target);if(!$allowed)throw new CatalogException('Tu rango no permite editar esta cuenta.',403);
        if(($input['original_name']??null)!==$target['name'] || ($input['original_company']??null)!==$target['company'])throw new CatalogException('La información cambió. Abre la ficha nuevamente para editarla.',409);
        $before=['name'=>$target['name'],'company'=>$target['company']];$after=['name'=>$name,'company'=>$company];$changes=[];
        if(isset($input['email'])) {
            if(!in_array('email',$allowed,true))throw new CatalogException('Solo un manager puede modificar el correo de una cuenta.',403);
            $email=is_string($input['email'])?strtolower(trim($input['email'])):'';
            if(strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL))throw new CatalogException('Ingresa un correo válido.',422);
            if(($input['original_email']??null)!==$target['email'])throw new CatalogException('El correo cambió. Abre la ficha nuevamente para editarla.',409);
            $before['email']=$target['email'];$after['email']=$email;
        }
        foreach($after as $field=>$value)if($before[$field]!==$value)$changes[$field]=['before'=>$before[$field],'after'=>$value];
        if($changes){
            if(isset($changes['email'])) {
                $s=$db->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?) AND id<>?');$s->execute([$after['email'],$targetId]);
                if($s->fetchColumn()!==false)throw new CatalogException('Ese correo ya pertenece a otra cuenta.',422);
                $db->prepare('UPDATE users SET pending_email=? WHERE id=?')->execute([$after['email'],$targetId]);
                $db->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$targetId]);
            }
            $db->prepare('UPDATE users SET name=?,company=? WHERE id=?')->execute([$name,$company,$targetId]);
            audit_event($db,$actorId,'account_updated','profile:'.$targetId.':'.bin2hex(random_bytes(12)),['category'=>'users','target_user'=>$targetId],$before,$after);
            $after['changes']=$changes;$after['actor']=$actor['name'];
            $db->prepare('INSERT INTO user_profile_events(user_id,actor_id,before_data,after_data) VALUES(?,?,?,?)')->execute([$targetId,$actorId,json_encode($before,JSON_UNESCAPED_UNICODE),json_encode($after,JSON_UNESCAPED_UNICODE)]);
        }
        $db->commit();if(isset($changes['email'])){require_once __DIR__.'/email-verification.php';try{email_request($db,$targetId,$after['email']);}catch(Throwable $mailError){error_log('Repotools pending email delivery unavailable');}}
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();if($e instanceof PDOException && is_unique_violation($e))throw new CatalogException('Ese correo ya pertenece a otra cuenta.',422);throw $e;}
}
function profile_roles(array $actor, array $target): array {
    if ($actor['role'] === 'manager') return ['usuario','moderador','administrador','manager'];
    return [];
}
function profile_change_role(PDO $db, int $actorId, int $targetId, string $role): void {
    $db->beginTransaction();
    try {
        report_lock($db);
        $actor = profile_user($db, $actorId); $target = profile_user($db, $targetId);
        if (!in_array($role, profile_roles($actor, $target), true)) throw new CatalogException('Tu rango no permite realizar este cambio.', 403);
        if ($target['role'] === 'manager' && $role !== 'manager' && (int)$db->query("SELECT COUNT(*) FROM users WHERE role='manager'")->fetchColumn() <= 1) throw new CatalogException('Debe quedar al menos un manager en la aplicación.', 422);
        if ($target['role'] !== $role) {
            audit_event($db,$actorId,'role_changed','role:'.$targetId.':'.bin2hex(random_bytes(12)),['category'=>'roles','target_user'=>$targetId],['role'=>$target['role']],['role'=>$role]);
            $s = $db->prepare('UPDATE users SET role=? WHERE id=?'); $s->execute([$role,$targetId]);
            $s = $db->prepare('INSERT INTO user_role_events(user_id,actor_id,old_role,new_role) VALUES(?,?,?,?)'); $s->execute([$targetId,$actorId,$target['role'],$role]);
        }
        $db->commit();
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
}
function profile_block_reports(PDO $db, int $actorId, int $targetId, bool $blocked): void {
    $db->beginTransaction();
    try {
        report_lock($db);
        $actor=profile_user($db,$actorId);$target=profile_user($db,$targetId);
        if (!in_array($actor['role'],['administrador','manager'],true) || !report_can_manage_sender($actor['role'],$actorId,$target,$blocked)) throw new CatalogException('No puedes cambiar el permiso de reportes de esta cuenta.',403);
        if (report_is_blocked($db,$target)!==$blocked) {
            if ($blocked) $db->prepare('INSERT INTO report_blocks(user_id,blocked_by) VALUES (?,?)')->execute([$targetId,$actorId]);
            else $db->prepare('DELETE FROM report_blocks WHERE user_id=?')->execute([$targetId]);
            $db->prepare('INSERT INTO report_block_events(user_id,actor_id,action) VALUES (?,?,?)')->execute([$targetId,$actorId,$blocked?'blocked':'unblocked']);audit_event($db,$actorId,$blocked?'blocked':'unblocked','block:'.$targetId.':'.bin2hex(random_bytes(10)),['category'=>'roles','target_user'=>$targetId],[],['blocked'=>$blocked]);
        }
        $db->commit();
    } catch (Throwable $e) {if ($db->inTransaction()) $db->rollBack();throw $e;}
}
function profile_can_view(array $actor,array $target): bool {
    return $actor['role']==='manager'||($actor['role']==='administrador'&&((int)$actor['id']===(int)$target['id']||in_array($target['role'],['usuario','moderador'],true)));
}
function profile_detail(PDO $db, array $actor, array $target, int $page = 0, string $activity = ''): array {
    if(!profile_can_view($actor,$target))throw new CatalogException('Tu rango no permite consultar esta cuenta.',403);
    $id = (int)$target['id']; $stats = [];
    foreach (['scans'=>'SELECT COUNT(*) FROM scan_events WHERE user_id=?','unique_codes'=>'SELECT COUNT(DISTINCT code) FROM scan_events WHERE user_id=?','reports'=>'SELECT COUNT(*) FROM product_reports WHERE user_id=?','approved'=>'SELECT COUNT(*) FROM report_rewards WHERE user_id=?','libraries'=>'SELECT COUNT(*) FROM libraries WHERE user_id=?','pallets'=>'SELECT COUNT(*) FROM pallets WHERE owner_id=? AND released=0'] as $key=>$sql) {
        $s=$db->prepare($sql);$s->execute([$id]);$stats[$key]=(int)$s->fetchColumn();
    }
    if ($actor['role']==='manager') {
        $s=$db->prepare("SELECT submitter_ip FROM product_reports WHERE user_id=? AND submitter_ip<>'' ORDER BY id DESC LIMIT 1");$s->execute([$id]);$target['report_ip']=$s->fetchColumn() ?: '';
    }
    $directEdit=catalog_direct_edit_condition($db);
    $offset=$page*30;
    $staffScope=$actor['role']==='manager'?'':' AND e.actor_id='.(int)$actor['id'];
    $eventsSql="
        SELECT 'scan' AS kind, code AS detail, created_at, id AS event_id FROM scan_events WHERE user_id=?
        UNION ALL SELECT 'report', ean, created_at, id FROM product_reports WHERE user_id=?
        UNION ALL SELECT 'review', status, reviewed_at, id FROM product_reports WHERE reviewed_by=? AND reviewed_at IS NOT NULL
        UNION ALL SELECT 'edit',ean,created_at,id FROM product_edits WHERE user_id=? AND {$directEdit}
        UNION ALL SELECT 'pallet',code,created_at,id FROM pallet_admin_events WHERE user_id=?
        UNION ALL SELECT 'role',a.name || ': ' || u.name || ' · ' || e.old_role || ' → ' || e.new_role,e.created_at,e.id FROM user_role_events e JOIN users a ON a.id=e.actor_id JOIN users u ON u.id=e.user_id WHERE (e.user_id=? OR e.actor_id=?){$staffScope}
        UNION ALL SELECT 'block',a.name || ': ' || u.name || ' · ' || e.action,e.created_at,e.id FROM report_block_events e JOIN users a ON a.id=e.actor_id JOIN users u ON u.id=e.user_id WHERE (e.user_id=? OR e.actor_id=?){$staffScope}
        UNION ALL SELECT 'profile',e.after_data,e.created_at,e.id FROM user_profile_events e WHERE (e.user_id=? OR e.actor_id=?){$staffScope}
    ";
    $s=$db->prepare("SELECT DISTINCT kind FROM ({$eventsSql}) events ORDER BY kind");$s->execute(array_fill(0,11,$id));$available=$s->fetchAll(PDO::FETCH_COLUMN);
    if ($activity!=='' && !in_array($activity,['scan','report','review','edit','pallet','role','block','profile'],true)) throw new CatalogException('Actividad no válida.',422);
    $s=$db->prepare("SELECT * FROM ({$eventsSql}) events".($activity!==''?' WHERE kind=?':'')." ORDER BY created_at DESC,kind,event_id DESC LIMIT 31 OFFSET {$offset}");
    $params=array_fill(0,11,$id);if($activity!=='')$params[]=$activity;$s->execute($params);$events=$s->fetchAll();
    $blocked=report_is_blocked($db,$target);
    $editFields=profile_edit_fields($actor,$target);
    return ['user'=>$target,'is_current_user'=>(int)$actor['id']===(int)$target['id'],'stats'=>$stats,'roles'=>profile_roles($actor,$target),'can_edit_profile'=>(bool)$editFields,'editable_fields'=>$editFields,'reports_blocked'=>$blocked,'can_manage_reports'=>report_can_manage_sender($actor['role'],(int)$actor['id'],$target,!$blocked),'activity_filters'=>$available,'events'=>array_slice($events,0,30),'has_more'=>count($events)>30];
}
