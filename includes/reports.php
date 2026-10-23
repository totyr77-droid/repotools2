<?php
declare(strict_types=1);
require_once __DIR__ . '/report-storage.php';
require_once __DIR__.'/audit.php';

// Forwarded IPs are diagnostic metadata, never the identity used for a block.
function report_client_ip(array $server): string {
    if (getenv('RENDER') === 'true') {
        foreach (explode(',', (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '')) as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return $ip;
        }
    }
    $ip = (string) ($server['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}
function report_is_blocked(PDO $db, ?array $user): bool {
    if (!$user) return false;
    $stmt = $db->prepare('SELECT user_id FROM report_blocks WHERE user_id = ?'); $stmt->execute([$user['id']]);
    return $stmt->fetchColumn() !== false;
}
function report_proposed_changes(?array $original, array $proposed): array {
    $changes = [];
    foreach (['brand','name','quantity','category','packaging'] as $field) {
        $before = trim((string)($original[$field] ?? '')); $after = trim((string)($proposed[$field] ?? ''));
        if ($before !== $after) $changes[] = ['field'=>$field,'before'=>$before,'after'=>$after];
    }
    return $changes;
}
function report_can_audit(PDO $db, ?array $user): bool {
    if (!$user) return false;
    $stmt = $db->prepare('SELECT role FROM users WHERE id = ?'); $stmt->execute([$user['id']]);
    return in_array($stmt->fetchColumn(), ['administrador', 'manager'], true);
}
function report_require_submission(PDO $db, ?array $user): void {
    if (report_is_blocked($db, $user)) throw new CatalogException('Tu cuenta tiene bloqueado el envío de reportes. Puedes seguir usando las demás funciones.', 403);
}
function report_can_manage_sender(string $actorRole, int $actorId, array $target, bool $blocking = true): bool {
    if(!in_array($actorRole,['administrador','manager'],true))return false;
    if ($actorRole === 'manager') return !empty($target['id']);
    $ranks = ['usuario' => 1, 'moderador' => 2, 'administrador' => 3, 'manager' => 4];
    if (!$blocking && !in_array($actorRole, ['administrador', 'manager'], true)) return false;
    return !empty($target['id']) && (int) $target['id'] !== $actorId && ($ranks[$actorRole] ?? 0) > ($ranks[$target['role']] ?? 4);
}
function report_is_manager(PDO $db, ?array $user): bool {
    if (!$user) return false;
    $stmt=$db->prepare('SELECT role FROM users WHERE id=?');$stmt->execute([$user['id']]);return $stmt->fetchColumn()==='manager';
}
function report_set_block(PDO $db, array $actor, int $id, bool $blocked): array {
    if (!can_edit_catalog($db, $actor)) throw new CatalogException('No tienes permiso para gestionar reportes.', 403);
    $db->beginTransaction();
    try {
        report_lock($db);
        $report = report_detail($db, $id, 'user:' . $actor['id'], true);
        $target = $report['submitter'];
        $stmt = $db->prepare('SELECT role FROM users WHERE id = ?'); $stmt->execute([$actor['id']]); $role = $stmt->fetchColumn();
        if (!report_can_manage_sender((string) $role, (int) $actor['id'], $target, $blocked)) throw new CatalogException('No puedes cambiar el permiso de reportes de esta cuenta.', 403);
        if (report_is_blocked($db, $target) !== $blocked) {
            if ($blocked) $db->prepare('INSERT INTO report_blocks(user_id, blocked_by) VALUES (?, ?)')->execute([$target['id'], $actor['id']]);
            else $db->prepare('DELETE FROM report_blocks WHERE user_id = ?')->execute([$target['id']]);
            $db->prepare('INSERT INTO report_block_events(user_id, actor_id, action) VALUES (?, ?, ?)')->execute([$target['id'], $actor['id'], $blocked ? 'blocked' : 'unblocked']);
        }
        audit_event($db,(int)$actor['id'],$blocked?'blocked':'unblocked','block:'.$id.':'.bin2hex(random_bytes(8)),['category'=>'roles','target_user'=>(int)$target['id']],[],['blocked'=>$blocked]);
        $db->commit(); return report_detail($db, $id, 'user:' . $actor['id'], true);
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
}

function report_owner(?array $user, $token): string {
    if ($user) return 'user:' . (int) $user['id'];
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) throw new CatalogException('Recarga la página para enviar el reporte.', 422);
    return 'guest:' . hash('sha256', $token);
}
function report_fields(array $input): array {
    $fields = [];
    foreach (['name' => 120, 'quantity' => 80, 'brand' => 160, 'category' => 240, 'packaging' => 240] as $key => $max) {
        $value = $input[$key] ?? '';
        if (!is_string($value) || mb_strlen(trim($value)) > $max) throw new CatalogException('Revisa los datos del producto.', 422);
        $fields[$key] = trim($value);
    }
    return $fields;
}
function report_lock(PDO $db): void {
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') $db->query('SELECT pg_advisory_xact_lock(72684623)');
    else $db->exec('DELETE FROM report_rewards WHERE report_id < 0');
}
function report_detail(PDO $db, int $id, string $owner, bool $moderator): array {
    $stmt = $db->prepare('SELECT id, owner_key, user_id, ean, description, proposed_data, original_data, status, review_note, created_at, reviewed_at FROM product_reports WHERE id = ?');
    $stmt->execute([$id]); $report = $stmt->fetch();
    if (!$report || (!$moderator && !hash_equals($report['owner_key'], $owner))) throw new CatalogException('Reporte no disponible.', 404);
    unset($report['owner_key']);
    $report['proposed'] = json_decode($report['proposed_data'], true);
    $report['original'] = json_decode($report['original_data'], true);
    unset($report['proposed_data'], $report['original_data']);
    $report['current'] = catalog_product($db, $report['ean']);
    $report['reference_image']='';$report['reference_image_source']='';
    if(catalog_approved_photo($db,$report['ean'])){$report['reference_image']='api/productos.php?ean='.rawurlencode($report['ean']).'&photo=product';$report['reference_image_source']='repotools';}
    elseif(!empty($report['current']['image'])){$report['reference_image']=$report['current']['image'];$report['reference_image_source']='openfoodfacts';}
    $stmt = $db->prepare('SELECT kind FROM report_photos WHERE report_id = ?'); $stmt->execute([$id]);
    $report['photos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($moderator) {
        $stmt = $db->prepare('SELECT id, name, email, company, role FROM users WHERE id = ?'); $stmt->execute([$report['user_id']]);
        $report['submitter'] = $stmt->fetch() ?: ['id' => null, 'name' => 'Invitado', 'email' => '', 'company' => '', 'role' => ''];
        $report['submitter']['blocked'] = report_is_blocked($db, $report['submitter']['id'] ? $report['submitter'] : null);
        $actorId = str_starts_with($owner, 'user:') ? (int) substr($owner, 5) : 0;
        $stmt = $db->prepare('SELECT role FROM users WHERE id = ?'); $stmt->execute([$actorId]); $actorRole = $stmt->fetchColumn();
        $target = $report['submitter'];
        $report['submitter']['can_manage'] = report_can_manage_sender((string) $actorRole, $actorId, $target, !$target['blocked']);
        if ($actorRole === 'manager') {
            $stmt = $db->prepare('SELECT submitter_ip FROM product_reports WHERE id = ?'); $stmt->execute([$id]);
            $report['submitter_ip'] = $stmt->fetchColumn();
        }
    }
    return $report;
}
function submit_product_report(PDO $db, ?array $user, string $owner, array $input, array $photos = [], ?callable $storage = null): array {
    $storage ??= 'report_object';
    report_require_submission($db, $user);
    $key = $input['submission_key'] ?? ''; $ean = $input['ean'] ?? ''; $description = $input['description'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $key) || !is_string($ean) || !valid_product_ean($ean)) throw new CatalogException('Revisa el EAN del reporte.', 422);
    $existing = $db->prepare('SELECT id, owner_key FROM product_reports WHERE submission_key = ?'); $existing->execute([$key]);
    if ($row = $existing->fetch()) {
        if (!hash_equals($row['owner_key'], $owner)) throw new CatalogException('Solicitud no válida.', 409);
        return report_detail($db, (int) $row['id'], $owner, false);
    }
    $fields = report_fields($input);
    if (is_string($description) && trim($description) === '') {
        $current = catalog_product($db, $ean);
        if ($current === null) {
            if ($fields['name'] === '') throw new CatalogException('Escribe el nombre del producto.', 422);
            $description = 'Solicitud de alta de producto.';
        } else {
            $changed = [];
            foreach (['name' => 'producto', 'brand' => 'marca', 'quantity' => 'contenido neto', 'category' => 'categoría', 'packaging' => 'envase'] as $field => $label) {
                if ($fields[$field] !== ($current[$field] ?? '')) $changed[] = $label;
            }
            if (!$changed && !$photos) throw new CatalogException('Cambia algún dato, agrega una foto o explica qué hay que revisar.', 422);
            $description = $changed ? 'Corrección propuesta: ' . implode(', ', $changed) . '.' : 'Revisar las fotos adjuntas del producto.';
        }
    }
    if (!is_string($description) || mb_strlen(trim($description)) < 5 || mb_strlen($description) > 1000) throw new CatalogException('Explica qué dato está mal (5 a 1000 caracteres).', 422);
    $objects = []; $saved = false;
    try {
        foreach (['ean', 'product'] as $kind) if (isset($photos[$kind])) {
            $object = 'reports/' . $key . '/' . bin2hex(random_bytes(12)) . '-' . $kind . '.jpg';
            $storage('PUT', $object, $photos[$kind]);
            $objects[$kind] = ['key' => $object, 'bytes' => strlen($photos[$kind])];
        }
        $db->beginTransaction(); report_lock($db);
        report_require_submission($db, $user);
        $existing->execute([$key]);
        if ($row = $existing->fetch()) {
            if (!hash_equals($row['owner_key'], $owner)) throw new CatalogException('Solicitud no válida.', 409);
            $db->commit(); return report_detail($db, (int) $row['id'], $owner, false);
        }
        $stmt = $db->prepare("SELECT id FROM product_reports WHERE owner_key = ? AND ean = ? AND status = 'pending'"); $stmt->execute([$owner, $ean]);
        if ($stmt->fetchColumn()) throw new CatalogException('Ya tienes un reporte pendiente para este EAN.', 409);
        $stmt = $db->prepare('SELECT COUNT(*) FROM product_reports WHERE owner_key = ? AND submitted_at > ?'); $stmt->execute([$owner, time() - 86400]);
        if ((int) $stmt->fetchColumn() >= ($user ? 10 : 3)) throw new CatalogException('Alcanzaste el límite diario de reportes.', 429);
        $original = catalog_product($db, $ean);
        $stmt = $db->prepare('INSERT INTO product_reports (submission_key, owner_key, user_id, ean, description, proposed_data, original_data, submitted_at, submitter_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([$key, $owner, $user['id'] ?? null, $ean, trim($description), json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), json_encode($original, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), time(), report_client_ip($_SERVER)]);
        $id = (int) $stmt->fetchColumn(); $stmt->closeCursor();
        foreach ($objects as $kind => $object) $db->prepare('INSERT INTO report_photos (report_id, kind, object_key, mime, bytes) VALUES (?, ?, ?, ?, ?)')->execute([$id, $kind, $object['key'], 'image/jpeg', $object['bytes']]);
        $db->commit(); $saved = true;
        return report_detail($db, $id, $owner, false);
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
    finally { if (!$saved) foreach ($objects as $object) { try { $storage('DELETE', $object['key']); } catch (Throwable $ignored) { error_log('Repotools: no se pudo limpiar una foto de reporte incompleto.'); } } }
}
function review_product_report(PDO $db, array $user, int $id, array $input): array {
    if (!can_edit_catalog($db, $user)) throw new CatalogException('No tienes permiso para revisar reportes.', 403);
    $decision = $input['decision'] ?? ''; $note = $input['review_note'] ?? '';
    if (!in_array($decision, ['approved', 'rejected'], true) || !is_string($note) || mb_strlen($note) > 1000) throw new CatalogException('Revisa la decisión.', 422);
    if ($decision === 'rejected' && trim($note) === '') $note = 'La petición no es apta para actualizar el producto.';
    $db->beginTransaction();
    try {
        report_lock($db);
        $report = report_detail($db, $id, 'user:' . $user['id'], true);
        if ((int) $report['user_id'] === (int) $user['id'] && !report_is_manager($db,$user)) throw new CatalogException('Otro moderador debe revisar tu reporte.', 403);
        if ($report['status'] !== 'pending') {
            if ($report['status'] !== $decision) throw new CatalogException('Este reporte ya fue revisado.', 409);
            $db->commit(); return $report;
        }
        if ($decision === 'approved') {
            $revision = filter_var($input['revision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($revision === false) throw new CatalogException('Revisa la versión actual del producto.', 422);
            $photo=$db->prepare("SELECT object_key FROM report_photos WHERE report_id=? AND kind='product'");$photo->execute([$id]);
            $reviewedProduct=edit_catalog_product($db, $report['ean'], report_fields($input), $revision, (int) $user['id'], $id, $photo->fetchColumn()?:null);
            if ($report['user_id'] !== null) $db->prepare('INSERT INTO report_rewards (report_id, user_id) VALUES (?, ?) ON CONFLICT(report_id) DO NOTHING')->execute([$id, $report['user_id']]);
        }
        $db->prepare('UPDATE product_reports SET status = ?, reviewed_by = ?, review_note = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = ?')->execute([$decision, $user['id'], trim($note), $id, 'pending']);
        if ($report['user_id'] !== null) {
            $title = $decision === 'approved' ? 'Tu reporte fue aprobado' : 'Tu reporte fue rechazado';
            $body = 'EAN ' . $report['ean'] . ($decision === 'approved' ? ' · Actualizamos el producto.' : ' · ' . trim($note));
            $db->prepare('INSERT INTO notifications (user_id, event_key, type, title, body, report_id) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT(event_key) DO NOTHING')->execute([$report['user_id'], 'report:' . $id . ':' . $decision, 'report_review', $title, $body, $id]);
        }
        audit_event($db,(int)$user['id'],$decision,'report:'.$id,['category'=>'roles','ean'=>$report['ean'],'target_user'=>$report['user_id']],['status'=>$report['status'],'product'=>$report['current']??[]],['status'=>$decision,'product'=>$reviewedProduct??$report['current']??[],'proposal'=>$report['proposed']??[],'note'=>trim($note)],'confirmed',null,1);
        $db->commit(); return report_detail($db, $id, 'user:' . $user['id'], true);
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
}

function report_resolve_reference_photo(PDO $db,array $report,?callable $fetcher=null): array {
    if(in_array('product',$report['photos'],true)||!empty($report['reference_image']))return $report;
    try {
        $existing=$report['current'];$product=$existing??lookup_product($db,$report['ean'],$fetcher);
        if($product){
            if($existing!==null||!empty($product['image']))$product=catalog_product_image($db,$product,$fetcher);
            $report['current']=$product;
            $report['reference_image']=$product['image']??'';
            $report['reference_image_source']=$product['image_source']??($report['reference_image']?'openfoodfacts':'');
        }
    }catch(Throwable $error){/* Missing/rate-limited external imagery must never prevent reviewing a ticket. */}
    return $report;
}
