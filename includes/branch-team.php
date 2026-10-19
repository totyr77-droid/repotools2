<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';

// Business permissions are independent of users.role (catalog moderation).
function team_role(PDO $db,int $user,int $branch): string {
    if(!$user||!$branch)return '';
    $owner=stock_query($db,'SELECT w.owner_id FROM branch_workspace_locations l JOIN branch_workspaces w ON w.id=l.workspace_id WHERE l.branch_id=?',[$branch])->fetchColumn();
    if((int)$owner===$user)return 'admin';
    return (string)(stock_query($db,'SELECT role FROM branch_team_members WHERE branch_id=? AND user_id=? AND active=1',[$branch,$user])->fetchColumn()?:'');
}
function team_require(PDO $db,int $user,int $branch,bool $manage=false): string {
    $role=team_role($db,$user,$branch);
    if(!$role||($manage&&!in_array($role,['admin','manager'],true)))throw new CatalogException('Tu vínculo no permite esta acción en la sucursal.',403);
    return $role;
}
function team_event(PDO $db,int $user,int $branch,string $action,?int $target=null,?int $pallet=null): void {
    stock_query($db,'INSERT INTO branch_team_events(branch_id,actor_id,action,target_id,pallet_id) VALUES(?,?,?,?,?)',[$branch,$user,$action,$target,$pallet]);
}
function team_locations(PDO $db,int $user): array {
    return stock_query($db,"SELECT b.id,b.name,b.address,w.id AS workspace_id,w.name AS workspace_name,CASE WHEN w.owner_id=? THEN 'admin' ELSE m.role END AS role FROM branch_workspace_locations l JOIN stock_branches b ON b.id=l.branch_id JOIN branch_workspaces w ON w.id=l.workspace_id LEFT JOIN branch_team_members m ON m.branch_id=b.id AND m.user_id=? AND m.active=1 WHERE w.owner_id=? OR m.user_id IS NOT NULL ORDER BY w.name,b.name,b.id",[$user,$user,$user])->fetchAll();
}
function team_active(PDO $db,int $user): int {
    $branch=(int)stock_query($db,'SELECT branch_id FROM branch_team_active WHERE user_id=?',[$user])->fetchColumn();
    return $branch&&team_role($db,$user,$branch)?$branch:0;
}
function team_select(PDO $db,int $user,int $branch): void {
    if(!$branch){stock_query($db,'DELETE FROM branch_team_active WHERE user_id=?',[$user]);return;}
    team_require($db,$user,$branch);
    stock_query($db,'INSERT INTO branch_team_active(user_id,branch_id) VALUES(?,?) ON CONFLICT(user_id) DO UPDATE SET branch_id=excluded.branch_id',[$user,$branch]);
}
function team_attach(PDO $db,int $user,int $workspace,int $branch): void {
    if((int)stock_query($db,'SELECT owner_id FROM branch_workspaces WHERE id=?',[$workspace])->fetchColumn()!==$user)throw new CatalogException('Solo el administrador puede añadir sucursales.',403);
    $directory=stock_query($db,'SELECT owner_id FROM stock_branches WHERE id=?',[$branch])->fetchColumn();
    // A directory entry is not proof of authority. Never take over another creator's entry.
    if((int)$directory!==$user)throw new CatalogException('Solo podés habilitar sucursales creadas por tu cuenta. Una sucursal de otra persona requiere autorización.',403);
    if(stock_query($db,'SELECT 1 FROM branch_workspace_locations WHERE branch_id=?',[$branch])->fetchColumn())throw new CatalogException('Esta sucursal ya tiene un equipo.',409);
    stock_query($db,'INSERT INTO branch_workspace_locations(branch_id,workspace_id) VALUES(?,?)',[$branch,$workspace]);
    team_event($db,$user,$branch,'team_created');team_select($db,$user,$branch);
}
function team_invite(PDO $db,int $user,int $branch,string $role): string {
    $actor=team_require($db,$user,$branch,true);
    if(!in_array($role,['manager','operator'],true)||($role==='manager'&&$actor!=='admin'))throw new CatalogException('Solo el administrador puede nombrar encargados.',403);
    if((int)stock_query($db,'SELECT COUNT(*) FROM branch_team_invites WHERE branch_id=? AND revoked=0 AND used_by IS NULL AND expires>?',[$branch,time()])->fetchColumn()>=50)throw new CatalogException('Revocá una invitación pendiente antes de crear otra.',422);
    $token=bin2hex(random_bytes(32));
    stock_query($db,'INSERT INTO branch_team_invites(token_hash,branch_id,role,invited_by,expires) VALUES(?,?,?,?,?)',[hash('sha256',$token),$branch,$role,$user,time()+259200]);
    team_event($db,$user,$branch,'invite_created');return $token;
}
function team_accept(PDO $db,int $user,string $token): void {
    if(!preg_match('/^[a-f0-9]{64}$/D',$token))throw new CatalogException('La invitación no está disponible.',404);
    $hash=hash('sha256',$token);$invite=stock_query($db,'SELECT * FROM branch_team_invites WHERE token_hash=?',[$hash])->fetch();
    if(!$invite||$invite['revoked']||$invite['used_by']||$invite['expires']<=time())throw new CatalogException('La invitación venció o ya fue utilizada. Pedí una nueva.',410);
    $branch=(int)$invite['branch_id'];$issuer=team_require($db,(int)$invite['invited_by'],$branch,true);
    if($invite['role']==='manager'&&$issuer!=='admin')throw new CatalogException('Esta invitación ya no está autorizada.',403);
    if(team_role($db,$user,$branch))throw new CatalogException('Ya tenés un vínculo activo con esta sucursal.',409);
    stock_query($db,"INSERT INTO branch_team_members(branch_id,user_id,role,active) VALUES(?,?,?,1) ON CONFLICT(branch_id,user_id) DO UPDATE SET role=excluded.role,active=1,revision=branch_team_members.revision+1",[$branch,$user,$invite['role']]);
    if(!stock_query($db,'UPDATE branch_team_invites SET used_by=? WHERE token_hash=? AND used_by IS NULL AND revoked=0',[$user,$hash])->rowCount())throw new CatalogException('La invitación ya fue utilizada.',409);
    team_event($db,$user,$branch,'member_joined',$user);team_select($db,$user,$branch);
}
function team_revoke(PDO $db,int $user,int $branch,int $target,int $revision): void {
    $actor=team_require($db,$user,$branch,true);$role=team_role($db,$target,$branch);
    if(!$role||$role==='admin'||($role==='manager'&&$actor!=='admin')||$target===$user)throw new CatalogException('No podés quitar este acceso.',403);
    if(!stock_query($db,'UPDATE branch_team_members SET active=0,revision=revision+1 WHERE branch_id=? AND user_id=? AND active=1 AND revision=?',[$branch,$target,$revision])->rowCount())throw new CatalogException('El vínculo cambió. Actualizá el equipo.',409);
    stock_query($db,'DELETE FROM branch_team_active WHERE branch_id=? AND user_id=?',[$branch,$target]);
    // Previously shared links cannot silently restore a revoked membership.
    stock_query($db,'UPDATE branch_team_invites SET revoked=1 WHERE branch_id=? AND used_by IS NULL',[$branch]);
    team_event($db,$user,$branch,'member_revoked',$target);
}
function team_scan(PDO $db,int $user,string $code,int $expectedBranch): array {
    $branch=team_active($db,$user);
    if(!$branch||$branch!==$expectedBranch)throw new CatalogException('La sucursal activa cambió. Volvé a escanear.',409);
    team_require($db,$user,$branch);
    $label=stock_label($db,$code);
    if(!$label['pallet_id'])throw new CatalogException('Este QR todavía no tiene un palet. Su dueño debe prepararlo primero.',409);
    $p=stock_query($db,'SELECT * FROM pallets WHERE id=? AND released=0',[$label['pallet_id']])->fetch();
    if(!$p)throw new CatalogException('El palet ya no está disponible.',409);
    $assigned=false;
    if(!$p['branch_id']){
        $assigned=(bool)stock_query($db,'UPDATE pallets SET branch_id=?,revision=revision+1,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND branch_id IS NULL AND released=0',[$branch,$user,$p['id']])->rowCount();
        if($assigned){
            team_event($db,$user,$branch,'pallet_assigned',null,(int)$p['id']);
            $name=stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$branch])->fetchColumn();
            stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(?,?,?,?)',[$user,$p['id'],'branch_updated',json_encode(['old'=>'Sin sucursal','new'=>$name],JSON_UNESCAPED_UNICODE)]);
            // Joining a team is not consent to publish this pallet through an existing link.
            foreach(stock_query($db,'SELECT id FROM stock_shares WHERE owner_id=? AND branch_id=? AND active=1',[$p['owner_id'],$branch])->fetchAll(PDO::FETCH_COLUMN) as $share){
                stock_query($db,'INSERT INTO stock_share_exclusions(share_id,pallet_id) VALUES(?,?) ON CONFLICT(share_id,pallet_id) DO NOTHING',[$share,$p['id']]);
            }
        }
    }elseif(!team_role($db,$user,(int)$p['branch_id'])){
        // Existing owner/invited access remains valid; scanning alone grants no foreign access.
        stock_editable($db,(int)$p['id'],$user);
    }
    return ['code'=>$code,'assigned'=>$assigned,'pallet'=>stock_detail($db,$code,$user)];
}
function team_overview(PDO $db,int $user,int $branch,int $cursor=0): array {
    team_require($db,$user,$branch,true);
    $members=stock_query($db,'SELECT m.user_id,m.role,m.revision,u.public_name FROM branch_team_members m JOIN users u ON u.id=m.user_id WHERE m.branch_id=? AND m.active=1 ORDER BY m.role,u.public_name',[$branch])->fetchAll();
    foreach($members as &$member)$member['name']=get_public_user_name(['id'=>$member['user_id'],'public_name'=>$member['public_name']]);unset($member);
    $pallets=stock_query($db,"SELECT p.id,p.code,u.public_name AS owner_name,COALESCE(SUM(b.units),0) AS units,COUNT(b.id) AS products FROM pallets p JOIN users u ON u.id=p.owner_id LEFT JOIN pallet_products b ON b.pallet_id=p.id WHERE p.branch_id=? AND p.released=0 AND p.id>? GROUP BY p.id,p.code,u.public_name ORDER BY p.id LIMIT 31",[$branch,$cursor])->fetchAll();
    $more=count($pallets)>30;$pallets=array_slice($pallets,0,30);
    $events=stock_query($db,"SELECT e.id,e.action,e.created_at,u.public_name AS actor,t.public_name AS target,p.code FROM branch_team_events e JOIN users u ON u.id=e.actor_id LEFT JOIN users t ON t.id=e.target_id LEFT JOIN pallets p ON p.id=e.pallet_id WHERE e.branch_id=? ORDER BY e.id DESC LIMIT 25",[$branch])->fetchAll();
    require_once __DIR__.'/pallet-activity.php';
    $rows=stock_query($db,'SELECT a.id,a.action,a.metadata,a.created_at,a.user_id AS actor_id,u.public_name,p.code FROM pallet_activity a JOIN pallets p ON p.id=a.pallet_id LEFT JOIN users u ON u.id=a.user_id WHERE p.branch_id=? ORDER BY a.id DESC LIMIT 25',[$branch])->fetchAll();
    $invites=stock_query($db,'SELECT token_hash,role,expires FROM branch_team_invites WHERE branch_id=? AND revoked=0 AND used_by IS NULL AND expires>? ORDER BY expires DESC',[$branch,time()])->fetchAll();
    $movements=pallet_activity_present($db,$rows);foreach($movements as $index=>&$movement)$movement['code']=$rows[$index]['code'];unset($movement);
    return ['members'=>$members,'pallets'=>$pallets,'next_cursor'=>$more?(int)end($pallets)['id']:null,'events'=>$events,'movements'=>$movements,'invites'=>$invites];
}
