<?php
declare(strict_types=1);

function postgres_connection(string $url): array {
    $parts = parse_url($url);
    if (!$parts || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true)) {
        throw new PDOException('DATABASE_URL debe ser una dirección PostgreSQL.');
    }
    $host = $parts['host'] ?? '';
    $name = rawurldecode(ltrim($parts['path'] ?? '', '/'));
    $user = rawurldecode($parts['user'] ?? '');
    $password = rawurldecode($parts['pass'] ?? '');
    $port = $parts['port'] ?? 5432;
    if (!$host || !$name || !$user || !$password || $port < 1 || $port > 65535 || preg_match('/[;\s]/', $host . $name)) {
        throw new PDOException('DATABASE_URL no es válida.');
    }
    // TLS es obligatorio. Las credenciales nunca se incluyen en el DSN ni en mensajes.
    return ['dsn' => "pgsql:host={$host};port={$port};dbname={$name};sslmode=require;connect_timeout=15", 'user' => $user, 'password' => $password];
}

function database(): PDO {
    static $db = null;
    if ($db instanceof PDO) return $db;
    $url = trim(getenv('DATABASE_URL') ?: '');
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    if ($url !== '') {
        $connection = postgres_connection($url);
        // Consultas parametrizadas sin PREPARE persistente, compatibles con pooling.
        $options[PDO::PGSQL_ATTR_DISABLE_PREPARES] = true;
        $candidate = new PDO($connection['dsn'], $connection['user'], $connection['password'], $options);
    } else {
        if (getenv('REPOTOOLS_REQUIRE_DATABASE_URL') === '1') {
            throw new PDOException('Configura DATABASE_URL para guardar las cuentas de forma permanente.');
        }
        $path = getenv('REPOTOOLS_DB_PATH') ?: dirname(__DIR__) . '/data/accounts.sqlite';
        $candidate = new PDO('sqlite:' . $path, null, null, $options);
        $candidate->exec('PRAGMA busy_timeout = 5000');
    }
    initialize_database($candidate);
    $db = $candidate;
    return $db;
}

function initialize_database(PDO $db): void {
    $postgres = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    $db->beginTransaction();
    try {
        if ($postgres) $db->query('SELECT pg_advisory_xact_lock(72684621)');
        $db->exec('CREATE TABLE IF NOT EXISTS app_schema_migrations (version INTEGER PRIMARY KEY)');
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 1')->fetchColumn()) {
            $id = $postgres ? 'BIGSERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
            $ref = $postgres ? 'BIGINT' : 'INTEGER';
            $timestamp = $postgres ? 'TIMESTAMPTZ' : 'TEXT';
            $db->exec("CREATE TABLE IF NOT EXISTS users (id {$id}, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'usuario', created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            if (!$postgres) {
                $columns = $db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('role', $columns, true)) $db->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'usuario'");
            }
            $db->exec('CREATE TABLE IF NOT EXISTS login_attempts (key TEXT PRIMARY KEY, attempts INTEGER NOT NULL, started BIGINT NOT NULL)');
            $db->exec("CREATE TABLE IF NOT EXISTS scan_events (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), event_key TEXT NOT NULL, code TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, event_key))");
            $db->exec('CREATE INDEX IF NOT EXISTS scans_user_code ON scan_events(user_id, code)');
            $db->exec("CREATE TABLE IF NOT EXISTS libraries (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), name TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, name))");
            $db->exec("CREATE TABLE IF NOT EXISTS library_products (id {$id}, library_id {$ref} NOT NULL REFERENCES libraries(id), ean TEXT NOT NULL, name TEXT NOT NULL DEFAULT '', created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(library_id, ean))");
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (1)');
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}

function is_unique_violation(PDOException $error): bool {
    return in_array((string) $error->getCode(), ['23000', '23505'], true);
}
