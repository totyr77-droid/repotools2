<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'Usa POST.']);
if (!current_user()) respond(401, ['error' => 'Inicia sesión para registrar tus estadísticas.']);
if (!valid_csrf()) respond(403, ['error' => 'La sesión venció. Recarga la página.']);
$code = $_POST['code'] ?? '';
$event = $_POST['event'] ?? '';
if (!is_string($code) || !preg_match('/^(?:[0-9]{8}|[0-9]{13})$/D', $code) || !is_string($event) || !preg_match('/^[a-f0-9-]{36}$/D', $event)) {
    respond(422, ['error' => 'Escaneo no válido.']);
}
try {
    $db = database();
    $userId = (int) current_user()['id'];
    $stmt = $db->prepare('INSERT INTO scan_events (user_id, event_key, code) VALUES (?, ?, ?) ON CONFLICT(user_id, event_key) DO NOTHING');
    $stmt->execute([$userId, $event, $code]);
    $stmt = $db->prepare('SELECT COUNT(DISTINCT code) AS unique_codes, COUNT(*) AS total_scans FROM scan_events WHERE user_id = ?');
    $stmt->execute([$userId]);
    respond(200, $stmt->fetch(PDO::FETCH_ASSOC));
} catch (PDOException $exception) {
    respond(503, ['error' => 'No se pudo guardar el escaneo.']);
}
