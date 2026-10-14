<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/pallet-activity.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$db->exec('PRAGMA foreign_keys=ON');initialize_database($db);
function grouping_check(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
$db->exec("INSERT INTO users(id,name,public_name,email,password_hash) VALUES(1,'Fixture','Fixture','a@example.invalid','x'),(2,'Other','Other','b@example.invalid','x')");
$db->exec("INSERT INTO pallets(id,code,owner_id,updated_by,activated,prepared_until) VALUES(1,'00000001',1,1,1,0),(2,'00000002',2,2,1,0)");
$db->exec("INSERT INTO pallet_products(id,pallet_id,ean,name,units,expires) VALUES(1,1,'96385074','Agua',8,'2027-12-24')");
foreach([['product_added',['ean'=>'96385074','reference'=>'Agua','units'=>8]],['expiry_added',['ean'=>'96385074','old'=>'','new'=>'2027-12-24']]] as [$action,$meta])stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,product_id,action,metadata,created_at) VALUES(1,1,1,?,?,?)',[$action,json_encode($meta),'2026-10-13 12:00:00']);
foreach([pallet_activity_list($db,1,1),pallet_activity_feed($db,1)] as $view){grouping_check(count($view['items'])===1,'Legacy pair grouped');grouping_check($view['items'][0]['metadata']['expires']==='2027-12-24','Expiry retained');}
grouping_check(count(pallet_activity_recent($db,1))===1,'Overview agrees');grouping_check((int)$db->query('SELECT COUNT(*) FROM pallet_activity')->fetchColumn()===2,'Raw audit rows preserved');
$db->exec('DELETE FROM pallet_products WHERE id=1');grouping_check(count(pallet_activity_list($db,1,1)['items'])===1,'Deleted product still grouped');
stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata,created_at) VALUES(1,1,?,?,?)',['expiry_added',json_encode(['ean'=>'96385074','old'=>'','new'=>'2028-01-24']),'2026-10-13 12:01:00']);grouping_check(count(pallet_activity_list($db,1,1)['items'])===2,'Later expiry changes remain separate');
grouping_check(pallet_activity_feed($db,2)['items']===[],'No foreign audit disclosure');
echo "OK: legacy initial add/expiry grouped across history/overview/detail, raw rows retained, deleted-product grouping and later changes/privacy preserved.\n";
