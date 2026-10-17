<?php
require_once __DIR__.'/../includes/database.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
initialize_database($db);
$db->exec("INSERT INTO public_link_aliases(code,kind,target) VALUES('A1234567','p','12345678')");
// Only this disposable in-memory database is modified to simulate an installed v31 schema.
$db->exec('DROP TABLE catalog_photos');
$db->exec('DROP TABLE scanner_metrics');
$db->exec('DROP TABLE label_print_links');
$db->exec('DELETE FROM app_schema_migrations WHERE version>=32');
initialize_database($db);
if(REPOTOOLS_SCHEMA_VERSION!==34||(int)$db->query('SELECT COUNT(*) FROM app_schema_migrations')->fetchColumn()!==34)throw new RuntimeException('Migration did not advance');
if((int)$db->query('SELECT COUNT(*) FROM public_link_aliases')->fetchColumn()!==1)throw new RuntimeException('Existing aliases changed');
$db->query('SELECT event_id,scan_ms FROM scanner_metrics');
$db->query('SELECT code,target FROM label_print_links');
initialize_database($db);
echo "OK: existing schema31 upgrades32/33/34, preserves aliases and reopens without repeated DDL.\n";
