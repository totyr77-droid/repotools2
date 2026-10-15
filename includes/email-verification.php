<?php
declare(strict_types=1);
require_once __DIR__.'/referrals.php';
require_once __DIR__.'/audit.php';
function welcome_email(string $name,string $url): array {
    $e=fn($s)=>htmlspecialchars($s,ENT_QUOTES,'UTF-8');$origin=rtrim(getenv('APP_URL')?:'https://repotools.online','/');
    $html='<!doctype html><html lang="es"><meta name="viewport" content="width=device-width,initial-scale=1"><body style="margin:0;background:#f5f6fb;font-family:Arial,sans-serif;color:#172033"><table role="presentation" width="100%"><tr><td align="center" style="padding:20px 12px"><table role="presentation" width="100%" style="max-width:560px"><tr><td bgcolor="#27187e" style="padding:22px;border-radius:14px 14px 0 0"><img width="180" alt="RepoTools" src="'.$e($origin).'/img/Repotools_Logo_Pack_Blanco/06_horizontal_blanco_transparente/repotools-horizontal-blanco-320w.png"></td></tr><tr><td bgcolor="#ffffff" style="padding:24px;border-radius:0 0 14px 14px"><h1 style="font-size:23px;color:#27187e">Hola, '.$e($name).'</h1><p style="line-height:1.6">Bienvenido a RepoTools. Tu cuenta ya está lista para trabajar. Confirmá tu correo para proteger tu información y validar tu invitación, si llegaste con una.</p><table role="presentation" width="100%"><tr><td bgcolor="#27187e" style="border-radius:10px;text-align:center"><a href="'.$e($url).'" style="display:block;padding:16px;color:white;text-decoration:none;font-weight:bold">Verificar mi correo</a></td></tr></table><p style="font-size:13px;color:#626b7a">El enlace dura 24 horas y se usa una sola vez. Podés seguir usando la app mientras tanto.</p><table role="presentation" width="100%">';
    $text='Hola, '.$name.'. Tu cuenta de RepoTools ya está disponible. Verificá tu correo: '.$url."\nEl enlace vence en 24 horas. No hace falta verificar para usar la app.\n";
    foreach(['pallets'=>['Organizá tus palets','Creá palets y agrupá tu mercadería.'],'ean'=>['Escaneá productos','Leé códigos EAN con tu cámara.'],'stock'=>['Consultá tu stock','Revisá cantidades y vencimientos.'],'qr'=>['Accedé con QR','Consultá el palet o compartí su información.']] as $asset=>[$title,$description]){
        $html.='<tr><td width="88" style="padding:10px 12px 10px 0"><img width="80" height="64" src="'.$e($origin).'/img/email/'.$asset.'.png" alt="'.$e($title).'" style="display:block;border:0"></td><td style="padding:10px 0;font-size:14px;line-height:1.5"><strong style="color:#27187e">'.$e($title).'</strong><br>'.$e($description).'</td></tr>';$text.=$title.': '.$description."\n";
    }
    $html.='</table><p style="font-size:12px;line-height:1.6;color:#626b7a">Si no creaste esta cuenta ni pediste verificar una dirección, ignorá el mensaje. No compartas este enlace.</p><p style="font-size:12px;word-break:break-all">Si el botón no funciona: <a href="'.$e($url).'">'.$e($url).'</a></p></td></tr></table></td></tr></table></body></html>';
    return ['html'=>$html,'text'=>$text.'Si no solicitaste el registro, ignorá este correo.'];
}
function email_request(PDO $db,int $user,?string $newEmail=null): array {
    $db->beginTransaction();try{
        referral_lock($db);$u=audit_q($db,'SELECT name,email,email_verified_at,pending_email FROM users WHERE id=?',[$user])->fetch();if(!$u)throw new ReferralError('Ingresá para continuar.',401);
        $address=$newEmail!==null?strtolower(trim($newEmail)):($u['pending_email']?:$u['email']);
        if(!filter_var($address,FILTER_VALIDATE_EMAIL)||strlen($address)>254)throw new ReferralError('Ingresá un correo válido.');
        if($address===$u['email']&&$u['email_verified_at']){$db->commit();return ['needed'=>false,'sent'=>false];}
        $now=time();$limit=audit_q($db,'SELECT * FROM email_delivery_limits WHERE user_id=?',[$user])->fetch();
        if($limit&&((int)$limit['last_sent']>$now-60||((int)$limit['window_start']>$now-3600&&(int)$limit['attempts']>=5)))throw new ReferralError('Esperá al menos 60 segundos. Podés pedir hasta 5 correos por hora.',429);
        audit_q($db,'INSERT INTO email_delivery_limits(user_id,last_sent,window_start,attempts) VALUES(?,?,?,1) ON CONFLICT(user_id) DO UPDATE SET last_sent=excluded.last_sent,window_start=excluded.window_start,attempts=?',[$user,$now,$limit&&(int)$limit['window_start']>$now-3600?(int)$limit['window_start']:$now,$limit&&(int)$limit['window_start']>$now-3600?(int)$limit['attempts']+1:1]);
        if(audit_q($db,'SELECT id FROM users WHERE LOWER(email)=? AND id<>?',[$address,$user])->fetchColumn()){$db->commit();return ['needed'=>true,'sent'=>false,'new_address'=>true];}
        if($address!==$u['email'])audit_q($db,'UPDATE users SET pending_email=? WHERE id=?',[$address,$user]);
        // A resend supersedes unused links. Used hashes remain to distinguish replays.
        audit_q($db,'UPDATE email_verifications SET expires=? WHERE user_id=? AND used_at IS NULL',[$now-1,$user]);
        $token=bin2hex(random_bytes(32));audit_q($db,'INSERT INTO email_verifications(token_hash,user_id,address,expires,created) VALUES(?,?,?,?,?)',[hash('sha256',$token),$user,$address,$now+86400,$now]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    $url=rtrim(getenv('APP_URL')?:'https://repotools.online','/').'/verificar-correo.php#verificar='.$token;$mail=welcome_email($u['name'],$url);$key=getenv('RESEND_API_KEY')?:'';$from=getenv('MAIL_FROM')?:'';
    if(!$key||!$from||!function_exists('curl_init'))return ['needed'=>true,'sent'=>false];
    $c=curl_init('https://api.resend.com/emails');curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json','Idempotency-Key: welcome/'.hash('sha256',$token)],CURLOPT_POSTFIELDS=>json_encode(['from'=>$from,'to'=>[$address],'subject'=>'Bienvenido a RepoTools · Verificá tu correo',...$mail])]);curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);return ['needed'=>true,'sent'=>$status>=200&&$status<300];
}
function email_verify(PDO $db,string $token): string {
    if(!preg_match('/^[a-f0-9]{64}$/D',$token))return 'invalid';
    $db->beginTransaction();try{referral_lock($db);$p=audit_q($db,'SELECT * FROM email_verifications WHERE token_hash=?',[hash('sha256',$token)])->fetch();
        if(!$p){$db->rollBack();return 'invalid';}if($p['used_at']){$db->rollBack();return 'used';}if((int)$p['expires']<=time()){$db->rollBack();return 'expired';}
        $u=audit_q($db,'SELECT email,pending_email,email_verified_at FROM users WHERE id=?',[$p['user_id']])->fetch();if(!$u||!in_array($p['address'],[$u['email'],$u['pending_email']],true)){$db->rollBack();return 'invalid';}
        if(audit_q($db,'SELECT id FROM users WHERE LOWER(email)=LOWER(?) AND id<>?',[$p['address'],$p['user_id']])->fetchColumn()){$db->rollBack();return 'unavailable';}
        $now=time();if($u['email']!==$p['address'])audit_q($db,'DELETE FROM password_resets WHERE user_id=?',[$p['user_id']]);audit_q($db,'UPDATE users SET email=?,pending_email=NULL,email_verified_at=? WHERE id=?',[$p['address'],$now,$p['user_id']]);audit_q($db,'UPDATE email_verifications SET used_at=? WHERE token_hash=?',[$now,$p['token_hash']]);
        audit_event($db,(int)$p['user_id'],'email_verified','email:'.$p['user_id'].':'.$p['created'],['category'=>'users'],['verified'=>(bool)$u['email_verified_at']],['verified'=>true]);
        $r=audit_q($db,'SELECT * FROM referrals WHERE invitee_id=?',[$p['user_id']])->fetch();
        if($r&&!$r['email_verified_at']){$state=$r['state']==='pending'?'validated':$r['state'];audit_q($db,'UPDATE referrals SET email_verified_at=?,email_hash=?,state=?,validated_at=?,revision=revision+1 WHERE id=?',[$now,hash('sha256',strtolower($p['address'])),$state,$state==='validated'?$now:null,$r['id']]);audit_q($db,'INSERT INTO referral_decisions(referral_id,actor_id,at,previous_state,new_state,reason) VALUES(?,NULL,?,?,?,?)',[$r['id'],$now,$r['state'],$state,'Correo confirmado']);if($state==='validated')audit_event($db,(int)$r['referrer_id'],'referral_credited','referral:'.$r['id'],['category'=>'users','target_user'=>(int)$p['user_id']],[],['verified'=>true]);}
        $db->commit();return 'verified';
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
