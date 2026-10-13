<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';
function pallet_activity_snapshot(PDO $db,int $id): ?array {
    $p=stock_query($db,'SELECT id,description,released FROM pallets WHERE id=?',[$id])->fetch();if(!$p)return null;
    $p['products']=stock_query($db,'SELECT id,ean,name,units,expires FROM pallet_products WHERE pallet_id=?',[$id])->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
    $p['members']=stock_query($db,"SELECT user_id FROM pallet_access WHERE pallet_id=? AND status='accepted'",[$id])->fetchAll(PDO::FETCH_COLUMN);
    return $p;
}
function pallet_activity_record(PDO $db,int $user,int $id,?array $before,?array $after): void {
    if(!$after)return;
    $emit=static function(string $action,array $metadata=[],?int $product=null)use($db,$user,$id):void{stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,product_id,action,metadata) VALUES(?,?,?,?,?)',[$user,$id,$product,$action,json_encode($metadata,JSON_UNESCAPED_UNICODE)]);};
    if(!$before)$emit('pallet_created');
    if($before&&$before['released']!=$after['released'])$emit('pallet_deleted');
    if(($before['description']??'')!==$after['description'])$emit('description_updated',['old'=>$before['description']??'','new'=>$after['description']]);
    $old=$before['products']??[];$new=$after['products'];
    foreach($new as $pid=>$product){
        $meta=['ean'=>$product['ean'],'reference'=>$product['name']];
        if(!isset($old[$pid])){$emit('product_added',[...$meta,'units'=>(int)$product['units']],(int)$pid);if($product['expires']!=='')$emit('expiry_added',[...$meta,'old'=>'','new'=>$product['expires']],(int)$pid);continue;}
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
    foreach(array_diff($after['members'],$before['members']??[]) as $member)$emit('member_added',['member_id'=>(int)$member]);
    foreach(array_diff($before['members']??[],$after['members']) as $member)$emit('member_removed',['member_id'=>(int)$member]);
}
function pallet_activity_list(PDO $db,int $user,int $id,int $cursor=0,int $limit=25): array {
    $p=stock_query($db,'SELECT owner_id FROM pallets WHERE id=?',[$id])->fetch();
    if(!$p)throw new CatalogException('No encontramos este palet.',404);
    if((int)$p['owner_id']!==$user&&!stock_is_admin($db,$user)&&stock_query($db,'SELECT status FROM pallet_access WHERE pallet_id=? AND user_id=?',[$id,$user])->fetchColumn()!=='accepted')throw new CatalogException('No tenés acceso a la actividad de este palet.',403);
    $limit=max(1,min(30,$limit));$args=[$id];$filter='';if($cursor>0){$filter=' AND a.id<?';$args[]=$cursor;}
    // One join for actors; a batched member lookup below avoids per-event queries.
    $rows=stock_query($db,"SELECT a.id,a.action,a.metadata,a.created_at,u.public_name FROM pallet_activity a LEFT JOIN users u ON u.id=a.user_id WHERE a.pallet_id=?{$filter} ORDER BY a.id DESC LIMIT ".($limit+1),$args)->fetchAll();
    $more=count($rows)>$limit;$rows=array_slice($rows,0,$limit);$memberIds=[];
    foreach($rows as &$row){$row['metadata']=json_decode($row['metadata'],true)??[];if(isset($row['metadata']['member_id']))$memberIds[]=(int)$row['metadata']['member_id'];}unset($row);
    $members=[];if($memberIds){$ids=array_values(array_unique($memberIds));foreach(stock_query($db,'SELECT id,public_name FROM users WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).')',$ids)->fetchAll() as $member)$members[(int)$member['id']]=$member['public_name'];}
    $items=[];foreach($rows as $row){$meta=$row['metadata'];if(isset($meta['member_id'])){$meta['member']=get_public_user_name(['public_name'=>$members[$meta['member_id']]??'','name'=>'Usuario']);unset($meta['member_id']);}$items[]=['action'=>$row['action'],'metadata'=>$meta,'actor'=>get_public_user_name(['public_name'=>$row['public_name'],'name'=>'Usuario']),'created_at'=>$row['created_at']];}
    return ['items'=>$items,'next_cursor'=>$more?(int)end($rows)['id']:null];
}
