<?php
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/reports.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
function photo_check(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
$ean='96385074';$db->prepare('INSERT INTO products(ean,name) VALUES(?,?)')->execute([$ean,'Referencia propia']);
$report=['ean'=>$ean,'current'=>catalog_product($db,$ean),'photos'=>[],'reference_image'=>'','reference_image_source'=>''];$calls=0;
$fetch=function($code)use(&$calls){$calls++;return ['code'=>$code,'image_front_small_url'=>'https://images.openfoodfacts.org/reference.jpg'];};
$resolved=report_resolve_reference_photo($db,$report,$fetch);
photo_check($calls===1 && $resolved['reference_image']==='https://images.openfoodfacts.org/reference.jpg','Busca foto externa cuando falta en DB.');
photo_check(catalog_product($db,$ean)['image']===$resolved['reference_image'],'Guarda referencia de imagen en DB.');
photo_check(catalog_product($db,$ean)['name']==='Referencia propia','La imagen externa no cambia la ficha propia.');
$report['current']=catalog_product($db,$ean);report_resolve_reference_photo($db,$report,$fetch);photo_check($calls===1,'Siguiente consulta usa DB sin repetir API.');
report_resolve_reference_photo($db,[...$report,'photos'=>['product']],$fetch);photo_check($calls===1,'Foto adjunta evita búsqueda externa.');
$unknown='9002490100070';$missing=['ean'=>$unknown,'current'=>null,'photos'=>[],'reference_image'=>'','reference_image_source'=>''];
$result=report_resolve_reference_photo($db,$missing,fn()=>null);photo_check($result['reference_image']==='','API sin producto conserva ticket sin inventar foto.');
$ean2='7791234567898';$db->prepare('INSERT INTO products(ean,name) VALUES(?,?)')->execute([$ean2,'Sin foto']);
$noImage=[...$report,'ean'=>$ean2,'current'=>catalog_product($db,$ean2)];$result=report_resolve_reference_photo($db,$noImage,fn()=>['code'=>$ean2]);photo_check($result['reference_image']==='','API sin imagen queda disponible para revisar.');
echo "OK: DB primero, API fallback, referencia persistida, ficha preservada, adjunto prioritario y ausencia de imagen.\n";
