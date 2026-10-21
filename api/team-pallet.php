<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/branch-team.php';
require_once __DIR__.'/../includes/pallet-activity.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$db=null;
try{
    $user=current_user();$uid=(int)($user['id']??0);if(!$uid)throw new CatalogException('Ingresá para revisar la solicitud.',401);
    $method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','POST'],true))throw new CatalogException('Solicitud no válida.',405);
    if($method==='POST'&&!valid_csrf())throw new CatalogException('La sesión cambió. Recargá la página.',403);
    foreach($_POST as $value)if(!is_string($value)||strlen($value)>2000)throw new CatalogException('Formulario no válido.',422);
    foreach(['id','revision'] as $field)if(isset($_POST[$field])&&!preg_match('/^[1-9][0-9]{0,14}$/D',$_POST[$field]))throw new CatalogException('Solicitud no válida.',422);
    if(isset($_GET['id'])&&(!is_string($_GET['id'])||!preg_match('/^[1-9][0-9]{0,14}$/D',$_GET['id'])))throw new CatalogException('Solicitud no válida.',422);
    $csrf=csrf_token();session_write_close();$db=database();
    if($method==='POST'){
        $db->beginTransaction();if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql')$db->query('SELECT pg_advisory_xact_lock(72684623)');else $db->exec('UPDATE stock_mutex SET revision=revision+1 WHERE id=1');
        team_request_resolve($db,$uid,(int)($_POST['id']??0),(string)($_POST['decision']??''),(int)($_POST['revision']??0));$db->commit();
    }
    $items=stock_query($db,"SELECT r.id,r.revision,r.branch_id,p.code,b.name AS branch_name FROM team_pallet_requests r JOIN pallets p ON p.id=r.pallet_id JOIN stock_branches b ON b.id=r.branch_id WHERE r.owner_id=? AND p.owner_id=? AND p.released=0 AND r.status='pending' AND ".branch_visibility_sql($db,$uid,'b.id')." ORDER BY r.id DESC",[$uid,$uid])->fetchAll();
    if(isset($_GET['id'])){
        $r=stock_query($db,"SELECT r.*,p.code,p.revision AS current_revision,b.name AS branch_name FROM team_pallet_requests r JOIN pallets p ON p.id=r.pallet_id JOIN stock_branches b ON b.id=r.branch_id WHERE r.id=? AND r.owner_id=? AND p.owner_id=? AND p.released=0 AND r.status='pending' AND ".branch_visibility_sql($db,$uid,'b.id'),[(int)$_GET['id'],$uid,$uid])->fetch();
        if(!$r)throw new CatalogException('La solicitud ya no está disponible.',404);
        $r['original']=stock_detail($db,$r['code'],$uid)['products'];$r['proposed']=team_request_products($db,(int)$r['id']);
        $result=['request'=>$r];
    }
    echo json_encode(['ok'=>true,'csrf'=>$csrf,'items'=>$items,...($result??[])],JSON_UNESCAPED_UNICODE);
}catch(CatalogException $e){if($db?->inTransaction())$db->rollBack();http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()]);}
catch(Throwable $e){if($db?->inTransaction())$db->rollBack();error_log('Team pallet requests: '.get_class($e));http_response_code(503);echo json_encode(['error'=>'No pudimos completar la solicitud. Volvé a intentarlo.']);}
