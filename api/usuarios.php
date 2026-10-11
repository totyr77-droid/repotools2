<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user-profiles.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function user_response(int $status,array $body): void { http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE);exit; }
try {
    $session=current_user();if (!$session) throw new CatalogException('Inicia sesión para continuar.',401);
    $method=$_SERVER['REQUEST_METHOD'];if (!in_array($method,['GET','POST'],true)) throw new CatalogException('Solicitud no válida.',405);
    if ($method==='POST' && !valid_csrf()) throw new CatalogException('Recarga la página para continuar.',403);
    $csrf=csrf_token();session_write_close();$db=database();$actor=profile_user($db,(int)$session['id']);
    if ($method==='GET' && isset($_GET['mine'])) user_response(200,['token'=>profile_token($db,(int)$actor['id'])]);
    if (!in_array($actor['role'],['administrador','manager'],true)) throw new CatalogException('Solo administradores y managers pueden consultar perfiles.',403);
    if ($method==='POST') {
        if (($_POST['action']??'')!=='role') throw new CatalogException('Solicitud no válida.',422);
        profile_change_role($db,(int)$actor['id'],(int)($_POST['id']??0),(string)($_POST['role']??''));
        $actor=profile_user($db,(int)$actor['id']);
        if (!in_array($actor['role'],['administrador','manager'],true)) user_response(200,['changed'=>true,'access_removed'=>true]);
        $target=profile_user($db,(int)$_POST['id']);
    } else {
        $email=trim((string)($_GET['email']??''));$token=(string)($_GET['token']??'');
        if ($email!=='' && filter_var($email,FILTER_VALIDATE_EMAIL)) {$s=$db->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?)');$s->execute([$email]);}
        elseif (preg_match('/^[a-f0-9]{64}$/D',$token)) {$s=$db->prepare('SELECT user_id FROM user_profile_tokens WHERE token=?');$s->execute([$token]);}
        else throw new CatalogException('Ingresa un correo válido o escanea el QR del perfil.',422);
        $id=$s->fetchColumn();if (!$id) throw new CatalogException('No encontramos esa cuenta.',404);$target=profile_user($db,(int)$id);
    }
    $page=filter_var($_GET['page']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000]]);if ($page===false) throw new CatalogException('Página no válida.',422);
    user_response(200,profile_detail($db,$actor,$target,$page)+['csrf'=>$csrf]);
} catch (CatalogException $e) {user_response($e->status,['error'=>$e->getMessage()]);}
catch (Throwable $e) {error_log('Repotools profiles: '.get_class($e).' code='.$e->getCode());user_response(503,['error'=>'No pudimos consultar el perfil. Intenta nuevamente.']);}
