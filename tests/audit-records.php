<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/stock.php';require_once __DIR__.'/../includes/audit-read.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
function record_check($ok,$why){if(!$ok)throw new RuntimeException($why);}
foreach(['usuario','usuario','moderador','administrador','manager'] as $i=>$role)audit_q($db,'INSERT INTO users(name,public_name,email,password_hash,role) VALUES(?,?,?,?,?)',['Persona '.($i+1),'Persona '.($i+1),$i.'@example.invalid','x',$role]);
function emit_record($actor,$action,$key,$context=[],$before=[],$after=[],$result='confirmed'){global $db;$db->beginTransaction();audit_event($db,$actor,$action,$key,$context,$before,$after,$result);$db->commit();}
$db->exec("INSERT INTO stock_branches(id,owner_id,name,address,locality,province) VALUES(9,1,'Local','Calle','Ciudad','Provincia'),(12,1,'Test','Calle','Ciudad','Provincia')");
$root='12345678-1234-1234-1234-123456789012';
for($i=1;$i<=3;$i++)emit_record(1,'delete',$root.':'.$i,['pallet_id'=>$i,'code'=>str_pad((string)$i,8,'0',STR_PAD_LEFT)],['owner_id'=>1,'products'=>[['id'=>$i,'ean'=>'96385074','name'=>'Prueba','units'=>20,'expires'=>'2027-01-01']]],['released'=>1,'transaction_id'=>$root]);
$group=audit_read($db,5,['grouped'=>'1','section'=>'goods']);record_check(count($group['items'])===1&&(int)$group['items'][0]['event_count']===3,'batch groups before pagination');
$multipleMatches=audit_read($db,5,['grouped'=>'1','section'=>'goods','action'=>'delete']);record_check(count($multipleMatches['items'])===1&&(int)$multipleMatches['items'][0]['event_count']===3,'multiple matching members do not multiply joined events');
$filter=audit_read($db,5,['grouped'=>'1','section'=>'goods','pallet'=>'00000001']);record_check((int)$filter['items'][0]['event_count']===3,'filter retains whole operation');
$detail=audit_read($db,5,['grouped'=>'1','event'=>$group['items'][0]['event_id']])['items'][0];record_check(count($detail['events'])===3,'all members');record_check($detail['events'][0]['products'][0]['delta']===-20,'removed units');
record_check(!isset($group['items'][0]['before_data'])&&!isset($group['items'][0]['after']),'no eager snapshots');
emit_record(4,'save_product','staff-change',['pallet_id'=>7,'code'=>'77777777','branch_id'=>12],['owner_id'=>1,'products'=>[]],['owner_id'=>1,'products'=>[['id'=>7,'ean'=>'96385074','name'=>'Nuevo','units'=>30]]]);
record_check(count(audit_read($db,5,['grouped'=>'1','section'=>'moderation'])['items'])===1,'foreign staff edit moderation');record_check(count(audit_read($db,5,['grouped'=>'1','section'=>'moderation'])['branches'])===1,'manager test branch');record_check(audit_read($db,4,['grouped'=>'1','section'=>'moderation'])['branches']===[],'test branch hidden from administrator');
record_check(count(audit_read($db,3,['grouped'=>'1','section'=>'moderation'])['items'])===0,'moderator hierarchy');
record_check(audit_read($db,3,['grouped'=>'1','event'=>audit_read($db,5,['grouped'=>'1','section'=>'moderation'])['items'][0]['event_id']])['items']===[],'detail no bypass');
emit_record(1,'branch_assign','branch',['category'=>'movements','branch_id'=>9],[],['branch_id'=>3]);
record_check(count(audit_read($db,5,['grouped'=>'1','section'=>'management'])['items'])===1,'branch management');
audit_session($db,1,'session_started','mobile');record_check(audit_read($db,5,['grouped'=>'1','section'=>'users'])['items'][0]['action']==='session_started','session users');
try{audit_read($db,1,['grouped'=>'1']);throw new RuntimeException('user bypass');}catch(CatalogException $e){record_check($e->status===403,'user denied');}
try{audit_read($db,5,['grouped'=>'1','section'=>'invalid']);throw new RuntimeException('invalid section');}catch(CatalogException $e){record_check($e->status===422,'validated section');}
for($i=0;$i<52;$i++)emit_record(1,'save_product','separate-'.$i,['code'=>'87654321'],[],['units'=>$i]);
$page0=audit_read($db,5,['grouped'=>'1','section'=>'goods']);$page1=audit_read($db,5,['grouped'=>'1','section'=>'goods','page'=>'1']);record_check(count($page0['items'])===50&&$page0['has_more']&&count($page1['items'])===3,'group pagination');record_check(!array_intersect(array_column($page0['items'],'event_id'),array_column($page1['items'],'event_id')),'no duplicated pages');
record_check((int)$db->query('SELECT COUNT(*) FROM audit_events')->fetchColumn()===58,'immutable events');
echo "OK: grouped operations before pagination, complete filtered group, product deltas, sections, session events, lazy snapshots and permissions.\n";
