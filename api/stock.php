<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/stock.php';
require_once __DIR__.'/../includes/pallet-activity.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff');
function stock_response(int $status,array $body): void { http_response_code($status); echo json_encode($body,JSON_UNESCAPED_UNICODE); exit; }
function stock_offline_confirmation(PDO $db,array $result,string $key,int $uid): array {
    $id=(int)($result['id']??0);$code=(string)($result['code']??'');
    $pallet=stock_detail($db,$code,$uid);
    if((int)($pallet['id']??0)!==$id || !empty($pallet['available']) || empty($pallet['can_edit'])) throw new CatalogException('La carga fue registrada, pero el palet o tus permisos cambiaron. Conservamos la operación para revisar.',409);
    $movement=$result['movement']??null;
    if(!is_array($movement)||empty($movement['product_id'])||!isset($movement['units_added'],$movement['units_before'],$movement['units_after'],$movement['product_revision'])) throw new CatalogException('Esta confirmación anterior no permite verificar el producto y sus unidades. La carga sigue guardada para revisar, sin repetirla.',409);
    foreach(['product_id','units_added','units_before','units_after','product_revision'] as $field) if(is_bool($movement[$field])||filter_var($movement[$field],FILTER_VALIDATE_INT)===false) throw new CatalogException('El detalle de este movimiento necesita revisión. No se volverá a aplicar.',409);
    if((int)$movement['product_id']<1||(int)$movement['product_revision']<1||(int)$movement['units_added']<1||(int)$movement['units_before']<0||(int)$movement['units_after']>1000000000) throw new CatalogException('Las cantidades de este movimiento necesitan revisión. No se volverá a aplicar.',409);
    $product=null;foreach($pallet['products'] as $row)if((int)$row['id']===(int)$movement['product_id']){$product=$row;break;}
    if(!$product||$product['ean']!==$movement['ean']||$product['expires']!==$movement['expires']||(int)$product['units']<1||(int)$product['revision']<(int)$movement['product_revision']||(int)$movement['units_after']!==(int)$movement['units_before']+(int)$movement['units_added']||((int)$product['revision']===(int)$movement['product_revision']&&(int)$product['units']!==(int)$movement['units_after'])) throw new CatalogException('El movimiento ya se registró, pero el producto fue eliminado o cambió su asociación. La carga sigue guardada para revisar; no se volverán a sumar unidades.',409);
    $result['confirmation']=['contract'=>2,'operation_id'=>$key,'user_id'=>$uid,'action'=>'save_product','pallet_id'=>$id,'code'=>$code,'status'=>'confirmed',...$movement,'current_units'=>(int)$product['units']];
    $result['pallet']=$pallet;$result['user_id']=$uid;
    return $result;
}
$user=current_user(); $uid=$user?(int)$user['id']:null; $method=$_SERVER['REQUEST_METHOD'];
if (!in_array($method,['GET','POST'],true)) stock_response(405,['error'=>'Solicitud no válida.']);
if ($method==='POST' && (!$uid || !valid_csrf())) stock_response($uid?403:401,['error'=>$uid?'La sesión venció. Recarga la página.':'Inicia sesión para modificar palets.']);
$csrf=csrf_token(); session_write_close(); $db=null;
try {
    $db=database(); $db->beginTransaction();
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql') $db->query('SELECT pg_advisory_xact_lock(72684623)');
    else $db->exec('UPDATE stock_mutex SET revision=revision+1 WHERE id=1');
    stock_expire($db);
    if ($method==='GET') {
        if (isset($_GET['search'])||isset($_GET['expiries'])) {
            if(!$uid)throw new CatalogException('Inicia sesión para consultar Stock.',401);
            $search=is_string($_GET['search']??null)?trim($_GET['search']):'';
            if(mb_strlen($search)>100)throw new CatalogException('La búsqueda admite hasta 100 caracteres.',422);
            $sql="SELECT b.id,b.name,b.ean,b.expires,b.units,p.code,c.name AS catalog_product,c.brand AS catalog_brand,c.quantity AS catalog_quantity FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id LEFT JOIN products c ON c.ean=b.ean WHERE p.released=0 AND (p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access a WHERE a.pallet_id=p.id AND a.user_id=? AND a.status='accepted'))";
            $params=[$uid,$uid];
            if($search!==''){$sql.=' AND (LOWER(b.name) LIKE ? OR b.ean LIKE ? OR LOWER(c.name) LIKE ? OR LOWER(c.brand) LIKE ?)';$term='%'.mb_strtolower($search).'%';$params[]=$term;$params[]=$term;$params[]=$term;$params[]=$term;}
            if(isset($_GET['expiries'])){$sql.=" AND b.expires<>'' AND b.expires<=?";$params[]=(new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires')))->modify('+'.stock_warning_days($db,$uid).' days')->format('Y-m-d');}
            $result=['items'=>stock_query($db,$sql." ORDER BY CASE WHEN b.expires='' THEN 1 ELSE 0 END,b.expires,p.code,b.id LIMIT 201",$params)->fetchAll(),'warning_days'=>stock_warning_days($db,$uid)];
            $result['items']=array_map('stock_product_names',$result['items']);
            $result['has_more']=count($result['items'])>200;$result['items']=array_slice($result['items'],0,200);
        }
        elseif (isset($_GET['code'])) $result=['pallet'=>stock_detail($db,(string)$_GET['code'],$uid)];
        elseif (isset($_GET['ean'])) {
            if (!$uid) throw new CatalogException('Inicia sesión para agregar productos.',401);
            $ean=$_GET['ean'];
            if (!is_string($ean)||!valid_product_ean($ean)) throw new CatalogException('Ingresa un EAN válido.',422);
            $result=['references'=>stock_query($db,'SELECT kind,pack_size FROM stock_product_references WHERE ean=?',[$ean])->fetchAll()];
        } else {
            if (!$uid) throw new CatalogException('Inicia sesión para ver tus palets.',401);
            $days=stock_query($db,'SELECT expiry_days FROM stock_preferences WHERE user_id=?',[$uid])->fetchColumn();
            $result=['is_admin'=>stock_is_admin($db,$uid),'admin_pallets'=>[],'pallets'=>stock_list($db,$uid),'labels'=>stock_query($db,'SELECT code,reserved_until FROM pallet_labels WHERE reserved_by=? AND pallet_id IS NULL AND reserved_until>? ORDER BY reserved_until DESC',[$uid,time()])->fetchAll(),'invitations'=>stock_query($db,"SELECT p.id,p.code,u.name AS owner_name,u.public_name AS owner_public_name,u.id AS owner_user_id FROM pallet_access a JOIN pallets p ON p.id=a.pallet_id JOIN users u ON u.id=p.owner_id WHERE a.user_id=? AND a.status='pending' AND p.released=0",[$uid])->fetchAll(),'expiry_days'=>$days===false?30:(int)$days,'warning_days'=>stock_warning_days($db,$uid)];
            $result['invitations']=array_map('stock_public_identity',$result['invitations']);
        }
    } else {
        $key=$_POST['operation_key']??'';
        if (isset($_POST['offline_owner']) && (int)$_POST['offline_owner']!==$uid) throw new CatalogException('Esta carga pertenece a otra cuenta. Inicia sesión con la cuenta original.',403);
        if (!is_string($key)||!preg_match('/^[a-f0-9-]{36}$/D',$key)) throw new CatalogException('Recarga el formulario e intenta nuevamente.',422);
        if (isset($_POST['offline_owner']) && (($_POST['action']??'')!=='save_product' || (int)($_POST['product_id']??0)!==0 || (int)($_POST['pallet_id']??0)<1 || !isset($_POST['offline_revision']))) throw new CatalogException('Esta operación necesita conexión y confirmación. No se puede enviar como carga offline.',422);
        if(($_POST['action']??'')==='deduct_product')stock_editable($db,(int)($_POST['pallet_id']??0),$uid);
        $cached=stock_query($db,'SELECT result FROM stock_operations WHERE user_id=? AND operation_key=?',[$uid,$key])->fetchColumn();
        $deductPayload=($_POST['action']??'')==='deduct_product'?hash('sha256',json_encode(array_map(fn($field)=>$_POST[$field]??null,['pallet_id','product_id','revision','amount','kind','pack_size']))):null;
        $offlinePayload=isset($_POST['offline_owner'])?hash('sha256',json_encode(array_map(fn($field)=>$_POST[$field]??null,['pallet_id','code','product_id','ean','name','amount','kind','pack_size','expires']))):null;
        if ($cached!==false) { $previous=json_decode($cached,true);if($deductPayload!==null&&isset($previous['_deduct_payload'])&&!hash_equals($previous['_deduct_payload'],$deductPayload))throw new CatalogException('Esta operación ya está confirmada. Revisá la cantidad actual antes de otra resta.',409);unset($previous['_deduct_payload']);if($offlinePayload!==null&&isset($previous['_offline_payload'])&&!hash_equals($previous['_offline_payload'],$offlinePayload))throw new CatalogException('Esta operación ya fue enviada con otros datos. Conservamos la carga para revisar.',409);unset($previous['_offline_payload']);if($offlinePayload!==null){if(!isset(json_decode($cached,true)['_offline_payload']))throw new CatalogException('No pudimos verificar la confirmación antigua. Conservamos la carga para revisar.',409);$previous=stock_offline_confirmation($db,$previous,$key,$uid);}$previous['user_id']=$uid;$previous['csrf']=$csrf;$db->commit();if(isset($previous['confirmation']))$previous['confirmation']['committed']=true;stock_response(200,$previous); }
        $action=$_POST['action']??''; $id=(int)($_POST['pallet_id']??0); $result=[];
        $tracks=in_array($action,['claim','import','save_product','delete_product','description','delete','invite','accept','decline','revoke','leave'],true);
        $historyId=$id;
        if($action==='claim'||($action==='save_product'&&!$id))$historyId=(int)(stock_label($db,(string)($_POST['code']??''))['pallet_id']??0);
        $historyBefore=$tracks&&$historyId?pallet_activity_snapshot($db,$historyId):null;
        switch($action) {
            case 'generate':
                $count=filter_var($_POST['count']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);
                if (!$count) throw new CatalogException('Elige entre 1 y 100 etiquetas.',422);
                $result['labels']=[]; for($i=0;$i<$count;$i++) $result['labels'][]=stock_generate($db,$uid); break;
            case 'claim': $result=stock_claim($db,(string)($_POST['code']??''),$uid); break;
            case 'save_product':
                if (isset($_POST['offline_revision'])) {
                    $current=stock_editable($db,$id,$uid);
                    // Offline creation adds units atomically; unrelated pallet changes do not overwrite data.
                    if ($current['code']!==($_POST['code']??'') || (int)(stock_label($db,$current['code'])['pallet_id']??0)!==$id) throw new CatalogException('Este código ya no corresponde al palet original. La carga sigue guardada.',409);
                    if ((int)($_POST['product_id']??0)!==0 || (int)$_POST['offline_revision']>(int)$current['revision']) throw new CatalogException('La versión de esta carga necesita revisión. La carga sigue guardada.',409);
                }
                $result=stock_save_product($db,$uid,$_POST);
                $result['pallet_revision']=(int)stock_query($db,'SELECT revision FROM pallets WHERE id=?',[$result['id']])->fetchColumn();
                break;
            case 'deduct_product':
                $result=stock_deduct_product($db,$uid,$_POST);
                stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,product_id,action,metadata) VALUES(?,?,?,?,?)',[$uid,$id,$result['remaining']?(int)$_POST['product_id']:null,'quantity_deducted',json_encode($result['_movement'],JSON_UNESCAPED_UNICODE)]);
                unset($result['_movement']);break;
            case 'import':
                $products=json_decode(is_string($_POST['products']??null)?$_POST['products']:'',true);
                if (!is_array($products)||count($products)>500) throw new CatalogException('No pudimos leer este palet del dispositivo.',422);
                foreach($products as $product) { if(!is_array($product))throw new CatalogException('Producto no válido.',422); stock_product_input($product); }
                $label=stock_generate($db,$uid); $result=stock_claim($db,$label['code'],$uid);
                foreach($products as $product) stock_save_product($db,$uid,[...$product,'pallet_id'=>$result['id']]);
                stock_query($db,'UPDATE pallets SET description=? WHERE id=?',[catalog_text($_POST['description']??'',300),$result['id']]); break;
            case 'delete_product':
                stock_editable($db,$id,$uid); $pid=(int)($_POST['product_id']??0);
                $changed=stock_query($db,'DELETE FROM pallet_products WHERE id=? AND pallet_id=? AND revision=?',[$pid,$id,(int)($_POST['revision']??0)])->rowCount();
                if (!$changed) throw new CatalogException('El producto cambió. Recarga el palet antes de eliminarlo.',409);
                stock_touch($db,$id,$uid); break;
            case 'description':
                stock_editable($db,$id,$uid);
                $description=$_POST['description']??'';
                if (!is_string($description)||mb_strlen(trim($description))>300) throw new CatalogException('La descripción admite hasta 300 caracteres.',422);
                $updated=stock_query($db,'UPDATE pallets SET description=? WHERE id=? AND revision=?',[trim($description),$id,(int)($_POST['revision']??0)])->rowCount();
                if (!$updated) throw new CatalogException('El palet cambió. Recarga antes de guardar la descripción.',409);
                stock_touch($db,$id,$uid); break;
            case 'delete_label':
                $label=stock_label($db,(string)($_POST['code']??''));
                if ($label['pallet_id']) throw new CatalogException('Este código ya tiene un palet. Recarga antes de eliminarlo.',409);
                if ((int)$label['reserved_by']!==$uid&&!stock_is_admin($db,$uid)) throw new CatalogException('Solo puedes eliminar tus etiquetas pendientes.',403);
                stock_query($db,'UPDATE pallet_labels SET reserved_by=NULL,reserved_until=0 WHERE code=?',[$label['code']]); break;
            case 'delete':
                stock_editable($db,$id,$uid,true); stock_query($db,'UPDATE pallets SET released=1 WHERE id=?',[$id]);
                stock_query($db,'UPDATE pallet_labels SET pallet_id=NULL,reserved_by=NULL,reserved_until=0 WHERE pallet_id=?',[$id]); break;
            case 'invite':
                $p=stock_editable($db,$id,$uid,true); $email=strtolower(trim(is_string($_POST['email']??null)?$_POST['email']:''));
                if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new CatalogException('Ingresa el correo de la cuenta que quieres invitar.',422);
                $invite=stock_query($db,'SELECT id FROM users WHERE email=?',[$email])->fetchColumn();
                if (!$invite) throw new CatalogException('No encontramos una cuenta con ese correo.',404);
                if ((int)$invite===$uid) throw new CatalogException('Ya eres propietario de este palet.',422);
                $inserted=stock_query($db,"INSERT INTO pallet_access(pallet_id,user_id,status) VALUES(?,?,'pending') ON CONFLICT(pallet_id,user_id) DO NOTHING",[$id,$invite])->rowCount();
                if ($inserted) stock_query($db,'INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?)',[$invite,'pallet-invite:'.$id.':'.$invite.':'.$key,'stock_invitation','Te invitaron a un palet',get_public_user_name($user).' te invita al palet '.$p['code'].'. Acepta desde Stock.']); break;
            case 'accept': case 'decline':
                $p=stock_query($db,'SELECT id FROM pallets WHERE id=? AND released=0',[$id])->fetchColumn();
                if (!$p) throw new CatalogException('Este palet ya no está activo.',409);
                if ($action==='accept') $changed=stock_query($db,"UPDATE pallet_access SET status='accepted' WHERE pallet_id=? AND user_id=? AND status='pending'",[$id,$uid])->rowCount();
                else $changed=stock_query($db,"DELETE FROM pallet_access WHERE pallet_id=? AND user_id=? AND status='pending'",[$id,$uid])->rowCount();
                if (!$changed) throw new CatalogException('La invitación ya no está disponible.',409); break;
            case 'revoke':
                stock_editable($db,$id,$uid,true); stock_query($db,'DELETE FROM pallet_access WHERE pallet_id=? AND user_id=?',[$id,(int)($_POST['user_id']??0)]); break;
            case 'leave':
                $p=stock_editable($db,$id,$uid);
                if ((int)$p['owner_id']===$uid) throw new CatalogException('Como propietario puedes eliminar el palet.',422);
                stock_query($db,'DELETE FROM pallet_access WHERE pallet_id=? AND user_id=?',[$id,$uid]); break;
            case 'preferences':
                $days=filter_var($_POST['expiry_days']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>365]]);
                if ($days===false) throw new CatalogException('Elige entre 0 y 365 días de anticipación.',422);
                stock_query($db,'INSERT INTO stock_preferences(user_id,expiry_days) VALUES(?,?) ON CONFLICT(user_id) DO UPDATE SET expiry_days=excluded.expiry_days',[$uid,$days]); break;
            default: throw new CatalogException('Acción no válida.',422);
        }
        if($tracks){$historyId=(int)($result['id']??$historyId);if($historyId)pallet_activity_record($db,$uid,$historyId,($historyBefore&& (int)$historyBefore['id']===$historyId)?$historyBefore:null,pallet_activity_snapshot($db,$historyId));}
        if (stock_is_admin($db,$uid)&&($id||isset($_POST['code']))) {
            $target=$id?stock_query($db,'SELECT code FROM pallets WHERE id=?',[$id])->fetchColumn():(string)$_POST['code'];
            $details=$_POST;unset($details['csrf'],$details['operation_key']);
            stock_query($db,'INSERT INTO pallet_admin_events(user_id,pallet_id,code,action,details) VALUES(?,?,?,?,?)',[$uid,$id?:null,$target?:'',(string)$action,json_encode($details,JSON_UNESCAPED_UNICODE)]);
        }
        $result['ok']=true;if($deductPayload!==null)$result['_deduct_payload']=$deductPayload;if($offlinePayload!==null)$result['_offline_payload']=$offlinePayload; stock_query($db,'INSERT INTO stock_operations(user_id,operation_key,result) VALUES(?,?,?)',[$uid,$key,json_encode($result,JSON_UNESCAPED_UNICODE)]);
    }
    if($method==='POST' && $offlinePayload!==null)$result=stock_offline_confirmation($db,$result,$key,$uid);
    $db->commit();if(isset($result['confirmation']))$result['confirmation']['committed']=true;unset($result['_offline_payload'],$result['_deduct_payload']); $result['csrf']=$csrf; $result['user_id']=$uid; stock_response(200,$result);
} catch(CatalogException $error) { if($db?->inTransaction())$db->rollBack(); stock_response($error->status,['error'=>$error->getMessage()]); }
catch(Throwable $error) { if($db?->inTransaction())$db->rollBack(); error_log('Repotools stock error: '.get_class($error).' code='.$error->getCode()); stock_response(503,['error'=>'No pudimos guardar el palet. Tu formulario sigue disponible para reintentar.']); }
