<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/catalog.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function product_response(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) product_response(405, ['error' => 'Solicitud no válida.']);
$user = current_user();
if ($method === 'POST' && !$user) product_response(401, ['error' => 'Inicia sesión para editar productos.']);
if ($method === 'POST' && !valid_csrf()) product_response(403, ['error' => 'La sesión venció. Recarga la página.']);
$ean = $method === 'GET' ? ($_GET['ean'] ?? '') : ($_POST['ean'] ?? '');
if (!is_string($ean) || !valid_product_ean($ean)) product_response(422, ['error' => 'El EAN no es válido.']);
$csrf = csrf_token();
session_write_close();
try {
    $db = database();
    if ($method === 'GET' && isset($_GET['photo'])) {
        if ($_GET['photo'] !== 'product' || !($key = catalog_approved_photo($db, $ean))) product_response(404, ['error' => 'Foto no disponible.']);
        require_once __DIR__ . '/../includes/report-storage.php';
        $bytes = report_object('GET', $key); header('Content-Type: image/jpeg'); header('X-Content-Type-Options: nosniff'); echo $bytes; exit;
    }
    $canEdit = can_edit_catalog($db, $user);
    if ($method === 'POST') {
        if (!$canEdit) product_response(403, ['error' => 'No tienes permiso para editar productos.']);
        $revision = filter_var($_POST['revision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($revision === false) product_response(422, ['error' => 'Revisa la versión del producto.']);
        $product = edit_catalog_product($db, $ean, $_POST, $revision, (int) $user['id']);
    } else {
        $product = lookup_product($db, $ean);
        if ($product && ($_GET['image'] ?? '') === '1') $product = catalog_product_image($db, $product);
    }
    product_response(200, ['product' => $product, 'can_edit' => $canEdit, 'csrf' => $canEdit ? $csrf : null]);
} catch (CatalogException $error) {
    product_response($error->status, ['error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Repotools catalog error: ' . get_class($error) . ' code=' . $error->getCode());
    product_response(503, ['error' => 'No pudimos cargar el producto. Intenta nuevamente.']);
}
