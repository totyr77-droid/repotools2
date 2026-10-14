<?php
// Diagnostic only: no initialize_database, schema creation or application data.
require_once __DIR__.'/../includes/database.php';
$url=getenv('REPOTOOLS_TEST_DATABASE_URL');if(!$url)exit("Set connection in environment; do not place credentials in source.\n");
$c=postgres_connection($url);$endpoint=str_replace('-pooler','',explode('.',parse_url($url,PHP_URL_HOST))[0]);
$start=hrtime(true);$db=new PDO($c['dsn'].";options='endpoint={$endpoint}'",$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::PGSQL_ATTR_DISABLE_PREPARES=>true]);$connect=(hrtime(true)-$start)/1e6;
$db->exec('SET default_transaction_read_only = on');$samples=[];
for($i=0;$i<3;$i++){$start=hrtime(true);$db->query('SELECT 1')->fetchColumn();$simple=(hrtime(true)-$start)/1e6;$start=hrtime(true);$db->query('SELECT version FROM app_schema_migrations')->fetchAll(PDO::FETCH_COLUMN);$schema=(hrtime(true)-$start)/1e6;$samples[]=['select1_ms'=>round($simple,2),'versions_ms'=>round($schema,2)];}
echo json_encode(['environment'=>'Read-only Neon, Windows PHP client (not Render); no application rows or mutations','connect_ms'=>round($connect,2),'samples'=>$samples],JSON_PRETTY_PRINT)."\n";
