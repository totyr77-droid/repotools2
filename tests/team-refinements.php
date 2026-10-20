<?php
require __DIR__.'/team-inventory.php';
require_once __DIR__.'/../includes/stock-branches.php';
stock_query($db,'UPDATE branch_team_members SET expires_at=NULL WHERE user_id=2');
stock_query($db,"UPDATE pallet_products SET expires='2028-01-01' WHERE pallet_id=(SELECT id FROM pallets WHERE code='10000035')");
foreach(['palets','mercaderia'] as $view){$asc=team_inventory_page($db,2,1,$view,['sort'=>'expiry_asc'])['items'];$desc=team_inventory_page($db,2,1,$view,['sort'=>'expiry_desc'])['items'];inv_check(($view==='palets'?$asc[0]['code']:$asc[0]['expires'])===($view==='palets'?'10000035':'2028-01-01'),'nearest expiry first');inv_check(($view==='palets'?$desc[0]['code']:$desc[0]['expires'])!==($view==='palets'?'10000035':'2028-01-01'),'latest expiry first');}
inv_denied(fn()=>team_inventory_page($db,2,1,'palets',['sort'=>'p.id;DELETE']),422);
$p=stock_query($db,"SELECT * FROM pallets WHERE code='10000035'")->fetch();
inv_denied(fn()=>branch_assign($db,3,['pallet_id'=>$p['id'],'revision'=>$p['revision'],'branch_id'=>2]),403);
inv_denied(fn()=>branch_assign($db,4,['pallet_id'=>$p['id'],'revision'=>$p['revision'],'branch_id'=>0]),403);
branch_assign($db,3,['pallet_id'=>$p['id'],'revision'=>$p['revision'],'branch_id'=>0]);
inv_check(!stock_query($db,'SELECT branch_id FROM pallets WHERE id=?',[$p['id']])->fetchColumn(),'authorized member removes only location');
inv_denied(fn()=>stock_editable($db,(int)$p['id'],3),403);
stock_query($db,'INSERT INTO branch_team_security(branch_id,enabled,latitude,longitude) VALUES(1,0,0,0)');
$token=team_invite($db,1,1,'operator','inv3@example.invalid',true);team_accept($db,4,$token);
$event=team_inventory_page($db,1,1,'administracion',['action'=>'member_joined'])['items'][0];inv_check($event['actor']==='Admin'&&$event['target']==='Ajeno'&&$event['metadata']['role']==='operator','issuer and recipient retained');
echo "OK: expiry sort, invalid sort, member removal permission and lost access, invitation issuer to recipient.\n";
