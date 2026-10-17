<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/stock-branches.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
foreach(['manager','usuario'] as $i=>$role)$db->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)')->execute(['Prueba'.($i+1),'test'.$i.'@example.invalid','test-only',$role]);
$db->exec("INSERT INTO stock_branches(owner_id,name,address,locality,province) VALUES(1,'Centro','Calle','Ciudad','Provincia'),(1,'Norte','Calle','Ciudad','Provincia')");$ids=[];
for($i=0;$i<24;$i++){$code=$i===23?'99999999':sprintf('%08d',20000000+$i);$db->prepare('INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES(?,1,?)')->execute([$code,time()+86400]);$p=stock_claim($db,$code,1);$ids[]=(int)$p['id'];stock_save_product($db,1,['pallet_id'=>$p['id'],'ean'=>'96385074','name'=>$i===23?'Producto único del final':'Mercadería','amount'=>1,'expires'=>$i===23?'2027-01-01':'2028-01-01']);$db->prepare('UPDATE pallets SET branch_id=? WHERE id=?')->execute([$i%2+1,$p['id']]);if($i===23)stock_save_tag($db,1,$p['id'],'Etiqueta privada');}
$share=branch_share_create($db,1,['pallet_ids'=>json_encode($ids)])['share'];$token=$share['token'];$check=static function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$guest=branch_public($db,$token);$check(!isset($guest['filters'])&&!str_contains(json_encode($guest),'Etiqueta privada'),'Invitado no recibe etiquetas ni filtros privados');
$ordered=branch_public($db,$token,0,2);$check($ordered['pallets'][0]['code']==='99999999','Registrado ordena por vencimiento en todo el lote, no solo primera página');$check($ordered['has_more'],'Conserva paginación');
$found=branch_public($db,$token,0,2,['q'=>'único']);$check(count($found['pallets'])===1&&$found['pallets'][0]['code']==='99999999','Busca nombre más allá de primera página');$check(array_sum(array_column($found['summary'],'units'))===1,'Resumen respeta filtro');
$byCode=branch_public($db,$token,0,2,['q'=>'20000003']);$check(count($byCode['pallets'])===1,'Busca número de palet');
$byBranch=branch_public($db,$token,0,2,['branch'=>'1']);$check(count($byBranch['pallets'])===12,'Filtra sucursal dentro del enlace');
$ownTag=branch_public($db,$token,0,1,['tag'=>'Etiqueta privada','branch'=>'2']);$check(count($ownTag['pallets'])===1,'Etiqueta propia combinada con sucursal');
try{branch_public($db,$token,0,2,['tag'=>'Etiqueta privada']);throw new RuntimeException('No rechazó etiqueta sin rango');}catch(CatalogException $e){$check($e->status===403,'Valida rango vigente');}
$check($ordered['filters']['tags']===[],'No comparte etiquetas del propietario con otro registrado');
echo "OK: alcance público, búsqueda global antes de paginar, orden vencimiento, resumen filtrado, sucursal y etiquetas personales/rango.\n";
