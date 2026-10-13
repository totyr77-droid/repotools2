<?php
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/stock.php';
require_once __DIR__.'/../includes/stock-branches.php';
function pn_check(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
foreach([['Gastón Reyes','Gastón R.'],['Gastón Alejandro Reyes','Gastón A.'],['María Laura Rodríguez','María L.'],['Juan','Juan'],['  Gastón   Reyes  ','Gastón R.'],['Ñandú Álvarez','Ñandú Á.'],['','Usuario #123'],['<script>','Usuario #123']] as [$name,$expected])pn_check(generate_public_user_name($name,123)===$expected,'Generation mismatch');
pn_check(get_public_user_name(['id'=>123,'name'=>'Gastón Reyes'])==='Gastón R.','Missing public fallback');
foreach(['greeyes','Lucía F.','Ñandú_2','Mi-Nombre'] as $name)pn_check(validate_public_user_name($name)===$name,'Valid name rejected');
foreach(['<img src=x onerror=alert(1)>',"Hola\nNombre",'ab',str_repeat('a',25),'Hola@user',"Hola\0user",['name']] as $name)pn_check(validate_public_user_name($name)===null,'Unsafe name accepted');
$url=getenv('REPOTOOLS_TEST_DATABASE_URL');
if($url){$c=postgres_connection($url);$db=new PDO($c['dsn'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$db->exec('INSERT INTO app_schema_migrations(version) SELECT generate_series(1,23) ON CONFLICT(version) DO NOTHING');}
else $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
initialize_database($db);
$insert=$db->prepare('INSERT INTO users(name,email,password_hash,public_name) VALUES(?,?,?,?) RETURNING id');$ids=[];
foreach([['Gastón Reyes',''],['Gastón Rodríguez',''],['María Laura Rodríguez','Elegido'],['Juan',''],['','']] as $index=>[$name,$public]){$insert->execute([$name,'pn'.$index.'@example.invalid','fixture',$public]);$ids[]=(int)$insert->fetchColumn();$insert->closeCursor();}
$db->exec('DELETE FROM app_schema_migrations WHERE version=24');initialize_database($db);initialize_database($db);
$users=$db->query('SELECT id,name,public_name FROM users ORDER BY id')->fetchAll();pn_check($users[0]['public_name']==='Gastón R.'&&$users[1]['public_name']==='Gastón R.','Backfill/collision mismatch');pn_check($users[2]['public_name']==='Elegido','Overwrote chosen name');pn_check($users[4]['public_name']==='Usuario #'.$ids[4],'Empty fallback');pn_check(backfill_public_user_names($db)===0,'Not idempotent');
$owner=$ids[0];$member=$ids[1];stock_query($db,"INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES('12345678',?,?)",[$owner,time()+86400]);$claimed=stock_claim($db,'12345678',$owner);$pid=(int)$claimed['id'];stock_query($db,"INSERT INTO pallet_access(pallet_id,user_id,status) VALUES(?,?,'accepted')",[$pid,$member]);stock_query($db,'UPDATE pallets SET activated=1 WHERE id=?',[$pid]);
$p=stock_detail($db,'12345678',null);pn_check($p['owner_name']==='Gastón R.'&&$p['updated_name']==='Gastón R.','Public identity leak');pn_check(!str_contains(json_encode($p),'Gastón Reyes'),'Real name leaked');$p=stock_detail($db,'12345678',$owner);pn_check($p['members'][0]['name']==='Gastón R.','Member leak');
stock_query($db,"INSERT INTO stock_shares(owner_id,token,title) VALUES(?,'abcdefghijklmnopqrstuv','Test')",[$owner]);$sid=(int)stock_query($db,"SELECT id FROM stock_shares WHERE token='abcdefghijklmnopqrstuv'")->fetchColumn();stock_query($db,'INSERT INTO stock_share_pallets(share_id,pallet_id) VALUES(?,?)',[$sid,$pid]);
stock_query($db,"UPDATE users SET public_name='greeyes' WHERE id=?",[$owner]);pn_check(stock_detail($db,'12345678',null)['owner_name']==='greeyes','Palet did not refresh');pn_check(branch_public($db,'abcdefghijklmnopqrstuv')['pallets'][0]['owner_name']==='greeyes','Share did not refresh');pn_check(stock_list($db,$owner)[0]['owner_name']==='greeyes','List did not refresh');
$u=stock_query($db,'SELECT id,name,public_name FROM users WHERE id=?',[$owner])->fetch();pn_check($u['name']==='Gastón Reyes'&&get_public_user_name($u)==='greeyes','Private name changed');
echo 'OK: UTF-8, validation, fallback, migration/backfill preserving chosen values, duplicate labels, dynamic public pallets/members/shares; 4 fixture accounts migrated, 1 preserved.',PHP_EOL;
