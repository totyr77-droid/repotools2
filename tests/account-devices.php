<?php
require __DIR__.'/../includes/account-devices.php';
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);$db->exec('CREATE TABLE users(id INTEGER PRIMARY KEY,auth_version INTEGER,role TEXT DEFAULT "usuario");CREATE TABLE account_devices(user_id INTEGER,kind TEXT,token_hash TEXT,auth_version INTEGER,expires_at BIGINT,PRIMARY KEY(user_id,kind));INSERT INTO users(id,auth_version) VALUES(1,1)');
function check($yes){if(!$yes)throw new Exception('Device check failed');}
$u=['id'=>1,'auth_version'=>1];$a=str_repeat('a',64);$b=str_repeat('b',64);$c=str_repeat('c',64);
check(device_claim($db,$u,'desktop',$a));check(device_claim($db,$u,'mobile',$b));check(!device_claim($db,$u,'desktop',$c));check(device_valid($db,$u,$a,'desktop'));check(device_claim($db,$u,'desktop',$c,true));check(!device_valid($db,$u,$a,'desktop'));check(device_valid($db,$u,$b,'mobile'));device_release($db,1,$a,'desktop');check(device_valid($db,$u,$c,'desktop'));device_release($db,1,$c,'desktop');check(!device_valid($db,$u,$c,'desktop'));$db->exec('UPDATE users SET auth_version=2');check(!device_claim($db,$u,'desktop',$a,true));$u['auth_version']=2;check(device_claim($db,$u,'mobile',$c));check(!device_valid($db,$u,$b,'mobile'));check(device_kind('Mozilla iPhone Mobile')==='mobile');check(device_kind('Windows Chrome')==='desktop');check(device_kind('Mozilla iPad')==='desktop');check(device_kind('Mozilla Android Tablet')==='desktop');check(device_kind('Mozilla Android Mobile')==='mobile');echo "OK: device claims, conflict, replacement, logout and auth version.\n";

$db->exec("UPDATE users SET role='manager' WHERE id=1");
check(device_claim($db,$u,'mobile',$a));check(device_claim($db,$u,'mobile',$b));check(device_valid($db,$u,$a,'mobile'));check(device_valid($db,$u,$b,'mobile'));check(device_valid($db,$u,$c,'mobile'));
check(device_claim($db,$u,'desktop',$a));check(!device_claim($db,$u,'desktop',$b));
$db->exec("UPDATE account_devices SET expires_at=".(time()+5)." WHERE kind LIKE 'mobile:%'");device_renew($db,$u,$a,'mobile');check((int)$db->query("SELECT MAX(expires_at) FROM account_devices WHERE kind LIKE 'mobile:%'")->fetchColumn()>time()+1000);
device_release($db,1,$a,'mobile');check(!device_valid($db,$u,$a,'mobile'));check(device_valid($db,$u,$b,'mobile'));
$db->exec("UPDATE users SET role='administrador' WHERE id=1");check(!device_valid($db,$u,$b,'mobile'));check(device_valid($db,$u,$c,'mobile'));check(!device_claim($db,$u,'mobile',$b));
$db->exec("UPDATE users SET role='manager',auth_version=3 WHERE id=1");check(!device_claim($db,$u,'mobile',$b));$u['auth_version']=3;check(!device_valid($db,$u,$b,'mobile'));check(device_claim($db,$u,'mobile',$a));check(device_valid($db,$u,$a,'mobile'));check(!device_valid($db,$u,$c,'mobile'));
echo "OK: beta manager multiple mobile tokens, desktop limit retained, independent logout/renewal, current role and password invalidation.\n";
