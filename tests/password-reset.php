<?php
require __DIR__.'/../includes/database.php';require __DIR__.'/../includes/password-reset.php';
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);initialize_database($db);
$db->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute(['Persona','test@example.invalid',password_hash('old-password-123',PASSWORD_DEFAULT)]);
$token=reset_issue($db,1);$second=reset_issue($db,1);
function check($value){if(!$value)throw new Exception('Falló la recuperación.');}
check(!reset_password($db,$token,'corta'));check(reset_password($db,$token,'new-password-123'));check(!reset_password($db,$token,'another-password'));check(!reset_password($db,$second,'another-password'));
check(password_verify('new-password-123',$db->query('SELECT password_hash FROM users')->fetchColumn()));check((int)$db->query('SELECT auth_version FROM users')->fetchColumn()===2);
$expired=reset_issue($db,1);$db->exec('UPDATE password_resets SET expires_at=0');check(!reset_password($db,$expired,'another-password'));check(!reset_password($db,str_repeat('a',64),'another-password'));
echo "OK: token único, caducidad, invalidación de otros enlaces y sesiones, contraseña segura.\n";
