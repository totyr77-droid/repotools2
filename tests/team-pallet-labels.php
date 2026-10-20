<?php
require __DIR__.'/team-inventory.php';
stock_query($db,'UPDATE branch_team_members SET expires_at=NULL WHERE user_id=2');stock_query($db,"UPDATE branch_team_members SET role='operator' WHERE user_id=3");
$id=(int)stock_query($db,"SELECT id FROM pallets WHERE code='10000035'")->fetchColumn();
stock_query($db,'INSERT INTO pallet_user_tags(user_id,pallet_id,name) VALUES(5,?,?),(1,?,?)',[$id,'Dueño35',$id,'Privada ajena']);
$page=team_inventory_page($db,2,1,'palets',['tag'=>'Dueño35']);inv_check(count($page['items'])===1&&$page['items'][0]['tag']==='Dueño35','owner label filtered beyond first page');inv_check($page['tags']===['Dueño35'],'other personal labels hidden');
$goods=team_inventory_page($db,2,1,'mercaderia',['tag'=>'Dueño35']);inv_check(count($goods['items'])===1&&(int)$goods['items'][0]['units']===36,'merchandise totals scoped to tag');
$lots=team_inventory_page($db,2,1,'lotes',['tag'=>'Dueño35','ean'=>'96385074','expires'=>'2030-01-01']);inv_check(count($lots['items'])===1&&$lots['items'][0]['code']==='10000035','tag scopes lot breakdown');
inv_check(team_inventory_page($db,2,1,'palets',['tag'=>'Privada ajena'])['items']===[],'non-owner personal label cannot be filtered');
foreach([1,2] as $user){$p=stock_detail($db,'10000035',$user);inv_check($p['team_tag']==='Dueño35'&&$p['can_remove_branch'],'authorized ranks see owner label and remove control');}
$p=stock_detail($db,'10000035',3);inv_check($p['team_tag']===''&&!$p['can_remove_branch'],'operator sees no owner label or removal control');inv_check(!array_key_exists('team_tag',stock_detail($db,'10000035',2,true)),'public detail hides team label');
echo "OK: owner label in team, filter/options/totals/lots, other personal labels private, rank and public boundaries.\n";
