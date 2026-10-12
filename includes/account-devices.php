<?php
declare(strict_types=1);
require_once __DIR__.'/database.php';
function device_kind(?string $agent=null): string {
    $agent=$agent??($_SERVER['HTTP_USER_AGENT']??'');
    return preg_match('/Mobile|Android|iPhone|iPad|iPod/i',$agent)?'mobile':'desktop';
}
function device_claim(PDO $db,array $user,string $kind,string $token,bool $replace=false): bool {
    if(!in_array($kind,['desktop','mobile'],true)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new InvalidArgumentException('Invalid device');
    $db->beginTransaction();
    try {
        $q=$db->prepare('SELECT auth_version FROM users WHERE id=?'.($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?' FOR UPDATE':''));$q->execute([$user['id']]);$version=$q->fetchColumn();
        if($version===false||(int)$version!==(int)$user['auth_version']){$db->rollBack();return false;}
        $q=$db->prepare('SELECT token_hash,expires_at,auth_version FROM account_devices WHERE user_id=? AND kind=?');$q->execute([$user['id'],$kind]);$existing=$q->fetch();$hash=hash('sha256',$token);
        if($existing&&(int)$existing['expires_at']>time()&&(int)$existing['auth_version']===(int)$version&&!hash_equals($existing['token_hash'],$hash)&&!$replace){$db->rollBack();return false;}
        $db->prepare('INSERT INTO account_devices(user_id,kind,token_hash,auth_version,expires_at) VALUES(?,?,?,?,?) ON CONFLICT(user_id,kind) DO UPDATE SET token_hash=excluded.token_hash,auth_version=excluded.auth_version,expires_at=excluded.expires_at')->execute([$user['id'],$kind,$hash,(int)$version,time()+30*86400]);
        $db->commit();return true;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function device_valid(PDO $db,array $user,string $token,string $kind): bool {
    $q=$db->prepare('SELECT token_hash,auth_version,expires_at FROM account_devices WHERE user_id=? AND kind=?');$q->execute([$user['id'],$kind]);$row=$q->fetch();
    return $row&&(int)$row['expires_at']>time()&&(int)$row['auth_version']===(int)$user['auth_version']&&hash_equals($row['token_hash'],hash('sha256',$token));
}
function device_release(PDO $db,int $user,string $token,string $kind): void {
    $db->prepare('DELETE FROM account_devices WHERE user_id=? AND kind=? AND token_hash=?')->execute([$user,$kind,hash('sha256',$token)]);
}
