<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/branch-team.php';
require_once __DIR__.'/../includes/stock-branches.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
$user=current_user();$uid=(int)($user['id']??0);$db=null;$method=$_SERVER['REQUEST_METHOD'];
try {
    if(!$uid)throw new CatalogException('Ingresá para acceder a tu equipo.',401);
    if(!in_array($method,['GET','POST'],true))throw new CatalogException('Solicitud no válida.',405);
    if($method==='POST'&&!valid_csrf())throw new CatalogException('La sesión cambió. Recargá la página.',403);
    $csrf=csrf_token();session_write_close();$db=database();$result=[];
    if($method==='POST'){
        foreach($_POST as $value)if(!is_string($value)||strlen($value)>2000)throw new CatalogException('Formulario no válido.',422);
        foreach(['branch_id','workspace_id','target_id','revision'] as $field)if(isset($_POST[$field])&&!preg_match('/^[0-9]{1,15}$/D',$_POST[$field]))throw new CatalogException('Selección no válida.',422);
        $db->beginTransaction();
        if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql')$db->query('SELECT pg_advisory_xact_lock(72684623)');else $db->exec('UPDATE stock_mutex SET revision=revision+1 WHERE id=1');
        $branch=(int)($_POST['branch_id']??0);$action=$_POST['action']??'';
        switch($action){
            case 'create':
            case 'add_branch':
                $workspace=(int)($_POST['workspace_id']??0);
                if($action==='add_branch'&&(int)stock_query($db,'SELECT owner_id FROM branch_workspaces WHERE id=?',[$workspace])->fetchColumn()!==$uid)throw new CatalogException('Solo el administrador puede añadir sucursales.',403);
                if(!$branch){$saved=branch_save($db,$uid,array_intersect_key($_POST,array_flip(['name','address','locality','province'])));$branch=(int)$saved['branch']['id'];}
                if($action==='create'){
                    if((int)stock_query($db,'SELECT COUNT(*) FROM branch_workspaces WHERE owner_id=?',[$uid])->fetchColumn()>=20)throw new CatalogException('Ya tenés el máximo de espacios.',422);
                    $name=catalog_text($_POST['workspace_name']??'',100);if($name==='')$name=(string)stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$branch])->fetchColumn();
                    $workspace=(int)stock_query($db,'INSERT INTO branch_workspaces(owner_id,name) VALUES(?,?) RETURNING id',[$uid,$name])->fetchColumn();
                }
                team_attach($db,$uid,$workspace,$branch);break;
            case 'select':team_select($db,$uid,$branch);break;
            case 'invite':$result['token']=team_invite($db,$uid,$branch,$_POST['role']??'');break;
            case 'accept':team_accept($db,$uid,$_POST['token']??'');break;
            case 'revoke':team_revoke($db,$uid,$branch,(int)($_POST['target_id']??0),(int)($_POST['revision']??0));break;
            case 'cancel_invite':
                $role=team_require($db,$uid,$branch,true);$hash=$_POST['invite_id']??'';
                $inv=stock_query($db,'SELECT role FROM branch_team_invites WHERE token_hash=? AND branch_id=?',[$hash,$branch])->fetch();
                if(!$inv||($inv['role']==='manager'&&$role!=='admin'))throw new CatalogException('No podés revocar esta invitación.',403);
                stock_query($db,'UPDATE branch_team_invites SET revoked=1 WHERE token_hash=? AND branch_id=?',[$hash,$branch]);team_event($db,$uid,$branch,'invite_revoked');break;
            case 'leave':
                if(team_require($db,$uid,$branch)==='admin')throw new CatalogException('El administrador debe conservar la gestión del espacio.',422);
                stock_query($db,'UPDATE branch_team_members SET active=0,revision=revision+1 WHERE branch_id=? AND user_id=?',[$branch,$uid]);
                stock_query($db,'DELETE FROM branch_team_active WHERE user_id=? AND branch_id=?',[$uid,$branch]);
                stock_query($db,'UPDATE branch_team_invites SET revoked=1 WHERE branch_id=? AND invited_by=? AND used_by IS NULL',[$branch,$uid]);team_event($db,$uid,$branch,'member_left',$uid);break;
            case 'scan':$result=team_scan($db,$uid,$_POST['code']??'',$branch);break;
            default:throw new CatalogException('Acción no válida.',422);
        }
        $db->commit();
    }
    $locations=team_locations($db,$uid);$active=team_active($db,$uid);
    $result+=['locations'=>$locations,'active'=>$active,'csrf'=>$csrf,'ok'=>true];
    if($method==='GET'){
        $result['available']=stock_query($db,'SELECT b.id,b.name,b.address FROM stock_branches b WHERE b.owner_id=? AND NOT EXISTS(SELECT 1 FROM branch_workspace_locations l WHERE l.branch_id=b.id) ORDER BY b.name LIMIT 200',[$uid])->fetchAll();
        $branch=(int)($_GET['branch_id']??$active);$role=$branch?team_role($db,$uid,$branch):'';
        if(isset($_GET['branch_id'])&&!$role)throw new CatalogException('Tu vínculo no está activo.',403);
        if(in_array($role,['admin','manager'],true))$result['overview']=team_overview($db,$uid,$branch,max(0,(int)($_GET['cursor']??0)));
    }
    echo json_encode($result,JSON_UNESCAPED_UNICODE);
}catch(CatalogException $error){if($db?->inTransaction())$db->rollBack();http_response_code($error->status);echo json_encode(['error'=>$error->getMessage()]);}
catch(Throwable $error){if($db?->inTransaction())$db->rollBack();error_log('Repotools branch team: '.get_class($error));http_response_code(503);echo json_encode(['error'=>'No pudimos completar la acción. Tus datos siguen disponibles.']);}
