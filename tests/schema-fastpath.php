<?php
require_once __DIR__.'/../includes/database.php';
preg_match_all('/INSERT INTO app_schema_migrations\s*\(version\) VALUES\s*\((\d+)\)/',file_get_contents(__DIR__.'/../includes/database.php'),$matches);
if(max(array_map('intval',$matches[1]))!==REPOTOOLS_SCHEMA_VERSION)throw new RuntimeException('Actualizá REPOTOOLS_SCHEMA_VERSION junto a la migración nueva.');
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
initialize_database($db);initialize_database($db);
if((int)$db->query('SELECT MAX(version) FROM app_schema_migrations')->fetchColumn()!==REPOTOOLS_SCHEMA_VERSION)throw new RuntimeException('Esquema incompleto.');
// Simulate an older installed schema, without touching configured databases.
$db->exec('DROP TABLE pallet_activity');$db->exec('DELETE FROM app_schema_migrations WHERE version=27');initialize_database($db);
$db->query('SELECT id FROM pallet_activity');
$db->exec('DROP TABLE public_link_aliases');$db->exec('DELETE FROM app_schema_migrations WHERE version=28');initialize_database($db);$db->query('SELECT code FROM public_link_aliases');
echo "OK: fresh schema, installed fast path, old-schema upgrade and version guard.\n";
