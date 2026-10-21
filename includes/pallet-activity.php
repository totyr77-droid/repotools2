<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';
function pallet_activity_snapshot(PDO $db,int $id): ?array {
    $p=stock_query($db,'SELECT id,code,branch_id,description,released,activated,owner_id FROM pallets WHERE id=?',[$id])->fetch();if(!$p)return null;
    $p['products']=stock_query($db,'SELECT id,ean,name,units,expires,revision FROM pallet_products WHERE pallet_id=?',[$id])->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
    $p['members']=stock_query($db,"SELECT user_id FROM pallet_access WHERE pallet_id=? AND status='accepted'",[$id])->fetchAll(PDO::FETCH_COLUMN);
    $p['accesses']=stock_query($db,'SELECT user_id,status FROM pallet_access WHERE pallet_id=?',[$id])->fetchAll(PDO::FETCH_KEY_PAIR);
    return $p;
}
function pallet_activity_team_metadata(PDO $db,int $user,int $id,bool $teamContext=false): array {
    $p=stock_query($db,'SELECT owner_id,branch_id FROM pallets WHERE id=?',[$id])->fetch();
    if(!$p||!team_role($db,$user,(int)($p['branch_id']??0))||(!$teamContext&&(int)$p['owner_id']===$user))return [];
    return ['source'=>'team'];
}
function pallet_activity_record(PDO $db,int $user,int $id,?array $before,?array $after,bool $teamContext=false): void {
    if(!$after)return;
    $source=pallet_activity_team_metadata($db,$user,$id,$teamContext);
    $emit=static function(string $action,array $metadata=[],?int $product=null)use($db,$user,$id,$source):void{stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,product_id,action,metadata) VALUES(?,?,?,?,?)',[$user,$id,$product,$action,json_encode([...$metadata,...$source],JSON_UNESCAPED_UNICODE)]);};
    if(!$before)$emit('pallet_created');
    if($before&&$before['released']!=$after['released'])$emit('pallet_deleted');
    if(($before['description']??'')!==$after['description'])$emit('description_updated',['old'=>$before['description']??'','new'=>$after['description']]);
    $old=$before['products']??[];$new=$after['products'];
    foreach($new as $pid=>$product){
        $meta=['ean'=>$product['ean'],'reference'=>$product['name']];
        if(!isset($old[$pid])){$emit('product_added',[...$meta,'units'=>(int)$product['units'],'expires'=>$product['expires']],(int)$pid);continue;}
        foreach(['units'=>'quantity_updated','expires'=>'expiry_updated','name'=>'reference_updated'] as $field=>$action)if((string)$old[$pid][$field]!== (string)$product[$field]){
            if($field==='expires')$action=$old[$pid][$field]===''?'expiry_added':($product[$field]===''?'expiry_removed':'expiry_updated');
            $emit($action,[...$meta,'old'=>$old[$pid][$field],'new'=>$product[$field]],(int)$pid);
        }
    }
    foreach($old as $pid=>$product)if(!isset($new[$pid])){
        $merged=null;foreach($new as $target=>$v)if($v['ean']===$product['ean']&&isset($old[$target])&&(int)$v['units']>(int)$old[$target]['units']){$merged=$v;break;}
        $emit($merged?'product_merged':'product_removed',['ean'=>$product['ean'],'reference'=>$product['name'],'units'=>(int)$product['units']]);
        if($merged&&$merged['expires']!==$product['expires'])$emit('expiry_updated',['ean'=>$product['ean'],'reference'=>$product['name'],'old'=>$product['expires'],'new'=>$merged['expires']]);
    }
    foreach($after['accesses']??[] as $member=>$status)if($status==='pending'&&($before['accesses'][$member]??null)!=='pending')$emit('member_invited',['member_id'=>(int)$member]);
    foreach($before['accesses']??[] as $member=>$status)if($status==='pending'&&!isset($after['accesses'][$member]))$emit((int)$member===$user?'invitation_declined':'invitation_cancelled',['member_id'=>(int)$member]);
    foreach(array_diff($after['members'],$before['members']??[]) as $member)$emit('member_added',['member_id'=>(int)$member]);
    foreach(array_diff($before['members']??[],$after['members']) as $member)$emit('member_removed',['member_id'=>(int)$member]);
}
function pallet_activity_initial_joins(PDO $db): string {
    $pgsql=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql';
    $expiryEan=$pgsql?"(a.metadata::jsonb->>'ean')=(initial_expiry.metadata::jsonb->>'ean')":"json_extract(a.metadata,'$.ean')=json_extract(initial_expiry.metadata,'$.ean')";
    $addEan=$pgsql?"(a.metadata::jsonb->>'ean')=(initial_add.metadata::jsonb->>'ean')":"json_extract(a.metadata,'$.ean')=json_extract(initial_add.metadata,'$.ean')";
    // Fold legacy consecutive initial expiry records for presentation; keep the raw audit rows.
    return " LEFT JOIN pallet_activity initial_expiry ON a.action='product_added' AND initial_expiry.id=a.id+1 AND initial_expiry.action='expiry_added' AND initial_expiry.pallet_id=a.pallet_id AND (initial_expiry.product_id=a.product_id OR (initial_expiry.product_id IS NULL AND a.product_id IS NULL AND {$expiryEan})) AND initial_expiry.user_id=a.user_id AND initial_expiry.created_at=a.created_at LEFT JOIN pallet_activity initial_add ON a.action='expiry_added' AND initial_add.id=a.id-1 AND initial_add.action='product_added' AND initial_add.pallet_id=a.pallet_id AND (initial_add.product_id=a.product_id OR (initial_add.product_id IS NULL AND a.product_id IS NULL AND {$addEan})) AND initial_add.user_id=a.user_id AND initial_add.created_at=a.created_at ";
}
function pallet_activity_list(PDO $db,int $user,int $id,int $cursor=0,int $limit=25,int $event=0): array {
    $p=stock_query($db,'SELECT owner_id,code,branch_id FROM pallets WHERE id=?',[$id])->fetch();
    if(!$p)throw new CatalogException('No encontramos este palet.',404);
    $allowed=(int)$p['owner_id']===$user||stock_is_admin($db,$user)||team_role($db,$user,(int)($p['branch_id']??0))||stock_query($db,'SELECT status FROM pallet_access WHERE pallet_id=? AND user_id=?',[$id,$user])->fetchColumn()==='accepted';
    $pendingBranch=0;if(!$allowed){$pendingBranch=team_active($db,$user);if(!$pendingBranch||!team_pallet_request($db,$id,$pendingBranch))throw new CatalogException('No tenés acceso a la actividad de este palet.',403);}
    $limit=max(1,min(30,$limit));$args=[$id];$filter='';if($event){$filter=' AND a.id=?';$args[]=$event;}elseif($cursor>0){$filter=' AND a.id<?';$args[]=$cursor;}
    if($pendingBranch){$branchMeta=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?"COALESCE(a.metadata::jsonb->>'branch_id','0')":"COALESCE(json_extract(a.metadata,'$.branch_id'),0)";$filter.=" AND a.action='team_pending_edited' AND CAST($branchMeta AS BIGINT)=?";$args[]=$pendingBranch;}
    $join=pallet_activity_initial_joins($db);$rows=stock_query($db,"SELECT a.id,a.action,a.metadata,a.created_at,a.user_id AS actor_id,u.public_name,initial_expiry.metadata AS initial_expiry FROM pallet_activity a LEFT JOIN users u ON u.id=a.user_id {$join} WHERE initial_add.id IS NULL AND a.pallet_id=?{$filter} ORDER BY a.id DESC LIMIT ".($event?1:$limit+1),$args)->fetchAll();
    if($event&&!$rows)throw new CatalogException('Este movimiento no está disponible.',404);
    $more=count($rows)>$limit;$rows=array_slice($rows,0,$limit);$items=pallet_activity_present($db,$rows);foreach($items as &$item){$item['pallet_id']=$id;$item['code']=$p['code'];}unset($item);
    return $event?['item'=>$items[0]]:['items'=>$items,'next_cursor'=>$more?(int)end($rows)['id']:null];
}
function pallet_activity_recent(PDO $db,int $user): array {
    $join=pallet_activity_initial_joins($db);$rows=stock_query($db,"SELECT a.id,a.action,a.metadata,a.created_at,a.user_id AS actor_id,u.public_name,p.code,initial_expiry.metadata AS initial_expiry FROM pallet_activity a JOIN pallets p ON p.id=a.pallet_id LEFT JOIN users u ON u.id=a.user_id {$join} WHERE initial_add.id IS NULL AND p.released=0 AND p.activated=1 AND (p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access v WHERE v.pallet_id=p.id AND v.user_id=? AND v.status='accepted')) ORDER BY a.id DESC LIMIT 5",[$user,$user])->fetchAll();
    $items=pallet_activity_present($db,$rows);foreach($items as $i=>&$item)$item['code']=$rows[$i]['code'];unset($item);return $items;
}
function pallet_activity_feed(PDO $db,int $user,int $cursor=0,int $event=0): array {
    $args=[$user,$user];$filter='';if($event){$filter=' AND a.id=?';$args[]=$event;}elseif($cursor){$filter=' AND a.id<?';$args[]=$cursor;}
    $join=pallet_activity_initial_joins($db);$rows=stock_query($db,"SELECT a.id,a.action,a.metadata,a.created_at,a.user_id AS actor_id,u.public_name,p.id AS pallet_id,p.code,initial_expiry.metadata AS initial_expiry FROM pallet_activity a JOIN pallets p ON p.id=a.pallet_id LEFT JOIN users u ON u.id=a.user_id {$join} WHERE initial_add.id IS NULL AND (p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access v WHERE v.pallet_id=p.id AND v.user_id=? AND v.status='accepted')){$filter} ORDER BY a.id DESC LIMIT ".($event?1:26),$args)->fetchAll();
    if($event&&!$rows)throw new CatalogException('Este movimiento no está disponible.',404);
    $more=count($rows)>25;$rows=array_slice($rows,0,25);$items=pallet_activity_present($db,$rows);foreach($items as $i=>&$item){$item['pallet_id']=(int)$rows[$i]['pallet_id'];$item['code']=$rows[$i]['code'];}unset($item);
    return $event?['item'=>$items[0]]:['items'=>$items,'next_cursor'=>$more?(int)end($rows)['id']:null];
}
function pallet_activity_present(PDO $db,array $rows): array {
    $memberIds=[];
    foreach($rows as &$row){$row['metadata']=json_decode($row['metadata'],true)??[];if(isset($row['metadata']['member_id']))$memberIds[]=(int)$row['metadata']['member_id'];}unset($row);
    $members=[];if($memberIds){$ids=array_values(array_unique($memberIds));foreach(stock_query($db,'SELECT id,public_name FROM users WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).')',$ids)->fetchAll() as $member)$members[(int)$member['id']]=$member['public_name'];}
    $items=[];foreach($rows as $row){$meta=$row['metadata'];if($row['action']==='product_added'&&!empty($row['initial_expiry']))$meta['expires']=(json_decode($row['initial_expiry'],true)??[])['new']??'';if(isset($meta['member_id'])){$meta['member']=get_public_user_name(['public_name'=>$members[$meta['member_id']]??'','id'=>$meta['member_id']]);unset($meta['member_id']);}$items[]=['id'=>(int)$row['id'],'action'=>$row['action'],'metadata'=>$meta,'actor'=>get_public_user_name(['public_name'=>$row['public_name'],'id'=>$row['actor_id']??0]),'created_at'=>$row['created_at']];}
    return $items;
}
