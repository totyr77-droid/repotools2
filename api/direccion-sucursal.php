<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/branch-address.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
try{
    if($_SERVER['REQUEST_METHOD']!=='POST')throw new CatalogException('Solicitud no válida.',405);
    if(!current_user())throw new CatalogException('Inicia sesión para cargar la sucursal.',401);
    if(!valid_csrf())throw new CatalogException('Recarga el formulario.',403);
    $point=branch_coordinates($_POST);session_write_close();echo json_encode(['suggestion'=>branch_reverse_address($point)],JSON_UNESCAPED_UNICODE);
}catch(CatalogException $e){http_response_code($e->status);if($e->status===429)header('Retry-After: 2');echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
catch(Throwable $e){http_response_code(503);echo json_encode(['error'=>'Puedes completar la dirección manualmente.'],JSON_UNESCAPED_UNICODE);}
