<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/referrals.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try{
    if(!referrals_enabled())throw new ReferralError('Referidos todavía no está habilitado en este entorno.',503);
    $user=current_user();$uid=(int)($user['id']??0);$db=database();referral_maintenance($db);$method=$_SERVER['REQUEST_METHOD'];
    if(!in_array($method,['GET','POST'],true))throw new ReferralError('Solicitud no válida.',405);
    if($method==='POST'){
        if(!$uid)throw new ReferralError('Ingresá para continuar.',401);if(!valid_csrf())throw new ReferralError('La sesión del formulario venció. Recargá la página.',403);
        if(($_POST['action']??'')==='resend'){$result=referral_issue($db,$uid);if(!empty($result['needed'])&&empty($result['sent']))throw new ReferralError('No pudimos enviar el correo. Tu invitación sigue pendiente; intentá más tarde.',503);}
        elseif(($_POST['action']??'')==='decision'){referral_decide($db,$uid,(int)($_POST['id']??0),(string)($_POST['state']??''),(string)($_POST['reason']??''),(int)($_POST['revision']??-1));$result=['saved'=>true];}
        else throw new ReferralError('Solicitud no válida.');
    }elseif(isset($_GET['admin'])){
        if(!$uid)throw new ReferralError('Ingresá para continuar.',401);referral_admin($db,$uid);referral_maintenance($db);
        $conditions=[];$params=[];$state=$_GET['state']??'';if($state!==''){if(!in_array($state,['pending','validated','review','rejected'],true))throw new ReferralError('Estado no válido.');$conditions[]='r.state=?';$params[]=$state;}
        if(!empty($_GET['referrer'])){$conditions[]='r.referrer_id=?';$params[]=(int)$_GET['referrer'];}
        foreach(['from'=>'>=','to'=>'<'] as $key=>$op)if(!empty($_GET[$key])){[,,$unused]=referral_period('historico',(string)$_GET[$key]);$at=(new DateTimeImmutable($_GET[$key],new DateTimeZone('America/Argentina/Buenos_Aires')))->setTime(0,0);if($key==='to')$at=$at->modify('+1 day');$conditions[]='r.registered_at'.$op.'?';$params[]=$at->getTimestamp();}
        $page=max(1,min(100000,(int)($_GET['page']??1)));$where=$conditions?' WHERE '.implode(' AND ',$conditions):'';
        $rows=referral_q($db,'SELECT r.id,r.registered_at,r.validated_at,r.email_verified_at,r.state,r.risk,r.reason,r.revision,a.public_name AS referrer,b.public_name AS invitee FROM referrals r JOIN users a ON a.id=r.referrer_id JOIN users b ON b.id=r.invitee_id'.$where.' ORDER BY r.registered_at DESC,r.id DESC LIMIT 26 OFFSET '.(($page-1)*25),$params)->fetchAll();$more=count($rows)>25;$rows=array_slice($rows,0,25);
        foreach($rows as &$row)$row['history']=referral_q($db,'SELECT d.at,d.previous_state,d.new_state,d.reason,u.public_name AS actor FROM referral_decisions d LEFT JOIN users u ON u.id=d.actor_id WHERE referral_id=? ORDER BY d.id DESC LIMIT 50',[$row['id']])->fetchAll();unset($row);
        $result=['rows'=>$rows,'page'=>$page,'has_more'=>$more,'referrers'=>referral_q($db,'SELECT DISTINCT u.id,u.public_name FROM referrals r JOIN users u ON u.id=r.referrer_id ORDER BY u.public_name')->fetchAll()];
    }else{
        $period=is_string($_GET['period']??null)?$_GET['period']:'semana';$date=is_string($_GET['date']??null)?$_GET['date']:'';$page=max(1,min(100000,(int)($_GET['page']??1)));
        $result=referral_ranking($db,$period,$date,$uid,$page);if($uid)$result['mine']=referral_stats($db,$uid);
    }
    echo json_encode($result+['csrf'=>csrf_token()],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(ReferralError $e){http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
catch(Throwable $e){error_log('Repotools referrals: '.get_class($e));http_response_code(503);echo json_encode(['error'=>'No pudimos consultar las invitaciones. Intentá más tarde.']);}
