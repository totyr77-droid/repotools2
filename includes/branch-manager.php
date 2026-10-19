<?php
declare(strict_types=1);
require_once __DIR__.'/branch-team.php';
require_once __DIR__.'/stock-branches.php';

function team_manager_companies(PDO $db,int $user,string $search='',int $cursor=0): array {
    team_require_assignment($db,$user);
    $search=catalog_text($search,100);$params=[];$where=[];
    if($cursor){$where[]='w.id<?';$params[]=$cursor;}
    if($search!==''){
        $like='%'.str_replace(['!','%','_'],['!!','!%','!_'],mb_strtolower($search)).'%';
        $where[]="(LOWER(w.name) LIKE ? ESCAPE '!' OR LOWER(u.public_name) LIKE ? ESCAPE '!' OR LOWER(u.email) LIKE ? ESCAPE '!' OR EXISTS(SELECT 1 FROM branch_workspace_locations sl JOIN stock_branches sb ON sb.id=sl.branch_id WHERE sl.workspace_id=w.id AND (LOWER(sb.name) LIKE ? ESCAPE '!' OR LOWER(sb.locality) LIKE ? ESCAPE '!')))";
        array_push($params,$like,$like,$like,$like,$like);
    }
    $sql='SELECT w.id,w.name,u.public_name AS owner_name,(SELECT COUNT(*) FROM branch_workspace_locations l WHERE l.workspace_id=w.id) AS branches FROM branch_workspaces w JOIN users u ON u.id=w.owner_id';
    if($where)$sql.=' WHERE '.implode(' AND ',$where);
    $rows=stock_query($db,$sql.' ORDER BY w.id DESC LIMIT 21',$params)->fetchAll();
    $more=count($rows)>20;$rows=array_slice($rows,0,20);
    return ['companies'=>$rows,'next_cursor'=>$more?(int)end($rows)['id']:null];
}
function team_manager_company(PDO $db,int $user,int $workspace,int $cursor=0): array {
    team_require_assignment($db,$user);
    $company=stock_query($db,'SELECT w.id,w.name,w.owner_id,u.public_name AS owner_name,u.email AS owner_email FROM branch_workspaces w JOIN users u ON u.id=w.owner_id WHERE w.id=?',[$workspace])->fetch();
    if(!$company)throw new CatalogException('La empresa ya no está disponible.',404);
    $rows=stock_query($db,'SELECT b.id,b.name,b.address,b.locality,b.province,b.description,b.revision,(SELECT COUNT(*) FROM pallets p WHERE p.branch_id=b.id AND p.released=0) AS pallets,1+(SELECT COUNT(*) FROM branch_team_members m WHERE m.branch_id=b.id AND m.active=1 AND m.user_id<>?) AS people FROM branch_workspace_locations l JOIN stock_branches b ON b.id=l.branch_id WHERE l.workspace_id=? AND b.id>? ORDER BY b.id LIMIT 31',[(int)$company['owner_id'],$workspace,$cursor])->fetchAll();
    $more=count($rows)>30;$rows=array_slice($rows,0,30);
    return ['company'=>$company,'branches'=>$rows,'next_cursor'=>$more?(int)end($rows)['id']:null,'delete_stamp'=>team_manager_delete_stamp($db,$workspace)];
}
function team_manager_rename(PDO $db,int $user,int $workspace,string $expected,string $name): void {
    team_require_assignment($db,$user);$name=catalog_text($name,100);
    if($name==='')throw new CatalogException('Ingresá el nombre de la empresa.',422);
    $before=stock_query($db,'SELECT id,name FROM branch_workspaces WHERE id=?',[$workspace])->fetch();
    if(!$before)throw new CatalogException('La empresa ya no está disponible.',404);
    if($before['name']!==$expected)throw new CatalogException('La empresa cambió. Actualizá antes de guardar.',409);
    if($name===$before['name'])return;
    stock_query($db,'UPDATE branch_workspaces SET name=? WHERE id=? AND name=?',[$name,$workspace,$expected]);
    stock_query($db,'INSERT INTO branch_events(actor_id,branch_name,action,before_data,after_data) VALUES(?,?,?,?,?)',[$user,$name,'workspace_updated',json_encode($before,JSON_UNESCAPED_UNICODE),json_encode(['id'=>$workspace,'name'=>$name],JSON_UNESCAPED_UNICODE)]);
}
function team_manager_save_branch(PDO $db,int $user,int $workspace,array $input): void {
    team_require_assignment($db,$user);
    $branch=(int)($input['branch_id']??0);
    if(!stock_query($db,'SELECT 1 FROM branch_workspace_locations WHERE workspace_id=? AND branch_id=?',[$workspace,$branch])->fetchColumn())throw new CatalogException('La sucursal ya no pertenece a esta empresa.',409);
    branch_save($db,$user,['id'=>$branch,...array_intersect_key($input,array_flip(['name','address','locality','province','description','revision']))]);
}
function team_manager_delete_stamp(PDO $db,int $workspace): string {
    $rows=stock_query($db,'SELECT w.id,w.name,w.owner_id,l.branch_id,b.revision FROM branch_workspaces w LEFT JOIN branch_workspace_locations l ON l.workspace_id=w.id LEFT JOIN stock_branches b ON b.id=l.branch_id WHERE w.id=? ORDER BY l.branch_id',[$workspace])->fetchAll();
    return hash('sha256',json_encode($rows,JSON_UNESCAPED_UNICODE));
}
// Requires the API's Stock write transaction/lock. Inventory and directory remain intact.
function team_manager_delete(PDO $db,int $user,int $workspace,string $stamp): void {
    team_require_assignment($db,$user);
    $company=stock_query($db,'SELECT id,name,owner_id FROM branch_workspaces WHERE id=?',[$workspace])->fetch();
    if(!$company)throw new CatalogException('La empresa ya no está disponible.',404);
    if(!hash_equals(team_manager_delete_stamp($db,$workspace),$stamp))throw new CatalogException('La empresa cambió. Actualizá antes de eliminarla.',409);
    $branches=stock_query($db,'SELECT branch_id FROM branch_workspace_locations WHERE workspace_id=? ORDER BY branch_id',[$workspace])->fetchAll(PDO::FETCH_COLUMN);
    foreach($branches as $branch){
        stock_query($db,'UPDATE branch_team_members SET active=0,revision=revision+1 WHERE branch_id=? AND active=1',[$branch]);
        stock_query($db,'UPDATE branch_team_invites SET revoked=1 WHERE branch_id=?',[$branch]);
        stock_query($db,'DELETE FROM branch_team_active WHERE branch_id=?',[$branch]);
    }
    stock_query($db,'DELETE FROM branch_workspace_locations WHERE workspace_id=?',[$workspace]);
    stock_query($db,'DELETE FROM branch_workspaces WHERE id=?',[$workspace]);
    stock_query($db,'INSERT INTO branch_events(actor_id,branch_name,action,before_data,after_data) VALUES(?,?,?,?,?)',[$user,$company['name'],'workspace_deleted',json_encode($company+['branches'=>$branches],JSON_UNESCAPED_UNICODE),json_encode(['reason'=>'manager_deleted'],JSON_UNESCAPED_UNICODE)]);
}
