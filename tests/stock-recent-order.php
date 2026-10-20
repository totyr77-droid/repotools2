<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/stock.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
foreach(['Owner','Shared','Other'] as $i=>$name)stock_query($db,'INSERT INTO users(name,public_name,email,password_hash) VALUES(?,?,?,?)',[$name,$name,'recent'.$i.'@example.invalid','unused']);
function order_check(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
function recent_pallet(PDO $db,int $owner,string $code,string $expiry):array{stock_query($db,'INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES(?,?,?)',[$code,$owner,time()+3600]);$p=stock_claim($db,$code,$owner);stock_save_product($db,$owner,['pallet_id'=>$p['id'],'ean'=>'96385074','amount'=>10,'expires'=>$expiry]);return $p;}
$a=recent_pallet($db,1,'10000001','2020-01-01');$b=recent_pallet($db,1,'10000002','2030-01-01');$c=recent_pallet($db,1,'10000003','');$foreign=recent_pallet($db,3,'10000004','2020-01-01');
foreach([$a,$b,$c] as $i=>$p)stock_query($db,'UPDATE pallets SET updated_at=? WHERE id=?',['2026-01-0'.($i+1).' 10:00:00',$p['id']]);
order_check(array_column(stock_list($db,1),'code')===['10000003','10000002','10000001'],'recent creation before expiry and no foreign pallets');
$before=stock_query($db,'SELECT revision,updated_at FROM pallets WHERE id=?',[$a['id']])->fetch();stock_detail($db,$a['code'],1);order_check(stock_query($db,'SELECT revision,updated_at FROM pallets WHERE id=?',[$a['id']])->fetch()===$before,'consultation does not mutate activity');
stock_query($db,"INSERT INTO pallet_access(pallet_id,user_id,status) VALUES(?,2,'accepted')",[$a['id']]);stock_save_product($db,2,['pallet_id'=>$a['id'],'ean'=>'96385074','amount'=>2,'expires'=>'2020-01-01']);order_check(stock_list($db,1)[0]['code']===$a['code'],'load promotes pallet');order_check(stock_list($db,2)[0]['code']===$a['code'],'accepted account sees permitted pallet');
stock_query($db,'UPDATE pallets SET updated_at=? WHERE id=?',['2000-01-01 00:00:00',$b['id']]);$product=stock_detail($db,$b['code'],1)['products'][0];stock_deduct_product($db,1,['pallet_id'=>$b['id'],'product_id'=>$product['id'],'revision'=>$product['revision'],'amount'=>1]);order_check(stock_list($db,1)[0]['code']===$b['code'],'withdraw promotes pallet');
echo "OK: created/recent movement first independent of expiry, query no activity, shared scope and foreign denial.\n";
