<?php
// Shared by API acceptance and branch scanning. Never trust a client-calculated distance.
function team_invite_deadline(PDO $db,int $user,int $branch): int {
    $until=stock_query($db,'SELECT expires_at FROM branch_team_members WHERE branch_id=? AND user_id=? AND active=1',[$branch,$user])->fetchColumn();
    return $until?min(time()+259200,(int)$until):time()+259200;
}
function team_security(PDO $db,int $branch): array {
    $row=stock_query($db,'SELECT b.latitude AS original_latitude,b.longitude AS original_longitude,s.latitude,s.longitude,s.radius,s.revision,s.enabled,s.default_seconds,s.has_point FROM stock_branches b LEFT JOIN branch_team_security s ON s.branch_id=b.id WHERE b.id=?',[$branch])->fetch();
    if(!$row)throw new CatalogException('Sucursal no disponible.',404);
    return ['enabled'=>(int)($row['enabled']??1),'default_seconds'=>array_key_exists('default_seconds',$row)&&$row['revision']!==null?$row['default_seconds']:25200,'latitude'=>(int)($row['has_point']??1)===0?null:($row['latitude']??$row['original_latitude']),'longitude'=>(int)($row['has_point']??1)===0?null:($row['longitude']??$row['original_longitude']),'radius'=>(int)($row['radius']??200),'revision'=>(int)($row['revision']??0)];
}
function team_coordinate(mixed $value,float $limit): float {
    if(!is_scalar($value)||filter_var($value,FILTER_VALIDATE_FLOAT)===false||!is_finite((float)$value)||abs((float)$value)>$limit)throw new CatalogException('La ubicación no es válida. Volvé a intentarlo.',422);
    return (float)$value;
}
function team_require_position(PDO $db,int $branch,array $position): void {
    $site=team_security($db,$branch);if(!$site['enabled'])return;
    if($site['latitude']===null||$site['longitude']===null)throw new CatalogException('El administrador debe marcar la ubicación de esta sucursal.',409);
    foreach(['latitude','longitude','accuracy','position_at'] as $field)if(!isset($position[$field]))throw new CatalogException('Permití compartir tu ubicación para continuar.',422);
    $latitude=team_coordinate($position['latitude'],90);$longitude=team_coordinate($position['longitude'],180);
    $accuracy=team_coordinate($position['accuracy'],100000);$at=team_coordinate($position['position_at'],PHP_INT_MAX);
    if($accuracy<=0||$accuracy>min(100,$site['radius']/2)||$at<time()-60||$at>time()+10)throw new CatalogException('No pudimos verificar una ubicación reciente y precisa. Volvé a intentarlo.',422);
    $lat=deg2rad($latitude-(float)$site['latitude']);$lon=deg2rad($longitude-(float)$site['longitude']);
    $h=sin($lat/2)**2+cos(deg2rad($latitude))*cos(deg2rad((float)$site['latitude']))*sin($lon/2)**2;
    $distance=6371000*2*asin(sqrt(min(1,max(0,$h))));
    if($distance+$accuracy>$site['radius'])throw new CatalogException('No estás en esta sucursal. Acercate o abrí el palet desde Stock.',403);
}
function team_security_save(PDO $db,int $user,int $branch,array $data): void {
    if(team_require($db,$user,$branch)!=='admin')throw new CatalogException('Solo el administrador puede configurar la ubicación.',403);
    $old=team_security($db,$branch);$revision=(int)($data['revision']??-1);
    if($revision!==$old['revision'])throw new CatalogException('La ubicación cambió. Actualizá la página.',409);
    $enabled=$data['enabled']??'1';if(!in_array((string)$enabled,['0','1'],true))throw new CatalogException('Seguridad no válida.',422);
    $seconds=$data['default_seconds']??25200;if(!is_scalar($seconds)||!preg_match('/^(0|3600|14400|25200|43200)$/D',(string)$seconds))throw new CatalogException('Duración no válida.',422);if($seconds==='0'||$seconds===0)$seconds=null;
    if($seconds!==null&&!in_array((int)$seconds,[3600,14400,25200,43200],true))throw new CatalogException('Duración no válida.',422);
    $hasPoint=isset($data['latitude'],$data['longitude'])&&$data['latitude']!==''&&$data['longitude']!=='';
    if((int)$enabled&&!$hasPoint)throw new CatalogException('Marcá la ubicación para activar seguridad.',422);
    $latitude=$hasPoint?team_coordinate($data['latitude'],90):0;$longitude=$hasPoint?team_coordinate($data['longitude'],180):0;
    $radius=filter_var($data['radius']??null,FILTER_VALIDATE_INT);
    if($radius===false||$radius<50||$radius>1000)throw new CatalogException('Elegí un radio entre 50 y 1000 metros.',422);
    stock_query($db,'INSERT INTO branch_team_security(branch_id,latitude,longitude,radius,enabled,default_seconds,has_point) VALUES(?,?,?,?,?,?,?) ON CONFLICT(branch_id) DO UPDATE SET latitude=excluded.latitude,longitude=excluded.longitude,radius=excluded.radius,enabled=excluded.enabled,default_seconds=excluded.default_seconds,has_point=excluded.has_point,revision=branch_team_security.revision+1',[$branch,$latitude,$longitude,$radius,(int)$enabled,$seconds,(int)$hasPoint]);
    team_event($db,$user,$branch,'location_security_updated',null,null,['old_radius'=>$old['radius'],'radius'=>(int)$radius,'old_security'=>$old['enabled'],'security'=>(int)$enabled,'old_seconds'=>$old['default_seconds'],'access_seconds'=>$seconds,'point_changed'=>$hasPoint&&($old['latitude']!=$latitude||$old['longitude']!=$longitude)]);
}
function team_accept_location(PDO $db,int $user,string $proof,bool $pending,array $position): void {
    if(!preg_match('/^[a-f0-9]{64}$/D',$proof))throw new CatalogException('La invitación no está disponible.',404);
    $hash=$pending?$proof:hash('sha256',$proof);
    $invite=stock_query($db,'SELECT branch_id,recipient_id,used_by,revoked,expires FROM branch_team_invites WHERE token_hash=?',[$hash])->fetch();
    if(!$invite||$invite['used_by']||$invite['revoked']||$invite['expires']<=time())throw new CatalogException('La invitación venció o ya fue utilizada.',410);
    if(($pending&&(int)$invite['recipient_id']!==$user)||($invite['recipient_id']!==null&&(int)$invite['recipient_id']!==$user))throw new CatalogException('Esta invitación corresponde a otra cuenta.',403);
    team_accept_hash($db,$user,$hash,$position);
}
