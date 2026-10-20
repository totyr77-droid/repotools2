<?php
require __DIR__.'/team-inventory.php';
require_once __DIR__.'/../includes/pallet-activity.php';
stock_query($db,'UPDATE branch_team_members SET expires_at=NULL WHERE user_id=2');
$id=(int)stock_query($db,"SELECT id FROM pallets WHERE code='10000035'")->fetchColumn();
inv_check(pallet_activity_team_metadata($db,2,$id)===['source'=>'team'],'authorized team member marked');
inv_check(pallet_activity_team_metadata($db,4,$id,true)===[],'outsider cannot forge team marker');
inv_check(pallet_activity_team_metadata($db,5,$id)===[],'owner personal work unmarked');
$before=pallet_activity_snapshot($db,$id);$after=$before;$after['products'][array_key_first($after['products'])]['units']++;
pallet_activity_record($db,2,$id,$before,$after);
$meta=json_decode(stock_query($db,'SELECT metadata FROM pallet_activity WHERE pallet_id=? ORDER BY id DESC LIMIT 1',[$id])->fetchColumn(),true);
inv_check(($meta['source']??'')==='team'&&isset($meta['old'],$meta['new']),'team marker stored with before/after');
stock_query($db,'UPDATE branch_team_members SET expires_at=? WHERE user_id=2',[time()-1]);
inv_check(pallet_activity_team_metadata($db,2,$id,true)===[],'expired member cannot forge marker');
echo "OK: team activity attribution, before/after, owner and outsider/expired boundaries.\n";
