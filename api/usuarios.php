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
    if ($method==='GET' && isset($_GET['list'])) {
        $page=filter_var($_GET['page']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000]]);
        if ($page===false) throw new CatalogException('Página no válida.',422);
        $role=$_GET['role']??'';
        if (!is_string($role) || !in_array($role,['','usuario','moderador','administrador','manager'],true)) throw new CatalogException('Rango no válido.',422);
        $s=$db->prepare('SELECT id,name,email,company,role FROM users'.($role!==''?' WHERE role=?':'').' ORDER BY LOWER(name),id LIMIT 21 OFFSET '.($page*20));$s->execute($role!==''?[$role]:[]);$users=$s->fetchAll(PDO::FETCH_ASSOC);
        user_response(200,['users'=>array_slice($users,0,20),'page'=>$page,'page_size'=>20,'has_more'=>count($users)>20]);
    }
    if ($method==='POST') {
        $action=$_POST['action']??'';
        if ($action==='role') profile_change_role($db,(int)$actor['id'],(int)($_POST['id']??0),(string)($_POST['role']??''));
        elseif ($action==='profile') profile_update_information($db,(int)$actor['id'],(int)($_POST['id']??0),$_POST);
        elseif (in_array($action,['block_reports','unblock_reports'],true)) profile_block_reports($db,(int)$actor['id'],(int)($_POST['id']??0),$action==='block_reports');
        else throw new CatalogException('Solicitud no válida.',422);
        $actor=profile_user($db,(int)$actor['id']);
        if (!in_array($actor['role'],['administrador','manager'],true)) user_response(200,['changed'=>true,'access_removed'=>true]);
        $target=profile_user($db,(int)$_POST['id']);
    } else {
        $email=trim((string)($_GET['email']??''));$token=(string)($_GET['token']??'');
        if (isset($_GET['id'])) {$id=filter_var($_GET['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if ($id===false) throw new CatalogException('Cuenta no válida.',422);$s=$db->prepare('SELECT id FROM users WHERE id=?');$s->execute([$id]);}
        elseif ($email!=='' && filter_var($email,FILTER_VALIDATE_EMAIL)) {$s=$db->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?)');$s->execute([$email]);}
        elseif (preg_match('/^[a-f0-9]{64}$/D',$token)) {$s=$db->prepare('SELECT user_id FROM user_profile_tokens WHERE token=?');$s->execute([$token]);}
        else throw new CatalogException('Ingresa un correo válido o escanea el QR del perfil.',422);
        $id=$s->fetchColumn();if (!$id) throw new CatalogException('No encontramos esa cuenta.',404);$target=profile_user($db,(int)$id);
    }
    $page=filter_var($_GET['page']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000]]);if ($page===false) throw new CatalogException('Página no válida.',422);
    $activity=$_GET['activity']??'';if(!is_string($activity))throw new CatalogException('Actividad no válida.',422);
    user_response(200,profile_detail($db,$actor,$target,$page,$activity)+['csrf'=>$csrf]);
} catch (CatalogException $e) {if(isset($db,$session)&&$_SERVER['REQUEST_METHOD']==='POST')audit_denied($db,(int)$session['id'],'admin_'.(string)($_POST['action']??'request'),bin2hex(random_bytes(16)),$e->status);user_response($e->status,['error'=>$e->getMessage()]);}
catch (Throwable $e) {error_log('Repotools profiles: '.get_class($e).' code='.$e->getCode());user_response(503,['error'=>'No pudimos consultar el perfil. Intenta nuevamente.']);}
