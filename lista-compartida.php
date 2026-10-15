<?php
require_once __DIR__.'/includes/auth.php';
header('Cache-Control: no-store');$code=$_GET['code']??'';
if(!is_string($code)||!preg_match('/^[A-Za-z0-9]{8}$/D',$code)){http_response_code(404);exit('Lista no disponible.');}
$q=database()->prepare('SELECT payload FROM scan_list_shares WHERE code=?');$q->execute([$code]);$payload=$q->fetchColumn();
if(!$payload){http_response_code(404);exit('Lista no disponible.');}
$sharedScanCodes=json_decode($payload,true);$publicShareBase=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/lista-compartida.php')),'/').'/';
$_GET=[];require __DIR__.'/index.php';
