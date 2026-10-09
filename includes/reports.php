<?php
declare(strict_types=1);
require_once __DIR__ . '/report-storage.php';

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
    $stmt = $db->prepare('SELECT kind FROM report_photos WHERE report_id = ?'); $stmt->execute([$id]);
    $report['photos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $report;
}
function submit_product_report(PDO $db, ?array $user, string $owner, array $input, array $photos = [], ?callable $storage = null): array {
    $storage ??= 'report_object';
    $key = $input['submission_key'] ?? ''; $ean = $input['ean'] ?? ''; $description = $input['description'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $key) || !is_string($ean) || !valid_product_ean($ean)) throw new CatalogException('Revisa el EAN del reporte.', 422);
    $existing = $db->prepare('SELECT id, owner_key FROM product_reports WHERE submission_key = ?'); $existing->execute([$key]);
    if ($row = $existing->fetch()) {
        if (!hash_equals($row['owner_key'], $owner)) throw new CatalogException('Solicitud no válida.', 409);
        return report_detail($db, (int) $row['id'], $owner, false);
    }
    $fields = report_fields($input);
    if (is_string($description) && trim($description) === '' && catalog_product($db, $ean) === null) {
        if ($fields['name'] === '') throw new CatalogException('Escribe el nombre del producto.', 422);
        $description = 'Solicitud de alta de producto.';
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
        $stmt = $db->prepare('INSERT INTO product_reports (submission_key, owner_key, user_id, ean, description, proposed_data, original_data, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([$key, $owner, $user['id'] ?? null, $ean, trim($description), json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), json_encode($original, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), time()]);
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
    if ($decision === 'rejected' && mb_strlen(trim($note)) < 5) throw new CatalogException('Explica el motivo del rechazo.', 422);
    $db->beginTransaction();
    try {
        report_lock($db);
        $report = report_detail($db, $id, 'user:' . $user['id'], true);
        if ((int) $report['user_id'] === (int) $user['id']) throw new CatalogException('Otro moderador debe revisar tu reporte.', 403);
        if ($report['status'] !== 'pending') {
            if ($report['status'] !== $decision) throw new CatalogException('Este reporte ya fue revisado.', 409);
            $db->commit(); return $report;
        }
        if ($decision === 'approved') {
            $revision = filter_var($input['revision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($revision === false) throw new CatalogException('Revisa la versión actual del producto.', 422);
            edit_catalog_product($db, $report['ean'], report_fields($input), $revision, (int) $user['id']);
            if ($report['user_id'] !== null) $db->prepare('INSERT INTO report_rewards (report_id, user_id) VALUES (?, ?) ON CONFLICT(report_id) DO NOTHING')->execute([$id, $report['user_id']]);
        }
        $db->prepare('UPDATE product_reports SET status = ?, reviewed_by = ?, review_note = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = ?')->execute([$decision, $user['id'], trim($note), $id, 'pending']);
        $db->commit(); return report_detail($db, $id, 'user:' . $user['id'], true);
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
}
