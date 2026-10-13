<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/stock-export.php';
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
try{
    if($_SERVER['REQUEST_METHOD']!=='GET')throw new CatalogException('Solicitud no válida.',405);
    $user=current_user();if(!$user)throw new CatalogException('Ingresá para exportar tu Stock.',401);
    $type=$_GET['type']??'stock';if(!is_string($type))throw new CatalogException('Exportación no válida.',422);
    $id=null;if(isset($_GET['pallet_id'])){$id=filter_var($_GET['pallet_id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id)throw new CatalogException('Palet no válido.',422);}
    session_write_close();$db=database();$rows=stock_export_rows($db,(int)$user['id'],$type,$id);
    // Complete generation before returning download headers, so a DB failure
    // cannot produce a misleading, partially successful CSV.
    $output=fopen('php://temp/maxmemory:2097152','w+');stock_write_csv($output,$db,(int)$user['id'],$type,$rows);rewind($output);
    $date=(new DateTimeImmutable('now',new DateTimeZone('America/Buenos_Aires')))->format('Y-m-d');
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="repotools-'.$type.'-'.$date.'.csv"');fpassthru($output);fclose($output);
}catch(CatalogException $e){http_response_code($e->status);header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}catch(Throwable $e){http_response_code(503);header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>'No pudimos generar el archivo.'],JSON_UNESCAPED_UNICODE);}
