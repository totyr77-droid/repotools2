<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/tops.php';require_once __DIR__.'/../includes/pallet-activity.php';
$url=getenv('REPOTOOLS_TEST_DATABASE_URL');if(!$url)exit("Set test PostgreSQL URL in environment, never in source.\n");
$c=postgres_connection($url);$endpoint=explode('.',parse_url($url,PHP_URL_HOST))[0];$endpoint=str_replace('-pooler','',$endpoint);
$connectStart=hrtime(true);$db=new PDO($c['dsn'].";options='endpoint={$endpoint}'",$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::PGSQL_ATTR_DISABLE_PREPARES=>true]);
$connectMs=(hrtime(true)-$connectStart)/1e6;
require_once __DIR__.'/../includes/stock-overview.php';
$schema='rt_activity_test_'.bin2hex(random_bytes(6));$created=false;
function pg_check(bool $ok):void{if(!$ok)throw new RuntimeException('PostgreSQL validation failed');}
try{
 $db->exec("CREATE SCHEMA {$schema}");$created=true;$db->exec("SET search_path TO {$schema}");initialize_database($db);initialize_database($db);
 $db->exec("INSERT INTO users(id,name,email,password_hash,public_name) VALUES(1,'Private','owner@example.invalid','x','Owner'),(2,'Private','other@example.invalid','x','Other')");
 $db->exec("INSERT INTO scan_events(user_id,event_key,code,created_at) VALUES(1,'a','96385074','2026-10-12 03:00:00+00'),(1,'b','96385074','2026-10-13 10:00:00+00')");
 $now=new DateTimeImmutable('2026-10-13 12:00:00+00');pg_check(tops_ranking($db,'codigos','semana',1,$now)['leaders'][0]['value']===1);pg_check(tops_ranking($db,'escaneos','semana',1,$now)['leaders'][0]['value']===2);
 $db->beginTransaction();$label=stock_generate($db,1);$p=stock_claim($db,$label['code'],1);$id=$p['id'];pallet_activity_record($db,1,$id,null,pallet_activity_snapshot($db,$id));
 $before=pallet_activity_snapshot($db,$id);stock_save_product($db,1,['pallet_id'=>$id,'ean'=>'96385074','name'=>'Agua','amount'=>12,'kind'=>'units','expires'=>'2027-12-24']);pallet_activity_record($db,1,$id,$before,pallet_activity_snapshot($db,$id));$db->commit();pg_check(count(pallet_activity_list($db,1,$id)['items'])===3);
 try{pallet_activity_list($db,2,$id);throw new RuntimeException('IDOR');}catch(CatalogException $e){pg_check($e->status===403);}
 $times=[];$overviewTimes=[];for($i=0;$i<3;$i++){$start=hrtime(true);initialize_database($db);$times[]=(hrtime(true)-$start)/1e6;$start=hrtime(true);$overview=stock_overview($db,1);$overviewTimes[]=(hrtime(true)-$start)/1e6;pg_check($overview['counts']['products']===1);}
 $out=fopen('php://temp','w+');stock_write_csv($out,$db,1,'stock',stock_export_rows($db,1,'stock'));rewind($out);pg_check(str_contains(stream_get_contents($out),'Agua'));fclose($out);
 echo json_encode(['environment'=>'Neon development temporary schema, local PHP client, not Render','connect_ms'=>round($connectMs,2),'warm_schema_checks_ms'=>array_map(fn($v)=>round($v,2),$times),'overview_ms'=>array_map(fn($v)=>round($v,2),$overviewTimes)])."\n";
 $pid=stock_query($db,'SELECT id FROM pallet_products WHERE pallet_id=?',[$id])->fetchColumn();stock_query($db,'DELETE FROM pallet_products WHERE id=?',[$pid]);pg_check((int)stock_query($db,'SELECT COUNT(*) FROM pallet_activity WHERE pallet_id=? AND product_id IS NULL',[$id])->fetchColumn()===3);
 $beforeCount=stock_query($db,'SELECT COUNT(*) FROM pallet_activity')->fetchColumn();$db->beginTransaction();stock_query($db,'UPDATE pallets SET description=? WHERE id=?',['rollback',$id]);pallet_activity_record($db,1,$id,['description'=>'','released'=>0,'products'=>[],'members'=>[]],pallet_activity_snapshot($db,$id));$db->rollBack();pg_check(stock_query($db,'SELECT COUNT(*) FROM pallet_activity')->fetchColumn()===$beforeCount);
 echo "OK PostgreSQL: migrations26/27 idempotent, periods/timestamps, actor/activity, private403, deleted product SET NULL, atomic rollback.\n";
}finally{if($db->inTransaction())$db->rollBack();$db->exec('SET search_path TO public');if($created)$db->exec("DROP SCHEMA {$schema} CASCADE");}
