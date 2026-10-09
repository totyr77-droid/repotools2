<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/reports.php';
function check_report(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function denied_report(callable $run, int $status): void { try { $run(); } catch (CatalogException $error) { check_report($error->status === $status, $error->getMessage()); return; } throw new RuntimeException('Operación no rechazada.'); }
$url = getenv('REPOTOOLS_TEST_DATABASE_URL');
if ($url) { $config = postgres_connection($url); $db = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::PGSQL_ATTR_DISABLE_PREPARES => true]); }
else $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
initialize_database($db);
$previousRewards = (int) $db->query('SELECT COUNT(*) FROM report_rewards')->fetchColumn();
$suffix = bin2hex(random_bytes(6)); $users = [];
foreach (['usuario', 'moderador', 'administrador'] as $role) { $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?) RETURNING id'); $stmt->execute([$role, $suffix . $role . '@example.invalid', 'test-only', $role]); $users[$role] = ['id' => (int) $stmt->fetchColumn()]; $stmt->closeCursor(); }
$user = $users['usuario']; $mod = $users['moderador']; $owner = report_owner($user, '');
$ean = '7791234567898'; $fields = ['name' => 'Agua revisada', 'quantity' => '500 ml', 'brand' => 'Marca', 'category' => 'Bebidas', 'packaging' => 'Botella'];
$db->prepare('INSERT INTO products (ean, name, quantity) VALUES (?, ?, ?) ON CONFLICT(ean) DO NOTHING')->execute([$ean, 'Agua', '1 L']);
$original = catalog_product($db, $ean);
$objects = []; $storage = static function ($method, $key, $body = '') use (&$objects) { if ($method === 'PUT') $objects[$key] = $body; if ($method === 'DELETE') unset($objects[$key]); return $objects[$key] ?? ''; };
$uuid = static fn() => sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
$input = [...$fields, 'ean' => $ean, 'description' => 'La presentación correcta es 500 ml.', 'submission_key' => $uuid()];
$report = submit_product_report($db, $user, $owner, $input, ['ean' => 'photo-ean', 'product' => 'photo-product'], $storage);
check_report(count($report['photos']) === 2 && count($objects) === 2, 'Guarda las dos evidencias.');
check_report(catalog_product($db, $ean) === $original, 'El reporte no debe modificar la ficha.');
check_report(submit_product_report($db, $user, $owner, $input, [], $storage)['id'] === $report['id'], 'Reintentos no deben duplicar tickets.');
denied_report(fn() => report_detail($db, (int) $report['id'], report_owner(null, str_repeat('a', 64)), false), 404);
denied_report(fn() => review_product_report($db, $user, (int) $report['id'], [...$fields, 'decision' => 'approved', 'revision' => $original['revision']]), 403);
denied_report(fn() => submit_product_report($db, $user, $owner, [...$input, 'submission_key' => $uuid()], ['ean' => 'discard'], $storage), 409);
check_report(count($objects) === 2, 'Limpia evidencias de solicitudes fallidas.');
denied_report(fn() => review_product_report($db, $mod, (int) $report['id'], [...$fields, 'decision' => 'approved', 'revision' => 99999]), 409);
check_report((int) $db->query('SELECT COUNT(*) FROM report_rewards')->fetchColumn() === $previousRewards, 'Un conflicto no puede dar puntos.');
$review = [...$fields, 'decision' => 'approved', 'revision' => $original['revision']];
if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
    $db->exec("CREATE TRIGGER test_reward_failure BEFORE INSERT ON report_rewards BEGIN SELECT RAISE(ABORT, 'test-only'); END");
    try { review_product_report($db, $mod, (int) $report['id'], $review); throw new RuntimeException('Debió fallar el punto.'); } catch (PDOException $expected) {}
    check_report(catalog_product($db, $ean) === $original && report_detail($db, (int) $report['id'], $owner, false)['status'] === 'pending', 'Un fallo al dar el punto revierte el producto y el ticket.');
    $db->exec('DROP TRIGGER test_reward_failure');
}
check_report(review_product_report($db, $mod, (int) $report['id'], $review)['status'] === 'approved', 'Acepta el reporte.');
check_report(catalog_product($db, $ean)['quantity'] === '500 ml', 'La aprobación actualiza el catálogo.');
review_product_report($db, $mod, (int) $report['id'], $review);
check_report((int) $db->query('SELECT COUNT(*) FROM report_rewards')->fetchColumn() === $previousRewards + 1, 'Una aprobación repetida solo da un punto.');
$guestOwner = report_owner(null, str_repeat('b', 64));
$guest = submit_product_report($db, null, $guestOwner, [...$input, 'submission_key' => $uuid()], [], $storage);
review_product_report($db, $mod, (int) $guest['id'], [...$review, 'revision' => catalog_product($db, $ean)['revision']]);
check_report((int) $db->query('SELECT COUNT(*) FROM report_rewards')->fetchColumn() === $previousRewards + 1, 'Invitados no participan en TOPs.');
$self = submit_product_report($db, $mod, report_owner($mod, ''), [...$input, 'submission_key' => $uuid()], [], $storage);
denied_report(fn() => review_product_report($db, $mod, (int) $self['id'], [...$review, 'revision' => catalog_product($db, $ean)['revision']]), 403);
denied_report(fn() => review_product_report($db, $users['administrador'], (int) $self['id'], ['decision' => 'rejected', 'review_note' => '']), 422);
review_product_report($db, $users['administrador'], (int) $self['id'], ['decision' => 'rejected', 'review_note' => 'La foto no demuestra el cambio.']);
check_report((int) $db->query('SELECT COUNT(*) FROM report_rewards')->fetchColumn() === $previousRewards + 1, 'Rechazar no da puntos.');
$db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute(['usuario', $mod['id']]);
denied_report(fn() => review_product_report($db, $mod, (int) $self['id'], $review), 403);
echo "OK: privacidad, evidencias, roles vigentes, conflictos, invitados, rechazo y puntos idempotentes.\n";
