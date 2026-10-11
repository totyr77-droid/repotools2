<?php
require_once __DIR__.'/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function account_reply(int $status,array $data): void {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
if (!current_user()) account_reply(401,['error'=>'Ingresá para editar tu cuenta.']);
if ($_SERVER['REQUEST_METHOD']!=='POST') account_reply(405,['error'=>'Solicitud no válida.']);
if (!valid_csrf()) account_reply(403,['error'=>'Recargá la página para continuar.']);
$name=is_string($_POST['name']??null)?trim($_POST['name']):'';
$company=is_string($_POST['company']??null)?trim($_POST['company']):'';
if(mb_strlen($name)<2||mb_strlen($name)>80||mb_strlen($company)>100) account_reply(422,['error'=>'Ingresá un nombre de 2 a 80 caracteres y una empresa de hasta 100.']);
try {
    $db=database();$stmt=$db->prepare('UPDATE users SET name=?,company=? WHERE id=?');
    $stmt->execute([$name,$company,current_user()['id']]);
    $_SESSION['user']['name']=$name;$_SESSION['user']['company']=$company;
    account_reply(200,['name'=>$name,'company'=>$company]);
}catch(Throwable $e){error_log('Repotools: no se pudo actualizar la cuenta.');account_reply(503,['error'=>'No pudimos guardar los datos. Intentá nuevamente.']);}
