<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/stock.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff');
function stock_response(int $status,array $body): void { http_response_code($status); echo json_encode($body,JSON_UNESCAPED_UNICODE); exit; }
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
        if (isset($_GET['code'])) $result=['pallet'=>stock_detail($db,(string)$_GET['code'],$uid)];
        elseif (isset($_GET['ean'])) {
            if (!$uid) throw new CatalogException('Inicia sesión para agregar productos.',401);
            $ean=$_GET['ean'];
            if (!is_string($ean)||!valid_product_ean($ean)) throw new CatalogException('Ingresa un EAN válido.',422);
            $result=['references'=>stock_query($db,'SELECT kind,pack_size FROM stock_product_references WHERE ean=?',[$ean])->fetchAll()];
        } else {
            if (!$uid) throw new CatalogException('Inicia sesión para ver tus palets.',401);
            $days=stock_query($db,'SELECT expiry_days FROM stock_preferences WHERE user_id=?',[$uid])->fetchColumn();
            $result=['pallets'=>stock_list($db,$uid),'labels'=>stock_query($db,'SELECT code,reserved_until FROM pallet_labels WHERE reserved_by=? AND pallet_id IS NULL AND reserved_until>? ORDER BY reserved_until DESC',[$uid,time()])->fetchAll(),'invitations'=>stock_query($db,"SELECT p.id,p.code,u.name AS owner_name FROM pallet_access a JOIN pallets p ON p.id=a.pallet_id JOIN users u ON u.id=p.owner_id WHERE a.user_id=? AND a.status='pending' AND p.released=0",[$uid])->fetchAll(),'expiry_days'=>$days===false?30:(int)$days,'warning_days'=>stock_warning_days($db,$uid)];
        }
    } else {
        $key=$_POST['operation_key']??'';
        if (!is_string($key)||!preg_match('/^[a-f0-9-]{36}$/D',$key)) throw new CatalogException('Recarga el formulario e intenta nuevamente.',422);
        $cached=stock_query($db,'SELECT result FROM stock_operations WHERE user_id=? AND operation_key=?',[$uid,$key])->fetchColumn();
        if ($cached!==false) { $db->commit(); stock_response(200,json_decode($cached,true)); }
        $action=$_POST['action']??''; $id=(int)($_POST['pallet_id']??0); $result=[];
        switch($action) {
            case 'generate':
                $count=filter_var($_POST['count']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);
                if (!$count) throw new CatalogException('Elige entre 1 y 100 etiquetas.',422);
                $result['labels']=[]; for($i=0;$i<$count;$i++) $result['labels'][]=stock_generate($db,$uid); break;
            case 'claim': $result=stock_claim($db,(string)($_POST['code']??''),$uid); break;
            case 'save_product': $result=stock_save_product($db,$uid,$_POST); break;
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
                if ((int)$label['reserved_by']!==$uid) throw new CatalogException('Solo puedes eliminar tus etiquetas pendientes.',403);
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
                if ($inserted) stock_query($db,'INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?)',[$invite,'pallet-invite:'.$id.':'.$invite.':'.$key,'stock_invitation','Te invitaron a un palet',$user['name'].' te invita al palet '.$p['code'].'. Acepta desde Stock.']); break;
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
        $result['ok']=true; stock_query($db,'INSERT INTO stock_operations(user_id,operation_key,result) VALUES(?,?,?)',[$uid,$key,json_encode($result,JSON_UNESCAPED_UNICODE)]);
    }
    $db->commit(); $result['csrf']=$csrf; stock_response(200,$result);
} catch(CatalogException $error) { if($db?->inTransaction())$db->rollBack(); stock_response($error->status,['error'=>$error->getMessage()]); }
catch(Throwable $error) { if($db?->inTransaction())$db->rollBack(); error_log('Repotools stock error: '.get_class($error).' code='.$error->getCode()); stock_response(503,['error'=>'No pudimos guardar el palet. Tu formulario sigue disponible para reintentar.']); }
