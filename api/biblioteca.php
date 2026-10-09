<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function library_response(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
if (!current_user()) library_response(401, ['error' => 'Inicia sesión para usar Biblioteca.']);
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) library_response(405, ['error' => 'Solicitud no válida.']);
if ($method === 'POST' && !valid_csrf()) library_response(403, ['error' => 'La sesión venció. Recarga la página.']);
$userId = (int) current_user()['id'];
try {
    $db = database();
    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
        if (in_array($action, ['create', 'rename'], true) && (mb_strlen($name) < 1 || mb_strlen($name) > 80)) library_response(422, ['error' => 'Escribe un nombre de hasta 80 caracteres.']);
        if ($action === 'create') {
            $stmt = $db->prepare('INSERT INTO libraries (user_id, name) VALUES (?, ?)');
            $stmt->execute([$userId, $name]);
        } elseif (in_array($action, ['rename', 'delete', 'add_product', 'delete_product'], true)) {
            $id = filter_var($_POST['library_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) library_response(422, ['error' => 'Colección no válida.']);
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT id FROM libraries WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
            if (!$stmt->fetchColumn()) {
                $db->rollBack();
                library_response(404, ['error' => 'No encontramos esa colección.']);
            }
            if ($action === 'rename') {
                $db->prepare('UPDATE libraries SET name = ? WHERE id = ? AND user_id = ?')->execute([$name, $id, $userId]);
            } elseif ($action === 'delete') {
                $db->prepare('DELETE FROM library_products WHERE library_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM libraries WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
            } else {
                $ean = $_POST['ean'] ?? '';
                if (!is_string($ean) || !preg_match('/^(?:[0-9]{8}|[0-9]{13})$/D', $ean) || mb_strlen($name) > 120) {
                    $db->rollBack();
                    library_response(422, ['error' => 'Usa un EAN de 8 o 13 números y un nombre de hasta 120 caracteres.']);
                }
                if ($action === 'add_product') {
                    $sum = 0;
                    $weight = 3;
                    for ($i = strlen($ean) - 2; $i >= 0; $i--) {
                        $sum += (int) $ean[$i] * $weight;
                        $weight = $weight === 3 ? 1 : 3;
                    }
                    if ((10 - $sum % 10) % 10 !== (int) $ean[strlen($ean) - 1]) {
                        $db->rollBack();
                        library_response(422, ['error' => 'El EAN no es válido. Revisa el código.']);
                    }
                }
                if ($action === 'add_product') {
                    $db->prepare("INSERT INTO library_products (library_id, ean, name) VALUES (?, ?, ?) ON CONFLICT(library_id, ean) DO UPDATE SET name = CASE WHEN excluded.name != '' THEN excluded.name ELSE library_products.name END")->execute([$id, $ean, $name]);
                } else {
                    $db->prepare('DELETE FROM library_products WHERE library_id = ? AND ean = ?')->execute([$id, $ean]);
                }
            }
            $db->commit();
        } else {
            library_response(422, ['error' => 'Acción no válida.']);
        }
    }
    $stmt = $db->prepare('SELECT id, name FROM libraries WHERE user_id = ? ORDER BY id');
    $stmt->execute([$userId]);
    $libraries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare('SELECT p.library_id, p.ean, p.name FROM library_products p JOIN libraries l ON l.id = p.library_id WHERE l.user_id = ? ORDER BY p.id');
    $stmt->execute([$userId]);
    $products = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
        $products[$product['library_id']][] = ['ean' => $product['ean'], 'name' => $product['name']];
    }
    foreach ($libraries as &$library) {
        $library['products'] = $products[$library['id']] ?? [];
    }
    unset($library);
    library_response(200, ['libraries' => $libraries]);
} catch (PDOException $exception) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    library_response(is_unique_violation($exception) ? 409 : 503, ['error' => is_unique_violation($exception) ? 'Ya tienes una colección con ese nombre.' : 'No pudimos guardar los cambios. Intenta nuevamente.']);
}
