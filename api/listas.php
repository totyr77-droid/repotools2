<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/list-sharing.php';require_once __DIR__.'/../includes/referrals.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try{
    if($_SERVER['REQUEST_METHOD']!=='POST')throw new CatalogException('Usá Compartir lista.',405);
    if(!valid_csrf())throw new CatalogException('Recargá la página para compartir.',403);
    $codes=json_decode(is_string($_POST['codes']??null)?$_POST['codes']:'',true);if(!is_array($codes))throw new CatalogException('La lista no es válida.',422);
    $origin=rtrim(getenv('APP_URL')?:'https://repotools.online','/');$parts=parse_url($origin);if(!filter_var($origin,FILTER_VALIDATE_URL)||!in_array($parts['scheme']??'',['http','https'],true)||isset($parts['user'])||isset($parts['pass'])||isset($parts['query'])||isset($parts['fragment']))throw new CatalogException('No pudimos preparar el dominio del enlace.',503);
    $db=database();$db->beginTransaction();try{referral_lock($db);referral_limit($db,'list-share:'.hash('sha256',session_id()),60,3600);$alias=scan_list_share($db,$codes);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    echo json_encode(['url'=>$origin.'/l/'.$alias],JSON_UNESCAPED_SLASHES);
}catch(CatalogException|ReferralError $e){http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()]);}catch(Throwable $e){http_response_code(503);echo json_encode(['error'=>'No pudimos preparar el enlace corto.']);}
