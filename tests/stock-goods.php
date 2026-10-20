<?php
require __DIR__.'/stock-recent-order.php';require_once __DIR__.'/../includes/team-inventory.php';
stock_save_product($db,1,['pallet_id'=>$a['id'],'ean'=>'96385074','amount'=>3,'expires'=>'2031-01-01']);
$goods=team_inventory_page($db,1,0,'mercaderia',[],true)['items'];order_check(count($goods)===1&&(int)$goods[0]['units']===34&&(int)$goods[0]['pallets']===3&&(int)$goods[0]['date_count']===4,'one product, all dates, own exact totals');
$dates=team_inventory_page($db,1,0,'fechas',['ean'=>'96385074'],true)['items'];order_check(count($dates)===4&&end($dates)['expires']==='','dates split with undated last');
$lots=team_inventory_page($db,1,0,'lotes',['ean'=>'96385074','expires'=>'2031-01-01'],true)['items'];order_check(count($lots)===1&&$lots[0]['code']===$a['code']&&(int)$lots[0]['units']===3,'exact pallet/expiry drilldown');
order_check((int)team_inventory_page($db,2,0,'mercaderia',[],true)['items'][0]['units']===15,'accepted shared scope');
stock_query($db,"UPDATE users SET role='manager' WHERE id=2");order_check((int)team_inventory_page($db,2,0,'mercaderia',[],true)['items'][0]['units']===15,'platform manager still uses personal Stock scope');
stock_query($db,"UPDATE pallet_access SET status='pending' WHERE user_id=2");order_check(team_inventory_page($db,2,0,'mercaderia',[],true)['items']===[],'pending/revoked shared access excluded');
order_check(team_inventory_page($db,1,0,'mercaderia',['q'=>'%'],true)['items']===[],'literal search');
stock_query($db,"UPDATE users SET role='manager' WHERE id=1");stock_query($db,"INSERT INTO pallet_user_tags(user_id,pallet_id,name) VALUES(1,?,'Personal'),(3,?,'Private')",[$a['id'],$a['id']]);
order_check((int)team_inventory_page($db,1,0,'mercaderia',['tag'=>'Personal'],true)['items'][0]['units']===15,'own label filter');order_check(team_inventory_page($db,1,0,'mercaderia',['tag'=>'Private'],true)['items']===[],'other label private');
try{team_inventory_page($db,1,0,'administracion',[],true);throw new RuntimeException('wrong view accepted');}catch(CatalogException $e){order_check($e->status===422,'personal API only merchandise views');}
echo "OK: personal product/date/pallet hierarchy, exact totals, accepted access, manager scope, private labels and literal filtering.\n";

for($i=1;$i<=31;$i++){$base=str_pad((string)$i,7,'0',STR_PAD_LEFT);$sum=0;for($j=0;$j<7;$j++)$sum+=(int)$base[$j]*($j%2?1:3);$ean=$base.((10-$sum%10)%10);stock_query($db,"INSERT INTO pallet_products(pallet_id,ean,name,units,expires,kind,pack_size) VALUES(?,?,?,1,'','units',1)",[$a['id'],$ean,'Pagination product '.$i]);}
$page=team_inventory_page($db,1,0,'mercaderia',['q'=>'Pagination product'],true);order_check(count($page['items'])===30&&$page['next_offset']===30,'product pagination after grouping');order_check(count(team_inventory_page($db,1,0,'mercaderia',['q'=>'Pagination product','offset'=>'30'],true)['items'])===1,'remaining products');
echo "OK: personal merchandise pagination beyond30 product groups.\n";
