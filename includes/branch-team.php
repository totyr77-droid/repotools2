<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';
require_once __DIR__.'/branch-team-security.php';

// Business permissions are independent of users.role (catalog moderation).
function team_can_assign(PDO $db,int $user): bool {
    return stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn()==='manager';
}
function team_require_assignment(PDO $db,int $user): void {
    if(!team_can_assign($db,$user))throw new CatalogException('Solo un manager de Repotools puede asignar empresas y sus sucursales.',403);
}
function team_role(PDO $db,int $user,int $branch): string {
    if(!$user||!$branch)return '';
    $owner=stock_query($db,'SELECT w.owner_id FROM branch_workspace_locations l JOIN branch_workspaces w ON w.id=l.workspace_id WHERE l.branch_id=?',[$branch])->fetchColumn();
    if((int)$owner===$user)return 'admin';
    return (string)(stock_query($db,'SELECT role FROM branch_team_members WHERE branch_id=? AND user_id=? AND active=1 AND (expires_at IS NULL OR expires_at>?)',[$branch,$user,time()])->fetchColumn()?:'');
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
    return stock_query($db,"SELECT b.id,b.name,b.address,b.revision,m.expires_at,COALESCE(s.enabled,1) AS security_enabled,CASE WHEN s.branch_id IS NULL THEN 25200 ELSE s.default_seconds END AS default_seconds,w.id AS workspace_id,w.name AS workspace_name,CASE WHEN w.owner_id=? THEN 'admin' ELSE m.role END AS role FROM branch_workspace_locations l JOIN stock_branches b ON b.id=l.branch_id JOIN branch_workspaces w ON w.id=l.workspace_id LEFT JOIN branch_team_security s ON s.branch_id=b.id LEFT JOIN branch_team_members m ON m.branch_id=b.id AND m.user_id=? AND m.active=1 AND (m.expires_at IS NULL OR m.expires_at>".time().") WHERE w.owner_id=? OR m.user_id IS NOT NULL ORDER BY w.name,b.name,b.id",[$user,$user,$user])->fetchAll();
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
    team_require_assignment($db,$user);
    $owner=(int)stock_query($db,'SELECT owner_id FROM branch_workspaces WHERE id=?',[$workspace])->fetchColumn();
    if(!$owner)throw new CatalogException('La empresa no está disponible.',404);
    $directory=stock_query($db,'SELECT owner_id FROM stock_branches WHERE id=?',[$branch])->fetchColumn();
    if($directory===false)throw new CatalogException('La sucursal no está disponible.',404);
    if(stock_query($db,'SELECT 1 FROM branch_workspace_locations WHERE branch_id=?',[$branch])->fetchColumn())throw new CatalogException('Esta sucursal ya tiene un equipo.',409);
    stock_query($db,'INSERT INTO branch_workspace_locations(branch_id,workspace_id) VALUES(?,?)',[$branch,$workspace]);
    team_event($db,$user,$branch,'company_assigned',$owner);
    if(!team_active($db,$owner))team_select($db,$owner,$branch);
}
function team_invite(PDO $db,int $user,int $branch,string $role,string $email='',bool $targeted=false,?int $seconds=null): string {
    $actor=team_require($db,$user,$branch,true);
    if($seconds!==null&&!in_array($seconds,[3600,14400,25200,43200],true))throw new CatalogException('Duración de acceso no válida.',422);
    if(!in_array($role,['manager','operator'],true)||($role==='manager'&&$actor!=='admin'))throw new CatalogException('Solo el administrador puede nombrar encargados.',403);
    if((int)stock_query($db,'SELECT COUNT(*) FROM branch_team_invites WHERE branch_id=? AND revoked=0 AND used_by IS NULL AND expires>?',[$branch,time()])->fetchColumn()>=50)throw new CatalogException('Revocá una invitación pendiente antes de crear otra.',422);
    $recipient=null;
    if($email!==''||$targeted){
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new CatalogException('Ingresá el correo de la cuenta que querés invitar.',422);
        $recipient=(int)stock_query($db,'SELECT id FROM users WHERE LOWER(email)=LOWER(?)',[trim($email)])->fetchColumn();
        if(!$recipient)throw new CatalogException('No encontramos esa cuenta. Revisá el correo o escaneá su QR.',422);
        if(team_role($db,$recipient,$branch))throw new CatalogException('Esta persona ya tiene acceso a la sucursal.',409);
        if(stock_query($db,'SELECT 1 FROM branch_team_invites WHERE branch_id=? AND recipient_id=? AND revoked=0 AND used_by IS NULL AND expires>?',[$branch,$recipient,time()])->fetchColumn())throw new CatalogException('Esta persona ya tiene una invitación pendiente.',409);
    }
    $token=bin2hex(random_bytes(32));
    stock_query($db,'INSERT INTO branch_team_invites(token_hash,branch_id,role,invited_by,expires,recipient_id,access_seconds) VALUES(?,?,?,?,?,?,?)',[hash('sha256',$token),$branch,$role,$user,team_invite_deadline($db,$user,$branch),$recipient,$seconds]);
    if($recipient){$name=stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$branch])->fetchColumn();stock_query($db,'INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?)',[$recipient,'team-invite:'.hash('sha256',$token),'team_invitation','Invitación a una sucursal','Te invitaron a '.$name.'. Abrí Mi equipo para revisar y aceptar.']);}
    team_event($db,$user,$branch,'invite_created',$recipient);return $token;
}
function team_accept(PDO $db,int $user,string $token,array $position=[]): void {
    if(!preg_match('/^[a-f0-9]{64}$/D',$token))throw new CatalogException('La invitación no está disponible.',404);
    team_accept_hash($db,$user,hash('sha256',$token),$position);
}
function team_accept_pending(PDO $db,int $user,string $hash,array $position=[]): void {
    if(!preg_match('/^[a-f0-9]{64}$/D',$hash)||(int)stock_query($db,'SELECT recipient_id FROM branch_team_invites WHERE token_hash=?',[$hash])->fetchColumn()!==$user)throw new CatalogException('Esta invitación no corresponde a tu cuenta.',403);
    team_accept_hash($db,$user,$hash,$position);
}
function team_accept_hash(PDO $db,int $user,string $hash,array $position=[]): void {
    $invite=stock_query($db,'SELECT * FROM branch_team_invites WHERE token_hash=?',[$hash])->fetch();
    if(!$invite||$invite['revoked']||$invite['used_by']||$invite['expires']<=time())throw new CatalogException('La invitación venció o ya fue utilizada. Pedí una nueva.',410);
    if($invite['recipient_id']!==null&&(int)$invite['recipient_id']!==$user)throw new CatalogException('Esta invitación corresponde a otra cuenta.',403);
    $branch=(int)$invite['branch_id'];$issuer=team_require($db,(int)$invite['invited_by'],$branch,true);
    if($invite['role']==='manager'&&$issuer!=='admin')throw new CatalogException('Esta invitación ya no está autorizada.',403);
    if(team_role($db,$user,$branch))throw new CatalogException('Ya tenés un vínculo activo con esta sucursal.',409);
    team_require_position($db,$branch,$position);
    stock_query($db,"INSERT INTO branch_team_members(branch_id,user_id,role,active,expires_at) VALUES(?,?,?,1,?) ON CONFLICT(branch_id,user_id) DO UPDATE SET role=excluded.role,active=1,expires_at=excluded.expires_at,revision=branch_team_members.revision+1",[$branch,$user,$invite['role'],$invite['access_seconds']===null?null:time()+(int)$invite['access_seconds']]);
    if(!stock_query($db,'UPDATE branch_team_invites SET used_by=? WHERE token_hash=? AND used_by IS NULL AND revoked=0',[$user,$hash])->rowCount())throw new CatalogException('La invitación ya fue utilizada.',409);
    team_event($db,$user,$branch,'member_joined',$user);team_select($db,$user,$branch);
}

function team_incoming(PDO $db,int $user): array {
    return stock_query($db,'SELECT i.token_hash,i.role,i.access_seconds,b.name,COALESCE(s.enabled,1) AS security_enabled FROM branch_team_invites i JOIN stock_branches b ON b.id=i.branch_id LEFT JOIN branch_team_security s ON s.branch_id=b.id WHERE i.recipient_id=? AND i.revoked=0 AND i.used_by IS NULL AND i.expires>? ORDER BY i.expires DESC',[$user,time()])->fetchAll();
}
function team_change_role(PDO $db,int $user,int $branch,int $target,string $role,int $revision): void {
    if(team_require($db,$user,$branch,true)!=='admin'||$target===$user||!in_array(team_role($db,$target,$branch),['manager','operator'],true))throw new CatalogException('Solo el administrador puede cambiar este rango.',403);
    if(!in_array($role,['manager','operator'],true))throw new CatalogException('Elegí un rango válido.',422);
    if(!stock_query($db,'UPDATE branch_team_members SET role=?,revision=revision+1 WHERE branch_id=? AND user_id=? AND active=1 AND revision=?',[$role,$branch,$target,$revision])->rowCount())throw new CatalogException('El vínculo cambió. Actualizá el equipo.',409);
    if($role==='operator')stock_query($db,'UPDATE branch_team_invites SET revoked=1 WHERE branch_id=? AND invited_by=? AND used_by IS NULL',[$branch,$target]);
    team_event($db,$user,$branch,'member_role_changed',$target);
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
function team_scan(PDO $db,int $user,string $code,int $expectedBranch,array $position=[]): array {
    $branch=team_active($db,$user);
    if(!$branch||$branch!==$expectedBranch)throw new CatalogException('La sucursal activa cambió. Volvé a escanear.',409);
    team_require($db,$user,$branch);
    team_require_position($db,$branch,$position);
    $label=stock_label($db,$code);
    if(!$label['pallet_id'])throw new CatalogException('Este QR todavía no tiene un palet. Su dueño debe prepararlo primero.',409);
    $p=stock_query($db,'SELECT * FROM pallets WHERE id=? AND released=0',[$label['pallet_id']])->fetch();
    if(!$p)throw new CatalogException('El palet ya no está disponible.',409);
    $assigned=false;
    if((int)($p['branch_id']??0)!==$branch){
        $assigned=(bool)stock_query($db,'UPDATE pallets SET branch_id=?,revision=revision+1,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND revision=? AND released=0',[$branch,$user,$p['id'],$p['revision']])->rowCount();
        if($assigned){
            team_event($db,$user,$branch,'pallet_assigned',null,(int)$p['id']);
            $name=stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$branch])->fetchColumn();
            stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(?,?,?,?)',[$user,$p['id'],'branch_updated',json_encode(['old'=>$p['branch_id']?stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$p['branch_id']])->fetchColumn():'Sin sucursal','new'=>$name],JSON_UNESCAPED_UNICODE)]);
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
    $members=stock_query($db,'SELECT m.user_id,m.role,m.revision,m.expires_at,u.public_name FROM branch_team_members m JOIN users u ON u.id=m.user_id WHERE m.branch_id=? AND m.active=1 AND (m.expires_at IS NULL OR m.expires_at>?) ORDER BY m.role,u.public_name',[$branch,time()])->fetchAll();
    foreach($members as &$member)$member['name']=get_public_user_name(['id'=>$member['user_id'],'public_name'=>$member['public_name']]);unset($member);
    $owner=stock_query($db,'SELECT u.id AS user_id,u.public_name FROM branch_workspace_locations l JOIN branch_workspaces w ON w.id=l.workspace_id JOIN users u ON u.id=w.owner_id WHERE l.branch_id=?',[$branch])->fetch();
    if($owner){$owner['name']=get_public_user_name(['id'=>$owner['user_id'],'public_name'=>$owner['public_name']]);$owner['role']='admin';$owner['revision']=0;array_unshift($members,$owner);}
    $warning=stock_warning_days($db,$user);$limit=(new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires')))->modify('+'.$warning.' days')->format('Y-m-d');
    $pallets=stock_query($db,"SELECT p.id,p.code,p.description,p.updated_at,u.public_name AS owner_name,MIN(NULLIF(b.expires,'')) AS nearest_expiry,COALESCE(SUM(CASE WHEN b.expires<>'' AND b.expires<=? THEN 1 ELSE 0 END),0) AS expiry_count,COALESCE(SUM(b.units),0) AS units,COUNT(b.id) AS products FROM pallets p JOIN users u ON u.id=p.owner_id LEFT JOIN pallet_products b ON b.pallet_id=p.id WHERE p.branch_id=? AND p.released=0 AND p.id>? GROUP BY p.id,p.code,p.description,p.updated_at,u.public_name ORDER BY p.id LIMIT 31",[$limit,$branch,$cursor])->fetchAll();
    $more=count($pallets)>30;$pallets=array_slice($pallets,0,30);$tags=array_column(stock_user_tags($db,$user),'name','pallet_id');foreach($pallets as &$pallet){$pallet['tag']=$tags[$pallet['id']]??'';$pallet['expiry']=$pallet['nearest_expiry']?stock_expiry_state($pallet['nearest_expiry'],$warning):null;}unset($pallet);
    $events=stock_query($db,"SELECT e.id,e.action,e.created_at,u.public_name AS actor,t.public_name AS target,p.code FROM branch_team_events e JOIN users u ON u.id=e.actor_id LEFT JOIN users t ON t.id=e.target_id LEFT JOIN pallets p ON p.id=e.pallet_id WHERE e.branch_id=? ORDER BY e.id DESC LIMIT 25",[$branch])->fetchAll();
    require_once __DIR__.'/pallet-activity.php';
    $rows=stock_query($db,'SELECT a.id,a.action,a.metadata,a.created_at,a.user_id AS actor_id,u.public_name,p.code FROM pallet_activity a JOIN pallets p ON p.id=a.pallet_id LEFT JOIN users u ON u.id=a.user_id WHERE p.branch_id=? ORDER BY a.id DESC LIMIT 25',[$branch])->fetchAll();
    $invites=stock_query($db,'SELECT i.token_hash,i.role,i.expires,i.access_seconds,u.public_name AS recipient FROM branch_team_invites i LEFT JOIN users u ON u.id=i.recipient_id WHERE i.branch_id=? AND i.revoked=0 AND i.used_by IS NULL AND i.expires>? ORDER BY i.expires DESC',[$branch,time()])->fetchAll();
    $movements=pallet_activity_present($db,$rows);foreach($movements as $index=>&$movement)$movement['code']=$rows[$index]['code'];unset($movement);
    return ['members'=>$members,'pallets'=>$pallets,'next_cursor'=>$more?(int)end($pallets)['id']:null,'events'=>$events,'movements'=>$movements,'invites'=>$invites];
}

// Remove only the company link; the shared directory and stock remain intact.
function team_unlink_branch(PDO $db,int $user,int $branch,int $revision): void {
    if(team_require($db,$user,$branch)!=='admin')throw new CatalogException('Solo el administrador de la sucursal puede desvincularla.',403);
    $before=stock_query($db,'SELECT * FROM stock_branches WHERE id=?',[$branch])->fetch();
    if(!$before||(int)$before['revision']!==$revision)throw new CatalogException('La sucursal cambió. Actualizá antes de desvincularla.',409);
    $workspace=stock_query($db,'SELECT w.id,w.name,w.owner_id FROM branch_workspace_locations l JOIN branch_workspaces w ON w.id=l.workspace_id WHERE l.branch_id=?',[$branch])->fetch();
    require_once __DIR__.'/stock-branches.php';
    team_event($db,$user,$branch,'branch_unlinked');branch_event($db,$user,'branch_unlinked',$before,$before);
    stock_query($db,'UPDATE branch_team_members SET active=0,revision=revision+1 WHERE branch_id=? AND active=1',[$branch]);
    stock_query($db,'UPDATE branch_team_invites SET revoked=1 WHERE branch_id=?',[$branch]);
    stock_query($db,'DELETE FROM branch_team_active WHERE branch_id=?',[$branch]);
    stock_query($db,'DELETE FROM branch_workspace_locations WHERE branch_id=?',[$branch]);
    if($workspace&&!stock_query($db,'SELECT 1 FROM branch_workspace_locations WHERE workspace_id=?',[$workspace['id']])->fetchColumn()){
        stock_query($db,'DELETE FROM branch_workspaces WHERE id=?',[$workspace['id']]);
        stock_query($db,'INSERT INTO branch_events(actor_id,branch_name,action,before_data,after_data) VALUES(?,?,?,?,?)',[$user,$workspace['name'],'workspace_deleted',json_encode($workspace,JSON_UNESCAPED_UNICODE),json_encode(['reason'=>'last_branch_unlinked'],JSON_UNESCAPED_UNICODE)]);
    }
}
// Compatibility for old clients: this action no longer deletes the directory entry.
function team_delete_branch(PDO $db,int $user,int $branch,int $revision): void {team_unlink_branch($db,$user,$branch,$revision);}
