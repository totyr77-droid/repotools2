<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';
require_once __DIR__.'/share-links.php';

function branch_owned(PDO $db, int $id, int $user): array {
    // Legacy helper name: entries are now shared, not scoped to their creator.
    $branch=stock_query($db,'SELECT * FROM stock_branches WHERE id=?',[$id])->fetch();
    if (!$branch) throw new CatalogException('Esta sucursal no está disponible.',404);
    return $branch;
}
function branch_favorite(PDO $db,int $user,array $input): void {
    $id=(int)($input['branch_id']??0); branch_owned($db,$id,$user);
    if (!in_array($input['favorite']??null,['0','1'],true)) throw new CatalogException('El favorito no es valido.',422);
    stock_query($db,'INSERT INTO user_branch_preferences(user_id,branch_id,favorite) VALUES(?,?,?) ON CONFLICT(user_id,branch_id) DO UPDATE SET favorite=excluded.favorite',[$user,$id,(int)$input['favorite']]);
}
function branch_recent(PDO $db,int $user,int $id): void {
    stock_query($db,'INSERT INTO user_branch_preferences(user_id,branch_id,last_used,use_count) VALUES(?,?,?,1) ON CONFLICT(user_id,branch_id) DO UPDATE SET last_used=excluded.last_used,use_count=user_branch_preferences.use_count+1',[$user,$id,time()]);
}
function branch_event(PDO $db,int $user,string $action,?array $before,?array $after): void {
    $b=$after??$before; $fields=array_flip(['name','address','locality','province','description','latitude','longitude']);
    stock_query($db,'INSERT INTO branch_events(actor_id,branch_id,branch_name,action,before_data,after_data) VALUES(?,?,?,?,?,?)',[$user,$b['id'],$b['name'],$action,json_encode(array_intersect_key($before??[],$fields),JSON_UNESCAPED_UNICODE),json_encode(array_intersect_key($after??[],$fields),JSON_UNESCAPED_UNICODE)]);
}
function branch_management(PDO $db,int $user,array $query): array {
    if (!stock_is_admin($db,$user)) throw new CatalogException('Solo administradores y managers pueden gestionar sucursales.',403);
    if(isset($query['id'])) { if(!is_string($query['id'])||!preg_match('/^[0-9]{1,15}$/D',$query['id']))throw new CatalogException('Sucursal no valida.',422);return ['branch'=>array_intersect_key(branch_owned($db,(int)$query['id'],$user),array_flip(['id','name','address','locality','province','description','latitude','longitude','revision']))]; }
    $page=filter_var($query['page']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000]]);$q=$query['q']??'';
    if($page===false||!is_string($q)||mb_strlen($q)>160)throw new CatalogException('Busqueda no valida.',422);
    $q=mb_strtolower(trim($q));$offset=$page*20;
    $items=stock_query($db,"SELECT b.id,b.name,b.address,b.locality,b.province,b.description,b.latitude,b.longitude,b.revision,(SELECT COUNT(*) FROM pallets p WHERE p.branch_id=b.id AND p.released=0) AS pallet_count FROM stock_branches b WHERE LOWER(b.name || ' ' || b.address || ' ' || b.locality || ' ' || b.province) LIKE ? ESCAPE '!' ORDER BY LOWER(b.name),b.id LIMIT 21 OFFSET {$offset}",['%'.strtr($q,['!'=>'!!','%'=>'!%','_'=>'!_']).'%'])->fetchAll();
    return ['items'=>array_slice($items,0,20),'has_more'=>count($items)>20,'page'=>$page];
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
    $before=$id?branch_owned($db,$id,$user):null;
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
    $after=branch_owned($db,$id,$user);branch_event($db,$user,$id&&$before?'branch_updated':'branch_created',$before,$after);
    return ['branch'=>$after];
}
function branch_assign(PDO $db,int $user,array $input): void {
    $id=(int)($input['pallet_id']??0); $p=stock_editable($db,$id,$user,true);
    // The directory is shared; only the pallet owner assigns its location.
    if ((int)$p['owner_id']!==$user) throw new CatalogException('Solo el propietario puede asignar la sucursal.',403);
    $branchId=(int)($input['branch_id']??0);
    if ($branchId) branch_owned($db,$branchId,$user);
    $shares=$branchId ? stock_query($db,'SELECT id FROM stock_shares WHERE owner_id=? AND branch_id=? AND active=1',[$user,$branchId])->fetchAll(PDO::FETCH_COLUMN) : [];
    $changing=(int)($p['branch_id']??0)!==$branchId;
    if(isset($input['share_pallet'])&&!in_array((string)$input['share_pallet'],['0','1'],true))throw new CatalogException('Elegí si querés compartir este palet.',422);
    if($changing&&$shares&&!isset($input['share_pallet']))throw new CatalogException('Esta sucursal tiene un enlace compartido. Volvé a guardar para elegir si mostrar este palet.',409);
    $changed=stock_query($db,'UPDATE pallets SET branch_id=? WHERE id=? AND revision=?',[$branchId?:null,$id,(int)($input['revision']??0)])->rowCount();
    if (!$changed) throw new CatalogException('El palet cambió. Vuelve a abrirlo antes de guardar.',409);
    if($changing&&$shares){foreach($shares as $share){stock_query($db,'DELETE FROM stock_share_exclusions WHERE share_id=? AND pallet_id=?',[$share,$id]);if((string)$input['share_pallet']==='0')stock_query($db,'INSERT INTO stock_share_exclusions(share_id,pallet_id) VALUES(?,?)',[$share,$id]);}}
    stock_touch($db,$id,$user);
    if ($branchId) branch_recent($db,$user,$branchId);
    if($changing){$oldName=($p['branch_id']??0)?stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$p['branch_id']])->fetchColumn():false;$newName=$branchId?stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$branchId])->fetchColumn():false;stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(?,?,?,?)',[$user,$id,'branch_updated',json_encode(['old'=>$oldName?:'Sin sucursal','new'=>$newName?:'Sin sucursal'],JSON_UNESCAPED_UNICODE)]);}
}
function branch_delete(PDO $db,int $user,array $input): void {
    if (!stock_is_admin($db,$user)) throw new CatalogException('Solo administradores y managers pueden eliminar sucursales.',403);
    $id=(int)($input['id']??0); branch_owned($db,$id,$user);
    if ((int)stock_query($db,'SELECT revision FROM stock_branches WHERE id=?',[$id])->fetchColumn()!==(int)($input['revision']??0)) throw new CatalogException('La sucursal cambió. Vuelve a abrirla.',409);
    stock_query($db,'UPDATE pallets SET branch_id=NULL,revision=revision+1 WHERE branch_id=?',[$id]);
    stock_query($db,'UPDATE stock_shares SET active=0,branch_id=NULL WHERE branch_id=?',[$id]);
    branch_event($db,$user,'branch_deleted',branch_owned($db,$id,$user),null);
    stock_query($db,'DELETE FROM stock_branches WHERE id=?',[$id]);
}
function branch_exclusion_ids(PDO $db,int $user,array $input): array {
    $ids=json_decode(is_string($input['excluded_ids']??null)?$input['excluded_ids']:'[]',true);
    if(!is_array($ids)||!array_is_list($ids)||count($ids)>200)throw new CatalogException('Puedes excluir hasta 200 palets por enlace.',422);
    foreach($ids as $id){if(!is_int($id)||$id<1)throw new CatalogException('La seleccion no es valida.',422);if(!stock_query($db,'SELECT id FROM pallets WHERE id=? AND owner_id=?',[$id,$user])->fetchColumn())throw new CatalogException('Solo puedes excluir tus propios palets.',403);}
    return array_values(array_unique($ids));
}
function branch_share_exclusions(PDO $db,int $user,array $input): void {
    $share=stock_query($db,'SELECT id FROM stock_shares WHERE id=? AND owner_id=? AND active=1 AND branch_id IS NOT NULL',[(int)($input['id']??0),$user])->fetchColumn();
    if(!$share)throw new CatalogException('Este enlace no esta disponible en tu cuenta.',404);
    $ids=branch_exclusion_ids($db,$user,$input);
    stock_query($db,'DELETE FROM stock_share_exclusions WHERE share_id=?',[$share]);
    foreach($ids as $id)stock_query($db,'INSERT INTO stock_share_exclusions(share_id,pallet_id) VALUES(?,?)',[$share,$id]);
}
function branch_share_create(PDO $db,int $user,array $input): array {
    $branchId=(int)($input['branch_id']??0); $ids=[];$excluded=$branchId?branch_exclusion_ids($db,$user,$input):[];
    if ($branchId) { $b=branch_owned($db,$branchId,$user);if(!stock_query($db,'SELECT id FROM pallets WHERE owner_id=? AND branch_id=? AND released=0 AND activated=1 LIMIT 1',[$user,$branchId])->fetchColumn())throw new CatalogException('Esta sucursal no tiene palets tuyos cargados para compartir.',422); $title=$b['name']; }
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
    $created=stock_share_insert($db,$user,$branchId?:null,$title);$token=$created['token'];$id=$created['id'];
    foreach ($ids as $palletId) stock_query($db,'INSERT INTO stock_share_pallets(share_id,pallet_id) VALUES(?,?)',[$id,$palletId]);
    if($branchId)branch_share_exclusions($db,$user,['id'=>$id,'excluded_ids'=>json_encode($excluded)]);
    return ['share'=>['id'=>$id,'token'=>$token,'title'=>$title,'branch_id'=>$branchId?:null]];
}
function branch_public(PDO $db,string $token,int $page=0,?int $viewer=null,array $filters=[]): array {
    if (!stock_share_token_valid($token)) throw new CatalogException('El enlace no está disponible.',404);
    $share=stock_query($db,'SELECT * FROM stock_shares WHERE token=? AND active=1',[$token])->fetch();
    if (!$share) throw new CatalogException('Este enlace fue desactivado o no existe.',404);
    $branch=$share['branch_id']?branch_owned($db,(int)$share['branch_id'],(int)$share['owner_id']):null;
    $sql='SELECT p.id,p.code,b.name AS branch_name,b.address AS branch_address,b.locality AS branch_locality,b.province AS branch_province,b.description AS branch_description FROM pallets p LEFT JOIN stock_branches b ON b.id=p.branch_id WHERE p.owner_id=? AND p.released=0 AND p.activated=1';
    $args=[$share['owner_id']];
    if ($branch) { $sql.=' AND p.branch_id=? AND NOT EXISTS(SELECT 1 FROM stock_share_exclusions x WHERE x.share_id=? AND x.pallet_id=p.id)'; $args[]=$branch['id'];$args[]=$share['id']; }
    else { $sql.=' AND EXISTS(SELECT 1 FROM stock_share_pallets sp WHERE sp.share_id=? AND sp.pallet_id=p.id)'; $args[]=$share['id']; }
    $filterOptions=null;
    if($viewer){
        $names=stock_query($db,"SELECT DISTINCT COALESCE(b.id,0) AS id,COALESCE(b.name,'') AS name".substr($sql,strpos($sql,' FROM pallets p ')).' ORDER BY name,id',$args)->fetchAll();
        $filterOptions=['branches'=>$names];
        $q=$filters['q']??'';$place=$filters['branch']??'';$tag=$filters['tag']??'';
        if(!is_string($q)||mb_strlen($q)>100||!is_string($place)||mb_strlen($place)>160||!is_string($tag)||mb_strlen($tag)>40||!in_array($filters['sort']??'expiry',['expiry','code','quantity'],true))throw new CatalogException('Filtro no válido.',422);
        if($place!==''){if($place!=='__none__'&&(!ctype_digit($place)||filter_var($place,FILTER_VALIDATE_INT)===false))throw new CatalogException('Sucursal no válida.',422);$sql.=' AND COALESCE(p.branch_id,0)=CAST(? AS INTEGER)';$args[]=$place==='__none__'?0:(int)$place;}
        if($tag!=='')throw new CatalogException('Las etiquetas no están disponibles en esta vista.',422);
        if(trim($q)!==''){$sql.=" AND (p.code LIKE ? OR EXISTS(SELECT 1 FROM pallet_products match_product LEFT JOIN products c ON c.ean=match_product.ean WHERE match_product.pallet_id=p.id AND (LOWER(match_product.name) LIKE ? OR LOWER(c.name) LIKE ? OR LOWER(c.brand) LIKE ? OR match_product.ean LIKE ?)))";$term='%'.mb_strtolower(trim($q)).'%';array_push($args,$term,$term,$term,$term,$term);}
    }
    $summary=[];
    if($page===0){
        $scope='SELECT p.id'.substr($sql,strpos($sql,' FROM pallets p '));
        $totals=stock_query($db,"SELECT b.ean,MIN(NULLIF(b.name,'')) AS name,b.expires,SUM(b.units) AS units,c.name AS catalog_product,c.brand AS catalog_brand,c.quantity AS catalog_quantity FROM pallet_products b LEFT JOIN products c ON c.ean=b.ean WHERE b.pallet_id IN ({$scope}) GROUP BY b.ean,b.expires,c.name,c.brand,c.quantity ORDER BY b.ean,b.expires",$args)->fetchAll();
        foreach($totals as $product)$summary[]=array_intersect_key(stock_product_names($product),array_flip(['ean','display_name','catalog_name','expires','units']));
    }
    $pallets=[];
    $order=($filters['sort']??'expiry')==='expiry'?"COALESCE((SELECT MIN(NULLIF(exp.expires,'')) FROM pallet_products exp WHERE exp.pallet_id=p.id),'9999-12-31'),p.code,p.id":(($filters['sort']??'')==='quantity'?'COALESCE((SELECT SUM(qty.units) FROM pallet_products qty WHERE qty.pallet_id=p.id),0) DESC,p.code,p.id':'p.code,p.id');
    $rows=stock_query($db,$sql.' ORDER BY '.$order.' LIMIT 21 OFFSET '.($page*20),$args)->fetchAll();
    $hasMore=count($rows)>20;
    $rows=array_slice($rows,0,20); $products=[];
    if ($rows) {
        $ids=array_column($rows,'id'); $marks=implode(',',array_fill(0,count($ids),'?'));
        foreach (stock_query($db,"SELECT b.pallet_id,b.ean,b.name,b.expires,b.units,b.kind,b.pack_size,c.name AS catalog_product,c.brand AS catalog_brand,c.quantity AS catalog_quantity FROM pallet_products b LEFT JOIN products c ON c.ean=b.ean WHERE b.pallet_id IN ({$marks}) ORDER BY ".(($filters['sort']??'')==='quantity'?'b.units DESC,':'')."CASE WHEN b.expires='' THEN 1 ELSE 0 END,b.expires,b.name,b.id",$ids)->fetchAll() as $product) {
            $products[$product['pallet_id']][]=array_intersect_key(stock_product_names($product),array_flip(['ean','display_name','reference_name','catalog_name','expires','units','kind','pack_size']));
        }
    }
    foreach ($rows as $row) {
        $branchInfo=$row['branch_name']===null?null:['name'=>$row['branch_name'],'address'=>$row['branch_address'],'locality'=>$row['branch_locality'],'province'=>$row['branch_province'],'description'=>$row['branch_description']];
        $pallets[]=['code'=>$row['code'],'branch'=>$branchInfo,'products'=>$products[$row['id']]??[]];
    }
    $result=['owner_name'=>get_public_user_name(stock_query($db,'SELECT id,name,public_name FROM users WHERE id=?',[(int)$share['owner_id']])->fetch()?:['id'=>(int)$share['owner_id']]),'title'=>$branch['name']??$share['title'],'branch'=>$branch?array_intersect_key($branch,array_flip(['name','address','locality','province','description'])):null,'pallets'=>$pallets,'has_more'=>$hasMore,'page'=>$page,'summary'=>$summary];
    if($filterOptions!==null)$result['filters']=$filterOptions;return $result;
}
