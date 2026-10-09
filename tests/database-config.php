<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/database.php';
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$config = postgres_connection('postgresql://test_user:p%40ss%3Aword@ep-test-pooler.example.org/neondb?sslmode=require&channel_binding=require');
check($config['user'] === 'test_user', 'Usuario incorrecto.');
check($config['password'] === 'p@ss:word', 'Contraseña codificada incorrectamente.');
check(str_contains($config['dsn'], 'sslmode=require'), 'TLS debe ser obligatorio.');
check(!str_contains($config['dsn'], 'p@ss'), 'El DSN no debe contener contraseñas.');
check(str_contains(postgres_connection('postgres://user:password@example.org:5433/db')['dsn'], 'port=5433'), 'Puerto incorrecto.');
foreach (['https://example.org', 'postgresql://user@example.org/db', 'postgresql://user:password@example.org/db%3Bsslmode=disable'] as $url) {
    $rejected = false;
    try { postgres_connection($url); } catch (PDOException $error) { $rejected = true; }
    check($rejected, 'Debe rechazar configuraciones no válidas.');
}
putenv('DATABASE_URL=');
putenv('REPOTOOLS_REQUIRE_DATABASE_URL=1');
$rejected = false;
try { database(); } catch (PDOException $error) { $rejected = true; }
check($rejected, 'No debe crear una base temporal si falta la URL en Render.');
check(is_unique_violation(new PDOException('duplicate', 23505)), 'Debe reconocer duplicados PostgreSQL.');
$legacy = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$legacy->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$legacy->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')->execute(['Cuenta existente', 'legacy@example.com', password_hash('clave-anterior', PASSWORD_DEFAULT)]);
initialize_database($legacy);
initialize_database($legacy);
$user = $legacy->query('SELECT name, role, password_hash FROM users')->fetch(PDO::FETCH_ASSOC);
check($user['name'] === 'Cuenta existente' && $user['role'] === 'usuario' && password_verify('clave-anterior', $user['password_hash']), 'La actualización debe conservar las cuentas existentes en SQLite.');
echo "OK: URL PostgreSQL, credenciales codificadas, TLS y rechazo de almacenamiento temporal en Render.\n";
