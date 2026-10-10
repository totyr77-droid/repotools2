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
    $id = $postgres ? 'BIGSERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $ref = $postgres ? 'BIGINT' : 'INTEGER';
    $timestamp = $postgres ? 'TIMESTAMPTZ' : 'TEXT';
    $db->beginTransaction();
    try {
        if ($postgres) $db->query('SELECT pg_advisory_xact_lock(72684621)');
        $db->exec('CREATE TABLE IF NOT EXISTS app_schema_migrations (version INTEGER PRIMARY KEY)');
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 1')->fetchColumn()) {
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
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 2')->fetchColumn()) {
            $db->exec("CREATE TABLE products (ean TEXT PRIMARY KEY, name TEXT NOT NULL DEFAULT '', brand TEXT NOT NULL DEFAULT '', quantity TEXT NOT NULL DEFAULT '', category TEXT NOT NULL DEFAULT '', packaging TEXT NOT NULL DEFAULT '', image TEXT NOT NULL DEFAULT '', source TEXT NOT NULL DEFAULT 'manual', source_url TEXT NOT NULL DEFAULT '', external_payload TEXT NOT NULL DEFAULT '{}', revision INTEGER NOT NULL DEFAULT 1, updated_by {$ref} REFERENCES users(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec("CREATE TABLE product_edits (id {$id}, ean TEXT NOT NULL REFERENCES products(ean), user_id {$ref} NOT NULL REFERENCES users(id), before_data TEXT NOT NULL, after_data TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('CREATE INDEX product_edits_ean ON product_edits(ean)');
            $db->exec("CREATE TABLE product_lookup_state (ean TEXT PRIMARY KEY, state TEXT NOT NULL, checked_at BIGINT NOT NULL, token TEXT NOT NULL)");
            $db->exec("CREATE TABLE product_api_requests (id {$id}, requested_at BIGINT NOT NULL)");
            $db->exec('CREATE INDEX product_api_requests_time ON product_api_requests(requested_at)');
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (2)');
        }
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 3')->fetchColumn()) {
            $db->exec("CREATE TABLE product_reports (id {$id}, submission_key TEXT NOT NULL UNIQUE, owner_key TEXT NOT NULL, user_id {$ref} REFERENCES users(id), ean TEXT NOT NULL, description TEXT NOT NULL, proposed_data TEXT NOT NULL, original_data TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'pending', reviewed_by {$ref} REFERENCES users(id), review_note TEXT NOT NULL DEFAULT '', created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, submitted_at BIGINT NOT NULL, reviewed_at {$timestamp})");
            $db->exec('CREATE INDEX product_reports_owner ON product_reports(owner_key, id)');
            $db->exec('CREATE INDEX product_reports_queue ON product_reports(status, id)');
            $db->exec('CREATE INDEX product_reports_user ON product_reports(user_id, status)');
            $db->exec("CREATE TABLE report_photos (id {$id}, report_id {$ref} NOT NULL REFERENCES product_reports(id), kind TEXT NOT NULL, object_key TEXT NOT NULL, mime TEXT NOT NULL, bytes INTEGER NOT NULL, UNIQUE(report_id, kind))");
            $db->exec("CREATE TABLE report_rewards (report_id {$ref} PRIMARY KEY REFERENCES product_reports(id), user_id {$ref} NOT NULL REFERENCES users(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('CREATE INDEX report_rewards_user ON report_rewards(user_id)');
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (3)');
        }
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 4')->fetchColumn()) {
            $db->exec("CREATE TABLE library_shares (token TEXT PRIMARY KEY, source_library_id {$ref} NOT NULL UNIQUE, owner_id {$ref} NOT NULL REFERENCES users(id), payload TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec("CREATE TABLE library_imports (user_id {$ref} NOT NULL REFERENCES users(id), source_library_id {$ref} NOT NULL, library_id {$ref} NOT NULL REFERENCES libraries(id), PRIMARY KEY(user_id, source_library_id))");
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (4)');
        }
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 5')->fetchColumn()) {
            $db->exec("CREATE TABLE notifications (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), event_key TEXT NOT NULL UNIQUE, type TEXT NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, report_id {$ref} REFERENCES product_reports(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, read_at {$timestamp})");
            $db->exec('CREATE INDEX notifications_user ON notifications(user_id, id)');
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (5)');
        }
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 6')->fetchColumn()) {
            $db->exec("CREATE TABLE pallets (id {$id}, code TEXT NOT NULL, owner_id {$ref} NOT NULL REFERENCES users(id), description TEXT NOT NULL DEFAULT '', prepared_until BIGINT NOT NULL, activated INTEGER NOT NULL DEFAULT 0, released INTEGER NOT NULL DEFAULT 0, revision INTEGER NOT NULL DEFAULT 1, updated_by {$ref} NOT NULL REFERENCES users(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec("CREATE TABLE pallet_labels (code TEXT PRIMARY KEY, reserved_by {$ref} REFERENCES users(id), reserved_until BIGINT NOT NULL DEFAULT 0, pallet_id {$ref} REFERENCES pallets(id))");
            $db->exec("CREATE TABLE pallet_products (id {$id}, pallet_id {$ref} NOT NULL REFERENCES pallets(id), ean TEXT NOT NULL, name TEXT NOT NULL DEFAULT '', expires TEXT NOT NULL DEFAULT '', units INTEGER NOT NULL, kind TEXT NOT NULL DEFAULT 'units', pack_size INTEGER NOT NULL DEFAULT 1, revision INTEGER NOT NULL DEFAULT 1, UNIQUE(pallet_id, ean, expires))");
            $db->exec("CREATE TABLE pallet_access (pallet_id {$ref} NOT NULL REFERENCES pallets(id), user_id {$ref} NOT NULL REFERENCES users(id), status TEXT NOT NULL DEFAULT 'pending', PRIMARY KEY(pallet_id,user_id))");
            $db->exec("CREATE TABLE stock_preferences (user_id {$ref} PRIMARY KEY REFERENCES users(id), expiry_days INTEGER NOT NULL DEFAULT 7)");
            $db->exec("CREATE TABLE stock_operations (user_id {$ref} NOT NULL REFERENCES users(id), operation_key TEXT NOT NULL, result TEXT NOT NULL, PRIMARY KEY(user_id,operation_key))");
            $db->exec('CREATE TABLE stock_mutex (id INTEGER PRIMARY KEY, revision INTEGER NOT NULL)');
            $db->exec('INSERT INTO stock_mutex(id,revision) VALUES(1,0)');
            $db->exec('CREATE INDEX pallets_owner ON pallets(owner_id,released)');
            $db->exec('CREATE INDEX pallet_expiries ON pallet_products(expires,pallet_id)');
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (6)');
        }
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 7')->fetchColumn()) {
            $db->exec("CREATE TABLE stock_product_references (ean TEXT NOT NULL, kind TEXT NOT NULL, pack_size INTEGER NOT NULL, updated_by {$ref} NOT NULL REFERENCES users(id), updated_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(ean,kind))");
            $db->exec("INSERT INTO stock_product_references(ean,kind,pack_size,updated_by) SELECT b.ean,b.kind,b.pack_size,p.updated_by FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id WHERE b.kind IN ('boxes','packs') ON CONFLICT(ean,kind) DO NOTHING");
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (7)');
        }
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 8')->fetchColumn()) {
            $db->exec("CREATE TABLE pallet_admin_events (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), pallet_id {$ref} REFERENCES pallets(id), code TEXT NOT NULL, action TEXT NOT NULL, details TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(8)');
        }
        if (!$db->query('SELECT version FROM app_schema_migrations WHERE version = 9')->fetchColumn()) {
            $db->exec("ALTER TABLE users ADD COLUMN company TEXT NOT NULL DEFAULT ''");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(9)');
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
