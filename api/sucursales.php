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
        foreach (['action','id','revision','pallet_id','branch_id','title','pallet_ids','latitude','longitude','favorite','excluded_ids','share_pallet'] as $field) {
            if (isset($_POST[$field])&&!is_string($_POST[$field])) throw new CatalogException('El formulario no es válido.',422);
        }
        foreach (['id','revision','pallet_id','branch_id'] as $field) {
            if (isset($_POST[$field])&&$_POST[$field]!==''&&!preg_match('/^[0-9]{1,15}$/D',$_POST[$field])) throw new CatalogException('El formulario no es válido.',422);
        }
        $key=$_POST['operation_key']??'';
        if (!is_string($key)||!preg_match('/^[a-f0-9-]{36}$/D',$key)) throw new CatalogException('Recarga el formulario e intenta nuevamente.',422);
        $key='branches:'.$key;
        $assignmentHash=in_array($_POST['action']??'',['assign','prepare'],true)?hash('sha256',json_encode(array_map(fn($field)=>(string)($_POST[$field]??''),['action','code','pallet_id','revision','branch_id','share_pallet']))):null;
        $cached=stock_query($db,'SELECT result FROM stock_operations WHERE user_id=? AND operation_key=?',[$uid,$key])->fetchColumn();
        if ($cached!==false) { $result=json_decode($cached,true);if($assignmentHash&&($result['_assignment_hash']??null)!==$assignmentHash)throw new CatalogException('La selección cambió. Volvé a abrir la sucursal antes de guardar.',409);if($assignmentHash){$assigned=stock_editable($db,(int)($result['id']??$_POST['pallet_id']??0),$uid,true);if((int)$assigned['owner_id']!==$uid)throw new CatalogException('Solo el propietario puede asignar la sucursal.',403);}unset($result['_assignment_hash']);$db->commit(); branch_response(200,$result); }
        switch ($_POST['action']??'') {
            case 'prepare':
                $branchId=(int)($_POST['branch_id']??0);
                if ($branchId) branch_owned($db,$branchId,$uid);
                $result=stock_claim($db,is_string($_POST['code']??null)?$_POST['code']:'',$uid);
                $revision=(int)stock_query($db,'SELECT revision FROM pallets WHERE id=?',[$result['id']])->fetchColumn();
                branch_assign($db,$uid,['pallet_id'=>$result['id'],'revision'=>$revision,'branch_id'=>$branchId]+array_intersect_key($_POST,array_flip(['share_pallet'])));
                break;
            case 'favorite': branch_favorite($db,$uid,$_POST); break;
            case 'save': $result=branch_save($db,$uid,$_POST); break;
            case 'assign': branch_assign($db,$uid,$_POST); break;
            case 'delete': branch_delete($db,$uid,$_POST); break;
            case 'share_exclusions': branch_share_exclusions($db,$uid,$_POST); break;
            case 'share': $result=branch_share_create($db,$uid,$_POST); break;
            case 'revoke':
                if (!stock_query($db,'UPDATE stock_shares SET active=0 WHERE id=? AND owner_id=?',[(int)($_POST['id']??0),$uid])->rowCount()) throw new CatalogException('El enlace no está disponible en tu cuenta.',404);
                break;
            default: throw new CatalogException('Acción no válida.',422);
        }
        if(isset($result['branch'])) $result['branch']=array_intersect_key($result['branch'],array_flip(['id','name','address','locality','province','description','revision','latitude','longitude']));
        $result['ok']=true; $result['csrf']=$csrf;
        stock_query($db,'INSERT INTO stock_operations(user_id,operation_key,result) VALUES(?,?,?)',[$uid,$key,json_encode($assignmentHash?$result+['_assignment_hash'=>$assignmentHash]:$result,JSON_UNESCAPED_UNICODE)]);
    } elseif (isset($_GET['manage'])) {
        $result=branch_management($db,$uid,$_GET);$result['csrf']=$csrf;
    } else {
        $result=['can_manage_branches'=>stock_is_admin($db,$uid),'branches'=>stock_query($db,'SELECT b.id,b.name,b.address,b.locality,b.province,b.description,b.revision,b.latitude,b.longitude, COALESCE(pref.favorite,0) AS favorite, COALESCE(pref.last_used,0) AS last_used, COALESCE(pref.use_count,0) AS use_count, (SELECT COUNT(*) FROM pallets p WHERE p.branch_id=b.id AND p.owner_id=? AND p.released=0 AND p.activated=1) AS pallet_count FROM stock_branches b LEFT JOIN user_branch_preferences pref ON pref.branch_id=b.id AND pref.user_id=? ORDER BY LOWER(b.name),b.id',[$uid,$uid])->fetchAll(),'shares'=>stock_query($db,'SELECT s.id,s.token,s.title,s.branch_id,s.created_at,(SELECT COUNT(*) FROM pallets p WHERE p.owner_id=s.owner_id AND p.released=0 AND p.activated=1 AND ((s.branch_id IS NOT NULL AND p.branch_id=s.branch_id AND NOT EXISTS(SELECT 1 FROM stock_share_exclusions x WHERE x.share_id=s.id AND x.pallet_id=p.id)) OR (s.branch_id IS NULL AND EXISTS(SELECT 1 FROM stock_share_pallets sp WHERE sp.share_id=s.id AND sp.pallet_id=p.id)))) AS pallet_count FROM stock_shares s WHERE s.owner_id=? AND s.active=1 ORDER BY s.id DESC',[$uid])->fetchAll(),'pallets'=>stock_query($db,'SELECT id,code,description,branch_id,revision,activated FROM pallets WHERE owner_id=? AND released=0 ORDER BY updated_at DESC,id DESC',[$uid])->fetchAll(),'csrf'=>$csrf];
    }
    if(isset($result['shares'])){ $excluded=stock_query($db,'SELECT x.share_id,x.pallet_id FROM stock_share_exclusions x JOIN stock_shares s ON s.id=x.share_id WHERE s.owner_id=? AND s.active=1',[$uid])->fetchAll();foreach($result['shares'] as &$share){$share['excluded_ids']=array_map('intval',array_column(array_filter($excluded,fn($x)=>(int)$x['share_id']===(int)$share['id']),'pallet_id'));}unset($share);}
    $db->commit(); branch_response(200,$result);
} catch(CatalogException $e) { if($db?->inTransaction())$db->rollBack(); branch_response($e->status,['error'=>$e->getMessage()]); }
catch(Throwable $e) { if($db?->inTransaction())$db->rollBack(); error_log('Repotools branches: '.get_class($e).' code='.$e->getCode()); branch_response(503,['error'=>'No pudimos acceder a las sucursales. Tus datos siguen disponibles para reintentar.']); }
