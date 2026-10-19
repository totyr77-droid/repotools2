<?php
// Short-lived, session-bound permission for a fresh online stock operation only.
require_once __DIR__.'/catalog.php';
require_once __DIR__.'/stock.php';
function catalog_reference_prepare(PDO $db,int $user,array $input,?float $now=null): array {
    $now??=microtime(true);$ean=$input['ean']??null;$name=$input['name']??null;$code=$input['code']??null;$key=$input['operation_key']??null;
    if(!is_string($ean)||!valid_product_ean($ean)||!is_string($name)||mb_strlen(trim($name))<2||mb_strlen($name)>120||!is_string($code)||!preg_match('/^[0-9]{8}$/D',$code)||!is_string($key)||!preg_match('/^[a-f0-9-]{36}$/D',$key))throw new CatalogException('Referencia no válida.',422);
    $pallet=stock_detail($db,$code,$user);if(empty($pallet['can_edit'])&&empty($pallet['can_claim']))throw new CatalogException('No tenés permiso para cargar este palet.',403);
    if(stock_query($db,'SELECT 1 FROM stock_operations WHERE user_id=? AND operation_key=?',[$user,$key])->fetchColumn())throw new CatalogException('El movimiento ya fue confirmado.',409);
    $missing=stock_query($db,'SELECT state,checked_at FROM product_lookup_state WHERE ean=?',[$ean])->fetch();
    if(catalog_product($db,$ean)||!$missing||$missing['state']!=='missing'||(int)$missing['checked_at']<(int)$now-86400)throw new CatalogException('No hay una consulta sin resultados confirmada.',409);
    $permits=$_SESSION['catalog_reference_permits']??[];foreach($permits as $id=>$row)if(($row['at']??0)<$now-3||($row['user']??0)!==$user)unset($permits[$id]);
    if(count($permits)>=20)throw new CatalogException('Esperá unos segundos para guardar otra referencia.',429);
    $nonce=bin2hex(random_bytes(24));$permits[$key]=['at'=>$now,'client_at'=>is_numeric($input['client_at']??null)?(float)$input['client_at']:$now*1000,'nonce'=>$nonce,'user'=>$user,'code'=>$code,'ean'=>$ean,'name'=>catalog_text($name,120)];$_SESSION['catalog_reference_permits']=$permits;
    return ['permit'=>$nonce];
}
function catalog_reference_take(int $user,array $input,?float $now=null): ?array {
    $now??=microtime(true);$key=$input['operation_key']??'';$nonce=$input['catalog_permit']??null;if(!is_string($key)||!is_string($nonce))return null;
    $permit=$_SESSION['catalog_reference_permits'][$key]??null;unset($_SESSION['catalog_reference_permits'][$key]);
    if(!$permit||$permit['user']!==$user||$now<$permit['at']||$now-$permit['at']>3||!hash_equals($permit['nonce'],$nonce)||($input['action']??'')!=='save_product'||(int)($input['product_id']??0)!==0)return null;
    if(isset($input['offline_created'])&&abs((float)$input['offline_created']-$permit['client_at'])>3000)return null;
    foreach(['ean','code','name'] as $field)if(!is_string($input[$field]??null)||($field==='name'?catalog_text($input[$field],120):$input[$field])!==$permit[$field])return null;
    return $permit;
}
function catalog_reference_insert(PDO $db,int $user,array $permit,array $movement): bool {
    if(($movement['code']??'')!==$permit['code']||empty($movement['movement']['product_id']))return false;
    stock_editable($db,(int)$movement['id'],$user);
    $row=stock_query($db,'SELECT ean,name FROM pallet_products WHERE id=? AND pallet_id=?',[(int)$movement['movement']['product_id'],(int)$movement['id']])->fetch();
    if(!$row||$row['ean']!==$permit['ean']||$row['name']!==$permit['name'])return false;
    // No edit/reward/scan event: this is a stock reference, not an approved report.
    $insert=stock_query($db,"INSERT INTO products(ean,name,source,updated_by) VALUES(?,?,'stock_reference',?) ON CONFLICT(ean) DO NOTHING",[$permit['ean'],$permit['name'],$user]);
    if(!$insert->rowCount())return false;stock_query($db,'DELETE FROM product_lookup_state WHERE ean=?',[$permit['ean']]);return true;
}

// Independent transaction after confirmed Stock: catalog failure cannot undo merchandise.
function catalog_reference_sync(PDO $db,int $user,array $result): bool {
    if($db->inTransaction()||($result['confirmation']['action']??'')!=='save_product'||empty($result['confirmation']['committed']))return false;
    try {
        $db->beginTransaction();
        $pallet=stock_editable($db,(int)$result['id'],$user);
        if(($pallet['code']??'')!==($result['code']??'')){$db->rollBack();return false;}
        $row=stock_query($db,'SELECT ean,name FROM pallet_products WHERE id=? AND pallet_id=?',[(int)$result['movement']['product_id'],(int)$result['id']])->fetch();
        if(!$row||$row['ean']!==($result['movement']['ean']??'')||!valid_product_ean($row['ean'])||mb_strlen(trim($row['name']??''))<2){$db->rollBack();return false;}
        $saved=catalog_reference_insert($db,$user,['code'=>$result['code'],'ean'=>$row['ean'],'name'=>$row['name']],$result);
        $db->commit();return $saved;
    } catch(Throwable $error) {
        if($db->inTransaction())$db->rollBack();
        error_log('Repotools catalog reference sync: '.get_class($error));return false;
    }
}
