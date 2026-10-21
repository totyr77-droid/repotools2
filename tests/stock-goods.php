<?php
require __DIR__.'/stock-recent-order.php';require_once __DIR__.'/../includes/team-inventory.php';
stock_save_product($db,1,['pallet_id'=>$a['id'],'ean'=>'96385074','amount'=>3,'expires'=>'2031-01-01']);
$goods=team_inventory_page($db,1,0,'mercaderia',[],true)['items'];order_check(count($goods)===1&&(int)$goods[0]['units']===34&&(int)$goods[0]['pallets']===3&&(int)$goods[0]['date_count']===4,'one product, all dates, own exact totals');
$dates=team_inventory_page($db,1,0,'fechas',['ean'=>'96385074'],true)['items'];order_check(count($dates)===4&&end($dates)['expires']==='2020-01-01','dates split with expired last');
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

$today=new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires'));$date=fn(int $n)=>$today->modify(($n>=0?'+':'').$n.' days')->format('Y-m-d');
foreach([3,5,730,731,-1,-2] as $day)stock_save_product($db,1,['pallet_id'=>$a['id'],'ean'=>'12345670','name'=>'Varias fechas','amount'=>1,'expires'=>$date($day)]);
stock_save_product($db,1,['pallet_id'=>$b['id'],'ean'=>'12345670','amount'=>1,'expires'=>$date(3)]);
$summary=team_inventory_page($db,1,0,'mercaderia',['q'=>'12345670'],true)['items'][0];order_check($summary['nearest_active_expiry']===$date(3),'nearest nonexpired first');order_check((int)$summary['upcoming_dates']===2&&(int)$summary['safe_dates']===2&&(int)$summary['expired_dates']===2,'distinct date states, no duplicate pallets');
$dates=team_inventory_page($db,1,0,'fechas',['ean'=>'12345670'],true)['items'];order_check(array_column($dates,'expires')===[$date(3),$date(5),$date(730),$date(731),$date(-2),$date(-1)],'active then expired date groups');
$due=team_inventory_page($db,1,0,'mercaderia',['q'=>'12345670','expiry'=>'due'],true)['items'][0];order_check((int)$due['units']===5&&(int)$due['safe_dates']===0,'quick expiry includes upcoming/expired only');
echo "OK: distinct date badges, nearest active, expired last and quick expiry scope.\n";

stock_query($db,"INSERT INTO stock_branches(id,owner_id,name,address,locality,province) VALUES(1,1,'Local visible','A','B','C'),(12,1,'Test privado','A','B','C')");
stock_query($db,'UPDATE pallets SET branch_id=1 WHERE id=?',[$a['id']]);
$lot=team_inventory_page($db,1,0,'lotes',['ean'=>'96385074','expires'=>'2031-01-01'],true)['items'][0];
order_check($lot['branch_name']==='Local visible'&&$lot['tag']==='Personal','lot shows visible branch and own tag');
stock_query($db,'UPDATE pallets SET branch_id=12 WHERE id=?',[$a['id']]);
stock_query($db,"UPDATE users SET role='usuario' WHERE id=1");
$lot=team_inventory_page($db,1,0,'lotes',['ean'=>'96385074','expires'=>'2031-01-01'],true)['items'][0];
order_check($lot['branch_name']===null&&$lot['tag']===null,'restricted branch and rank-private tag remain hidden');
echo "OK: lot badges respect branch visibility and private label scope.\n";
