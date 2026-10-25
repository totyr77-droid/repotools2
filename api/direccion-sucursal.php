<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/branch-address.php';
require_once __DIR__.'/../includes/branch-places.php';
require_once __DIR__.'/../includes/stock.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
try{
    if($_SERVER['REQUEST_METHOD']!=='POST')throw new CatalogException('Solicitud no válida.',405);
    if(!current_user())throw new CatalogException('Inicia sesión para cargar la sucursal.',401);
    if(!valid_csrf())throw new CatalogException('Recarga el formulario.',403);
    $point=branch_coordinates($_POST);session_write_close();
    if(($_POST['mode']??'')==='places'){
        $db=database();$nearby=[];
        $query=$db->prepare('SELECT id,name,address,locality,province,description,latitude,longitude,revision FROM stock_branches WHERE '.branch_visibility_sql($db,(int)current_user()['id'],'id').' AND latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?');
        $span=min(360,0.003/max(0.01,cos(deg2rad($point['latitude']))));
        $query->execute([$point['latitude']-0.002,$point['latitude']+0.002,$point['longitude']-$span,$point['longitude']+$span]);
        foreach($query->fetchAll(PDO::FETCH_ASSOC) as $branch){$distance=branch_point_distance($point,$branch);if($distance<=200){$branch['distance']=(int)round($distance);$nearby[]=$branch;}}
        usort($nearby,fn($a,$b)=>$a['distance']<=>$b['distance']);
        if($nearby&&($_POST['external']??'')!=='1')$result=['branches'=>array_slice($nearby,0,10),'places'=>[]];
        else {
            $result=['branches'=>[],...branch_places($point)];
            if(!$result['places'])try{$result['suggestion']=branch_reverse_address($point);}catch(CatalogException $e){/* Manual entry remains available. */}
        }
        echo json_encode($result,JSON_UNESCAPED_UNICODE);
    }else echo json_encode(['suggestion'=>branch_reverse_address($point)],JSON_UNESCAPED_UNICODE);
}catch(CatalogException $e){http_response_code($e->status);if($e->status===429)header('Retry-After: 2');echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
catch(Throwable $e){http_response_code(503);echo json_encode(['error'=>'Puedes completar la dirección manualmente.'],JSON_UNESCAPED_UNICODE);}
