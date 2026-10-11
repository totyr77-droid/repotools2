<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/stock.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function notification_response(int $status, array $data): void {
    http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
if (!current_user()) notification_response(401, ['error' => 'Inicia sesión para ver tus notificaciones.']);
$userId = (int) current_user()['id'];
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) notification_response(405, ['error' => 'Solicitud no válida.']);
if ($method === 'POST' && !valid_csrf()) notification_response(403, ['error' => 'La sesión venció. Recarga la página.']);
session_write_close();
try {
    $db = database();
    stock_notify($db, $userId);
    if ($method === 'POST') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id || !in_array($_POST['action'] ?? '', ['read', 'read_all'], true)) notification_response(422, ['error' => 'Notificación no válida.']);
        $comparison = $_POST['action'] === 'read_all' ? '<=' : '=';
        $db->prepare("UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND id {$comparison} ? AND read_at IS NULL")->execute([$userId, $id]);
    }
    $stmt = $db->prepare('SELECT n.id, n.type, n.title, n.body, n.report_id, n.created_at, n.read_at, r.status AS report_status FROM notifications n LEFT JOIN product_reports r ON r.id = n.report_id WHERE n.user_id = ? ORDER BY n.id DESC LIMIT 40');
    $stmt->execute([$userId]); $items = $stmt->fetchAll();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
    $stmt->execute([$userId]);
    notification_response(200, ['notifications' => $items, 'unread' => (int) $stmt->fetchColumn()]);
} catch (Throwable $error) {
    error_log('Repotools notifications error: ' . get_class($error));
    notification_response(503, ['error' => 'No pudimos cargar tus notificaciones.']);
}
