<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/stock.php';
require_once __DIR__.'/../includes/achievements.php';
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
    $stockAlerts = stock_notify($db, $userId);
    if ($method === 'POST') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id || !in_array($_POST['action'] ?? '', ['read', 'read_all'], true)) notification_response(422, ['error' => 'Notificación no válida.']);
        $comparison = $_POST['action'] === 'read_all' ? '<=' : '=';
        $db->prepare("UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND id {$comparison} ? AND read_at IS NULL")->execute([$userId, $id]);
    }
    $stmt = $db->prepare('SELECT n.event_key, n.id, n.type, n.title, n.body, n.report_id, n.created_at, n.read_at, r.status AS report_status FROM notifications n LEFT JOIN product_reports r ON r.id = n.report_id WHERE n.user_id = ? ORDER BY n.id DESC LIMIT 40');
    $stmt->execute([$userId]); $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        if($item['type']==='stock_invitation'){
            if(preg_match('/^pallet-invite:(\d+):/',$item['event_key'],$match)){
                $p=stock_query($db,'SELECT p.code,u.id,u.name,u.public_name FROM pallets p JOIN users u ON u.id=p.owner_id WHERE p.id=?',[(int)$match[1]])->fetch();
                $item['invitation_code']=$p?$p['code']:null;
                $item['body']=$p?get_public_user_name($p).' te invita al palet '.$p['code'].'. Acepta desde Stock.':'Te invitaron a un palet. Consultá Stock.';
            }else $item['body']='Te invitaron a un palet. Consultá Stock.';
        }
        if (isset($stockAlerts[$item['event_key']])) $item=array_merge($item,$stockAlerts[$item['event_key']]);
        $item=achievement_notification($item);
        unset($item['event_key']);
        $item['created_at']=(new DateTimeImmutable($item['created_at'],new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
    }
    unset($item);
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
    $stmt->execute([$userId]);
    $unread = (int) $stmt->fetchColumn();
    $limit = (new DateTimeImmutable('today', new DateTimeZone('America/Buenos_Aires')))->modify('+' . stock_warning_days($db, $userId) . ' days')->format('Y-m-d');
    $expiryCount = (int) stock_query($db, "SELECT COUNT(*) FROM pallet_products b JOIN pallets p ON p.id = b.pallet_id WHERE p.released = 0 AND b.expires <> '' AND b.expires <= ? AND (p.owner_id = ? OR EXISTS(SELECT 1 FROM pallet_access a WHERE a.pallet_id = p.id AND a.user_id = ? AND a.status = 'accepted'))", [$limit, $userId, $userId])->fetchColumn();
    notification_response(200, ['notifications' => $items, 'unread' => $unread, 'has_expiring_stock' => $expiryCount > 0, 'expiring_stock_count' => $expiryCount]);
} catch (Throwable $error) {
    error_log('Repotools notifications error: ' . get_class($error));
    notification_response(503, ['error' => 'No pudimos cargar tus notificaciones.']);
}
