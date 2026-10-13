<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/stock-overview.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
try{
    if($_SERVER['REQUEST_METHOD']!=='GET')throw new CatalogException('Solicitud no válida.',405);
    $user=current_user();if(!$user)throw new CatalogException('Ingresá para ver tu Stock.',401);
    session_write_close();echo json_encode(stock_overview(database(),(int)$user['id']),JSON_UNESCAPED_UNICODE);
}catch(CatalogException $e){http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}catch(Throwable $e){http_response_code(503);echo json_encode(['error'=>'No pudimos cargar el resumen.'],JSON_UNESCAPED_UNICODE);}
