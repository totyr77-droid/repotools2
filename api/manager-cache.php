<?php
require_once __DIR__.'/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
$user=current_user();$role=null;if($user){$q=database()->prepare('SELECT role FROM users WHERE id=?');$q->execute([(int)$user['id']]);$role=$q->fetchColumn();}$status=$_SERVER['REQUEST_METHOD']!=='POST'?405:(!$user?401:($role!=='manager'||!valid_csrf()?403:200));
http_response_code($status);echo json_encode($status===200?['ok'=>true]:['error'=>$status===405?'Solicitud no válida.':($status===401?'Iniciá sesión para actualizar la caché.':'No tenés permiso para actualizar la caché.')],JSON_UNESCAPED_UNICODE);
