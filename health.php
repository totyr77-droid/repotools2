<?php
declare(strict_types=1);

// Readiness must not start a session, initialize a schema or expose connection errors.
require_once __DIR__.'/includes/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (!in_array($_SERVER['REQUEST_METHOD']??'GET', ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    echo json_encode(['ready'=>false]);
    exit;
}
$ready=false;
try {
    $connection=postgres_connection(trim(getenv('DATABASE_URL')?:''));
    $dsn=str_replace('connect_timeout=15', 'connect_timeout=2', $connection['dsn']);
    $db=new PDO($dsn, $connection['user'], $connection['password'], [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::PGSQL_ATTR_DISABLE_PREPARES=>true,
    ]);
    $db->exec('SET statement_timeout = 1500');
    $db->exec('SET default_transaction_read_only = on');
    $count=(int)$db->query('SELECT COUNT(*) FROM app_schema_migrations WHERE version BETWEEN 1 AND '.REPOTOOLS_SCHEMA_VERSION)->fetchColumn();
    $ready=$count===REPOTOOLS_SCHEMA_VERSION;
} catch (Throwable $error) {
    // The public probe returns a fixed status, never provider details or credentials.
}
http_response_code($ready?200:503);
if (($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD') echo json_encode(['ready'=>$ready]);
