<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/audit.php';
try {
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')support_reject('Usá el botón Ingresar desde Moderación → Usuarios.',405);
$user=current_user();if(!$user||!valid_csrf())support_reject('Recargá la página para continuar.');
if(support_active()){
    if(($_POST['action']??'')!=='exit')support_reject('Volvé a tu cuenta de manager para ingresar como otra persona.');
    $actor=$GLOBALS['repo_support_actor'];$target=$GLOBALS['repo_support_target'];
    $db=database();$db->beginTransaction();audit_event($db,(int)$actor['id'],'support_ended',bin2hex(random_bytes(16)),['category'=>'users','target_user'=>$target['id']]);$db->commit();
    $_SESSION=[];session_destroy();$params=session_get_cookie_params();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$params['path'],'secure'=>$params['secure'],'httponly'=>true,'samesite'=>'Lax']);
    header('Location: reportes.php?queue=1&view=users&support=exit',true,303);exit;
}
$db=database();$q=$db->prepare('SELECT role FROM users WHERE id=?');$q->execute([$user['id']]);if($q->fetchColumn()!=='manager')support_reject('Solo un manager puede ingresar como usuario.');
$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id||$id===(int)$user['id'])support_reject('Elegí otra cuenta.',422);
$q=$db->prepare('SELECT id,auth_version FROM users WHERE id=?');$q->execute([$id]);$target=$q->fetch();if(!$target)support_reject('No encontramos esa cuenta.',404);
$db->beginTransaction();audit_event($db,(int)$user['id'],'support_started',bin2hex(random_bytes(16)),['category'=>'users','target_user'=>$id]);$db->commit();
$source=array_intersect_key($_SESSION,array_flip(['user','device_token','device_kind','device_renewed']));session_write_close();
$context=bin2hex(random_bytes(16));$params=session_get_cookie_params();$params['lifetime']=1800;session_set_cookie_params($params);session_name('repotools_support_'.$context);session_id('');if(!session_start())throw new RuntimeException('Support session unavailable');$_SESSION=$source;$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['support']=['target'=>$id,'version'=>(int)$target['auth_version'],'until'=>time()+1800];
database()->prepare('UPDATE app_sessions SET expires_at=? WHERE id_hash=?')->execute([time()+1800,hash('sha256',session_id())]);
header('Cache-Control: no-store');header('Location: index.php?support='.$context,true,303);exit;

} catch(Throwable $error) {
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    if(str_starts_with(session_name(),'repotools_support_')){$_SESSION=[];if(session_status()===PHP_SESSION_ACTIVE)session_destroy();}
    error_log('Repotools: support access unavailable '.get_class($error));
    support_reject('No pudimos abrir este acceso. Intentá nuevamente.',503);
}
