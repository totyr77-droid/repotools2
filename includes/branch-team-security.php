<?php
// Token duration and legacy location fields; GPS no longer grants or blocks team access.
function team_invite_deadline(PDO $db,int $user,int $branch): int {
    $until=stock_query($db,'SELECT expires_at FROM branch_team_members WHERE branch_id=? AND user_id=? AND active=1',[$branch,$user])->fetchColumn();
    return $until?min(time()+259200,(int)$until):time()+259200;
}
function team_security(PDO $db,int $branch): array {
    $row=stock_query($db,'SELECT b.latitude AS original_latitude,b.longitude AS original_longitude,s.latitude,s.longitude,s.radius,s.revision,s.enabled,s.default_seconds,s.has_point FROM stock_branches b LEFT JOIN branch_team_security s ON s.branch_id=b.id WHERE b.id=?',[$branch])->fetch();
    if(!$row)throw new CatalogException('Sucursal no disponible.',404);
    return ['enabled'=>0,'default_seconds'=>array_key_exists('default_seconds',$row)&&$row['revision']!==null?$row['default_seconds']:25200,'latitude'=>(int)($row['has_point']??1)===0?null:($row['latitude']??$row['original_latitude']),'longitude'=>(int)($row['has_point']??1)===0?null:($row['longitude']??$row['original_longitude']),'radius'=>(int)($row['radius']??200),'revision'=>(int)($row['revision']??0)];
}
function team_coordinate(mixed $value,float $limit): float {
    if(!is_scalar($value)||filter_var($value,FILTER_VALIDATE_FLOAT)===false||!is_finite((float)$value)||abs((float)$value)>$limit)throw new CatalogException('La ubicación no es válida. Volvé a intentarlo.',422);
    return (float)$value;
}
function team_require_position(PDO $db,int $branch,array $position): void {
    // Team tokens grant membership; GPS is no longer an authorization mechanism.
    return;
}
function team_security_save(PDO $db,int $user,int $branch,array $data): void {
    if(team_require($db,$user,$branch)!=='admin')throw new CatalogException('Solo el administrador puede configurar la sucursal.',403);
    $old=team_security($db,$branch);$revision=(int)($data['revision']??-1);
    if($revision!==$old['revision'])throw new CatalogException('La configuración cambió. Actualizá la página.',409);
    $enabled='0';if(!in_array((string)$enabled,['0','1'],true))throw new CatalogException('Seguridad no válida.',422);
    $seconds=$data['default_seconds']??25200;if(!is_scalar($seconds)||!preg_match('/^(0|3600|14400|25200|43200)$/D',(string)$seconds))throw new CatalogException('Duración no válida.',422);if($seconds==='0'||$seconds===0)$seconds=null;
    if($seconds!==null&&!in_array((int)$seconds,[3600,14400,25200,43200],true))throw new CatalogException('Duración no válida.',422);
    $hasPoint=isset($data['latitude'],$data['longitude'])&&$data['latitude']!==''&&$data['longitude']!=='';
    if((int)$enabled&&!$hasPoint)throw new CatalogException('Marcá la ubicación para activar seguridad.',422);
    $latitude=$hasPoint?team_coordinate($data['latitude'],90):0;$longitude=$hasPoint?team_coordinate($data['longitude'],180):0;
    $radius=filter_var($data['radius']??$old['radius'],FILTER_VALIDATE_INT);
    if($radius===false||$radius<50||$radius>1000)throw new CatalogException('Elegí un radio entre 50 y 1000 metros.',422);
    stock_query($db,'INSERT INTO branch_team_security(branch_id,latitude,longitude,radius,enabled,default_seconds,has_point) VALUES(?,?,?,?,?,?,?) ON CONFLICT(branch_id) DO UPDATE SET latitude=excluded.latitude,longitude=excluded.longitude,radius=excluded.radius,enabled=excluded.enabled,default_seconds=excluded.default_seconds,has_point=excluded.has_point,revision=branch_team_security.revision+1',[$branch,$latitude,$longitude,$radius,(int)$enabled,$seconds,(int)$hasPoint]);
    team_event($db,$user,$branch,'location_security_updated',null,null,['old_seconds'=>$old['default_seconds'],'access_seconds'=>$seconds]);
}
function team_accept_location(PDO $db,int $user,string $proof,bool $pending,array $position): void {
    if(!preg_match('/^[a-f0-9]{64}$/D',$proof))throw new CatalogException('La invitación no está disponible.',404);
    $hash=$pending?$proof:hash('sha256',$proof);
    $invite=stock_query($db,'SELECT branch_id,recipient_id,used_by,revoked,expires FROM branch_team_invites WHERE token_hash=?',[$hash])->fetch();
    if(!$invite||$invite['used_by']||$invite['revoked']||$invite['expires']<=time())throw new CatalogException('La invitación venció o ya fue utilizada.',410);
    if(($pending&&(int)$invite['recipient_id']!==$user)||($invite['recipient_id']!==null&&(int)$invite['recipient_id']!==$user))throw new CatalogException('Esta invitación corresponde a otra cuenta.',403);
    team_accept_hash($db,$user,$hash,$position);
}
