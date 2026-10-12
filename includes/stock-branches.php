<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';

function branch_owned(PDO $db, int $id, int $user): array {
    // Legacy helper name: entries are now shared, not scoped to their creator.
    $branch=stock_query($db,'SELECT * FROM stock_branches WHERE id=?',[$id])->fetch();
    if (!$branch) throw new CatalogException('Esta sucursal no está disponible.',404);
    return $branch;
}
function branch_input(array $input): array {
    $result=[];
    foreach (['name'=>100,'address'=>160,'locality'=>100,'province'=>80,'description'=>300] as $field=>$max) {
        $value=$input[$field]??'';
        if (!is_string($value)||mb_strlen(trim($value))>$max||($field!=='description'&&trim($value)==='')) throw new CatalogException('Completa el nombre, dirección, localidad y provincia de la sucursal.',422);
        $result[$field]=preg_replace('/\s+/u',' ',trim($value));
    }
    $latitude=$input['latitude']??''; $longitude=$input['longitude']??'';
    if (($latitude===''||$latitude===null)&&($longitude===''||$longitude===null)) { $result['latitude']=null; $result['longitude']=null; }
    else {
        foreach (['latitude'=>[$latitude,90],'longitude'=>[$longitude,180]] as $field=>[$value,$limit]) {
            if ((!is_string($value)&&!is_int($value)&&!is_float($value))||!is_numeric($value)||!is_finite((float)$value)||abs((float)$value)>$limit) throw new CatalogException('La ubicación de la sucursal no es válida.',422);
            $result[$field]=(float)$value;
        }
    }
    return $result;
}
function branch_save(PDO $db, int $user, array $input): array {
    $id=(int)($input['id']??0);
    if ($id&&!stock_is_admin($db,$user)) throw new CatalogException('Solo administradores y managers pueden modificar sucursales.',403);
    if ($id&&!array_key_exists('latitude',$input)&&!array_key_exists('longitude',$input)) {
        $existing=branch_owned($db,$id,$user); $input['latitude']=$existing['latitude']; $input['longitude']=$existing['longitude'];
    }
    $values=branch_input($input);
    $key=fn($v)=>strtr(mb_strtolower(preg_replace('/\s+/u',' ',trim($v))),['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u']);
    foreach (stock_query($db,'SELECT id,name,address,locality,province FROM stock_branches WHERE id<>? ORDER BY id',[$id])->fetchAll() as $existing) {
        $same=true;foreach(['name','address','locality','province'] as $field) if($key($values[$field])!==$key($existing[$field])){$same=false;break;}
        if($same){if(!$id)return ['branch'=>branch_owned($db,(int)$existing['id'],$user),'reused'=>true];throw new CatalogException('Esa sucursal ya existe con el mismo nombre y dirección. Selecciónala en la lista.',422);}
    }
    if ($id) {
        branch_owned($db,$id,$user);
        $changed=stock_query($db,'UPDATE stock_branches SET name=?,address=?,locality=?,province=?,description=?,latitude=?,longitude=?,revision=revision+1 WHERE id=? AND revision=?',[...array_values($values),$id,(int)($input['revision']??0)])->rowCount();
        if (!$changed) throw new CatalogException('La sucursal cambió. Vuelve a abrirla antes de guardar.',409);
    } else {
        if ((int)stock_query($db,'SELECT COUNT(*) FROM stock_branches WHERE owner_id=?',[$user])->fetchColumn()>=200) throw new CatalogException('Puedes tener hasta 200 sucursales. Edita una existente.',422);
        $id=(int)stock_query($db,'INSERT INTO stock_branches(owner_id,name,address,locality,province,description,latitude,longitude) VALUES(?,?,?,?,?,?,?,?) RETURNING id',[$user,...array_values($values)])->fetchColumn();
    }
    return ['branch'=>branch_owned($db,$id,$user)];
}
function branch_assign(PDO $db,int $user,array $input): void {
    $id=(int)($input['pallet_id']??0); $p=stock_editable($db,$id,$user,true);
    // The directory is shared; only the pallet owner assigns its location.
    if ((int)$p['owner_id']!==$user) throw new CatalogException('Solo el propietario puede asignar la sucursal.',403);
    $branchId=(int)($input['branch_id']??0);
    if ($branchId) branch_owned($db,$branchId,$user);
    $changed=stock_query($db,'UPDATE pallets SET branch_id=? WHERE id=? AND revision=?',[$branchId?:null,$id,(int)($input['revision']??0)])->rowCount();
    if (!$changed) throw new CatalogException('El palet cambió. Vuelve a abrirlo antes de guardar.',409);
    stock_touch($db,$id,$user);
}
function branch_delete(PDO $db,int $user,array $input): void {
    if (!stock_is_admin($db,$user)) throw new CatalogException('Solo administradores y managers pueden eliminar sucursales.',403);
    $id=(int)($input['id']??0); branch_owned($db,$id,$user);
    if ((int)stock_query($db,'SELECT revision FROM stock_branches WHERE id=?',[$id])->fetchColumn()!==(int)($input['revision']??0)) throw new CatalogException('La sucursal cambió. Vuelve a abrirla.',409);
    stock_query($db,'UPDATE pallets SET branch_id=NULL,revision=revision+1 WHERE branch_id=?',[$id]);
    stock_query($db,'UPDATE stock_shares SET active=0,branch_id=NULL WHERE branch_id=?',[$id]);
    stock_query($db,'DELETE FROM stock_branches WHERE id=?',[$id]);
}
function branch_share_create(PDO $db,int $user,array $input): array {
    $branchId=(int)($input['branch_id']??0); $ids=[];
    if ($branchId) { $b=branch_owned($db,$branchId,$user); $title=$b['name']; }
    else {
        $ids=json_decode(is_string($input['pallet_ids']??null)?$input['pallet_ids']:'',true);
        if (!is_array($ids)||!array_is_list($ids)||!count($ids)||count($ids)>200) throw new CatalogException('Selecciona entre 1 y 200 palets.',422);
        foreach ($ids as $id) {
            if (!is_int($id)||$id<1) throw new CatalogException('La selección de palets no es válida.',422);
            $p=stock_query($db,'SELECT id FROM pallets WHERE id=? AND owner_id=? AND released=0 AND activated=1',[$id,$user])->fetchColumn();
            if (!$p) throw new CatalogException('Solo puedes compartir tus palets activos.',403);
        }
        $ids=array_values(array_unique($ids));
        $title=catalog_text($input['title']??'Palets compartidos',100)?:'Palets compartidos';
    }
    if ((int)stock_query($db,'SELECT COUNT(*) FROM stock_shares WHERE owner_id=? AND active=1',[$user])->fetchColumn()>=100) throw new CatalogException('Desactiva un enlace anterior antes de crear otro.',422);
    $token=bin2hex(random_bytes(32));
    $id=(int)stock_query($db,'INSERT INTO stock_shares(owner_id,token,branch_id,title) VALUES(?,?,?,?) RETURNING id',[$user,$token,$branchId?:null,$title])->fetchColumn();
    foreach ($ids as $palletId) stock_query($db,'INSERT INTO stock_share_pallets(share_id,pallet_id) VALUES(?,?)',[$id,$palletId]);
    return ['share'=>['id'=>$id,'token'=>$token,'title'=>$title,'branch_id'=>$branchId?:null]];
}
function branch_public(PDO $db,string $token,int $page=0): array {
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new CatalogException('El enlace no está disponible.',404);
    $share=stock_query($db,'SELECT * FROM stock_shares WHERE token=? AND active=1',[$token])->fetch();
    if (!$share) throw new CatalogException('Este enlace fue desactivado o no existe.',404);
    $branch=$share['branch_id']?branch_owned($db,(int)$share['branch_id'],(int)$share['owner_id']):null;
    $sql='SELECT p.id,p.code,p.description,p.updated_at,u.name AS owner_name,u.company AS owner_company,a.name AS updated_name,b.name AS branch_name,b.address AS branch_address,b.locality AS branch_locality,b.province AS branch_province,b.description AS branch_description FROM pallets p JOIN users u ON u.id=p.owner_id JOIN users a ON a.id=p.updated_by LEFT JOIN stock_branches b ON b.id=p.branch_id WHERE p.owner_id=? AND p.released=0 AND p.activated=1';
    $args=[$share['owner_id']];
    if ($branch) { $sql.=' AND p.branch_id=?'; $args[]=$branch['id']; }
    else { $sql.=' AND EXISTS(SELECT 1 FROM stock_share_pallets sp WHERE sp.share_id=? AND sp.pallet_id=p.id)'; $args[]=$share['id']; }
    $pallets=[];
    $rows=stock_query($db,$sql.' ORDER BY p.code,p.id LIMIT 21 OFFSET '.($page*20),$args)->fetchAll();
    $hasMore=count($rows)>20;
    $rows=array_slice($rows,0,20); $products=[];
    if ($rows) {
        $ids=array_column($rows,'id'); $marks=implode(',',array_fill(0,count($ids),'?'));
        foreach (stock_query($db,"SELECT b.pallet_id,b.ean,b.name,b.expires,b.units,c.name AS catalog_product,c.brand AS catalog_brand,c.quantity AS catalog_quantity FROM pallet_products b LEFT JOIN products c ON c.ean=b.ean WHERE b.pallet_id IN ({$marks}) ORDER BY CASE WHEN b.expires='' THEN 1 ELSE 0 END,b.expires,b.name,b.id",$ids)->fetchAll() as $product) {
            $products[$product['pallet_id']][]=array_intersect_key(stock_product_names($product),array_flip(['ean','display_name','reference_name','catalog_name','expires','units']));
        }
    }
    foreach ($rows as $row) {
        $branchInfo=$row['branch_name']===null?null:['name'=>$row['branch_name'],'address'=>$row['branch_address'],'locality'=>$row['branch_locality'],'province'=>$row['branch_province'],'description'=>$row['branch_description']];
        $updated=(new DateTimeImmutable($row['updated_at'],new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
        $pallets[]=['code'=>$row['code'],'description'=>$row['description'],'branch'=>$branchInfo,'owner_name'=>$row['owner_name'],'owner_company'=>$row['owner_company'],'updated_at'=>$updated,'updated_name'=>$row['updated_name'],'products'=>$products[$row['id']]??[]];
    }
    return ['title'=>$branch['name']??$share['title'],'branch'=>$branch?array_intersect_key($branch,array_flip(['name','address','locality','province','description'])):null,'pallets'=>$pallets,'has_more'=>$hasMore,'page'=>$page];
}
