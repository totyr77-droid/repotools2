<?php
require_once __DIR__ . '/../includes/auth.php';
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
$user = current_user();
if (!$user) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
$uid = (int)$user['id'];
$kind = $_SESSION['device_kind'];
$hash = hash('sha256', $_SESSION['device_token']);
$flow = $_GET['flow'] ?? '';
$sequence = filter_var($_GET['sequence'] ?? null, FILTER_VALIDATE_INT);
session_write_close();
if ($kind !== 'desktop' || !is_string($flow) || !preg_match('/^[a-f0-9-]{36}$/D', $flow) || $sequence === false) { http_response_code(404); exit; }
try {
    $db = database();
    $q = $db->prepare("SELECT p.photo FROM scan_relay_photos p JOIN scan_relays r ON r.user_id=p.user_id JOIN account_devices d ON d.user_id=p.user_id AND d.kind='desktop' JOIN users u ON u.id=p.user_id WHERE p.user_id=? AND p.flow=? AND p.sequence=? AND p.expires_at>? AND r.context_key=p.flow AND r.sequence=p.sequence AND r.desktop_hash=? AND r.expires_at>? AND d.token_hash=? AND d.expires_at>? AND d.auth_version=u.auth_version AND EXISTS(SELECT 1 FROM account_devices m WHERE m.user_id=r.user_id AND m.kind='mobile' AND m.token_hash=r.mobile_hash AND m.expires_at>? AND m.auth_version=u.auth_version)");
    $q->execute([$uid,$flow,$sequence,time(),$hash,time(),$hash,time(),time()]);
    $encoded = $q->fetchColumn();
    if (!$encoded) { http_response_code(404); exit; }
    $bytes = base64_decode($encoded, true);
    if ($bytes === false) { http_response_code(404); exit; }
    header('Content-Type: image/jpeg'); echo $bytes;
} catch (Throwable $e) { http_response_code(503); }
