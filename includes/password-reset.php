<?php
declare(strict_types=1);
function reset_issue(PDO $db, int $user): string {
    $token=bin2hex(random_bytes(32));
    $db->prepare('INSERT INTO password_resets(token_hash,user_id,expires_at) VALUES(?,?,?)')->execute([hash('sha256',$token),$user,time()+1800]);
    return $token;
}
function reset_password(PDO $db,string $token,string $password): bool {
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)||strlen($password)<10||strlen($password)>72) return false;
    $db->beginTransaction();
    try {
        // DELETE RETURNING makes concurrent consumption atomic on both database engines.
        $query=$db->prepare('DELETE FROM password_resets WHERE token_hash=? AND expires_at>? RETURNING user_id');
        $query->execute([hash('sha256',$token),time()]); $user=$query->fetchColumn();$query->closeCursor();
        if ($user===false) {$db->rollBack();return false;}
        $db->prepare('UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$user]);
        $db->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$user]);
        $db->commit();return true;
    } catch(Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
}
function reset_send(string $email,string $token): bool {
    $key=getenv('RESEND_API_KEY')?:'';$from=getenv('MAIL_FROM')?:'';$origin=rtrim(getenv('APP_URL')?:'','/');
    if (!$key||!$from||!preg_match('~^https://[^\s]+$~D',$origin)) return false;
    $url=$origin.'/recuperar.php?token='.$token;
    $curl=curl_init('https://api.resend.com/emails');
    curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json','Idempotency-Key: reset/'.hash('sha256',$token)],CURLOPT_POSTFIELDS=>json_encode(['from'=>$from,'to'=>[$email],'subject'=>'Recuperá tu acceso a Repotools','text'=>"Para elegir una nueva contraseña abrí este enlace:\n$url\n\nVence en 30 minutos. Si no lo pediste, podés ignorarlo."])]);
    curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);return $status>=200&&$status<300;
}
