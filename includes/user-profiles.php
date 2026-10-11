<?php
declare(strict_types=1);
require_once __DIR__ . '/reports.php';

function profile_user(PDO $db, int $id): array {
    $s = $db->prepare('SELECT id,name,email,company,role,created_at FROM users WHERE id=?'); $s->execute([$id]);
    $user = $s->fetch();
    if (!$user) throw new CatalogException('No encontramos esa cuenta.', 404);
    return $user;
}
function profile_token(PDO $db, int $id): string {
    $s = $db->prepare('INSERT INTO user_profile_tokens(user_id,token) VALUES(?,?) ON CONFLICT(user_id) DO NOTHING'); $s->execute([$id, bin2hex(random_bytes(32))]);
    $s = $db->prepare('SELECT token FROM user_profile_tokens WHERE user_id=?'); $s->execute([$id]); return $s->fetchColumn();
}
function profile_roles(array $actor, array $target): array {
    if ($actor['role'] === 'manager') return ['usuario','moderador','administrador','manager'];
    if ($actor['role'] === 'administrador' && $actor['id'] != $target['id'] && in_array($target['role'], ['usuario','moderador'], true)) return ['usuario','moderador'];
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
            $db->prepare('INSERT INTO report_block_events(user_id,actor_id,action) VALUES (?,?,?)')->execute([$targetId,$actorId,$blocked?'blocked':'unblocked']);
        }
        $db->commit();
    } catch (Throwable $e) {if ($db->inTransaction()) $db->rollBack();throw $e;}
}
function profile_detail(PDO $db, array $actor, array $target, int $page = 0): array {
    $id = (int)$target['id']; $stats = [];
    foreach (['scans'=>'SELECT COUNT(*) FROM scan_events WHERE user_id=?','unique_codes'=>'SELECT COUNT(DISTINCT code) FROM scan_events WHERE user_id=?','reports'=>'SELECT COUNT(*) FROM product_reports WHERE user_id=?','approved'=>'SELECT COUNT(*) FROM report_rewards WHERE user_id=?','libraries'=>'SELECT COUNT(*) FROM libraries WHERE user_id=?','pallets'=>'SELECT COUNT(*) FROM pallets WHERE owner_id=? AND released=0'] as $key=>$sql) {
        $s=$db->prepare($sql);$s->execute([$id]);$stats[$key]=(int)$s->fetchColumn();
    }
    if ($actor['role']==='manager') {
        $s=$db->prepare("SELECT submitter_ip FROM product_reports WHERE user_id=? AND submitter_ip<>'' ORDER BY id DESC LIMIT 1");$s->execute([$id]);$target['report_ip']=$s->fetchColumn() ?: '';
    }
    $directEdit=catalog_direct_edit_condition($db);
    $offset=$page*30;
    $s=$db->prepare("SELECT * FROM (
        SELECT 'scan' AS kind, code AS detail, created_at, id AS event_id FROM scan_events WHERE user_id=?
        UNION ALL SELECT 'report', ean, created_at, id FROM product_reports WHERE user_id=?
        UNION ALL SELECT 'review', status, reviewed_at, id FROM product_reports WHERE reviewed_by=? AND reviewed_at IS NOT NULL
        UNION ALL SELECT 'edit',ean,created_at,id FROM product_edits WHERE user_id=? AND {$directEdit}
        UNION ALL SELECT 'pallet',code,created_at,id FROM pallet_admin_events WHERE user_id=?
        UNION ALL SELECT 'role',a.name || ': ' || u.name || ' · ' || e.old_role || ' → ' || e.new_role,e.created_at,e.id FROM user_role_events e JOIN users a ON a.id=e.actor_id JOIN users u ON u.id=e.user_id WHERE e.user_id=? OR e.actor_id=?
        UNION ALL SELECT 'block',a.name || ': ' || u.name || ' · ' || e.action,e.created_at,e.id FROM report_block_events e JOIN users a ON a.id=e.actor_id JOIN users u ON u.id=e.user_id WHERE e.user_id=? OR e.actor_id=?
    ) events ORDER BY created_at DESC,kind,event_id DESC LIMIT 31 OFFSET {$offset}");
    $s->execute(array_fill(0,9,$id));$events=$s->fetchAll();
    $blocked=report_is_blocked($db,$target);
    return ['user'=>$target,'stats'=>$stats,'roles'=>profile_roles($actor,$target),'reports_blocked'=>$blocked,'can_manage_reports'=>report_can_manage_sender($actor['role'],(int)$actor['id'],$target,!$blocked),'events'=>array_slice($events,0,30),'has_more'=>count($events)>30];
}
