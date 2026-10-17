<?php
declare(strict_types=1);
require_once __DIR__.'/public-names.php';
// Increment together with the next app_schema_migrations block.
const REPOTOOLS_SCHEMA_VERSION=34;

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
    // A complete installed schema needs no DDL or global migration lock.
    // Missing/older installations still take the original lock and recheck under it.
    try {$versions=$db->query('SELECT version FROM app_schema_migrations WHERE version BETWEEN 1 AND '.REPOTOOLS_SCHEMA_VERSION)->fetchAll(PDO::FETCH_COLUMN);if(count(array_unique(array_map('intval',$versions)))===REPOTOOLS_SCHEMA_VERSION)return;}catch(PDOException $e){if(!in_array((string)$e->getCode(),['42P01','HY000'],true)||($e->getCode()==='HY000'&&!str_contains($e->getMessage(),'no such table')))throw $e;}
    $db->beginTransaction();
    try {
        if ($postgres) $db->query('SELECT pg_advisory_xact_lock(72684621)');
        $db->exec('CREATE TABLE IF NOT EXISTS app_schema_migrations (version INTEGER PRIMARY KEY)');
        // Read under the existing transaction/advisory lock. One round trip,
        // preserving individual versions and safe upgrade/rollback behavior.
        $applied=array_fill_keys(array_map('intval',$db->query('SELECT version FROM app_schema_migrations')->fetchAll(PDO::FETCH_COLUMN)),true);
        if (!isset($applied[1])) {
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
        if (!isset($applied[2])) {
            $db->exec("CREATE TABLE products (ean TEXT PRIMARY KEY, name TEXT NOT NULL DEFAULT '', brand TEXT NOT NULL DEFAULT '', quantity TEXT NOT NULL DEFAULT '', category TEXT NOT NULL DEFAULT '', packaging TEXT NOT NULL DEFAULT '', image TEXT NOT NULL DEFAULT '', source TEXT NOT NULL DEFAULT 'manual', source_url TEXT NOT NULL DEFAULT '', external_payload TEXT NOT NULL DEFAULT '{}', revision INTEGER NOT NULL DEFAULT 1, updated_by {$ref} REFERENCES users(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec("CREATE TABLE product_edits (id {$id}, ean TEXT NOT NULL REFERENCES products(ean), user_id {$ref} NOT NULL REFERENCES users(id), before_data TEXT NOT NULL, after_data TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('CREATE INDEX product_edits_ean ON product_edits(ean)');
            $db->exec("CREATE TABLE product_lookup_state (ean TEXT PRIMARY KEY, state TEXT NOT NULL, checked_at BIGINT NOT NULL, token TEXT NOT NULL)");
            $db->exec("CREATE TABLE product_api_requests (id {$id}, requested_at BIGINT NOT NULL)");
            $db->exec('CREATE INDEX product_api_requests_time ON product_api_requests(requested_at)');
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (2)');
        }
        if (!isset($applied[3])) {
            $db->exec("CREATE TABLE product_reports (id {$id}, submission_key TEXT NOT NULL UNIQUE, owner_key TEXT NOT NULL, user_id {$ref} REFERENCES users(id), ean TEXT NOT NULL, description TEXT NOT NULL, proposed_data TEXT NOT NULL, original_data TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'pending', reviewed_by {$ref} REFERENCES users(id), review_note TEXT NOT NULL DEFAULT '', created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, submitted_at BIGINT NOT NULL, reviewed_at {$timestamp})");
            $db->exec('CREATE INDEX product_reports_owner ON product_reports(owner_key, id)');
            $db->exec('CREATE INDEX product_reports_queue ON product_reports(status, id)');
            $db->exec('CREATE INDEX product_reports_user ON product_reports(user_id, status)');
            $db->exec("CREATE TABLE report_photos (id {$id}, report_id {$ref} NOT NULL REFERENCES product_reports(id), kind TEXT NOT NULL, object_key TEXT NOT NULL, mime TEXT NOT NULL, bytes INTEGER NOT NULL, UNIQUE(report_id, kind))");
            $db->exec("CREATE TABLE report_rewards (report_id {$ref} PRIMARY KEY REFERENCES product_reports(id), user_id {$ref} NOT NULL REFERENCES users(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('CREATE INDEX report_rewards_user ON report_rewards(user_id)');
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (3)');
        }
        if (!isset($applied[4])) {
            $db->exec("CREATE TABLE library_shares (token TEXT PRIMARY KEY, source_library_id {$ref} NOT NULL UNIQUE, owner_id {$ref} NOT NULL REFERENCES users(id), payload TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec("CREATE TABLE library_imports (user_id {$ref} NOT NULL REFERENCES users(id), source_library_id {$ref} NOT NULL, library_id {$ref} NOT NULL REFERENCES libraries(id), PRIMARY KEY(user_id, source_library_id))");
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (4)');
        }
        if (!isset($applied[5])) {
            $db->exec("CREATE TABLE notifications (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), event_key TEXT NOT NULL UNIQUE, type TEXT NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, report_id {$ref} REFERENCES product_reports(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, read_at {$timestamp})");
            $db->exec('CREATE INDEX notifications_user ON notifications(user_id, id)');
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (5)');
        }
        if (!isset($applied[6])) {
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
        if (!isset($applied[7])) {
            $db->exec("CREATE TABLE stock_product_references (ean TEXT NOT NULL, kind TEXT NOT NULL, pack_size INTEGER NOT NULL, updated_by {$ref} NOT NULL REFERENCES users(id), updated_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(ean,kind))");
            $db->exec("INSERT INTO stock_product_references(ean,kind,pack_size,updated_by) SELECT b.ean,b.kind,b.pack_size,p.updated_by FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id WHERE b.kind IN ('boxes','packs') ON CONFLICT(ean,kind) DO NOTHING");
            $db->exec('INSERT INTO app_schema_migrations (version) VALUES (7)');
        }
        if (!isset($applied[8])) {
            $db->exec("CREATE TABLE pallet_admin_events (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), pallet_id {$ref} REFERENCES pallets(id), code TEXT NOT NULL, action TEXT NOT NULL, details TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(8)');
        }
        if (!isset($applied[9])) {
            $db->exec("ALTER TABLE users ADD COLUMN company TEXT NOT NULL DEFAULT ''");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(9)');
        }
        if (!isset($applied[10])) {
            $db->exec("ALTER TABLE product_reports ADD COLUMN submitter_ip TEXT NOT NULL DEFAULT ''");
            $db->exec("CREATE TABLE report_blocks (user_id {$ref} PRIMARY KEY REFERENCES users(id), blocked_by {$ref} NOT NULL REFERENCES users(id), created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec("CREATE TABLE report_block_events (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), actor_id {$ref} NOT NULL REFERENCES users(id), action TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(10)');
        }
        if (!isset($applied[11])) {
            $db->exec("CREATE TABLE user_profile_tokens (user_id {$ref} PRIMARY KEY REFERENCES users(id), token TEXT NOT NULL UNIQUE)");
            $db->exec("CREATE TABLE user_role_events (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), actor_id {$ref} NOT NULL REFERENCES users(id), old_role TEXT NOT NULL, new_role TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(11)');
        }
        if (!isset($applied[12])) {
            $db->exec("ALTER TABLE users ADD COLUMN handedness TEXT NOT NULL DEFAULT ''");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(12)');
        }
        if (!isset($applied[13])) {
            $db->exec("CREATE TABLE app_sessions (id_hash TEXT PRIMARY KEY, payload TEXT NOT NULL DEFAULT '', expires_at BIGINT NOT NULL, lock_token TEXT NOT NULL DEFAULT '', locked_until BIGINT NOT NULL DEFAULT 0)");
            $db->exec('CREATE INDEX app_sessions_expiry ON app_sessions(expires_at)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(13)');
        }
        if (!isset($applied[14])) {
            $db->exec('ALTER TABLE users ADD COLUMN auth_version INTEGER NOT NULL DEFAULT 1');
            $db->exec("CREATE TABLE password_resets (token_hash TEXT PRIMARY KEY, user_id {$ref} NOT NULL REFERENCES users(id), expires_at BIGINT NOT NULL)");
            $db->exec('CREATE INDEX password_resets_user ON password_resets(user_id)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(14)');
        }
        if (!isset($applied[15])) {
            $db->exec("CREATE TABLE user_profile_events (id {$id}, user_id {$ref} NOT NULL REFERENCES users(id), actor_id {$ref} NOT NULL REFERENCES users(id), before_data TEXT NOT NULL, after_data TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('CREATE INDEX user_profile_events_user ON user_profile_events(user_id,created_at)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(15)');
        }
        if (!isset($applied[16])) {
            $db->exec("CREATE TABLE stock_branches (id {$id}, owner_id {$ref} NOT NULL REFERENCES users(id), name TEXT NOT NULL, address TEXT NOT NULL, locality TEXT NOT NULL, province TEXT NOT NULL, description TEXT NOT NULL DEFAULT '', revision INTEGER NOT NULL DEFAULT 1)");
            $db->exec("ALTER TABLE pallets ADD COLUMN branch_id {$ref} REFERENCES stock_branches(id)");
            $db->exec('CREATE INDEX stock_branches_owner ON stock_branches(owner_id,id)');
            $db->exec('CREATE INDEX pallets_branch ON pallets(branch_id,released)');
            $db->exec("CREATE TABLE stock_shares (id {$id}, owner_id {$ref} NOT NULL REFERENCES users(id), token TEXT NOT NULL UNIQUE, branch_id {$ref} REFERENCES stock_branches(id), title TEXT NOT NULL, active INTEGER NOT NULL DEFAULT 1, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec("CREATE TABLE stock_share_pallets (share_id {$ref} NOT NULL REFERENCES stock_shares(id), pallet_id {$ref} NOT NULL REFERENCES pallets(id), PRIMARY KEY(share_id,pallet_id))");
            $db->exec('CREATE INDEX stock_shares_owner ON stock_shares(owner_id,active)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(16)');
        }
        if (!isset($applied[17])) {
            $db->exec('ALTER TABLE stock_branches ADD COLUMN latitude DOUBLE PRECISION');
            $db->exec('ALTER TABLE stock_branches ADD COLUMN longitude DOUBLE PRECISION');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(17)');
        }
        if (!isset($applied[18])) {
            $db->exec("CREATE TABLE user_branch_preferences (user_id {$ref} NOT NULL REFERENCES users(id) ON DELETE CASCADE, branch_id {$ref} NOT NULL REFERENCES stock_branches(id) ON DELETE CASCADE, favorite INTEGER NOT NULL DEFAULT 0, last_used BIGINT NOT NULL DEFAULT 0, use_count INTEGER NOT NULL DEFAULT 0, PRIMARY KEY(user_id,branch_id))");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(18)');
        }
        if (!isset($applied[19])) {
            $db->exec("CREATE TABLE branch_events (id {$id}, actor_id {$ref} NOT NULL REFERENCES users(id), branch_id {$ref}, branch_name TEXT NOT NULL, action TEXT NOT NULL, before_data TEXT NOT NULL, after_data TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('CREATE INDEX branch_events_date ON branch_events(created_at)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(19)');
        }
        if (!isset($applied[20])) {
            $db->exec("CREATE TABLE stock_share_exclusions (share_id {$ref} NOT NULL REFERENCES stock_shares(id) ON DELETE CASCADE, pallet_id {$ref} NOT NULL REFERENCES pallets(id) ON DELETE CASCADE, PRIMARY KEY(share_id,pallet_id))");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(20)');
        }
        if (!isset($applied[21])) {
            $db->exec("CREATE TABLE account_devices (user_id {$ref} NOT NULL REFERENCES users(id) ON DELETE CASCADE, kind TEXT NOT NULL, token_hash TEXT NOT NULL, auth_version INTEGER NOT NULL, expires_at BIGINT NOT NULL, PRIMARY KEY(user_id,kind))");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(21)');
        }
        if (!isset($applied[22])) {
            $db->exec("CREATE TABLE scan_relays (user_id {$ref} PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE, desktop_hash TEXT NOT NULL, mobile_hash TEXT NOT NULL DEFAULT '', context_key TEXT NOT NULL DEFAULT '', mode TEXT NOT NULL, context TEXT NOT NULL, ready INTEGER NOT NULL DEFAULT 0, code TEXT NOT NULL DEFAULT '', sequence INTEGER NOT NULL DEFAULT 0, expires_at BIGINT NOT NULL)");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(22)');
        }
        if (!isset($applied[23])) {
            $db->exec("CREATE TABLE scan_relay_photos (user_id {$ref} PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE, flow TEXT NOT NULL, sequence INTEGER NOT NULL, kind TEXT NOT NULL, photo TEXT NOT NULL, expires_at BIGINT NOT NULL)");
            $db->exec('CREATE INDEX scan_relay_photos_expiry ON scan_relay_photos(expires_at)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(23)');
        }
        if (!isset($applied[24])) {
            $columns=$postgres ? $db->query("SELECT column_name FROM information_schema.columns WHERE table_schema=current_schema() AND table_name='users'")->fetchAll(PDO::FETCH_COLUMN) : array_column($db->query('PRAGMA table_info(users)')->fetchAll(),'name');
            if (!in_array('public_name',$columns,true)) $db->exec("ALTER TABLE users ADD COLUMN public_name TEXT NOT NULL DEFAULT ''");
            backfill_public_user_names($db);
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(24)');
        }
        if (!isset($applied[25])) {
            $columns=$postgres ? $db->query("SELECT column_name FROM information_schema.columns WHERE table_schema=current_schema() AND table_name='users'")->fetchAll(PDO::FETCH_COLUMN) : array_column($db->query('PRAGMA table_info(users)')->fetchAll(),'name');
            foreach (['sound_enabled','vibration_enabled'] as $column) {
                if (!in_array($column,$columns,true)) $db->exec("ALTER TABLE users ADD COLUMN {$column} INTEGER NOT NULL DEFAULT 1 CHECK ({$column} IN (0,1))");
            }
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(25)');
        }
        if (!isset($applied[26])) {
            // Existing user-first indexes do not cover date-range rankings.
            $db->exec('CREATE INDEX scans_period_user_code ON scan_events(created_at,user_id,code)');
            $db->exec('CREATE INDEX rewards_period_user ON report_rewards(created_at,user_id)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(26)');
        }
        if (!isset($applied[27])) {
            $db->exec("CREATE TABLE pallet_activity (id {$id}, user_id {$ref} REFERENCES users(id) ON DELETE SET NULL, pallet_id {$ref} REFERENCES pallets(id) ON DELETE SET NULL, product_id {$ref} REFERENCES pallet_products(id) ON DELETE SET NULL, action TEXT NOT NULL, metadata TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('CREATE INDEX pallet_activity_recent ON pallet_activity(pallet_id,id)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(27)');
        }
        if (!isset($applied[28])) {
            $db->exec("CREATE TABLE public_link_aliases (code TEXT PRIMARY KEY CHECK(length(code)=8), kind TEXT NOT NULL CHECK(kind IN ('p','s','b')), target TEXT NOT NULL, UNIQUE(kind,target))");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(28)');
        }
        if (!isset($applied[29])) {
            $db->exec("CREATE TABLE audit_events(event_id TEXT PRIMARY KEY,actor_id BIGINT NOT NULL,actor_name TEXT NOT NULL,actor_role TEXT NOT NULL,actor_rank INTEGER NOT NULL,action TEXT NOT NULL,category TEXT NOT NULL,result TEXT NOT NULL,occurred_at TEXT NOT NULL,synced_at TEXT NOT NULL,operation_id TEXT NOT NULL,branch_id BIGINT,pallet_id BIGINT,pallet_code TEXT NOT NULL DEFAULT '',ean TEXT NOT NULL DEFAULT '',target_user BIGINT,before_data TEXT NOT NULL,after_data TEXT NOT NULL)");
            $db->exec('CREATE INDEX audit_recent ON audit_events(synced_at,event_id)');
            $db->exec('CREATE INDEX audit_actor_date ON audit_events(actor_id,occurred_at)');
            $db->exec('CREATE INDEX audit_pallet_date ON audit_events(pallet_code,occurred_at)');
            $db->exec('CREATE INDEX audit_branch_date ON audit_events(branch_id,occurred_at)');
            $db->exec('CREATE INDEX audit_ean_date ON audit_events(ean,occurred_at)');
            $db->exec('CREATE TABLE ranking_daily(kind TEXT NOT NULL,subject_id BIGINT NOT NULL,day TEXT NOT NULL,value BIGINT NOT NULL,PRIMARY KEY(kind,subject_id,day))');
            $db->exec('CREATE INDEX ranking_day_kind ON ranking_daily(kind,day,subject_id)');
            $db->exec('ALTER TABLE users ADD COLUMN email_verified_at BIGINT');
            $db->exec('ALTER TABLE users ADD COLUMN pending_email TEXT');
            $db->exec('CREATE TABLE email_verifications(token_hash TEXT PRIMARY KEY,user_id BIGINT NOT NULL REFERENCES users(id),address TEXT NOT NULL,expires BIGINT NOT NULL,used_at BIGINT,created BIGINT NOT NULL)');
            $db->exec('CREATE INDEX email_verification_user ON email_verifications(user_id,created)');
            $db->exec('CREATE TABLE email_delivery_limits(user_id BIGINT PRIMARY KEY,last_sent BIGINT NOT NULL,window_start BIGINT NOT NULL,attempts INTEGER NOT NULL)');
            require_once __DIR__.'/referrals.php';referral_schema($db,true);$db->exec('CREATE INDEX IF NOT EXISTS referrals_verified_period ON referrals(state,email_verified_at,referrer_id)');$db->exec('CREATE INDEX IF NOT EXISTS referrals_owner_status ON referrals(referrer_id,state,email_verified_at)');$db->exec('CREATE INDEX audit_category_date ON audit_events(category,occurred_at)');
            // Legacy logs remain inspectable; their historical rank was not captured.
            $sources=[
                ['product_edits','e.user_id','e.ean','e.created_at',"'edited'",'movements'],
                ['pallet_admin_events','e.user_id','e.code','e.created_at','e.action','movements'],
                ['user_role_events','e.actor_id',"''",'e.created_at',"'role_changed'",'roles'],
                ['user_profile_events','e.actor_id',"''",'e.created_at',"'account_updated'",'users'],
                ['branch_events','e.actor_id',"''",'e.created_at','e.action','movements'],
                ['report_block_events','e.actor_id',"''",'e.created_at','e.action','roles']
            ];
            foreach($sources as [$table,$actor,$target,$date,$action,$category])$db->exec("INSERT INTO audit_events(event_id,actor_id,actor_name,actor_role,actor_rank,action,category,result,occurred_at,synced_at,operation_id,pallet_code,before_data,after_data) SELECT 'legacy:{$table}:'||e.id,COALESCE({$actor},0),COALESCE(u.public_name,'Usuario eliminado'),'No registrado',3,{$action},'{$category}','confirmed',CAST({$date} AS TEXT),CAST({$date} AS TEXT),'legacy:{$table}:'||e.id,{$target},'{}','{\"legacy\":true,\"historical_role_unknown\":true}' FROM {$table} e LEFT JOIN users u ON u.id={$actor} ON CONFLICT(event_id) DO NOTHING");

            require_once __DIR__.'/audit.php';
            foreach($sources as [$table]){foreach(array_chunk($db->query('SELECT * FROM '.$table)->fetchAll(),40) as $batch){$values=[];foreach($batch as $legacy){$before=json_decode($legacy['before_data']??'{}',true)??[];$after=json_decode($legacy['after_data']??$legacy['details']??'{}',true)??[];if(isset($legacy['old_role'])){$before=['role'=>$legacy['old_role']];$after=['role'=>$legacy['new_role']];}$after['historical_role_unknown']=true;$values[]=['legacy:'.$table.':'.$legacy['id'],json_encode(audit_clean($before)),json_encode(audit_clean($after)),$legacy['ean']??'',$legacy['user_id']??null,(new DateTimeImmutable($legacy['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];}$sets=[];$params=[];foreach(['before_data','after_data','ean','target_user','occurred_at','synced_at'] as $index=>$column){$case=$column.'=CASE event_id';foreach($values as $row){$case.=' WHEN ? THEN ?';$params[]=$row[0];$params[]=$row[min($index+1,5)];}$sets[]=$case.' ELSE '.$column.' END';}$ids=array_column($values,0);$q=$db->prepare('UPDATE audit_events SET '.implode(',',$sets).' WHERE event_id IN ('.implode(',',array_fill(0,count($ids),'?')).')');$q->execute([...$params,...$ids]);}}
            $db->exec("UPDATE audit_events SET pallet_code='' WHERE event_id LIKE 'legacy:product_edits:%'");
            $db->exec("INSERT INTO audit_events(event_id,actor_id,actor_name,actor_role,actor_rank,action,category,result,occurred_at,synced_at,operation_id,ean,before_data,after_data) SELECT 'legacy:review:'||r.id,COALESCE(r.reviewed_by,0),COALESCE(u.public_name,'Usuario eliminado'),'No registrado',3,r.status,'roles','confirmed',CAST(r.reviewed_at AS TEXT),CAST(r.reviewed_at AS TEXT),'legacy:review:'||r.id,r.ean,'{}','{}' FROM product_reports r LEFT JOIN users u ON u.id=r.reviewed_by WHERE r.reviewed_at IS NOT NULL ON CONFLICT(event_id) DO NOTHING");
            $db->exec("INSERT INTO audit_events(event_id,actor_id,actor_name,actor_role,actor_rank,action,category,result,occurred_at,synced_at,operation_id,before_data,after_data) SELECT 'legacy:registration:'||u.id,u.id,u.public_name,'No registrado',3,'account_created','users','confirmed',CAST(u.created_at AS TEXT),CAST(u.created_at AS TEXT),'legacy:registration:'||u.id,'{}','{}' FROM users u WHERE 1=1 ON CONFLICT(event_id) DO NOTHING");
            foreach($db->query('SELECT r.invitee_id,r.email_verified_at,r.email_hash,u.email FROM referrals r JOIN users u ON u.id=r.invitee_id WHERE r.email_verified_at IS NOT NULL') as $legacy)if(hash_equals($legacy['email_hash'],hash('sha256',strtolower(trim($legacy['email'])))))$db->prepare('UPDATE users SET email_verified_at=? WHERE id=?')->execute([$legacy['email_verified_at'],$legacy['invitee_id']]);
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(29)');
        }
        if(!isset($applied[30])){
            $db->exec("CREATE TABLE scan_list_shares (code TEXT PRIMARY KEY CHECK(length(code)=8), payload_hash TEXT NOT NULL UNIQUE, payload TEXT NOT NULL, created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(30)');
        }
        if(!isset($applied[31])){
            $db->exec("CREATE TABLE pallet_user_tags (user_id {$ref} NOT NULL REFERENCES users(id) ON DELETE CASCADE, pallet_id {$ref} NOT NULL REFERENCES pallets(id) ON DELETE CASCADE, name TEXT NOT NULL CHECK(length(name)<=40), PRIMARY KEY(user_id,pallet_id))");
            $db->exec('CREATE INDEX pallet_user_tags_name ON pallet_user_tags(user_id,name,pallet_id)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(31)');
        }
        if(!isset($applied[32])){
            $db->exec("CREATE TABLE label_print_links (code TEXT PRIMARY KEY CHECK(length(code)=8), target TEXT NOT NULL UNIQUE)");
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(32)');
        }
        if(!isset($applied[33])){
            $db->exec("CREATE TABLE scanner_metrics (event_id TEXT PRIMARY KEY, user_id {$ref} NOT NULL REFERENCES users(id) ON DELETE CASCADE, recorded_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP, mode TEXT NOT NULL CHECK(mode IN ('qr','ean')), context TEXT NOT NULL, outcome TEXT NOT NULL CHECK(outcome IN ('confirmed','cancelled','error')), source TEXT NOT NULL, app_version TEXT NOT NULL, browser TEXT NOT NULL, device TEXT NOT NULL, rapid INTEGER NOT NULL CHECK(rapid IN (0,1)), open_ms INTEGER, scan_ms INTEGER NOT NULL, confirm_ms INTEGER, total_ms INTEGER NOT NULL, width INTEGER NOT NULL, height INTEGER NOT NULL)");
            $db->exec('CREATE INDEX scanner_metrics_period ON scanner_metrics(recorded_at,mode,app_version)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(33)');
        }
        if(!isset($applied[34])){
            $db->exec('CREATE TABLE catalog_photos (ean TEXT PRIMARY KEY REFERENCES products(ean) ON DELETE CASCADE, object_key TEXT NOT NULL)');
            $db->exec('INSERT INTO app_schema_migrations(version) VALUES(34)');
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
