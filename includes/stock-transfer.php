<?php
// Transfers run inside the Stock transaction and its shared writer lock.
require_once __DIR__.'/stock.php';
function stock_transfer_preview(PDO $db,int $user,array $input): array {
    if(!isset($input['pallet_id'])||is_array($input['pallet_id'])||!preg_match('/^[1-9][0-9]{0,14}$/D',(string)$input['pallet_id']))throw new CatalogException('El palet de origen no es válido.',422);
    $source=stock_editable($db,(int)($input['pallet_id']??0),$user);
    if((int)$source['owner_id']!==$user)throw new CatalogException('Solo el propietario puede transferir este palet o su mercadería.',403);
    $mode=$input['mode']??'';if(!in_array($mode,['pallet','user'],true))throw new CatalogException('Elegí el destino de la transferencia.',422);
    $rows=stock_query($db,'SELECT * FROM pallet_products WHERE pallet_id=? ORDER BY id',[$source['id']])->fetchAll();
    $result=['mode'=>$mode,'source_id'=>(int)$source['id'],'source_code'=>$source['code'],'source_revision'=>(int)$source['revision'],'products'=>count($rows),'units'=>array_sum(array_column($rows,'units'))];
    if($mode==='user'){
        if((int)$source['owner_id']!==$user)throw new CatalogException('Solo el dueño puede transferir la propiedad.',403);
        $email=$input['email']??'';if(!is_string($email)||!filter_var(trim($email),FILTER_VALIDATE_EMAIL))throw new CatalogException('Ingresá un correo válido.',422);
        $target=stock_query($db,'SELECT id,public_name FROM users WHERE LOWER(email)=LOWER(?)',[trim($email)])->fetch();
        if(!$target)throw new CatalogException('No encontramos una cuenta con ese correo.',404);
        if((int)$target['id']===$user)throw new CatalogException('Este palet ya es tuyo.',422);
        return $result+['target_user'=>(int)$target['id'],'target_name'=>get_public_user_name($target),'email'=>trim($email)];
    }
    if(!$rows)throw new CatalogException('Este palet no tiene mercadería para trasladar.',422);
    $code=$input['target_code']??'';if(!is_string($code)||!preg_match('/^[0-9]{8}$/D',$code))throw new CatalogException('Escaneá un QR de palet o ingresá sus 8 números.',422);
    if($code===$source['code'])throw new CatalogException('Elegí un palet distinto al original.',422);
    $label=stock_label($db,$code);$target=null;
    if($label['pallet_id'])$target=stock_editable($db,(int)$label['pallet_id'],$user);
    elseif($label['reserved_by']&&(int)$label['reserved_by']!==$user)throw new CatalogException('La etiqueta de destino está reservada para otra persona.',403);
    $description=trim(implode(' · ',array_unique(array_filter([$target['description']??'',$source['description']],static fn($v)=>$v!==''))));
    if(mb_strlen($description)>300)throw new CatalogException('Las descripciones combinadas superan 300 caracteres. Ajustalas antes de trasladar.',422);
    foreach($rows as $row){$same=$target?stock_query($db,'SELECT * FROM pallet_products WHERE pallet_id=? AND ean=? AND expires=?',[$target['id'],$row['ean'],$row['expires']])->fetch():false;if($same){if((int)$same['units']+(int)$row['units']>1000000000)throw new CatalogException('La cantidad de destino supera el máximo permitido.',422);$names=implode(' / ',array_unique(array_filter([$same['name'],$row['name']],static fn($v)=>$v!=='')));if(mb_strlen($names)>120)throw new CatalogException('Las referencias combinadas son demasiado largas. Ajustalas antes de trasladar.',422);}}
    return $result+['target_code'=>$code,'target_id'=>(int)($target['id']??0),'target_revision'=>(int)($target['revision']??0),'target_products'=>$target?(int)stock_query($db,'SELECT COUNT(*) FROM pallet_products WHERE pallet_id=?',[$target['id']])->fetchColumn():0,'description'=>$description];
}
function stock_transfer_commit(PDO $db,int $user,array $input): array {
    $preview=stock_transfer_preview($db,$user,$input);
    foreach(['source_revision','target_revision','target_id','target_user'] as $key)if(isset($preview[$key])&&(!isset($input[$key])||(string)$preview[$key]!==$input[$key]))throw new CatalogException('El origen o el destino cambió. Revisá y confirmá nuevamente.',409);
    $source=stock_query($db,'SELECT * FROM pallets WHERE id=?',[$preview['source_id']])->fetch();
    $emit=static function(int $id,string $action,array $metadata)use($db,$user){stock_query($db,'INSERT INTO pallet_activity(user_id,pallet_id,action,metadata) VALUES(?,?,?,?)',[$user,$id,$action,json_encode($metadata,JSON_UNESCAPED_UNICODE)]);};
    if($preview['mode']==='user'){
        stock_query($db,'UPDATE pallets SET owner_id=? WHERE id=?',[$preview['target_user'],$source['id']]);
        stock_query($db,'DELETE FROM pallet_access WHERE pallet_id=?',[$source['id']]);
        stock_query($db,'DELETE FROM pallet_user_tags WHERE pallet_id=?',[$source['id']]);
        stock_query($db,'DELETE FROM stock_share_pallets WHERE pallet_id=?',[$source['id']]);
        stock_touch($db,(int)$source['id'],$user);
        stock_query($db,'INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?)',[$preview['target_user'],'pallet-transfer:'.$source['id'].':'.$source['revision'],'stock_transfer','Recibiste un palet','Ahora sos dueño del palet '.$source['code'].'.']);
        $emit((int)$source['id'],'ownership_transferred',['old'=>get_public_user_name(stock_query($db,'SELECT id,public_name FROM users WHERE id=?',[$user])->fetch()),'new'=>$preview['target_name']]);
        return ['id'=>(int)$source['id'],'code'=>$source['code'],'mode'=>'user','target_name'=>$preview['target_name']];
    }
    $targetId=$preview['target_id'];if(!$targetId){$created=stock_claim($db,$preview['target_code'],$user);if($created['code']!==$preview['target_code'])throw new CatalogException('El destino cambió. Volvé a escanearlo.',409);$targetId=(int)$created['id'];}
    $rows=stock_query($db,'SELECT * FROM pallet_products WHERE pallet_id=? ORDER BY id',[$source['id']])->fetchAll();
    foreach($rows as $row){$same=stock_query($db,'SELECT * FROM pallet_products WHERE pallet_id=? AND ean=? AND expires=?',[$targetId,$row['ean'],$row['expires']])->fetch();if($same){$name=implode(' / ',array_unique(array_filter([$same['name'],$row['name']])));$differentInner=((int)$same['inner_pack_size']>0||(int)$row['inner_pack_size']>0)&&($same['kind']!==$row['kind']||(int)$same['pack_size']!==(int)$row['pack_size']||(int)$same['inner_pack_size']!==(int)$row['inner_pack_size']);stock_query($db,'UPDATE pallet_products SET units=units+?,name=?,kind=?,pack_size=?,inner_pack_size=?,revision=revision+1 WHERE id=?',[$row['units'],$name,$differentInner?'units':$same['kind'],$differentInner?1:$same['pack_size'],$differentInner?0:$same['inner_pack_size'],$same['id']]);stock_query($db,'DELETE FROM pallet_products WHERE id=?',[$row['id']]);}else stock_query($db,'UPDATE pallet_products SET pallet_id=?,revision=revision+1 WHERE id=?',[$targetId,$row['id']]);}
    if($source['branch_id'])stock_query($db,'UPDATE pallets SET branch_id=? WHERE id=? AND branch_id IS NULL',[$source['branch_id'],$targetId]);
    stock_query($db,'UPDATE pallets SET description=?,activated=1 WHERE id=?',[$preview['description'],$targetId]);
    stock_query($db,"UPDATE pallets SET description='' WHERE id=?",[$source['id']]);
    foreach([(int)$source['id'],$targetId] as $id)stock_touch($db,$id,$user);
    $lots=array_map(static fn($r)=>array_intersect_key($r,array_flip(['ean','name','expires','units','kind','pack_size','inner_pack_size'])),$rows);
    $metadata=['old'=>$source['code'],'new'=>$preview['target_code'],'units'=>$preview['units'],'lots'=>$lots];
    $emit((int)$source['id'],'stock_transferred_out',$metadata);$emit($targetId,'stock_transferred_in',$metadata);
    return ['id'=>(int)$source['id'],'code'=>$source['code'],'mode'=>'pallet','target_code'=>$preview['target_code'],'target_id'=>$targetId,'units'=>$preview['units']];
}
