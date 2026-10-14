<?php
require_once __DIR__.'/../includes/database.php';require_once __DIR__.'/../includes/stock-branches.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
function check_branch($ok,$message){if(!$ok)throw new RuntimeException($message);}
$db->exec("INSERT INTO users(id,name,email,password_hash) VALUES(1,'Owner','owner@example.invalid','x'),(2,'Other','other@example.invalid','x')");
$db->exec("INSERT INTO pallet_labels(code) VALUES('12345678')");
$p=stock_claim($db,'12345678',1);check_branch(empty(stock_query($db,'SELECT branch_id FROM pallets WHERE id=?',[$p['id']])->fetchColumn()),'Claim without branch');
$product=stock_save_product($db,1,['pallet_id'=>$p['id'],'ean'=>'96385074','name'=>'Test','amount'=>2,'expires'=>'']);
$b=branch_save($db,1,['name'=>'Local','address'=>'Calle 123','locality'=>'Ciudad','province'=>'Provincia'])['branch'];
$db->exec("INSERT INTO stock_shares(owner_id,token,title,branch_id,active) VALUES(1,'".str_repeat('a',64)."','Test',".$b['id'].",1),(1,'".str_repeat('b',64)."','Test',".$b['id'].",1)");
function assign_branch($db,$p,$b,$choice=null){$input=['pallet_id'=>$p['id'],'branch_id'=>$b,'revision'=>(int)stock_query($db,'SELECT revision FROM pallets WHERE id=?',[$p['id']])->fetchColumn()];if($choice!==null)$input['share_pallet']=$choice;branch_assign($db,1,$input);}
try{assign_branch($db,$p,$b['id']);throw new RuntimeException('Missing confirmation allowed');}catch(CatalogException $e){check_branch($e->status===409,'Confirmation required');}
assign_branch($db,$p,$b['id'],'0');check_branch((int)$db->query('SELECT COUNT(*) FROM stock_share_exclusions')->fetchColumn()===2,'Excluded from both active links');
check_branch(count(branch_public($db,str_repeat('a',64))['pallets'])===0,'Excluded pallet absent from public link');
assign_branch($db,$p,$b['id']);check_branch((int)$db->query('SELECT COUNT(*) FROM stock_share_exclusions')->fetchColumn()===2,'Same branch retains exclusions');
assign_branch($db,$p,0);assign_branch($db,$p,$b['id'],'1');check_branch((int)$db->query('SELECT COUNT(*) FROM stock_share_exclusions')->fetchColumn()===0,'Include clears only this pallet exclusions');
check_branch(count(branch_public($db,str_repeat('a',64))['pallets'])===1,'Included pallet present in public link');
try{branch_assign($db,2,['pallet_id'=>$p['id'],'branch_id'=>0,'revision'=>999]);throw new RuntimeException('Foreign assignment allowed');}catch(CatalogException $e){check_branch($e->status===403,'Owner only');}
echo "OK: direct claim without branch, explicit sharing choice, multiple links, preserved exclusions, inclusion and owner validation.\n";
