<?php
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/supervision.php';
require_once __DIR__.'/../includes/supervision-xlsx.php';
require_once __DIR__.'/../includes/supervision-catalog.php';
function sc_check(bool $ok,string $label):void {if(!$ok)throw new RuntimeException($label);}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);initialize_database($db);
foreach(['manager','usuario','usuario']as$i=>$role)stock_query($db,'INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',['Fixture','fixture'.$i.'@example.invalid','unused',$role]);
supervisor_enable($db,1,['email'=>'fixture1@example.invalid','name'=>'Company']);
$bytes=supervisor_write_xlsx([['EAN','Descripcion','UXB','UXC'],['96385074','New',6,30],['4006381333931','Existing',12,24],['01234565','Conflict',6,12],['01234565','Different',6,12]]);
$parsed=supervisor_parse_xlsx($bytes,'A');$map=['name'=>'B','uxb'=>'C','uxc'=>'D'];$c=supervisor_catalog_candidates($bytes,$parsed,$map);sc_check(count($c['items'])===2&&$c['conflicts']===1,'Contradictory duplicate excluded');
$preview=supervisor_parse_xlsx($bytes);sc_check(supervisor_catalog_columns($preview['columns'])===$map,'Suggested mapping');
edit_catalog_product($db,'4006381333931',['name'=>'Original','quantity'=>'250 g','brand'=>'Brand','category'=>'','packaging'=>'','uxb'=>'8','uxc'=>'16'],0,1);
$db->beginTransaction();$count=supervisor_catalog_propose($db,2,$c['items'],'Fixture list');$db->commit();sc_check($count===2&&catalog_product($db,'96385074')===null&&catalog_product($db,'4006381333931')['name']==='Original','Only proposals, no direct catalog mutation');
$db->beginTransaction();sc_check(supervisor_catalog_propose($db,2,$c['items'],'Fixture list')===0,'Pending proposal deduplication');$db->commit();
stock_query($db,"UPDATE product_reports SET proposed_data=REPLACE(proposed_data,',\"_origin\":\"supervision_excel\"','') WHERE ean='4006381333931'");
$reports=stock_query($db,'SELECT id,ean FROM product_reports ORDER BY id')->fetchAll();foreach($reports as$r){$detail=report_detail($db,(int)$r['id'],'user:1',true);review_product_report($db,['id'=>1],(int)$r['id'],[...$detail['proposed'],'revision'=>$detail['current']['revision']??0,'decision'=>'approved']);}
sc_check((int)stock_query($db,'SELECT COUNT(*) FROM report_rewards WHERE user_id=2')->fetchColumn()===0,'Automated imports never award report points');sc_check((int)stock_query($db,"SELECT COUNT(*) FROM achievement_events WHERE user_id=2")->fetchColumn()===0,'Automated imports never grant edits or activity achievements');
$product=catalog_product($db,'96385074');sc_check($product['uxb']==='6'&&$product['uxc']==='30','Separate approved pack values');$existing=catalog_product($db,'4006381333931');sc_check($existing['brand']==='Brand'&&$existing['quantity']==='250 g'&&$existing['name']==='Existing','Unmapped fields preserved');
edit_catalog_product($db,'96385074',['name'=>'Updated','quantity'=>'','brand'=>'','category'=>'','packaging'=>''],(int)$product['revision'],1);sc_check(catalog_product($db,'96385074')['uxc']==='30','Legacy editors preserve approved packs');
stock_query($db,'INSERT INTO report_blocks(user_id,blocked_by) VALUES(2,1)');try{supervisor_catalog_propose($db,2,$c['items'],'Fixture');throw new RuntimeException('Blocked sender accepted');}catch(CatalogException $e){sc_check($e->status===403,'Blocked sender denied');}
try{supervisor_catalog_candidates($bytes,$parsed,['name'=>'A']);throw new RuntimeException('EAN reused');}catch(CatalogException $e){sc_check($e->status===422,'Mapping validation');}
echo "OK: import mapping, conflicting rows, proposal-only writes, dedupe, review approval, independent UXB/UXC, old editor preservation and blocked sender.\n";
