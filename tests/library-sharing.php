<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/library-sharing.php';
function verify_share(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
function rejected_share(callable $task): void {
    try { $task(); } catch (DomainException $error) { return; }
    throw new RuntimeException('Debía rechazar esta operación.');
}
$url = getenv('REPOTOOLS_TEST_DATABASE_URL');
if ($url) {
    $config = postgres_connection($url);
    $db = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::PGSQL_ATTR_DISABLE_PREPARES => true]);
} else $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
initialize_database($db); initialize_database($db);
$suffix = bin2hex(random_bytes(5)); $users = [];
foreach (['sender', 'recipient', 'other'] as $name) {
    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?) RETURNING id');
    $stmt->execute([$name, $name . $suffix . '@example.invalid', 'test-only']); $users[] = (int) $stmt->fetchColumn(); $stmt->closeCursor();
}
$stmt = $db->prepare('INSERT INTO libraries (user_id, name) VALUES (?, ?) RETURNING id');
$stmt->execute([$users[0], 'Bebidas']); $source = (int) $stmt->fetchColumn(); $stmt->closeCursor();
rejected_share(fn() => library_share_create($db, $users[0], $source));
$insert = $db->prepare('INSERT INTO library_products (library_id, ean, name) VALUES (?, ?, ?)');
$insert->execute([$source, '96385074', 'Agua']);
$oneToken = library_share_create($db, $users[0], $source);
verify_share(count(library_share_preview($db, $oneToken)['collection']['products']) === 1, 'Permite compartir un solo producto.');
$insert->execute([$source, '9002490100070', 'Energizante']);
rejected_share(fn() => library_share_create($db, $users[1], $source));
$token = library_share_create($db, $users[0], $source);
verify_share(strlen($token) === 64 && library_share_preview($db, 'wrong') === null, 'Enlaces impredecibles y validados.');
verify_share(count(library_share_preview($db, $token)['collection']['products']) === 2, 'Vista previa completa.');
rejected_share(fn() => library_share_import($db, $users[0], $token));
$stmt->execute([$users[1], 'Bebidas']); $stmt->closeCursor();
$previousScans = (int) $db->query('SELECT COUNT(*) FROM scan_events')->fetchColumn();
$copy = library_share_import($db, $users[1], $token);
verify_share($db->query('SELECT name FROM libraries WHERE id = ' . $copy)->fetchColumn() === 'Bebidas (2)', 'No sobrescribe nombres existentes.');
verify_share((int) $db->query('SELECT COUNT(*) FROM library_products WHERE library_id = ' . $copy)->fetchColumn() === 2, 'Copia productos.');
rejected_share(fn() => library_share_import($db, $users[1], $token));
$db->prepare('UPDATE libraries SET name = ? WHERE id = ?')->execute(['Renombrada', $source]);
verify_share(library_share_create($db, $users[0], $source) === $token, 'El enlace conserva identidad al actualizar.');
rejected_share(fn() => library_share_import($db, $users[1], $token));
$other = library_share_import($db, $users[2], $token);
verify_share($other !== $copy, 'Cada usuario obtiene su propia copia.');
verify_share((int) $db->query('SELECT COUNT(*) FROM scan_events')->fetchColumn() === $previousScans, 'Importar no genera escaneos.');
echo "OK: migración repetible, permisos, mínimo de productos, enlace estable, copia independiente, nombres y duplicados.\n";

$beforeLibraries=(int)$db->query('SELECT COUNT(*) FROM libraries')->fetchColumn();
library_share_to_user($db,$users[0],$source,'recipient'.$suffix.'@example.invalid');
library_share_to_user($db,$users[0],$source,'RECIPIENT'.$suffix.'@example.invalid');
$q=$db->prepare("SELECT user_id,read_at FROM notifications WHERE type='library_invitation'");$q->execute();$notices=$q->fetchAll();
verify_share(count($notices)===1&&(int)$notices[0]['user_id']===$users[1]&&$notices[0]['read_at']===null,'targeted unread and idempotent notification');
verify_share((int)$db->query('SELECT COUNT(*) FROM libraries')->fetchColumn()===$beforeLibraries,'recipient must explicitly import');
rejected_share(fn()=>library_share_to_user($db,$users[1],$source,'other'.$suffix.'@example.invalid'));
rejected_share(fn()=>library_share_to_user($db,$users[0],$source,'sender'.$suffix.'@example.invalid'));
rejected_share(fn()=>library_share_to_user($db,$users[0],$source,'unknown@example.invalid'));
echo "OK: share by account email, sender ownership, unknown/self rejection, no auto import and deduplication.
";
