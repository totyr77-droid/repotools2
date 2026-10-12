<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/stock-branches.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff');
function branch_response(int $status,array $data): void { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE); exit; }
$user=current_user(); $uid=(int)($user['id']??0); $method=$_SERVER['REQUEST_METHOD'];
if (!in_array($method,['GET','POST'],true)) branch_response(405,['error'=>'Solicitud no válida.']);
if ($method==='POST'&&(!$uid||!valid_csrf())) branch_response($uid?403:401,['error'=>'Inicia sesión y vuelve a intentar.']);
$csrf=$uid?csrf_token():null; session_write_close(); $db=null;
try {
    if (isset($_GET['token'])&&$method==='GET') {
        if (!is_string($_GET['token'])) throw new CatalogException('El enlace no está disponible.',404);
        $db=database(); $db->beginTransaction();
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql') $db->query('SELECT pg_advisory_xact_lock(72684623)');
        else $db->exec('UPDATE stock_mutex SET revision=revision+1 WHERE id=1');
        $page=filter_var($_GET['page']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000]]);
        if ($page===false) throw new CatalogException('La página no es válida.',422);
        stock_expire($db); $result=branch_public($db,$_GET['token'],$page); $db->commit(); branch_response(200,$result);
    }
    if (!$uid) throw new CatalogException('Inicia sesión para ver tus sucursales.',401);
    $db=database(); $db->beginTransaction();
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql') $db->query('SELECT pg_advisory_xact_lock(72684623)');
    else $db->exec('UPDATE stock_mutex SET revision=revision+1 WHERE id=1');
    stock_expire($db); $result=[];
    if ($method==='POST') {
        foreach (['action','id','revision','pallet_id','branch_id','title','pallet_ids'] as $field) {
            if (isset($_POST[$field])&&!is_string($_POST[$field])) throw new CatalogException('El formulario no es válido.',422);
        }
        foreach (['id','revision','pallet_id','branch_id'] as $field) {
            if (isset($_POST[$field])&&$_POST[$field]!==''&&!preg_match('/^[0-9]{1,15}$/D',$_POST[$field])) throw new CatalogException('El formulario no es válido.',422);
        }
        $key=$_POST['operation_key']??'';
        if (!is_string($key)||!preg_match('/^[a-f0-9-]{36}$/D',$key)) throw new CatalogException('Recarga el formulario e intenta nuevamente.',422);
        $key='branches:'.$key;
        $cached=stock_query($db,'SELECT result FROM stock_operations WHERE user_id=? AND operation_key=?',[$uid,$key])->fetchColumn();
        if ($cached!==false) { $db->commit(); branch_response(200,json_decode($cached,true)); }
        switch ($_POST['action']??'') {
            case 'prepare':
                $branchId=(int)($_POST['branch_id']??0);
                if ($branchId) branch_owned($db,$branchId,$uid);
                $result=stock_claim($db,is_string($_POST['code']??null)?$_POST['code']:'',$uid);
                $revision=(int)stock_query($db,'SELECT revision FROM pallets WHERE id=?',[$result['id']])->fetchColumn();
                branch_assign($db,$uid,['pallet_id'=>$result['id'],'revision'=>$revision,'branch_id'=>$branchId]);
                break;
            case 'save': $result=branch_save($db,$uid,$_POST); break;
            case 'assign': branch_assign($db,$uid,$_POST); break;
            case 'delete': branch_delete($db,$uid,$_POST); break;
            case 'share': $result=branch_share_create($db,$uid,$_POST); break;
            case 'revoke':
                if (!stock_query($db,'UPDATE stock_shares SET active=0 WHERE id=? AND owner_id=?',[(int)($_POST['id']??0),$uid])->rowCount()) throw new CatalogException('El enlace no está disponible en tu cuenta.',404);
                break;
            default: throw new CatalogException('Acción no válida.',422);
        }
        if(isset($result['branch'])) $result['branch']=array_intersect_key($result['branch'],array_flip(['id','name','address','locality','province','description','revision']));
        $result['ok']=true; $result['csrf']=$csrf;
        stock_query($db,'INSERT INTO stock_operations(user_id,operation_key,result) VALUES(?,?,?)',[$uid,$key,json_encode($result,JSON_UNESCAPED_UNICODE)]);
    } else {
        $result=['can_manage_branches'=>stock_is_admin($db,$uid),'branches'=>stock_query($db,'SELECT b.id,b.name,b.address,b.locality,b.province,b.description,b.revision, (SELECT COUNT(*) FROM pallets p WHERE p.branch_id=b.id AND p.owner_id=? AND p.released=0 AND p.activated=1) AS pallet_count FROM stock_branches b ORDER BY LOWER(b.name),b.id',[$uid])->fetchAll(),'shares'=>stock_query($db,'SELECT id,token,title,branch_id,created_at FROM stock_shares WHERE owner_id=? AND active=1 ORDER BY id DESC',[$uid])->fetchAll(),'pallets'=>stock_query($db,'SELECT id,code,description,branch_id,revision,activated FROM pallets WHERE owner_id=? AND released=0 ORDER BY updated_at DESC,id DESC',[$uid])->fetchAll(),'csrf'=>$csrf];
    }
    $db->commit(); branch_response(200,$result);
} catch(CatalogException $e) { if($db?->inTransaction())$db->rollBack(); branch_response($e->status,['error'=>$e->getMessage()]); }
catch(Throwable $e) { if($db?->inTransaction())$db->rollBack(); error_log('Repotools branches: '.get_class($e).' code='.$e->getCode()); branch_response(503,['error'=>'No pudimos acceder a las sucursales. Tus datos siguen disponibles para reintentar.']); }
