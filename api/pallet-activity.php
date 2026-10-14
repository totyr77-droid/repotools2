<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/pallet-activity.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
$user=current_user();
try{
    if($_SERVER['REQUEST_METHOD']!=='GET')throw new CatalogException('Solicitud no válida.',405);
    if(!$user)throw new CatalogException('Ingresá para ver la actividad.',401);
    if(($_GET['scope']??'')==='stock'){
        $cursor=filter_var($_GET['cursor']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        $event=filter_var($_GET['event_id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if($cursor===false||$event===false)throw new CatalogException('Movimiento no válido.',422);
        session_write_close();echo json_encode(pallet_activity_feed(database(),(int)$user['id'],$cursor,$event),JSON_UNESCAPED_UNICODE);exit;
    }
    $id=filter_var($_GET['pallet_id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$cursor=filter_var($_GET['cursor']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
    if(!$id||$cursor===false)throw new CatalogException('Palet no válido.',422);
    session_write_close();echo json_encode(pallet_activity_list(database(),(int)$user['id'],$id,$cursor),JSON_UNESCAPED_UNICODE);
}catch(CatalogException $e){http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()]);}catch(Throwable $e){http_response_code(503);echo json_encode(['error'=>'No pudimos cargar la actividad. Intentá nuevamente.']);}
