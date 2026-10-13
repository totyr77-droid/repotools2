<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/stock-overview.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
function overview_check(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
$db->exec("INSERT INTO users(id,name,public_name,email,password_hash) VALUES(1,'PRIVATE','Público','a@example.invalid','x'),(2,'PRIVATE','Otro','b@example.invalid','x'),(3,'PRIVATE','Vacío','c@example.invalid','x')");
$today=new DateTimeImmutable('2026-10-13',new DateTimeZone('America/Buenos_Aires'));
overview_check(stock_overview($db,3,$today)['counts']===['pallets'=>0,'products'=>0,'upcoming'=>0,'expired'=>0],'Empty zeros');
$db->exec("INSERT INTO pallets(id,code,owner_id,updated_by,activated,prepared_until) VALUES(1,'00000001',1,1,1,0),(2,'00000002',2,2,1,0),(3,'00000003',1,1,0,0)");
foreach(['2026-10-12','2026-10-13','2026-10-23','2026-10-24',''] as $date)stock_query($db,"INSERT INTO pallet_products(pallet_id,ean,name,units,expires) VALUES(1,'96385074','Agua',8,?)",[$date]);
$db->exec("INSERT INTO stock_preferences(user_id,expiry_days) VALUES(1,10)");
for($i=0;$i<8;$i++)stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(1,1,?,?)',['quantity_updated','{"old":1,"new":2}']);
stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(2,2,?,?)',['description_updated','{"new":"SECRET"}']);
$result=stock_overview($db,1,$today);overview_check($result['counts']===['pallets'=>1,'products'=>5,'upcoming'=>2,'expired'=>1],'Expiry custom boundaries, lots not units');overview_check(count($result['activity'])===5,'Only recent5');overview_check(!str_contains(json_encode($result),'PRIVATE')&&!str_contains(json_encode($result),'SECRET'),'Privacy');
$db->exec("UPDATE users SET public_name='Nuevo' WHERE id=1");overview_check(stock_overview($db,1,$today)['activity'][0]['actor']==='Nuevo','Dynamic public name');
$db->exec("INSERT INTO pallet_access(pallet_id,user_id,status) VALUES(2,1,'pending')");overview_check(stock_overview($db,1,$today)['counts']['pallets']===1,'Pending excluded');$db->exec("UPDATE pallet_access SET status='accepted'");overview_check(stock_overview($db,1,$today)['counts']['pallets']===2,'Shared accepted included');
$db->exec("UPDATE stock_preferences SET expiry_days=0 WHERE user_id=1");overview_check(stock_overview($db,1,$today)['counts']['upcoming']===1,'Today included at0');
$db->beginTransaction();for($i=10;$i<310;$i++)stock_query($db,'INSERT INTO pallets(code,owner_id,updated_by,activated,prepared_until) VALUES(?,1,1,1,0)',[str_pad((string)$i,8,'0',STR_PAD_LEFT)]);$db->commit();$start=microtime(true);overview_check(stock_overview($db,1,$today)['counts']['pallets']===302,'Many pallets');echo 'OK: empty/300+, active/shared scope, lots, expired/today/custom preference, recent5, current public name. Local aggregate '.round((microtime(true)-$start)*1000,2)."ms (not production).\n";
