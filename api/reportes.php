<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reports.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function report_response(int $status, array $body): void { http_response_code($status); echo json_encode($body, JSON_UNESCAPED_UNICODE); exit; }
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) report_response(405, ['error' => 'Solicitud no válida.']);
$user = current_user();
if ($method === 'POST' && !valid_csrf()) report_response(403, ['error' => 'La sesión venció. Recarga la página.']);
$csrf = csrf_token(); session_write_close();
try {
    $db = database(); $moderator = can_edit_catalog($db, $user);
    $owner = report_owner($user, $_POST['owner_token'] ?? $_SERVER['HTTP_X_REPORT_OWNER'] ?? '');
    if ($method === 'POST') {
        if (($_POST['action'] ?? '') === 'review') {
            if (!$user) throw new CatalogException('Inicia sesión para revisar reportes.', 401);
            $report = review_product_report($db, $user, (int) ($_POST['id'] ?? 0), $_POST);
        } else {
            $photos = [];
            foreach (['ean', 'product'] as $kind) if ($jpeg = prepare_report_photo($_FILES['photo_' . $kind] ?? [])) $photos[$kind] = $jpeg;
            $report = submit_product_report($db, $user, $owner, $_POST, $photos);
        }
        report_response(200, ['report' => $report]);
    }
    if (isset($_GET['id'])) {
        $report = report_detail($db, (int) $_GET['id'], $owner, $moderator);
        if (isset($_GET['photo'])) {
            $kind = $_GET['photo'];
            if (!is_string($kind) || !in_array($kind, ['ean', 'product'], true)) throw new CatalogException('Foto no disponible.', 404);
            $stmt = $db->prepare('SELECT object_key FROM report_photos WHERE report_id = ? AND kind = ?'); $stmt->execute([$report['id'], $kind]);
            $key = $stmt->fetchColumn(); if (!$key) throw new CatalogException('Foto no disponible.', 404);
            $bytes = report_object('GET', $key); header('Content-Type: image/jpeg'); echo $bytes; exit;
        }
        report_response(200, ['report' => $report, 'csrf' => $csrf, 'can_review' => $moderator]);
    }
    $queue = $moderator && ($_GET['queue'] ?? '') === '1';
    $stmt = $db->prepare('SELECT id, ean, description, status, created_at, review_note FROM product_reports WHERE ' . ($queue ? "status = 'pending'" : 'owner_key = ?') . ' ORDER BY id DESC LIMIT 50'); $stmt->execute($queue ? [] : [$owner]);
    report_response(200, ['reports' => $stmt->fetchAll(), 'csrf' => $csrf, 'can_review' => $moderator, 'photos_enabled' => report_storage_ready(), 'registered' => $user !== null]);
} catch (CatalogException $error) { report_response($error->status, ['error' => $error->getMessage()]); }
catch (Throwable $error) { error_log('Repotools reports error: ' . get_class($error) . ' code=' . $error->getCode()); report_response(503, ['error' => 'No pudimos acceder a los reportes. Intenta nuevamente.']); }
