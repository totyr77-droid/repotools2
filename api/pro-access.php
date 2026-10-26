<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/pro-access.php';
require_once __DIR__.'/../includes/pro-subscriptions.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){http_response_code(405);exit;}
$user=current_user();if(!$user){http_response_code(401);echo '{"error":"Iniciá sesión para continuar."}';exit;}
session_write_close();
try{$db=database();$role=stock_query($db,'SELECT role FROM users WHERE id=?',[(int)$user['id']])->fetchColumn();echo json_encode(['pro'=>pro_has_access($db,(int)$user['id']),'checkout_ready'=>pro_config()['enabled']&&$role==='manager']);}
catch(Throwable $e){http_response_code(503);echo '{"error":"No pudimos comprobar tu plan. Volvé a intentar."}';}
