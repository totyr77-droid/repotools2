<?php
declare(strict_types=1);
putenv('DATABASE_URL=');putenv('REPOTOOLS_REQUIRE_DATABASE_URL=0');$file=tempnam(sys_get_temp_dir(),'rt-ach-');putenv('REPOTOOLS_DB_PATH='.$file);
require __DIR__.'/../includes/database.php';require __DIR__.'/../includes/achievements.php';require __DIR__.'/../includes/audit.php';
function ok(bool $value,string $message):void {if(!$value)throw new RuntimeException($message);}
try{
 $db=database();$db->exec("INSERT INTO users(name,email,password_hash) VALUES('Uno','one@example.invalid','x'),('Dos','two@example.invalid','x')");
 ok(achievement_snapshot($db,1,true)['unlocked']===0,'Start at zero, no historical backfill');ok(count(achievement_catalog())===12,'12 categories');
 $db->beginTransaction();for($i=0;$i<100;$i++)achievement_record($db,1,'escaneos','EAN'.$i,'2026-10-12');achievement_record($db,1,'escaneos','EAN0','2026-10-12');$db->commit();
 $snapshot=achievement_snapshot($db,1,true);ok($snapshot['categories'][0]['progress']===100,'Unique EAN');ok($snapshot['unlocked']===3,'Multiple levels');ok($snapshot['days']===1,'One activity day');ok((int)$db->query('SELECT COUNT(*) FROM notifications')->fetchColumn()===3,'No duplicate notifications');
 $db->beginTransaction();achievement_record($db,1,'retiros','rolledback');$db->rollBack();ok((int)$db->query("SELECT COUNT(*) FROM achievement_events WHERE category='retiros'")->fetchColumn()===0,'Rollback');
 ok(achievement_streak(['2026-10-05','2026-10-10','2026-10-12','2026-10-17'])===2,'Monday to Saturday, Sunday not required');
 ok(achievement_streak(['2026-10-10','2026-10-11','2026-10-17','2026-10-18'])===2,'Sunday counts');
 ok(achievement_streak(['2026-10-05','2026-10-10','2026-10-12','2026-10-26','2026-10-27'])===1,'One day week breaks streak');
 $db->beginTransaction();achievement_record($db,1,'listas','EAN1');achievement_record($db,1,'listas','EAN1');achievement_record($db,2,'listas','EAN1');
 $before=['activated'=>0,'products'=>[]];$after=['id'=>1,'owner_id'=>1,'activated'=>1,'products'=>[['units'=>20]]];
 audit_event($db,1,'save_product','first-load',['new_load'=>true],$before,$after);audit_event($db,1,'save_product','first-load',['new_load'=>true],$before,$after);
 audit_event($db,1,'save_product','edit-quantity',['new_load'=>false],$after,['id'=>1,'owner_id'=>1,'activated'=>1,'products'=>[['units'=>30]]]);
 audit_event($db,1,'transfer_commit','transfer');audit_event($db,1,'transfer_commit','transfer');
 audit_event($db,1,'deduct_product','denied',[],[],[],'denied');
 audit_event($db,2,'approved','report1',['target_user'=>1],['product'=>['name'=>'Old']],['product'=>['name'=>'New']]);
 audit_event($db,1,'approved','self',['target_user'=>1],['product'=>['name'=>'Old']],['product'=>['name'=>'New']]);
 $db->commit();$values=achievement_q($db,'SELECT category,value FROM achievement_progress WHERE user_id=?',[1])->fetchAll(PDO::FETCH_KEY_PAIR);
 ok((int)$values['pallets']===1,'First confirmed load only');ok((int)$values['productos_agregados']===1,'Operations, edits excluded');ok((int)$values['transferencias']===1,'Idempotent transfers');ok((int)$values['ediciones']===1,'Approved correction, no self approval');ok((int)$values['listas']===1,'Unique list EAN');ok(!isset($values['retiros']),'Denied does not count');
 $public=achievement_snapshot($db,1,false);foreach($public['categories'] as $c)ok($c['progress']===null,'Public hides progress');
 ok(achievement_catalog()['ediciones']['metas']===[5,15,40,100,250,500],'Approved report goals');
 echo "OK: zero start, unique actions, multi-level unlock, rollback/idempotency, days/Sundays/streaks, confirmed audit hooks, privacy.\n";
}finally{unset($db);@unlink($file);}
