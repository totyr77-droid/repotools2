<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/stock-export.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
function export_check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$db->exec("INSERT INTO users(id,name,public_name,email,password_hash) VALUES(1,'PRIVATE','Dueño público','a@example.invalid','x'),(2,'PRIVATE','Colaborador','b@example.invalid','x'),(3,'PRIVATE','Otro','c@example.invalid','x')");
$db->exec("INSERT INTO pallets(id,code,owner_id,updated_by,activated,prepared_until) VALUES(1,'00000001',1,1,1,0),(2,'00000002',3,3,1,0)");
$db->exec("INSERT INTO pallet_access(pallet_id,user_id,status) VALUES(1,2,'accepted')");
$db->exec("INSERT INTO pallet_products(pallet_id,ean,name,units,expires) VALUES(1,'96385074','Café ñ; azúcar',5,'2026-10-13'),(2,'96385074','SECRET',20,'2026-10-13')");
foreach(['stock'=>6,'palets'=>6,'vencimientos'=>7] as $type=>$columns){$out=fopen('php://temp','w+');stock_write_csv($out,$db,1,$type,stock_export_rows($db,1,$type));rewind($out);$csv=stream_get_contents($out);export_check(str_starts_with($csv,"\xEF\xBB\xBF"),'BOM');export_check(!str_contains($csv,'PRIVATE')&&!str_contains($csv,'SECRET')&&!str_contains($csv,'example.invalid'),'Privacy');rewind($out);fread($out,3);export_check(count(fgetcsv($out,0,';','"',''))===$columns,'Header');$row=fgetcsv($out,0,';','"','');export_check(count($row)===$columns,'Escaped delimiter');if($type==='stock')export_check($row[1]==='Café ñ; azúcar','Accents');fclose($out);}
export_check(stock_export_rows($db,2,'stock',1)->fetch()['units']===5,'Accepted access');
try{stock_export_rows($db,3,'stock',1);throw new RuntimeException('IDOR not denied');}catch(CatalogException $e){export_check($e->status===403,'IDOR403');}
$db->exec("UPDATE pallet_access SET status='pending' WHERE user_id=2");export_check(!stock_export_rows($db,2,'stock')->fetch(),'Pending denied');
export_check(stock_csv_cell('=HYPERLINK("x")')[0]==="'",'Formula injection');export_check(stock_csv_cell("\t+1")[0]==="'",'Whitespace formula');
$today=new DateTimeImmutable('2026-10-13',new DateTimeZone('America/Buenos_Aires'));
foreach(['2026-10-12'=>'Vencido','2026-10-13'=>'Hoy','2026-10-23'=>'Próximo','2026-10-24'=>'Normal'] as $date=>$state)export_check(stock_expiry_state($date,10,$today)['label']===$state,'Expiry boundary');
$db->beginTransaction();for($i=0;$i<3000;$i++)stock_query($db,"INSERT INTO pallet_products(pallet_id,ean,name,units,expires) VALUES(1,?,?,1,'')",[(string)$i,'Producto '.$i]);$db->commit();$out=fopen('php://temp/maxmemory:1024','w+');stock_write_csv($out,$db,1,'stock',stock_export_rows($db,1,'stock'));rewind($out);export_check(substr_count(stream_get_contents($out),"\n")===3002,'Large export');fclose($out);
echo "OK: CSV3 formats, UTF8/BOM, accents, escape, privacy, accepted/pending/IDOR, formula guard, expiry boundaries, 3001 rows.\n";
