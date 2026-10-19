<?php
// Isolated SQLite baseline; never connects to the configured production database.
require_once ($argv[1] ?? __DIR__ . '/../includes/database.php');
class MeasuredPDO extends PDO {
 public int $calls=0;
 public function query(string $query, ?int $fetchMode=null, mixed ...$args): PDOStatement|false {$this->calls++;return $fetchMode===null?parent::query($query):parent::query($query,$fetchMode,...$args);}
 public function exec(string $statement): int|false {$this->calls++;return parent::exec($statement);}
 public function beginTransaction(): bool {$this->calls++;return parent::beginTransaction();}
 public function commit(): bool {$this->calls++;return parent::commit();}
}
$path = tempnam(sys_get_temp_dir(), 'repo-perf-');
function ms(int $start): float { return (hrtime(true) - $start) / 1000000; }
try {
    $start = hrtime(true);
    $db = new MeasuredPDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connect = ms($start);
    $start = hrtime(true); initialize_database($db); $cold = ms($start);
    $checks = []; $queries = []; $calls=[];
    for ($i = 0; $i < 30; $i++) {
        $db->calls=0;$start = hrtime(true); initialize_database($db); $checks[] = ms($start);$calls[]=$db->calls;
        $start = hrtime(true); $db->query('SELECT COUNT(*) FROM users')->fetchColumn(); $queries[] = ms($start);
    }
    sort($checks); sort($queries);
    echo json_encode(['environment' => 'temporary SQLite, no network/API', 'samples' => 30, 'warm_initializer_calls' => $calls[0],
        'db_connect_ms' => round($connect, 3), 'new_schema_ms' => round($cold, 3),
        'warm_migration_median_ms' => round($checks[15], 3), 'warm_migration_p95_ms' => round($checks[28], 3),
        'count_users_median_ms' => round($queries[15], 3), 'migration_version_checks' => substr_count(file_get_contents(__DIR__ . '/../includes/database.php'), 'SELECT version FROM app_schema_migrations')], JSON_PRETTY_PRINT), PHP_EOL;
} finally { $db = null; unlink($path); }
