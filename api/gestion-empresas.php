<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/branch-manager.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
$db=null;
try {
    $uid=(int)(current_user()['id']??0);
    if(!$uid)throw new CatalogException('Ingresá para continuar.',401);
    $db=database();team_require_assignment($db,$uid);
    $method=$_SERVER['REQUEST_METHOD'];
    if(!in_array($method,['GET','POST'],true))throw new CatalogException('Solicitud no válida.',405);
    if($method==='POST'&&!valid_csrf())throw new CatalogException('La sesión cambió. Recargá la página.',403);
    $csrf=csrf_token();session_write_close();
    $input=$method==='POST'?$_POST:$_GET;
    foreach($input as $value)if(!is_string($value)||strlen($value)>2000)throw new CatalogException('Revisá los datos ingresados.',422);
    foreach(['workspace_id','branch_id','cursor','revision'] as $field)if(isset($input[$field])&&!preg_match('/^[0-9]{1,15}$/D',$input[$field]))throw new CatalogException('Selección no válida.',422);
    $workspace=(int)($input['workspace_id']??0);
    if($method==='GET')$result=$workspace?team_manager_company($db,$uid,$workspace,(int)($input['cursor']??0)):team_manager_companies($db,$uid,$input['q']??'',(int)($input['cursor']??0));
    else {
        $db->beginTransaction();
        if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql')$db->query('SELECT pg_advisory_xact_lock(72684623)');else $db->exec('UPDATE stock_mutex SET revision=revision+1 WHERE id=1');
        switch($input['action']??''){
            case 'delete_company':team_manager_delete($db,$uid,$workspace,$input['delete_stamp']??'');break;
            case 'rename':team_manager_rename($db,$uid,$workspace,$input['expected_name']??'',$input['name']??'');break;
            case 'save_branch':team_manager_save_branch($db,$uid,$workspace,$input);break;
            default:throw new CatalogException('Acción no válida.',422);
        }
        $db->commit();$result=['ok'=>true];
    }
    echo json_encode($result+['csrf'=>$csrf],JSON_UNESCAPED_UNICODE);
}catch(CatalogException $error){if($db?->inTransaction())$db->rollBack();http_response_code($error->status);echo json_encode(['error'=>$error->getMessage()]);}
catch(Throwable $error){if($db?->inTransaction())$db->rollBack();error_log('Repotools company management: '.get_class($error));http_response_code(503);echo json_encode(['error'=>'No pudimos completar la acción. Volvé a intentar.']);}
