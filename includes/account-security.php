<?php
declare(strict_types=1);
require_once __DIR__.'/password-reset.php';

function account_security_update(PDO $db, array $user, string $current, ?string $password): int {
    if ($current==='' || strlen($current)>72) throw new InvalidArgumentException('Ingresa tu contraseña actual.');
    if ($password!==null && (strlen($password)<10 || strlen($password)>72)) throw new InvalidArgumentException('Usa una contraseña de 10 a 72 bytes.');
    $key='security:'.hash('sha256',(string)$user['id']);
    $db->prepare('DELETE FROM login_attempts WHERE key=? AND started<?')->execute([$key,time()-900]);
    $s=$db->prepare('INSERT INTO login_attempts(key,attempts,started) VALUES(?,1,?) ON CONFLICT(key) DO UPDATE SET attempts=login_attempts.attempts+1 RETURNING attempts');$s->execute([$key,time()]);$attempts=(int)$s->fetchColumn();$s->closeCursor();
    if ($attempts>10) throw new InvalidArgumentException('Demasiados intentos. Espera 15 minutos para volver a intentar.');
    $s=$db->prepare('SELECT password_hash,auth_version FROM users WHERE id=?');$s->execute([$user['id']]);$row=$s->fetch();
    if (!$row || (int)$row['auth_version']!==(int)$user['auth_version']) throw new RuntimeException('Tu sesión cambió. Vuelve a ingresar.');
    if (!password_verify($current,$row['password_hash'])) throw new InvalidArgumentException('La contraseña actual no es correcta.');
    if ($password!==null && password_verify($password,$row['password_hash'])) throw new InvalidArgumentException('Elige una contraseña diferente de la actual.');
    $db->beginTransaction();
    try {
        $s=$db->prepare('UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE id=? AND auth_version=? RETURNING auth_version');
        $s->execute([$password===null?$row['password_hash']:password_hash($password,PASSWORD_DEFAULT),$user['id'],$row['auth_version']]);$version=$s->fetchColumn();$s->closeCursor();
        if ($version===false) throw new RuntimeException('Tu sesión cambió. Vuelve a ingresar.');
        $db->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$user['id']]);
        $db->prepare('DELETE FROM login_attempts WHERE key=?')->execute([$key]);
        $db->commit();return (int)$version;
    } catch(Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
}

