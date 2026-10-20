<?php
declare(strict_types=1);
require_once __DIR__.'/database.php';
require_once __DIR__.'/beta-settings.php';
function device_storage_kind(PDO $db,int $user,string $kind,string $token): string {
    if($kind!=='mobile'||!REPOTOOLS_BETA_MANAGER_MULTI_MOBILE)return $kind;
    $q=$db->prepare('SELECT role FROM users WHERE id=?');$q->execute([$user]);
    return $q->fetchColumn()==='manager'?'mobile:'.hash('sha256',$token):$kind;
}
function device_kind(?string $agent=null): string {
    $agent=$agent??($_SERVER['HTTP_USER_AGENT']??'');
    // Tablets occupy the workstation slot so a phone can remain the companion reader.
    if(preg_match('/iPad/i',$agent)||preg_match('/Android/i',$agent)&&!preg_match('/Mobile/i',$agent))return 'desktop';
    return preg_match('/Mobile|Android|iPhone|iPod/i',$agent)?'mobile':'desktop';
}
function device_claim(PDO $db,array $user,string $kind,string $token,bool $replace=false): bool {
    if(!in_array($kind,['desktop','mobile'],true)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new InvalidArgumentException('Invalid device');
    $db->beginTransaction();
    try {
        $q=$db->prepare('SELECT auth_version FROM users WHERE id=?'.($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?' FOR UPDATE':''));$q->execute([$user['id']]);$version=$q->fetchColumn();
        if($version===false||(int)$version!==(int)$user['auth_version']){$db->rollBack();return false;}
        $kind=device_storage_kind($db,(int)$user['id'],$kind,$token);
        if(str_starts_with($kind,'mobile:'))$db->prepare("DELETE FROM account_devices WHERE user_id=? AND kind LIKE 'mobile:%' AND (expires_at<=? OR auth_version<>?)")->execute([$user['id'],time(),(int)$version]);
        $q=$db->prepare('SELECT token_hash,expires_at,auth_version FROM account_devices WHERE user_id=? AND kind=?');$q->execute([$user['id'],$kind]);$existing=$q->fetch();$hash=hash('sha256',$token);
        if($existing&&(int)$existing['expires_at']>time()&&(int)$existing['auth_version']===(int)$version&&!hash_equals($existing['token_hash'],$hash)&&!$replace){$db->rollBack();return false;}
        $db->prepare('INSERT INTO account_devices(user_id,kind,token_hash,auth_version,expires_at) VALUES(?,?,?,?,?) ON CONFLICT(user_id,kind) DO UPDATE SET token_hash=excluded.token_hash,auth_version=excluded.auth_version,expires_at=excluded.expires_at')->execute([$user['id'],$kind,$hash,(int)$version,time()+30*86400]);
        $db->commit();return true;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function device_valid(PDO $db,array $user,string $token,string $kind): bool {
    $slot=device_storage_kind($db,(int)$user['id'],$kind,$token);
    $q=$db->prepare('SELECT token_hash,auth_version,expires_at FROM account_devices WHERE user_id=? AND kind IN (?,?)');$q->execute([$user['id'],$kind,$slot]);
    foreach($q->fetchAll() as $row)if((int)$row['expires_at']>time()&&(int)$row['auth_version']===(int)$user['auth_version']&&hash_equals($row['token_hash'],hash('sha256',$token)))return true;
    return false;
}
function device_renew(PDO $db,array $user,string $token,string $kind): void {
    $slot=device_storage_kind($db,(int)$user['id'],$kind,$token);
    $db->prepare('UPDATE account_devices SET expires_at=? WHERE user_id=? AND kind IN (?,?) AND token_hash=? AND auth_version=?')->execute([time()+30*86400,$user['id'],$kind,$slot,hash('sha256',$token),$user['auth_version']]);
}
function device_release(PDO $db,int $user,string $token,string $kind): void {
    $slot=$kind==='mobile'?'mobile:'.hash('sha256',$token):$kind;
    $db->prepare('DELETE FROM account_devices WHERE user_id=? AND kind IN (?,?) AND token_hash=?')->execute([$user,$kind,$slot,hash('sha256',$token)]);
}
