<?php
declare(strict_types=1);

class ReferralError extends RuntimeException { public function __construct(string $message,public int $status=422){parent::__construct($message);} }
function referrals_enabled(): bool {
    if(getenv('REPOTOOLS_REFERRALS_ENABLED')==='0')return false;
    $origin=parse_url(getenv('APP_URL')?:'https://repotools.online');
    return in_array($origin['scheme']??'',['https','http'],true)&&!empty($origin['host']);
}
function referral_q(PDO $db,string $sql,array $params=[]): PDOStatement {if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'&&!$db->inTransaction())$db->exec('PRAGMA foreign_keys=ON');$q=$db->prepare($sql);$q->execute($params);return $q;}
function referral_lock(PDO $db): void {if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql')$db->query('SELECT pg_advisory_xact_lock(72684639)');else $db->exec('UPDATE referral_mutex SET revision=revision+1 WHERE id=1');}
function referral_schema(PDO $db,bool $install=false): void {
    if(!$install&&!referrals_enabled())throw new ReferralError('Referidos no está habilitado.',503);
    $pg=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql';$id=$pg?'BIGSERIAL PRIMARY KEY':'INTEGER PRIMARY KEY AUTOINCREMENT';$ref=$pg?'BIGINT':'INTEGER';
    if(!$pg)$db->exec('PRAGMA foreign_keys=ON');
    $own=!$db->inTransaction();if($own)$db->beginTransaction();try{
        if($pg)$db->query('SELECT pg_advisory_xact_lock(72684639)');
        $db->exec('CREATE TABLE IF NOT EXISTS referral_mutex(id INTEGER PRIMARY KEY,revision INTEGER NOT NULL DEFAULT 0)');$db->exec('INSERT INTO referral_mutex(id) VALUES(1) ON CONFLICT(id) DO NOTHING');
        $db->exec("CREATE TABLE IF NOT EXISTS referral_codes(user_id {$ref} PRIMARY KEY REFERENCES users(id),code TEXT NOT NULL UNIQUE,created BIGINT NOT NULL)");
        $db->exec("CREATE TABLE IF NOT EXISTS referrals(id {$id},referrer_id {$ref} NOT NULL REFERENCES users(id),invitee_id {$ref} NOT NULL UNIQUE REFERENCES users(id),registered_at BIGINT NOT NULL,validated_at BIGINT,email_verified_at BIGINT,email_hash TEXT NOT NULL,state TEXT NOT NULL CHECK(state IN ('pending','validated','review','rejected')),risk TEXT NOT NULL DEFAULT '',reason TEXT NOT NULL DEFAULT '',ip_signal TEXT NOT NULL DEFAULT '',signal_until BIGINT NOT NULL,revision INTEGER NOT NULL DEFAULT 0,CHECK(referrer_id<>invitee_id),CONSTRAINT referrals_validation_consistency CHECK((state='validated' AND validated_at IS NOT NULL AND email_verified_at IS NOT NULL) OR (state<>'validated' AND validated_at IS NULL)))");
        if($pg&&!referral_q($db,"SELECT 1 FROM pg_constraint WHERE conname='referrals_validation_consistency' AND conrelid='referrals'::regclass")->fetchColumn())$db->exec("ALTER TABLE referrals ADD CONSTRAINT referrals_validation_consistency CHECK((state='validated' AND validated_at IS NOT NULL AND email_verified_at IS NOT NULL) OR (state<>'validated' AND validated_at IS NULL))");
        $db->exec('CREATE INDEX IF NOT EXISTS referrals_ranking ON referrals(state,validated_at,referrer_id)');$db->exec('CREATE INDEX IF NOT EXISTS referrals_signal ON referrals(ip_signal,registered_at)');
        $db->exec("CREATE TABLE IF NOT EXISTS referral_decisions(id {$id},referral_id {$ref} NOT NULL REFERENCES referrals(id),actor_id {$ref} REFERENCES users(id),at BIGINT NOT NULL,previous_state TEXT NOT NULL,new_state TEXT NOT NULL,reason TEXT NOT NULL)");
        $db->exec("CREATE TABLE IF NOT EXISTS referral_verifications(token_hash TEXT PRIMARY KEY,referral_id {$ref} NOT NULL REFERENCES referrals(id),email_hash TEXT NOT NULL,expires BIGINT NOT NULL)");
        $db->exec('CREATE TABLE IF NOT EXISTS referral_limits(key TEXT PRIMARY KEY,attempts INTEGER NOT NULL,started BIGINT NOT NULL)');if($own)$db->commit();
    }catch(Throwable $e){if($own&&$db->inTransaction())$db->rollBack();throw $e;}
}
function referral_origin(): string {if(!referrals_enabled())throw new ReferralError('Referidos no está habilitado.',503);return rtrim(getenv('APP_URL')?:'https://repotools.online','/');}
function referral_code(PDO $db,int $user): string {
    $old=referral_q($db,'SELECT code FROM referral_codes WHERE user_id=?',[$user])->fetchColumn();if($old!==false)return $old;
    for($i=0;$i<8;$i++){
        $code=strtoupper(bin2hex(random_bytes(6)));
        // ON CONFLICT handles simultaneous first visits without changing the permanent code.
        referral_q($db,'INSERT INTO referral_codes(user_id,code,created) VALUES(?,?,?) ON CONFLICT DO NOTHING',[$user,$code,time()]);
        $old=referral_q($db,'SELECT code FROM referral_codes WHERE user_id=?',[$user])->fetchColumn();if($old!==false)return $old;
    }throw new ReferralError('No pudimos preparar tu invitación. Intentá nuevamente.',503);
}
function referral_normalize($code): string {return is_string($code)&&preg_match('/^[A-Z0-9]{12}$/D',strtoupper(trim($code)))?strtoupper(trim($code)):'';}
function referral_capture(PDO $db,$code): bool {
    $code=referral_normalize($code);if(!$code||!referral_q($db,'SELECT user_id FROM referral_codes WHERE code=?',[$code])->fetchColumn())return false;
    $_SESSION['referral_invitation']=['code'=>$code,'expires'=>time()+7*86400];return true;
}
function referral_signal(array $server): string {
    // Trust only the socket peer unless an explicitly configured proxy matches it.
    if(strlen(getenv('REFERRALS_SIGNAL_KEY')?:'')<32)return '';
    $peer=$server['REMOTE_ADDR']??'';$trusted=array_filter(explode(',',getenv('REFERRALS_TRUSTED_PROXIES')?:''));
    if(in_array($peer,$trusted,true)){
        $hops=explode(',',(string)($server['HTTP_X_FORWARDED_FOR']??''));
        for($i=count($hops)-1;$i>=0;$i--){$hop=trim($hops[$i]);if(!filter_var($hop,FILTER_VALIDATE_IP))break;if(!in_array($hop,$trusted,true)){$peer=$hop;break;}}
    }
    return hash_hmac('sha256',filter_var($peer,FILTER_VALIDATE_IP)?$peer:'unknown',getenv('REFERRALS_SIGNAL_KEY')?:'');
}
function referral_limit(PDO $db,string $key,int $max,int $seconds): void {
    referral_q($db,'DELETE FROM referral_limits WHERE key=? AND started<?',[$key,time()-$seconds]);
    $q=referral_q($db,'INSERT INTO referral_limits(key,attempts,started) VALUES(?,1,?) ON CONFLICT(key) DO UPDATE SET attempts=referral_limits.attempts+1 RETURNING attempts',[$key,time()]);$n=(int)$q->fetchColumn();$q->closeCursor();
    if($n>$max)throw new ReferralError('Demasiados intentos. Esperá un momento y volvé a probar.',429);
}
function referral_attach(PDO $db,int $invitee,string $email,string $code,array $server,int $registeredAt): ?int {
    $referrer=referral_q($db,'SELECT user_id FROM referral_codes WHERE code=?',[referral_normalize($code)])->fetchColumn();if(!$referrer)return null;
    if((int)$referrer===$invitee)throw new ReferralError('No podés invitarte a vos mismo.');
    $created=referral_q($db,'SELECT created_at FROM users WHERE id=?',[$invitee])->fetchColumn();
    if(!$created||abs(time()-(new DateTimeImmutable($created,new DateTimeZone('UTC')))->getTimestamp())>60)throw new ReferralError('Una cuenta existente no puede convertirse en referido.');
    referral_maintenance($db);$signal=referral_signal($server);$burst=(int)referral_q($db,'SELECT COUNT(*) FROM referrals WHERE ip_signal=? AND registered_at>?',[$signal,time()-900])->fetchColumn();
    $risk=$burst>=8?'Ráfaga de registros en 15 minutos':(!empty($server['referral_honeypot'])?'Formulario automatizado':'');
    $q=referral_q($db,'INSERT INTO referrals(referrer_id,invitee_id,registered_at,email_hash,state,risk,ip_signal,signal_until) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(invitee_id) DO NOTHING RETURNING id',[(int)$referrer,$invitee,$registeredAt,hash('sha256',strtolower(trim($email))),!empty($server['referral_honeypot'])?'review':'pending',$risk,$signal,time()+30*86400]);$id=$q->fetchColumn();$q->closeCursor();
    return $id===false?null:(int)$id;
}
function referral_mail_send(string $email,string $token): bool {
    $key=getenv('RESEND_API_KEY')?:'';$from=getenv('MAIL_FROM')?:'';if(!$key||!$from)return false;
    $url=referral_origin().'/verificar-referido.php?token='.$token;$safe=htmlspecialchars($url,ENT_QUOTES,'UTF-8');
    require_once __DIR__.'/email-layout.php';$html=email_layout('Confirmá tu correo','<p>Para validar tu registro desde una invitación, confirmá esta dirección.</p>'.email_button($url,'Confirmar correo').'<p>El enlace vence en 24 horas. Si no creaste una cuenta, podés ignorarlo.</p>');
    $curl=curl_init('https://api.resend.com/emails');curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json','Idempotency-Key: referral/'.hash('sha256',$token)],CURLOPT_POSTFIELDS=>json_encode(['from'=>$from,'to'=>[$email],'subject'=>'Confirmá tu correo de Repotools','html'=>$html,'text'=>'Confirmá tu correo: '.$url.' El enlace vence en 24 horas.'])]);curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);return $status>=200&&$status<300;
}
function referral_issue(PDO $db,int $user): array {require_once __DIR__.'/email-verification.php';return email_request($db,$user);}

function referral_verify(PDO $db,string $token): bool {
    if(!preg_match('/^[a-f0-9]{64}$/D',$token))return false;
    $db->beginTransaction();try{referral_lock($db);
        $q=referral_q($db,'DELETE FROM referral_verifications WHERE token_hash=? AND expires>? RETURNING referral_id,email_hash',[hash('sha256',$token),time()]);$proof=$q->fetch();$q->closeCursor();if(!$proof){$db->rollBack();return false;}
        $row=referral_q($db,'SELECT r.*,u.email FROM referrals r JOIN users u ON u.id=r.invitee_id WHERE r.id=?',[$proof['referral_id']])->fetch();
        if(!hash_equals($proof['email_hash'],hash('sha256',strtolower(trim($row['email']))))){$db->rollBack();return false;}
        if(!$row['email_verified_at']){
            $state=$row['state']==='pending'?'validated':$row['state'];$at=time();
            referral_q($db,'UPDATE referrals SET email_verified_at=?,email_hash=?,state=?,validated_at=?,revision=revision+1 WHERE id=?',[$at,$proof['email_hash'],$state,$state==='validated'?$at:null,$row['id']]);
            referral_q($db,'INSERT INTO referral_decisions(referral_id,actor_id,at,previous_state,new_state,reason) VALUES(?,NULL,?,?,?,?)',[$row['id'],$at,$row['state'],$state,'Correo confirmado']);
        }require_once __DIR__.'/audit.php';$verifiedAt=(int)($row['email_verified_at']?:time());referral_q($db,'UPDATE users SET email_verified_at=COALESCE(email_verified_at,?) WHERE id=?',[$verifiedAt,$row['invitee_id']]);audit_event($db,(int)$row['invitee_id'],'email_verified','legacy-email:'.$row['id'],['category'=>'users'],[],['verified'=>true]);$final=referral_q($db,'SELECT state FROM referrals WHERE id=?',[$row['id']])->fetchColumn();if($final==='validated')audit_event($db,(int)$row['referrer_id'],'referral_credited','referral:'.$row['id'],['category'=>'users','target_user'=>(int)$row['invitee_id']],[],['verified'=>true]);$db->commit();return true;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function referral_admin(PDO $db,int $actor): string {
    $role=referral_q($db,'SELECT role FROM users WHERE id=?',[$actor])->fetchColumn();if(!in_array($role,['administrador','manager'],true))throw new ReferralError('No tenés permiso para revisar referidos.',403);return $role;
}
function referral_decide(PDO $db,int $actor,int $id,string $state,string $reason,int $revision): void {
    $role=referral_admin($db,$actor);if(!in_array($state,['validated','rejected','review'],true)||mb_strlen(trim($reason))<3||mb_strlen($reason)>300)throw new ReferralError('Indicá un motivo de 3 a 300 caracteres.');
    $db->beginTransaction();try{referral_lock($db);$r=referral_q($db,'SELECT * FROM referrals WHERE id=?',[$id])->fetch();if(!$r)throw new ReferralError('No encontramos esa invitación.',404);
        if($role!=='manager'&&in_array($actor,[(int)$r['referrer_id'],(int)$r['invitee_id']],true))throw new ReferralError('Otro administrador debe revisar esta invitación.',403);
        if((int)$r['revision']!==$revision)throw new ReferralError('La invitación cambió. Actualizá antes de decidir.',409);
        if(in_array($r['state'],['validated','rejected'],true)&&$role!=='manager')throw new ReferralError('Solo un manager puede revertir una decisión.',403);
        if($state==='validated'&&!$r['email_verified_at'])throw new ReferralError('Primero debe confirmar su correo.');
        if($state===$r['state']){$db->commit();return;}$at=time();
        referral_q($db,'UPDATE referrals SET state=?,reason=?,validated_at=?,revision=revision+1 WHERE id=?',[$state,trim($reason),$state==='validated'?(int)$r['email_verified_at']:null,$id]);
        referral_q($db,'INSERT INTO referral_decisions(referral_id,actor_id,at,previous_state,new_state,reason) VALUES(?,?,?,?,?,?)',[$id,$actor,$at,$r['state'],$state,trim($reason)]);require_once __DIR__.'/audit.php';audit_event($db,$actor,'referral_decision','referral-decision:'.$id.':'.($revision+1),['category'=>'users','target_user'=>(int)$r['invitee_id']],['state'=>$r['state']],['state'=>$state,'reason'=>trim($reason)]);if($state==='validated')audit_event($db,(int)$r['referrer_id'],'referral_credited','referral:'.$id,['category'=>'users','target_user'=>(int)$r['invitee_id']],[],['verified'=>true]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function referral_period(string $period,string $date=''): array {
    if(!in_array($period,['semana','mes','historico'],true))throw new ReferralError('Período no válido.');$zone=new DateTimeZone('America/Argentina/Buenos_Aires');
    if($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))))throw new ReferralError('Fecha no válida.');
    $day=new DateTimeImmutable($date?:'today',$zone);if($day>new DateTimeImmutable('today',$zone))throw new ReferralError('Elegí un período actual o anterior.');
    if($period==='historico')return [0,PHP_INT_MAX,'Histórico'];$start=$period==='mes'?$day->modify('first day of this month'):$day->modify('monday this week');$start=$start->setTime(0,0);$end=$start->modify($period==='mes'?'+1 month':'+1 week');return [$start->getTimestamp(),$end->getTimestamp(),$start->format('d/m/Y').' – '.$end->modify('-1 day')->format('d/m/Y')];
}
function referral_rank_sql(): string {return "WITH scores AS (SELECT referrer_id,COUNT(*) AS total,MAX(email_verified_at) AS achieved FROM referrals WHERE state='validated' AND email_verified_at>=? AND email_verified_at<? GROUP BY referrer_id),ranked AS (SELECT referrer_id,total,RANK() OVER(ORDER BY total DESC) AS position FROM scores) ";}
function referral_position(PDO $db,string $period,int $user): ?array {[$start,$end]=referral_period($period);return referral_q($db,referral_rank_sql().'SELECT position,total FROM ranked WHERE referrer_id=?',[$start,$end,$user])->fetch()?:null;}
function referral_ranking(PDO $db,string $period,string $date,int $user,int $page): array {
    [$start,$end,$label]=referral_period($period,$date);$cte=referral_rank_sql();
    $rows=referral_q($db,$cte.'SELECT r.position,r.total,u.public_name,u.name,u.id FROM ranked r JOIN users u ON u.id=r.referrer_id ORDER BY r.position LIMIT 51 OFFSET '.(($page-1)*50),[$start,$end])->fetchAll();$more=count($rows)>50;$rows=array_slice($rows,0,50);
    foreach($rows as &$row){$row['name']=get_public_user_name($row);unset($row['id'],$row['public_name']);}unset($row);
    $own=$user?referral_q($db,$cte.'SELECT position,total FROM ranked WHERE referrer_id=?',[$start,$end,$user])->fetch():null;
    return ['rows'=>$rows,'own'=>$own?:null,'has_more'=>$more,'label'=>$label,'page'=>$page];
}
function referral_stats(PDO $db,int $user): array {
    $counts=['pending'=>0,'validated'=>0,'review'=>0,'rejected'=>0];foreach(referral_q($db,'SELECT state,COUNT(*) AS total FROM referrals WHERE referrer_id=? GROUP BY state',[$user])->fetchAll() as $row)$counts[$row['state']]=(int)$row['total'];
    $code=referral_code($db,$user);$positions=[];foreach(['semana','mes','historico'] as $period)$positions[$period]=referral_position($db,$period,$user);
    return ['code'=>$code,'link'=>referral_origin().'/r/'.$code,'confirmed'=>referral_q($db,"SELECT u.public_name AS name,r.email_verified_at AS validated_at FROM referrals r JOIN users u ON u.id=r.invitee_id WHERE r.referrer_id=? AND r.state='validated' AND r.email_verified_at IS NOT NULL ORDER BY r.email_verified_at DESC LIMIT 100",[$user])->fetchAll(),'counts'=>['validated'=>$counts['validated']],'positions'=>$positions,'verification_needed'=>(bool)referral_q($db,'SELECT id FROM referrals WHERE invitee_id=? AND email_verified_at IS NULL',[$user])->fetchColumn()];
}

function referral_maintenance(PDO $db): void {
    referral_q($db,"UPDATE referrals SET ip_signal='' WHERE signal_until<? AND ip_signal<>''",[time()]);
    referral_q($db,'DELETE FROM referral_verifications WHERE expires<?',[time()]);
    referral_q($db,'DELETE FROM referral_limits WHERE started<?',[time()-86400]);
}
