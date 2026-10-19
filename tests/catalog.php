<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/catalog.php';
function check_catalog(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function test_ean(string $body): string {
    $sum = 0;
    for ($i = strlen($body) - 1, $weight = 3; $i >= 0; $i--, $weight = 4 - $weight) $sum += (int) $body[$i] * $weight;
    return $body . (string) ((10 - $sum % 10) % 10);
}
function expect_catalog_error(callable $operation, int $status): void {
    try { $operation(); } catch (CatalogException $error) { check_catalog($error->status === $status, 'Código de error incorrecto.'); return; }
    throw new RuntimeException('Se esperaba rechazar la operación.');
}
$testUrl = getenv('REPOTOOLS_TEST_DATABASE_URL');
if ($testUrl) {
    $config = postgres_connection($testUrl);
    $db = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::PGSQL_ATTR_DISABLE_PREPARES => true]);
} else $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
initialize_database($db);
$previousUsers = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
initialize_database($db);
check_catalog((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === $previousUsers, 'La migración debe conservar las cuentas.');
$suffix = bin2hex(random_bytes(5));
$roles = [];
foreach (['usuario', 'moderador', 'administrador'] as $role) {
    $email = $role . '-' . $suffix . '@example.invalid';
    $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')->execute([$role, $email, 'test-only', $role]);
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?'); $stmt->execute([$email]);
    $roles[$role] = (int) $stmt->fetchColumn();
}
check_catalog(!can_edit_catalog($db, null), 'Invitados no pueden editar.');
check_catalog(!can_edit_catalog($db, ['id' => $roles['usuario'], 'role' => 'administrador']), 'El servidor debe verificar el rol vigente, no confiar en la sesión.');
check_catalog(can_edit_catalog($db, ['id' => $roles['moderador']]), 'Moderadores pueden editar.');
check_catalog(can_edit_catalog($db, ['id' => $roles['administrador']]), 'Administradores pueden editar.');
$ean = test_ean('810000' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
$calls = 0;
$external = ['product_name_es' => 'Agua original', 'brands' => 'Marca', 'quantity' => '1 L', 'categories' => 'Bebidas', 'packaging' => 'Botella', 'image_front_small_url' => 'https://images.openfoodfacts.org/images/products/test.jpg'];
$fetch = function (string $code) use (&$calls, $external) { $calls++; return $external; };
$product = lookup_product($db, $ean, $fetch);
check_catalog($calls === 1 && $product['name'] === 'Agua original' && $product['source'] === 'openfoodfacts', 'Debe importar el producto desde la API.');
check_catalog(lookup_product($db, $ean, $fetch)['quantity'] === '1 L' && $calls === 1, 'La segunda consulta debe usar la base, sin llamar a la API.');
$stmt = $db->prepare('SELECT external_payload FROM products WHERE ean = ?'); $stmt->execute([$ean]);
check_catalog(json_decode($stmt->fetchColumn(), true)['brands'] === 'Marca', 'Debe conservar la información importada.');
$fields = ['name' => 'Agua corregida', 'quantity' => '500 ml', 'brand' => 'Marca', 'category' => 'Bebidas', 'packaging' => 'Botella'];
$edited = edit_catalog_product($db, $ean, $fields, 1, $roles['moderador']);
check_catalog($edited['revision'] === 2 && $edited['quantity'] === '500 ml' && $edited['source'] === 'openfoodfacts', 'La corrección debe conservar su procedencia.');
expect_catalog_error(fn() => edit_catalog_product($db, $ean, $fields, 1, $roles['administrador']), 409);
check_catalog(lookup_product($db, $ean, $fetch)['name'] === 'Agua corregida' && $calls === 1, 'La API no debe sobrescribir las correcciones.');
$stmt = $db->prepare('SELECT COUNT(*) FROM product_edits WHERE ean = ?'); $stmt->execute([$ean]);
check_catalog((int) $stmt->fetchColumn() === 1, 'Debe guardar el historial y evitar cambios con versiones antiguas.');
$missing = test_ean('820000' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
$missingCalls = 0;
$missingFetch = function () use (&$missingCalls) { $missingCalls++; return null; };
check_catalog(lookup_product($db, $missing, $missingFetch) === null && lookup_product($db, $missing, $missingFetch) === null && $missingCalls === 1, 'Productos ausentes deben evitar consultas repetidas por 24 horas.');
edit_catalog_product($db, $missing, $fields, 0, $roles['administrador']);
check_catalog(lookup_product($db, $missing, $missingFetch)['source'] === 'manual' && $missingCalls === 1, 'Debe permitir cargar manualmente un EAN ausente.');
$racing = test_ean('830000' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
$race = lookup_product($db, $racing, function () use ($db, $racing, $fields, $roles, $external) { edit_catalog_product($db, $racing, $fields, 0, $roles['administrador']); return $external; });
check_catalog($race['name'] === 'Agua corregida' && $race['source'] === 'manual', 'Una respuesta de API tardía no debe reemplazar una ficha manual.');
$retryEan = test_ean('840000' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
expect_catalog_error(fn() => lookup_product($db, $retryEan, fn() => throw new CatalogException('Timeout')), 503);
$safeImage = lookup_product($db, $retryEan, fn() => ['product_name' => 'Seguro', 'image_front_small_url' => 'https://example.invalid/tracker']);
check_catalog($safeImage['name'] === 'Seguro' && $safeImage['image'] === '', 'Fallos externos deben permitir reintentar y restringir las imágenes.');
expect_catalog_error(fn() => lookup_product($db, '7791234567890', $fetch), 422);
expect_catalog_error(fn() => edit_catalog_product($db, $ean, [...$fields, 'name' => ''], 2, $roles['administrador']), 422);
$db->exec('DELETE FROM product_api_requests');
for ($i = 0; $i < 15; $i++) $db->prepare('INSERT INTO product_api_requests (requested_at) VALUES (?)')->execute([time()]);
expect_catalog_error(fn() => lookup_product($db, '1234567890128', $fetch), 429);
check_catalog(lookup_product($db, $ean, $fetch)['name'] === 'Agua corregida', 'El límite externo no debe bloquear las fichas ya guardadas.');
$db->exec('DELETE FROM product_api_requests');
$photoEan = test_ean('850000' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
$ours = edit_catalog_product($db, $photoEan, $fields, 0, $roles['administrador']);
$photoCalls = 0;
$withImage = catalog_product_image($db, $ours, function () use (&$photoCalls, $external) { $photoCalls++; return $external; });
check_catalog($withImage['name'] === $ours['name'] && $withImage['quantity'] === $ours['quantity'] && $withImage['image'] === $external['image_front_small_url'], 'Foto externa con datos propios.');
catalog_product_image($db, $withImage, function () { throw new RuntimeException('No debe repetir consulta.'); });
check_catalog($photoCalls === 1, 'La foto externa se guarda para evitar consultas repetidas.');
$stmt = $db->prepare("INSERT INTO product_reports(submission_key,owner_key,user_id,ean,description,proposed_data,original_data,submitted_at) VALUES (?,?,?,?,?,?,?,?) RETURNING id");
$stmt->execute([$suffix, 'user:' . $roles['usuario'], $roles['usuario'], $photoEan, 'Foto de prueba', '{}', '{}', time()]); $reportId = (int) $stmt->fetchColumn(); $stmt->closeCursor();
$db->prepare('INSERT INTO report_photos(report_id,kind,object_key,mime,bytes) VALUES (?,?,?,?,?)')->execute([$reportId,'product','test-product-photo','image/jpeg',1]);
check_catalog(catalog_approved_photo($db, $photoEan) === null, 'Una foto pendiente no se publica.');
$db->prepare("UPDATE product_reports SET status = 'rejected' WHERE id = ?")->execute([$reportId]);
check_catalog(catalog_approved_photo($db, $photoEan) === null, 'Una foto rechazada no se publica.');
$db->prepare("UPDATE product_reports SET status = 'approved', reviewed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$reportId]);
$localImage = catalog_product_image($db, $withImage);
check_catalog($localImage['image_source'] === 'repotools' && $localImage['name'] === $ours['name'] && catalog_approved_photo($db, $photoEan) === 'test-product-photo', 'Foto aprobada tiene prioridad y conserva los datos locales.');
$currentRevision=catalog_product($db,$photoEan)['revision'];
edit_catalog_product($db,$photoEan,$fields,$currentRevision,$roles['moderador'],null,'direct-photo');
check_catalog(catalog_approved_photo($db,$photoEan)==='direct-photo','Foto directa reemplaza la aprobada antigua.');
expect_catalog_error(fn()=>edit_catalog_product($db,$photoEan,$fields,$currentRevision,$roles['moderador'],null,'stale-photo'),409);
check_catalog(catalog_approved_photo($db,$photoEan)==='direct-photo','Conflicto no reemplaza la foto.');
edit_catalog_product($db,$photoEan,$fields,$currentRevision+1,$roles['moderador']);
check_catalog(catalog_approved_photo($db,$photoEan)==='direct-photo','Editar texto conserva la foto.');
$failure = catalog_product_image($db, catalog_product($db, $retryEan), fn() => throw new CatalogException('Timeout'));
check_catalog($failure['name'] === 'Seguro', 'La API de fotos no puede ocultar la ficha local.');
echo "OK: catálogo, permisos, conflictos, foto aprobada prioritaria y foto externa sin reemplazar datos locales.\n";
