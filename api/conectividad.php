<?php
require_once __DIR__.'/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['error'=>'Solicitud no válida.']); exit; }
try {
    $user=current_user();
    database()->query('SELECT 1');
    session_write_close();
    echo json_encode(['reachable'=>true,'user_id'=>(int)($user['id']??0)]);
} catch (Throwable $error) {
    http_response_code(503); echo json_encode(['reachable'=>false]);
}
