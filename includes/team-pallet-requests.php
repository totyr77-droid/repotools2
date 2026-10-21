<?php
// Pending merchandise is isolated from confirmed Stock. Callers own the transaction.
function team_pallet_request(PDO $db,int $pallet,int $branch): ?array {
    return stock_query($db,"SELECT r.* FROM team_pallet_requests r JOIN pallets p ON p.id=r.pallet_id AND p.owner_id=r.owner_id AND p.released=0 WHERE r.pallet_id=? AND r.branch_id=? AND r.status='pending'",[$pallet,$branch])->fetch()?:null;
}
function team_request_start(PDO $db,int $user,array $p,int $branch): array {
    team_require($db,$user,$branch);
    stock_query($db,"UPDATE team_pallet_requests SET status='cancelled',revision=revision+1 WHERE pallet_id=? AND owner_id<>? AND status='pending'",[$p['id'],$p['owner_id']]);
    if($r=team_pallet_request($db,(int)$p['id'],$branch))return $r;
    $id=(int)stock_query($db,'INSERT INTO team_pallet_requests(pallet_id,branch_id,owner_id,requested_by,base_revision) VALUES(?,?,?,?,?) RETURNING id',[$p['id'],$branch,$p['owner_id'],$user,$p['revision']])->fetchColumn();
    stock_query($db,'INSERT INTO team_pallet_products(request_id,pallet_id,ean,name,expires,units,kind,pack_size) SELECT ?,pallet_id,ean,name,expires,units,kind,pack_size FROM pallet_products WHERE pallet_id=?',[$id,$p['id']]);
    $name=stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$branch])->fetchColumn();
    stock_query($db,'INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?)',[$p['owner_id'],'team-pallet-request:'.$id,'team_pallet_request','Solicitud de acceso a tu palet',$name.' solicita editar el palet '.$p['code'].'. Revisá los cambios pendientes antes de aceptar.']);
    team_event($db,$user,$branch,'pallet_access_requested',(int)$p['owner_id'],(int)$p['id'],['request_id'=>$id,'status'=>'pending']);
    return team_pallet_request($db,(int)$p['id'],$branch);
}
function team_request_products(PDO $db,int $request): array {
    $rows=stock_query($db,'SELECT b.*,c.name AS catalog_product,c.brand AS catalog_brand,c.quantity AS catalog_quantity FROM team_pallet_products b LEFT JOIN products c ON c.ean=b.ean WHERE b.request_id=? ORDER BY b.expires,b.id',[$request])->fetchAll();
    return array_map('stock_product_names',$rows);
}
function team_request_detail(PDO $db,string $code,int $user,int $branch): array {
    team_require($db,$user,$branch);
    $p=stock_detail($db,$code,$user);$id=(int)($p['id']??0);
    if(!$id)throw new CatalogException('El propietario debe preparar este palet primero.',409);
    $r=team_pallet_request($db,$id,$branch);
    if(!$r){if((int)($p['branch_id']??0)!==$branch)throw new CatalogException('Este palet no tiene acceso autorizado a tu equipo. Volvé a escanearlo.',403);return $p;}
    $original=stock_query($db,'SELECT COUNT(*) AS products,COALESCE(SUM(units),0) AS units FROM pallet_products WHERE pallet_id=?',[$id])->fetch();
    $originalProducts=$p['products'];
    $p['products']=team_request_products($db,(int)$r['id']);$p['revision']=(int)$r['revision'];$p['branch_id']=$branch;
    $p['branch']=stock_query($db,'SELECT name,address,locality,province,description FROM stock_branches WHERE id=?',[$branch])->fetch();
    foreach(['can_manage','can_delete','can_transfer_stock','can_tag','can_edit_description','is_owner'] as $flag)$p[$flag]=false;
    $p['team_tag']=in_array(team_role($db,$user,$branch),['admin','manager'],true)?(stock_query($db,'SELECT t.name FROM pallet_user_tags t JOIN pallets p ON p.id=t.pallet_id AND p.owner_id=t.user_id WHERE p.id=?',[$id])->fetchColumn()?:''):'';
    $p['description']=(string)stock_query($db,'SELECT description FROM pallets WHERE id=?',[$id])->fetchColumn();unset($p['members']);
    $p['can_edit']=true;$p['team_access']=true;$p['can_remove_branch']=in_array(team_role($db,$user,$branch),['admin','manager'],true);
    $p['team_request']=['id'=>(int)$r['id'],'revision'=>(int)$r['revision'],'status'=>'pending','original'=>$original,'original_products'=>$originalProducts];
    return $p;
}
function team_request_edit(PDO $db,int $user,int $branch,array $input): ?array {
    team_require($db,$user,$branch);$id=(int)($input['pallet_id']??0);
    $r=team_pallet_request($db,$id,$branch);
    if(!$r){$p=stock_query($db,'SELECT branch_id FROM pallets WHERE id=? AND released=0',[$id])->fetch();if(!$p||(int)$p['branch_id']!==$branch)throw new CatalogException('Este palet no pertenece a tu equipo.',403);return null;}
    if(isset($input['offline_owner']))throw new CatalogException('Los cambios pendientes de aprobación requieren conexión.',409);
    $action=$input['action']??'';if(!in_array($action,['save_product','deduct_product','delete_product'],true))throw new CatalogException('Mientras espera aprobación, solo podés modificar la mercadería.',403);
    $pid=(int)($input['product_id']??0);$old=$pid?stock_query($db,'SELECT * FROM team_pallet_products WHERE id=? AND request_id=?',[$pid,$r['id']])->fetch():null;
    if($pid&&(!$old||(int)$old['revision']!==(int)($input['revision']??0)))throw new CatalogException('El producto cambió. Volvé a abrirlo.',409);
    if($action==='save_product'){
        $v=stock_product_input($input);
        if($old&&$old['ean']!==$v['ean'])throw new CatalogException('El EAN del producto no puede cambiar.',422);
        $existing=stock_query($db,'SELECT * FROM team_pallet_products WHERE request_id=? AND ean=? AND expires=?',[$r['id'],$v['ean'],$v['expires']])->fetch();
        if($pid&&$existing&&(int)$existing['id']!==$pid){$v['units']+=(int)$existing['units'];$v['kind']='units';$v['pack_size']=1;stock_query($db,'DELETE FROM team_pallet_products WHERE id=? AND request_id=?',[$pid,$r['id']]);$pid=(int)$existing['id'];}
        if(!$pid){$old=stock_query($db,'SELECT * FROM team_pallet_products WHERE request_id=? AND ean=? AND expires=?',[$r['id'],$v['ean'],$v['expires']])->fetch();if($old){$pid=(int)$old['id'];$v['units']+=(int)$old['units'];}}
        if($v['units']>1000000000)throw new CatalogException('La cantidad supera el límite permitido.',422);
        if($pid)stock_query($db,'UPDATE team_pallet_products SET ean=?,name=?,expires=?,units=?,kind=?,pack_size=?,revision=revision+1 WHERE id=? AND request_id=?',[$v['ean'],$v['name'],$v['expires'],$v['units'],$v['kind'],$v['pack_size'],$pid,$r['id']]);
        else $pid=(int)stock_query($db,'INSERT INTO team_pallet_products(request_id,pallet_id,ean,name,expires,units,kind,pack_size) VALUES(?,?,?,?,?,?,?,?) RETURNING id',[$r['id'],$id,$v['ean'],$v['name'],$v['expires'],$v['units'],$v['kind'],$v['pack_size']])->fetchColumn();
    }elseif($action==='deduct_product'){
        if(!$old)throw new CatalogException('Seleccioná un producto.',422);
        $amount=filter_var($input['amount']??null,FILTER_VALIDATE_INT);$kind=$input['kind']??'units';$pack=$kind==='units'?1:filter_var($input['pack_size']??null,FILTER_VALIDATE_INT);
        if(!$amount||$amount<1||!$pack||$pack<1||!in_array($kind,['units','boxes','packs'],true)||$amount*$pack>(int)$old['units'])throw new CatalogException('Revisá la cantidad que querés retirar.',422);
        $v=[...$old,'units'=>(int)$old['units']-$amount*$pack];
        if($v['units'])stock_query($db,'UPDATE team_pallet_products SET units=?,revision=revision+1 WHERE id=?',[$v['units'],$pid]);else stock_query($db,'DELETE FROM team_pallet_products WHERE id=?',[$pid]);
    }else{if(!$old)throw new CatalogException('El producto no está disponible.',409);$v=null;stock_query($db,'DELETE FROM team_pallet_products WHERE id=?',[$pid]);}
    stock_query($db,'UPDATE team_pallet_requests SET revision=revision+1 WHERE id=?',[$r['id']]);
    $movement=['request_id'=>(int)$r['id'],'branch_id'=>$branch,'status'=>'pending','source'=>'team','ean'=>($v??$old)['ean'],'reference'=>($v??$old)['name'],'old'=>(int)($old['units']??0),'new'=>(int)($v['units']??0),'expires'=>($v??$old)['expires']];
    stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(?,?,?,?)',[$user,$id,'team_pending_edited',json_encode($movement,JSON_UNESCAPED_UNICODE)]);
    team_event($db,$user,$branch,'pallet_pending_edited',null,$id,['request_id'=>(int)$r['id'],'status'=>'pending','action'=>$action,'old'=>$old,'new'=>$v]);
    $code=(string)stock_query($db,'SELECT code FROM pallets WHERE id=?',[$id])->fetchColumn();
    return ['ok'=>true,'id'=>$id,'code'=>$code,'pending_approval'=>true,'pallet'=>team_request_detail($db,$code,$user,$branch)];
}
function team_request_resolve(PDO $db,int $user,int $id,string $decision,int $revision): void {
    $r=stock_query($db,"SELECT * FROM team_pallet_requests WHERE id=? AND status='pending'",[$id])->fetch();
    if(!$r)throw new CatalogException('La solicitud ya no está pendiente.',409);
    $p=stock_query($db,'SELECT * FROM pallets WHERE id=? AND released=0',[$r['pallet_id']])->fetch();
    $owner=$p&&(int)$p['owner_id']===$user&&(int)$r['owner_id']===$user;
    if(!$owner&&($decision!=='cancelled'||!in_array(team_role($db,$user,(int)$r['branch_id']),['admin','manager'],true)))throw new CatalogException('Solo el propietario puede aceptar o rechazar esta solicitud.',403);
    if(!in_array($decision,['accepted','rejected','cancelled'],true))throw new CatalogException('Decisión no válida.',422);
    if((int)$r['revision']!==$revision)throw new CatalogException('Hay cambios nuevos. Revisalos antes de confirmar.',409);
    if($decision==='accepted'){
        if(!stock_query($db,'SELECT 1 FROM branch_workspace_locations WHERE branch_id=?',[$r['branch_id']])->fetchColumn())throw new CatalogException('El equipo ya no está disponible.',409);
        if((int)$p['revision']!==(int)$r['base_revision'])throw new CatalogException('Modificaste el palet después de esta solicitud. Rechazala y pedí que vuelvan a escanearlo para no sobrescribir tus cambios.',409);
        $before=pallet_activity_snapshot($db,(int)$p['id']);
        stock_query($db,'DELETE FROM pallet_products WHERE pallet_id=? AND NOT EXISTS(SELECT 1 FROM team_pallet_products d WHERE d.request_id=? AND d.ean=pallet_products.ean AND d.expires=pallet_products.expires)',[$p['id'],$id]);
        foreach(stock_query($db,'SELECT * FROM team_pallet_products WHERE request_id=?',[$id])->fetchAll() as $v){
            $existing=stock_query($db,'SELECT * FROM pallet_products WHERE pallet_id=? AND ean=? AND expires=?',[$p['id'],$v['ean'],$v['expires']])->fetch();
            if(!$existing)stock_query($db,'INSERT INTO pallet_products(pallet_id,ean,name,expires,units,kind,pack_size) VALUES(?,?,?,?,?,?,?)',[$p['id'],$v['ean'],$v['name'],$v['expires'],$v['units'],$v['kind'],$v['pack_size']]);
            elseif($existing['name']!==$v['name']||(int)$existing['units']!==(int)$v['units']||$existing['kind']!==$v['kind']||(int)$existing['pack_size']!==(int)$v['pack_size'])stock_query($db,'UPDATE pallet_products SET name=?,units=?,kind=?,pack_size=?,revision=revision+1 WHERE id=?',[$v['name'],$v['units'],$v['kind'],$v['pack_size'],$existing['id']]);
        }
        stock_query($db,'UPDATE pallets SET branch_id=?,activated=1,revision=revision+1,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$r['branch_id'],$user,$p['id']]);
        // An approval never publishes merchandise through pre-existing public share links.
        stock_query($db,'INSERT INTO stock_share_exclusions(share_id,pallet_id) SELECT id,? FROM stock_shares WHERE owner_id=? AND branch_id=? AND active=1 ON CONFLICT(share_id,pallet_id) DO NOTHING',[$p['id'],$user,$r['branch_id']]);
        $oldBranch=$p['branch_id']?stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$p['branch_id']])->fetchColumn():'Sin sucursal';$newBranch=stock_query($db,'SELECT name FROM stock_branches WHERE id=?',[$r['branch_id']])->fetchColumn();
        stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(?,?,?,?)',[$user,$p['id'],'branch_updated',json_encode(['old'=>$oldBranch,'new'=>$newBranch,'request_id'=>$id,'status'=>'accepted'],JSON_UNESCAPED_UNICODE)]);
        pallet_activity_record($db,$user,(int)$p['id'],$before,pallet_activity_snapshot($db,(int)$p['id']));
    }
    stock_query($db,'UPDATE team_pallet_requests SET status=?,revision=revision+1 WHERE id=?',[$decision,$id]);
    team_event($db,$user,(int)$r['branch_id'],'pallet_request_'.$decision,null,(int)$r['pallet_id'],['request_id'=>$id,'status'=>$decision]);
}
