<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/user-profiles.php';
function profile_check(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function profile_denied(callable $run,int $status): void {try{$run();}catch(CatalogException $e){profile_check($e->status===$status,'Unexpected status');return;}throw new RuntimeException('Expected rejection');}
$url=getenv('REPOTOOLS_TEST_DATABASE_URL');
if($url){$c=postgres_connection($url);$db=new PDO($c['dsn'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::PGSQL_ATTR_DISABLE_PREPARES=>true]);}
else $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
initialize_database($db);$users=[];$suffix=bin2hex(random_bytes(6));
foreach(['usuario','moderador','administrador','manager'] as $role){$s=$db->prepare('INSERT INTO users(name,email,password_hash,role,company) VALUES(?,?,?,?,?) RETURNING id');$s->execute(['Juan Perez',$suffix.$role.'@example.invalid','test-only',$role,'Empresa']);$users[$role]=(int)$s->fetchColumn();$s->closeCursor();}
$u=$users['usuario'];$a=$users['administrador'];$m=$users['manager'];
$token=profile_token($db,$u);profile_check(strlen($token)===64&&profile_token($db,$u)===$token,'Stable opaque token');
$db->prepare('INSERT INTO scan_events(user_id,event_key,code) VALUES(?,?,?)')->execute([$u,$suffix.'1','7791234567898']);
$db->prepare('INSERT INTO scan_events(user_id,event_key,code) VALUES(?,?,?)')->execute([$u,$suffix.'2','7791234567898']);
$profile=profile_detail($db,profile_user($db,$a),profile_user($db,$u));profile_check($profile['stats']['scans']===2&&$profile['stats']['unique_codes']===1,'Unique versus total');profile_check(!isset($profile['user']['report_ip']),'Admin cannot read IP');profile_check(array_key_exists('report_ip',profile_detail($db,profile_user($db,$m),profile_user($db,$u))['user']),'Manager IP field');
profile_denied(fn()=>profile_change_role($db,$a,$u,'manager'),403);
profile_denied(fn()=>profile_change_role($db,$a,$m,'usuario'),403);
profile_denied(fn()=>profile_change_role($db,$a,$a,'usuario'),403);
profile_denied(fn()=>profile_change_role($db,$users['moderador'],$u,'moderador'),403);
profile_change_role($db,$a,$u,'moderador');profile_check(profile_user($db,$u)['role']==='moderador','Admin promotes lower account');
$s=$db->prepare('SELECT actor_id,old_role,new_role FROM user_role_events WHERE user_id=?');$s->execute([$u]);$event=$s->fetch();profile_check((int)$event['actor_id']===$a&&$event['old_role']==='usuario'&&$event['new_role']==='moderador','Role audit');
if(!$url)profile_denied(fn()=>profile_change_role($db,$m,$m,'usuario'),422);
profile_change_role($db,$m,$a,'manager');profile_change_role($db,$m,$m,'usuario');profile_denied(fn()=>profile_change_role($db,$m,$u,'usuario'),403);
profile_check(profile_user($db,$a)['role']==='manager','Manager grants manager');
echo "OK: QR tokens, stats, IP privacy, hierarchy, audit and fresh permissions\n";
