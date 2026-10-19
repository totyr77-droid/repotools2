<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/tops.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
function ensure_top(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$db->exec("INSERT INTO users(id,name,email,password_hash,public_name) VALUES(1,'Private Full Name','secret@example.invalid','x','Público'),(2,'Hidden Name','hidden@example.invalid','x','')");
$insert=$db->prepare('INSERT INTO scan_events(user_id,event_key,code,created_at) VALUES(?,?,?,?)');
foreach([[1,'a','111','2026-10-12 02:59:59'],[1,'b','222','2026-10-12 03:00:00'],[1,'c','222','2026-10-13 10:00:00'],[2,'d','333','2026-10-01 03:00:00']] as $row)$insert->execute($row);
$now=new DateTimeImmutable('2026-10-13 09:00:00',new DateTimeZone('America/Buenos_Aires'));
ensure_top(tops_ranking($db,'codigos','semana',1,$now)['leaders'][0]['value']===1,'Week starts Monday midnight Argentina and codes deduplicate');
ensure_top(tops_ranking($db,'escaneos','semana',1,$now)['leaders'][0]['value']===2,'Repeated scans count');
$month=tops_ranking($db,'codigos','mes',null,$now);ensure_top(count($month['leaders'])===2,'Month activity');ensure_top($month['leaders'][1]['name']==='Usuario','No private fallback or ID');
ensure_top(!str_contains(json_encode($month),'secret')&&!str_contains(json_encode($month),'Private'),'Public data only');
ensure_top(!tops_ranking($db,'reportes','historico',null,$now)['leaders'],'No fabricated approved reports');
$report=$db->prepare("INSERT INTO product_reports(id,submission_key,owner_key,user_id,ean,description,proposed_data,original_data,status,submitted_at) VALUES(?,?,'fixture',1,'96385074','','{}','{}',?,1)");$report->execute([1,'approved','approved']);$report->execute([2,'pending','pending']);$db->exec("INSERT INTO report_rewards(report_id,user_id,created_at) VALUES(1,1,'2026-10-13 10:00:00')");
ensure_top(tops_ranking($db,'reportes','semana',null,$now)['leaders'][0]['value']===1,'Only confirmed contribution, not pending/spam');
for($i=3;$i<=54;$i++){$db->prepare('INSERT INTO users(id,name,email,password_hash,public_name) VALUES(?,?,?,?,?)')->execute([$i,'Private','user'.$i.'@example.invalid','x','User '.$i]);for($j=0;$j<3;$j++)$insert->execute([$i,"$i-$j",'999','2026-10-13 11:00:00']);}
$ranking=tops_ranking($db,'escaneos','semana',2,$now);ensure_top(count($ranking['leaders'])===10,'Limit10');
$ranking=tops_ranking($db,'escaneos','historico',2,$now);ensure_top($ranking['own']['value']===1&&$ranking['own']['position']===54,'Own position outside visible list, ties ranked');
for($i=0;$i<2000;$i++)$insert->execute([1,'old'.$i,'111','2020-01-01 00:00:00']);$db->exec('ANALYZE');
$plan=$db->query("EXPLAIN QUERY PLAN SELECT user_id,COUNT(*) FROM scan_events WHERE created_at>='2026-10-12' AND created_at<='2026-10-13 12:00:00' GROUP BY user_id")->fetchAll();ensure_top(str_contains(json_encode($plan),'scans_period_user_code'),'Selective period uses date covering index');
initialize_database($db);echo "OK: Argentina boundaries, unique/repeated, month/history, privacy/fallback, empty, ties/outside10 and indexed plan.\n";
