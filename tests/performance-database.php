<?php
// Isolated SQLite baseline; never connects to the configured production database.
require_once __DIR__ . '/../includes/database.php';
$path = tempnam(sys_get_temp_dir(), 'repo-perf-');
function ms(int $start): float { return (hrtime(true) - $start) / 1000000; }
try {
    $start = hrtime(true);
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connect = ms($start);
    $start = hrtime(true); initialize_database($db); $cold = ms($start);
    $checks = []; $queries = [];
    for ($i = 0; $i < 30; $i++) {
        $start = hrtime(true); initialize_database($db); $checks[] = ms($start);
        $start = hrtime(true); $db->query('SELECT COUNT(*) FROM users')->fetchColumn(); $queries[] = ms($start);
    }
    sort($checks); sort($queries);
    echo json_encode(['environment' => 'temporary SQLite, no network/API', 'samples' => 30,
        'db_connect_ms' => round($connect, 3), 'new_schema_ms' => round($cold, 3),
        'warm_migration_median_ms' => round($checks[15], 3), 'warm_migration_p95_ms' => round($checks[28], 3),
        'count_users_median_ms' => round($queries[15], 3), 'migration_version_checks' => 22], JSON_PRETTY_PRINT), PHP_EOL;
} finally { $db = null; unlink($path); }
