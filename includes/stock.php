<?php
declare(strict_types=1);
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/public-names.php';

function stock_public_identity(array $row): array {
    foreach(['owner','updated'] as $prefix){
        if(array_key_exists($prefix.'_name',$row)){
            $row[$prefix.'_name']=get_public_user_name(['id'=>$row[$prefix.'_user_id']??0,'name'=>$row[$prefix.'_name'],'public_name'=>$row[$prefix.'_public_name']??'']);
            unset($row[$prefix.'_public_name'],$row[$prefix.'_user_id']);
        }
    }
    return $row;
}

function stock_query(PDO $db, string $sql, array $args = []): PDOStatement {
    $stmt = $db->prepare($sql); $stmt->execute($args); return $stmt;
}
function stock_expire(PDO $db): void {
    $now = time();
    stock_query($db, 'UPDATE pallets SET released=1 WHERE released=0 AND activated=0 AND prepared_until<=? AND NOT EXISTS (SELECT 1 FROM pallet_products b WHERE b.pallet_id=pallets.id)', [$now]);
    $db->exec('UPDATE pallet_labels SET pallet_id=NULL,reserved_by=NULL,reserved_until=0 WHERE pallet_id IN (SELECT id FROM pallets WHERE released=1)');
    stock_query($db, 'UPDATE pallet_labels SET reserved_by=NULL,reserved_until=0 WHERE pallet_id IS NULL AND reserved_until<=?', [$now]);
}
function stock_label(PDO $db, string $code): array {
    if (!preg_match('/^[0-9]{8}$/D', $code)) throw new CatalogException('Ingresa los 8 números del palet.', 422);
    $label = stock_query($db, 'SELECT * FROM pallet_labels WHERE code=?', [$code])->fetch();
    if (!$label) throw new CatalogException('Este código no fue generado por Repotools.', 404);
    if (stock_query($db,'SELECT id FROM pallets WHERE code=? AND released=0 AND id<>? LIMIT 1',[$code,(int)($label['pallet_id']??0)])->fetchColumn()) throw new CatalogException('No pudimos verificar el vínculo de este código con su palet. Necesita revisión; no está disponible para reutilizar.',409);
    return $label;
}
function stock_generate(PDO $db, int $user): array {
    // Prefer expired/free labels, never duplicate an unexpired reservation.
    $label = stock_query($db, 'SELECT code FROM pallet_labels WHERE pallet_id IS NULL AND reserved_by IS NULL ORDER BY code LIMIT 1')->fetch();
    $code = $label['code'] ?? null;
    if (!$code) {
        for ($i=0; $i<30; $i++) {
            $candidate = str_pad((string) random_int(0,99999999),8,'0',STR_PAD_LEFT);
            if (!stock_query($db,'SELECT code FROM pallet_labels WHERE code=?',[$candidate])->fetchColumn()) { $code=$candidate; break; }
        }
        if (!$code) throw new CatalogException('Intenta generar la etiqueta nuevamente.');
        stock_query($db,'INSERT INTO pallet_labels(code) VALUES(?)',[$code]);
    }
    stock_query($db,'UPDATE pallet_labels SET reserved_by=?,reserved_until=? WHERE code=?',[$user,time()+259200,$code]);
    return stock_label($db,$code);
}
function stock_is_admin(PDO $db,?int $user): bool { return $user!==null && in_array(stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn(),['administrador','manager'],true); }
function stock_product_names(array $product): array {
    $name=trim((string)($product['catalog_product']??'')); $brand=trim((string)($product['catalog_brand']??''));
    if ($brand!=='' && mb_stripos($name,$brand)===false) $name=trim($brand.' '.$name);
    $product['catalog_name']=trim($name.' '.trim((string)($product['catalog_quantity']??'')));
    $product['reference_name']=trim((string)$product['name']);
    $product['display_name']=$product['reference_name']?:$product['catalog_name'];
    unset($product['catalog_product'],$product['catalog_brand'],$product['catalog_quantity']);
    return $product;
}
function stock_detail(PDO $db, string $code, ?int $user): array {
    $label=stock_label($db,$code); $admin=stock_is_admin($db,$user);
    if (!$label['pallet_id']) return ['code'=>$code,'available'=>true,'reserved_until'=>(int)$label['reserved_until'],'reserved_name'=>$label['reserved_by'] && (int)$label['reserved_until']>time() ? get_public_user_name(stock_query($db,'SELECT id,name,public_name FROM users WHERE id=?',[(int)$label['reserved_by']])->fetch() ?: ['id'=>(int)$label['reserved_by']]) : null,'can_claim'=>$user !== null && (!$label['reserved_by'] || (int)$label['reserved_by']===$user),'can_print'=>$admin||($user !== null && (int)$label['reserved_by']===$user),'can_delete'=>$admin||($user !== null && (int)$label['reserved_by']===$user),'can_edit'=>false];
    $p=stock_query($db,'SELECT p.*,u.name AS owner_name,u.public_name AS owner_public_name,u.id AS owner_user_id,u.company AS owner_company,a.name AS updated_name,a.public_name AS updated_public_name,a.id AS updated_user_id FROM pallets p JOIN users u ON u.id=p.owner_id JOIN users a ON a.id=p.updated_by WHERE p.id=? AND p.released=0',[$label['pallet_id']])->fetch();
    $p=$p?stock_public_identity($p):$p;
    if (!$p) throw new CatalogException('Este palet ya no está disponible. Recarga la lista.',409);
    foreach(['created_at','updated_at'] as $field) $p[$field]=(new DateTimeImmutable($p[$field],new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
    $owner=$user !== null && (int)$p['owner_id']===$user;
    $access=$user ? stock_query($db,'SELECT status FROM pallet_access WHERE pallet_id=? AND user_id=?',[$p['id'],$user])->fetchColumn() : false;
    $p['available']=false; $p['is_owner']=$owner; $p['is_admin']=$admin; $p['can_manage']=$owner||$admin; $p['can_delete']=$owner||$admin; $p['can_edit']=$owner || $admin || $access==='accepted'; $p['pending_invitation']=$access==='pending';
    $p['products']=stock_query($db,'SELECT b.*,c.name AS catalog_product,c.brand AS catalog_brand,c.quantity AS catalog_quantity FROM pallet_products b LEFT JOIN products c ON c.ean=b.ean WHERE b.pallet_id=? ORDER BY CASE WHEN b.expires=\'\' THEN 1 ELSE 0 END,b.expires,b.name,b.id',[$p['id']])->fetchAll();
    foreach ($p['products'] as &$product) $product=stock_product_names($product);
    unset($product);
    if ($owner||$admin) $p['members']=stock_query($db,'SELECT a.user_id,a.status,u.name,u.public_name,u.email FROM pallet_access a JOIN users u ON u.id=a.user_id WHERE a.pallet_id=? ORDER BY u.name',[$p['id']])->fetchAll();
    if(isset($p['members']))foreach($p['members'] as &$member){$member['name']=get_public_user_name($member);unset($member['public_name']);}unset($member);
    if ($p['can_edit'] && $p['branch_id']) $p['branch']=stock_query($db,'SELECT name,address,locality,province,description FROM stock_branches WHERE id=?',[$p['branch_id']])->fetch() ?: null;
    if (!$p['can_edit']) unset($p['description'],$p['branch_id']);
    // Only names and product details are public; account identifiers/access are private.
    if (!$p['can_edit'] && !$p['pending_invitation']) unset($p['owner_id'],$p['updated_by']);
    return $p;
}
function stock_editable(PDO $db, int $id, int $user, bool $ownerOnly=false): array {
    $p=stock_query($db,'SELECT * FROM pallets WHERE id=? AND released=0',[$id])->fetch();
    if (!$p) throw new CatalogException('El palet ya no está activo.',409);
    $owner=(int)$p['owner_id']===$user;
    if (!$owner && !stock_is_admin($db,$user) && ($ownerOnly || stock_query($db,'SELECT status FROM pallet_access WHERE pallet_id=? AND user_id=?',[$id,$user])->fetchColumn()!=='accepted')) throw new CatalogException('No tienes permiso para modificar este palet.',403);
    return $p;
}
function stock_claim(PDO $db,string $code,int $user): array {
    $label=stock_label($db,$code); $relocated=false;
    if ($label['pallet_id']) {
        $p=stock_query($db,'SELECT * FROM pallets WHERE id=?',[$label['pallet_id']])->fetch();
        if ((int)$p['owner_id']===$user) return ['code'=>$code,'id'=>(int)$p['id'],'relocated'=>false];
        // A competing activation gets a separate pallet; its submitted data is preserved.
        $label=stock_generate($db,$user); $code=$label['code']; $relocated=true;
    } elseif ($label['reserved_by'] && (int)$label['reserved_by']!==$user) {
        throw new CatalogException('Esta etiqueta está reservada durante 3 días para quien la generó.',409);
    }
    $until=max(time()+1,(int)$label['reserved_until']);
    if (!$label['reserved_by']) $until=time()+259200;
    $id=(int)stock_query($db,'INSERT INTO pallets(code,owner_id,updated_by,prepared_until) VALUES(?,?,?,?) RETURNING id',[$code,$user,$user,$until])->fetchColumn();
    stock_query($db,'UPDATE pallet_labels SET pallet_id=?,reserved_by=?,reserved_until=? WHERE code=?',[$id,$user,$until,$code]);
    return ['code'=>$code,'id'=>$id,'relocated'=>$relocated];
}
function stock_touch(PDO $db,int $id,int $user): void {
    stock_query($db,'UPDATE pallets SET updated_by=?,updated_at=CURRENT_TIMESTAMP,revision=revision+1 WHERE id=?',[$user,$id]);
}
function stock_product_input(array $input): array {
    $ean=$input['ean']??''; $expires=$input['expires']??'';
    if (!is_string($ean)||!valid_product_ean($ean)) throw new CatalogException('Ingresa un EAN válido de 8 o 13 números.',422);
    if (!is_string($expires) || ($expires!=='' && (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$expires,$date)||!checkdate((int)$date[2],(int)$date[3],(int)$date[1])||(int)$date[1]<2000||(int)$date[1]>2099))) throw new CatalogException('Revisa el vencimiento. Usa día, mes y año.',422);
    $amount=filter_var($input['amount']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000000]]);
    $kind=$input['kind']??'units'; $pack= $kind==='units' ? 1 : filter_var($input['pack_size']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000000]]);
    if (!$amount||!$pack||!in_array($kind,['units','boxes','packs'],true)||$amount*$pack>1000000000) throw new CatalogException('Revisa la cantidad y las unidades por caja o bulto.',422);
    return ['ean'=>$ean,'name'=>catalog_text($input['name']??'',120),'expires'=>$expires,'units'=>$amount*$pack,'kind'=>$kind,'pack_size'=>$pack];
}
function stock_save_product(PDO $db,int $user,array $input): array {
    $v=stock_product_input($input); $id=(int)($input['pallet_id']??0); $relocated=false;
    if (!$id) {
        $code=(string)($input['code']??''); $label=stock_label($db,$code);
        if (!$label['pallet_id'] && $label['reserved_by'] && (int)$label['reserved_by']!==$user) {
            $fresh=stock_generate($db,$user); $claimed=stock_claim($db,$fresh['code'],$user); $claimed['relocated']=true;
        } else $claimed=stock_claim($db,$code,$user);
        $id=$claimed['id']; $relocated=$claimed['relocated'];
    }
    else {
        $old=stock_query($db,'SELECT * FROM pallets WHERE id=?',[$id])->fetch();
        if ($old && (int)$old['owner_id']===$user && (int)$old['released']===1 && (int)$old['activated']===0) {
            $label=stock_label($db,$old['code']);
            if (!$label['pallet_id'] && $label['reserved_by'] && (int)$label['reserved_by']!==$user) {
                $fresh=stock_generate($db,$user); $claimed=stock_claim($db,$fresh['code'],$user); $claimed['relocated']=true;
            } else $claimed=stock_claim($db,$old['code'],$user);
            $id=$claimed['id']; $relocated=$claimed['relocated'];
        }
    }
    $p=stock_editable($db,$id,$user); $productId=(int)($input['product_id']??0);
    $existing=stock_query($db,'SELECT * FROM pallet_products WHERE pallet_id=? AND ean=? AND expires=?',[$id,$v['ean'],$v['expires']])->fetch();
    if ($productId) {
        $old=stock_query($db,'SELECT * FROM pallet_products WHERE id=? AND pallet_id=?',[$productId,$id])->fetch();
        if (!$old || (int)$old['revision']!==(int)($input['revision']??0)||$old['ean']!==$v['ean']) throw new CatalogException('Otra persona actualizó este producto. Conservamos tu formulario; revisa la versión actual antes de guardar.',409);
        if ($existing && (int)$existing['id']!==$productId) {
            $units=(int)$existing['units']+$v['units'];
            if ($units>1000000000) throw new CatalogException('La cantidad es demasiado grande.',422);
            stock_query($db,'UPDATE pallet_products SET units=?,name=?,kind=\'units\',pack_size=1,revision=revision+1 WHERE id=?',[$units,$v['name'],$existing['id']]);
            stock_query($db,'DELETE FROM pallet_products WHERE id=?',[$productId]);
        } else stock_query($db,'UPDATE pallet_products SET name=?,expires=?,units=?,kind=?,pack_size=?,revision=revision+1 WHERE id=?',[$v['name'],$v['expires'],$v['units'],$v['kind'],$v['pack_size'],$productId]);
    } elseif ($existing) {
        $units=(int)$existing['units']+$v['units'];
        if ($units>1000000000) throw new CatalogException('La cantidad es demasiado grande.',422);
        stock_query($db,'UPDATE pallet_products SET units=?,kind=\'units\',pack_size=1,revision=revision+1 WHERE id=?',[$units,$existing['id']]);
    } else stock_query($db,'INSERT INTO pallet_products(pallet_id,ean,name,expires,units,kind,pack_size) VALUES(?,?,?,?,?,?,?)',[$id,$v['ean'],$v['name'],$v['expires'],$v['units'],$v['kind'],$v['pack_size']]);
    if ($v['kind']!=='units') stock_query($db,'INSERT INTO stock_product_references(ean,kind,pack_size,updated_by) VALUES(?,?,?,?) ON CONFLICT(ean,kind) DO UPDATE SET pack_size=excluded.pack_size,updated_by=excluded.updated_by,updated_at=CURRENT_TIMESTAMP',[$v['ean'],$v['kind'],$v['pack_size'],$user]);
    stock_query($db,'UPDATE pallets SET activated=1 WHERE id=?',[$id]); stock_touch($db,$id,$user);
    $result=['code'=>$p['code'],'id'=>$id,'relocated'=>$relocated];
    if (!$productId) {
        $saved=stock_query($db,'SELECT id,ean,expires,units,revision FROM pallet_products WHERE pallet_id=? AND ean=? AND expires=?',[$id,$v['ean'],$v['expires']])->fetch();
        if (!$saved || (int)$saved['units'] !== (int)($existing['units']??0)+$v['units']) throw new CatalogException('No pudimos confirmar las unidades del producto. La carga no se aplicó.',503);
        $result['movement']=['product_id'=>(int)$saved['id'],'ean'=>$saved['ean'],'expires'=>$saved['expires'],'units_added'=>$v['units'],'units_before'=>(int)($existing['units']??0),'units_after'=>(int)$saved['units'],'product_revision'=>(int)$saved['revision']];
    }
    return $result;
}
function stock_warning_days(PDO $db,int $user): int {
    $days=stock_query($db,'SELECT expiry_days FROM stock_preferences WHERE user_id=?',[$user])->fetchColumn();
    return $days===false?30:min(30,max(0,(int)$days));
}
function stock_list(PDO $db,int $user,bool $all=false): array {
    $all=$all&&stock_is_admin($db,$user);
    $limit=(new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires')))->modify('+'.stock_warning_days($db,$user).' days')->format('Y-m-d');
    return array_map('stock_public_identity',stock_query($db,"SELECT p.id,p.code,p.revision,p.description,p.owner_id,u.name AS owner_name,u.public_name AS owner_public_name,u.id AS owner_user_id,u.company AS owner_company,p.updated_at,p.activated,p.prepared_until,(SELECT COUNT(*) FROM pallet_products b WHERE b.pallet_id=p.id AND b.expires<>'' AND b.expires<=?) AS expiry_count,(SELECT COUNT(*) FROM pallet_products b WHERE b.pallet_id=p.id) AS product_count FROM pallets p JOIN users u ON u.id=p.owner_id WHERE p.released=0 AND (?='1' OR p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access a WHERE a.pallet_id=p.id AND a.user_id=? AND a.status='accepted')) ORDER BY p.updated_at DESC",[$limit,(int)$all,$user,$user])->fetchAll());
}
function stock_expiry_state(string $date,int $warningDays,?DateTimeImmutable $today=null): array {
    $today=$today??new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires'));
    $days=(int)$today->diff(new DateTimeImmutable($date,new DateTimeZone('America/Buenos_Aires')))->format('%r%a');
    $state=$days<0?'expired':($days===0?'today':($days<=$warningDays?'warning':'normal'));
    return ['days'=>$days,'state'=>$state,'label'=>['expired'=>'Vencido','today'=>'Hoy','warning'=>'Próximo','normal'=>'Normal'][$state]];
}
function stock_notify(PDO $db,int $user): array {
    $days=stock_warning_days($db,$user);
    $today=new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires'));
    $limit=$today->modify("+{$days} days")->format('Y-m-d');
    $items=stock_query($db,"SELECT b.id,b.name,b.ean,b.expires,p.code FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id WHERE p.released=0 AND b.expires<>'' AND b.expires<=? AND (p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access a WHERE a.pallet_id=p.id AND a.user_id=? AND a.status='accepted'))",[$limit,$user,$user])->fetchAll();
    $current=[];
    foreach($items as $item) {
        $expiry=stock_expiry_state($item['expires'],$days,$today);$remaining=$expiry['days'];$state=$expiry['state'];
        $title=$remaining<0?'Mercadería vencida':($remaining===0?'Mercadería que vence hoy':'Mercadería próxima a vencer');
        $label=$remaining<0?'Vencido hace '.(-$remaining).' día'.($remaining===-1?'':'s'):($remaining===0?'Vence hoy':'Vence en '.$remaining.' día'.($remaining===1?'':'s'));
        $key='expiry:'.$user.':'.$item['id'].':'.$item['expires'];
        $body='Palet '.$item['code'].' · '.($item['name']?:'EAN '.$item['ean']);
        stock_query($db,"INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?) ON CONFLICT(event_key) DO UPDATE SET title=excluded.title,body=excluded.body,read_at=CASE WHEN notifications.title<>excluded.title THEN NULL ELSE notifications.read_at END",[$user,$key,'stock_expiry',$title,$body]);
        $current[$key]=['pallet_code'=>$item['code'],'product_id'=>(int)$item['id'],'expiry_state'=>$state,'expiry_label'=>$label];
    }
    foreach(stock_query($db,"SELECT id,event_key FROM notifications WHERE user_id=? AND type='stock_expiry'",[$user])->fetchAll() as $old) {
        if(!isset($current[$old['event_key']]))stock_query($db,'DELETE FROM notifications WHERE user_id=? AND id=?',[$user,$old['id']]);
    }
    return $current;
}

function stock_deduct_product(PDO $db,int $user,array $input): array {
    $id=(int)($input['pallet_id']??0);stock_editable($db,$id,$user);
    $product=stock_query($db,'SELECT * FROM pallet_products WHERE id=? AND pallet_id=?',[(int)($input['product_id']??0),$id])->fetch();
    if(!$product||(int)$product['revision']!==(int)($input['revision']??-1))throw new CatalogException('El producto cambió. Revisá la cantidad actual antes de restar.',409);
    $quantity=stock_product_input([...$input,'ean'=>$product['ean'],'expires'=>$product['expires'],'name'=>$product['name']])['units'];
    $remaining=(int)$product['units']-$quantity;if($remaining<0)throw new CatalogException('No podés restar más mercadería de la disponible.',422);
    $changed=$remaining?stock_query($db,'UPDATE pallet_products SET units=?,revision=revision+1 WHERE id=? AND pallet_id=? AND revision=?',[$remaining,$product['id'],$id,$product['revision']])->rowCount():stock_query($db,'DELETE FROM pallet_products WHERE id=? AND pallet_id=? AND revision=?',[$product['id'],$id,$product['revision']])->rowCount();
    if(!$changed)throw new CatalogException('El producto cambió. Revisá la cantidad actual antes de restar.',409);
    stock_touch($db,$id,$user);
    return ['id'=>$id,'code'=>stock_query($db,'SELECT code FROM pallets WHERE id=?',[$id])->fetchColumn(),'remaining'=>$remaining,'removed'=>$quantity,'pallet_revision'=>(int)stock_query($db,'SELECT revision FROM pallets WHERE id=?',[$id])->fetchColumn(),'_movement'=>['ean'=>$product['ean'],'reference'=>$product['name'],'expires'=>$product['expires'],'removed_units'=>$quantity,'old'=>(int)$product['units'],'new'=>$remaining]];
}
