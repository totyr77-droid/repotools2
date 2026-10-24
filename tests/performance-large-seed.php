<?php
// Synthetic, isolated benchmark only. Refuse configured remote databases.
if (getenv('DATABASE_URL') || !str_contains((string)getenv('REPOTOOLS_DB_PATH'), 'rt-large-')) throw new RuntimeException('Use the isolated benchmark runner.');
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/supervision.php';
$db=database();
$db->beginTransaction();
$u=$db->prepare('INSERT INTO users(name,public_name,email,password_hash,role) VALUES(?,?,?,?,?)');
$hash=password_hash('benchmark-only-password',PASSWORD_DEFAULT);
for($i=1;$i<=21;$i++)$u->execute(['Prueba '.$i,'Prueba '.$i,'bench'.$i.'@example.invalid',$hash,$i===1?'manager':'usuario']);
$db->commit();
supervisor_enable($db,1,['email'=>'bench1@example.invalid','name'=>'Equipo de prueba']);
for($i=2;$i<=21;$i++){supervisor_invite($db,1,'bench'.$i.'@example.invalid');$link=supervisor_links($db,$i)[0];supervisor_decide($db,$i,$link['team_id'],$link['revision'],'accept');}
$db->beginTransaction();
$p=$db->prepare('INSERT INTO pallets(code,owner_id,prepared_until,activated,updated_by) VALUES(?,?,?,1,?)');
$label=$db->prepare('INSERT INTO pallet_labels(code,pallet_id) VALUES(?,?)');
$l=$db->prepare('INSERT INTO pallet_products(pallet_id,ean,name,expires,units,kind,pack_size) VALUES(?,?,?,?,120,\'boxes\',12)');
for($i=1;$i<=2000;$i++){$owner=$i<=1000?1:2+($i%20);$p->execute([(string)(10000000+$i),$owner,time()+86400,$owner]);$id=$db->lastInsertId();$label->execute([(string)(10000000+$i),$id]);for($j=0;$j<10;$j++){$n=($i*10+$j)%5000;$base='200'.str_pad((string)$n,9,'0',STR_PAD_LEFT);$sum=0;for($k=0;$k<12;$k++)$sum+=(int)$base[$k]*($k%2?3:1);$ean=$base.((10-$sum%10)%10);$l->execute([$id,$ean,'Producto de prueba '.$n,date('Y-m-d',time()+(($n%180)-15)*86400)]);}}
$e=$db->prepare('INSERT INTO audit_events(event_id,actor_id,actor_name,actor_role,actor_rank,action,category,result,occurred_at,synced_at,operation_id,pallet_code,ean,before_data,after_data) VALUES(?,1,\'Prueba\',\'manager\',3,\'save_product\',\'movements\',\'confirmed\',?,?,?,?,?,\'{}\',\'{}\')');
for($i=0;$i<50000;$i++){$at=gmdate('Y-m-d H:i:s',time()-$i*60);$e->execute(['bench-'.$i,$at,$at,'op-'.$i,(string)(10000001+$i%2000),'2000000000008']);}
$db->commit();
echo "Seed: 21 users, 2000 pallets, 20000 lots, 5000 EAN, 50000 events\n";
