<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/sessions.php';
if ($url = getenv('REPOTOOLS_TEST_DATABASE_URL')) {
    $connection = postgres_connection($url);
    $db = new PDO($connection['dsn'], $connection['user'], $connection['password']);
} else {
    $db = new PDO('sqlite::memory:');
}
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
initialize_database($db);
function check_session(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); }
$id=bin2hex(random_bytes(32));$key=hash('sha256',$id);
$a=new DatabaseSessions($db);
check_session(!$a->validateId($id),'Unknown IDs rejected');
check_session($a->read($id)==='','New empty session');
check_session($a->write($id,'user|a:1:{s:2:"id";i:1;}'),'Session saved');$a->close();
$b=new DatabaseSessions($db);check_session($b->validateId($id),'Survives handler restart');
check_session(str_contains($b->read($id),'user|'),'Payload survives restart');
// Simulate a stalled request whose lease was acquired by a newer process.
$db->prepare('UPDATE app_sessions SET locked_until=0 WHERE id_hash=?')->execute([$key]);
$c=new DatabaseSessions($db);$c->read($id);check_session(!$b->write($id,'stale'),'Stale writer cannot overwrite newer lease');
$c->destroy($id);check_session(!$b->write($id,'revived'),'Revoked session cannot be revived');$b->close();$c->close();
$a->read($id);$a->write($id,'expired');$a->close();
$db->prepare('UPDATE app_sessions SET expires_at=? WHERE id_hash=?')->execute([time()-1,$key]);
check_session(!$a->validateId($id),'Expired IDs rejected');check_session($a->gc(1)>=1,'Expired rows cleaned');
echo "OK: durable payload, strict ID validation, stale writes, revocation and expiry.\n";
