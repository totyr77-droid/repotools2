<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/achievements.php';
require_once __DIR__.'/../includes/stock.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function achievement_reply(int $status,array $data): void {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
if(!current_user())achievement_reply(401,['error'=>'Iniciá sesión para continuar.']);
$user=(int)current_user()['id'];$csrf=csrf_token();$method=$_SERVER['REQUEST_METHOD'];
if(!in_array($method,['GET','POST'],true))achievement_reply(405,['error'=>'Solicitud no válida.']);
if($method==='POST'&&!valid_csrf())achievement_reply(403,['error'=>'Recargá la página para continuar.']);
session_write_close();
try{
    $db=database();
    if($method==='POST'){
        $action=$_POST['action']??'';$db->beginTransaction();
        if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql')achievement_q($db,'SELECT id FROM users WHERE id=? FOR UPDATE',[$user]);
        if($action==='announce'){
            $ids=json_decode(is_string($_POST['ids']??null)?$_POST['ids']:'',true);if(!is_array($ids)||count($ids)>72)throw new InvalidArgumentException('Avisos no válidos.');$announcements=[];foreach($ids as $id){if(!is_int($id)||$id<1)throw new InvalidArgumentException('Aviso no válido.');$row=achievement_q($db,"SELECT id,title,body FROM notifications WHERE user_id=? AND id=? AND type='achievement' AND read_at IS NULL",[$user,$id])->fetch();if($row&&achievement_q($db,'INSERT INTO achievement_receipts(user_id,operation_key) VALUES(?,?) ON CONFLICT(user_id,operation_key) DO NOTHING',[$user,'announcement:'.$id])->rowCount())$announcements[]=$row;}$db->commit();achievement_reply(200,['announcements'=>$announcements]);
        }elseif($action==='featured'){
            $items=json_decode(is_string($_POST['items']??null)?$_POST['items']:'',true);if(!is_array($items)||count($items)>3)throw new InvalidArgumentException('Elegí hasta tres insignias obtenidas.');
            $valid=[];foreach($items as $item){if(!is_string($item)||!preg_match('/^([a-z_]+):([1-6])$/D',$item,$m)||isset($valid[$item]))throw new InvalidArgumentException('Revisá las insignias seleccionadas.');if(!achievement_q($db,'SELECT 1 FROM user_achievements WHERE user_id=? AND category=? AND level=?',[$user,$m[1],$m[2]])->fetchColumn())throw new InvalidArgumentException('Solo podés destacar insignias obtenidas.');$valid[$item]=[$m[1],(int)$m[2]];}
            achievement_q($db,'UPDATE user_achievements SET featured=0 WHERE user_id=?',[$user]);foreach($valid as [$category,$level])achievement_q($db,'UPDATE user_achievements SET featured=1 WHERE user_id=? AND category=? AND level=?',[$user,$category,$level]);
        }elseif($action==='camera'){
            $operation=$_POST['operation']??'';if(!is_string($operation)||!preg_match('/^[a-f0-9-]{36}$/D',$operation))throw new InvalidArgumentException('Lectura no válida.');
            $fresh=achievement_q($db,'INSERT INTO achievement_receipts(user_id,operation_key) VALUES(?,?) ON CONFLICT(user_id,operation_key) DO NOTHING',[$user,$operation])->rowCount();
            $code=is_string($_POST['code']??null)?$_POST['code']:'';$kind=$_POST['kind']??'';
            if(!$fresh){}
            elseif($kind==='saved'){if(!valid_product_ean($code))throw new InvalidArgumentException('EAN no válido.');achievement_record($db,$user,'listas',$code);}
            elseif($kind==='ean'){if(!valid_product_ean($code))throw new InvalidArgumentException('EAN no válido.');achievement_record($db,$user,'escaneos',$code);}
            elseif($kind==='user'){
                if(!preg_match('/^(?:[A-Z0-9]{12}|[a-f0-9]{64})$/D',$code))throw new InvalidArgumentException('Perfil no válido.');
                $target=achievement_q($db,strlen($code)===12?'SELECT user_id FROM referral_codes WHERE code=?':'SELECT user_id FROM user_profile_tokens WHERE token=?',[$code])->fetchColumn();
                if(!$target)throw new InvalidArgumentException('Perfil no disponible.');if((int)$target!==$user)achievement_record($db,$user,'qr','user:'.$target);
            }elseif($kind==='pallet'){
                $p=stock_label($db,$code);if(empty($p['pallet_id']))throw new InvalidArgumentException('Palet sin activar.');stock_detail($db,$code,$user);achievement_record($db,$user,'qr','pallet:'.$p['pallet_id']);
            }else throw new InvalidArgumentException('Lectura no válida.');
        }elseif($action==='seen')achievement_q($db,'UPDATE user_achievements SET seen_at=CURRENT_TIMESTAMP WHERE user_id=? AND seen_at IS NULL',[$user]);
        elseif($action==='search'){
            $q=is_string($_POST['q']??null)?trim($_POST['q']):'';if(mb_strlen($q)<2||mb_strlen($q)>100)throw new InvalidArgumentException('Búsqueda no válida.');
            $day=(new DateTimeImmutable('now',new DateTimeZone('America/Argentina/Buenos_Aires')))->format('Y-m-d');
            // Limit accepted, explicitly completed searches; typing/background requests never call this endpoint.
            $n=(int)achievement_q($db,"SELECT COUNT(*) FROM achievement_events WHERE user_id=? AND category='busquedas' AND day=?",[$user,$day])->fetchColumn();
            if($n<100)achievement_record($db,$user,'busquedas',$day.':'.mb_strtolower(preg_replace('/\s+/u',' ',$q)));
        }else throw new InvalidArgumentException('Acción no válida.');
        $db->commit();
    }
    if($method==='POST'&&in_array($action,['camera','search'],true))achievement_reply(200,['ok'=>true]);
    achievement_reply(200,['csrf'=>$csrf,'profile'=>achievement_snapshot($db,$user,true)]);
}catch(InvalidArgumentException|CatalogException $e){if(isset($db)&&$db->inTransaction())$db->rollBack();achievement_reply(422,['error'=>$e->getMessage()]);}
catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();error_log('RepoTools achievements unavailable');achievement_reply(503,['error'=>'No pudimos actualizar tus insignias. Intentá de nuevo.']);}
