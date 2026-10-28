<?php
require_once __DIR__.'/../includes/database.php';
function support_active(): bool {return $GLOBALS['test_support']??false;}
require_once __DIR__.'/../includes/manager-plans.php';
function reset_check($ok,$why){if(!$ok)throw new RuntimeException($why);}
function reset_denied($run,$status){try{$run();}catch(CatalogException $e){reset_check($e->status===$status,$e->getMessage());return;}throw new RuntimeException('Expected refusal');}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
foreach(['usuario','moderador','administrador','manager','usuario'] as $i=>$role)stock_query($db,'INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',['Fixture','reset'.$i.'@example.invalid','unused',$role]);
$settings=[];$remote=[];$calls=[];$fail=false;$cancelConfirmed=true;
foreach(['pro'=>10000,'supervision'=>20000] as $product=>$amount){
    $settings[$product]=['enabled'=>true,'product'=>$product,'seller'=>'123','buyer'=>'test_user_fixture@testuser.com','amount'=>$amount,'plan'=>$product.'-plan','url'=>'https://repotools-desarrollo.onrender.com'];
    $remote[$product.'-sub']=['id'=>$product.'-sub','external_reference'=>$product.'-ref','collector_id'=>123,'preapproval_plan_id'=>$product.'-plan','status'=>'authorized','auto_recurring'=>['frequency'=>1,'frequency_type'=>'months','transaction_amount'=>$amount/100,'currency_id'=>'ARS']];
    $table=$product==='pro'?'pro_subscriptions':'supervision_subscriptions';$payments=$product==='pro'?'pro_payments':'supervision_payments';
    stock_query($db,"INSERT INTO $table(reference,user_id,provider_id,status,amount_cents,paid_until) VALUES(?,1,?,'authorized',?,?)",[$product.'-ref',$product.'-sub',$amount,time()+86400]);
    stock_query($db,"INSERT INTO $payments(provider_id,reference,status,period_end,updated_at) VALUES(?,?,'approved',?,?)",[$product.'-payment',$product.'-ref',time()+86400,time()]);
}
$db->exec("INSERT INTO pro_subscriptions(reference,user_id,status,amount_cents,paid_until) VALUES('other-ref',5,'beta_trial',10000,1999999999)");
$db->exec("INSERT INTO supervisor_teams(owner_id,name,billing_required) VALUES(1,'Equipo propio',1);INSERT INTO supervisor_members(team_id,user_id,status) VALUES(1,5,'accepted');INSERT INTO supervisor_lists(team_id,name,source_base64,sheet_path,ean_column,ean_rows,row_count) VALUES(1,'Lista','fixture','sheet','A','{}',1)");
$db->exec("INSERT INTO pallets(code,owner_id,prepared_until,activated,updated_by) VALUES('12345678',1,0,1,1);INSERT INTO pallet_products(pallet_id,ean,name,units) VALUES(1,'96385074','Fixture',10)");
$preserve=[];foreach(['users','supervisor_teams','supervisor_members','supervisor_lists','pallets','pallet_products'] as $table)$preserve[$table]=$db->query("SELECT * FROM $table")->fetchAll();
$transport=function($method,$path,$body=null)use(&$calls,&$remote,&$fail,&$cancelConfirmed){
    $calls[]=[$method,$path];reset_check(in_array($method,['GET','PUT'],true),'Reset never creates or charges');
    if($path==='/users/me')return ['id'=>123,'tags'=>['test_user']];if(str_starts_with($path,'/preapproval/search?'))return ['results'=>isset($remote['resolve-sub'])?[$remote['resolve-sub']]:[]];
    $id=basename($path);if(!isset($remote[$id]))throw new RuntimeException('Unexpected resource');
    if($method==='PUT'){
        if($fail&&$id==='supervision-sub')throw new CatalogException('Provider unavailable',503);
        reset_check($body===['status'=>'cancelled'],'Only cancellation allowed');if($cancelConfirmed)$remote[$id]['status']='cancelled';
    }
    return $remote[$id];
};
$version=manager_plan_version(1,manager_plan_rows($db,1));
foreach([1,2,3] as $actor)reset_denied(fn()=>manager_remove_plans($db,$actor,1,$version,$settings,$transport),403);
reset_check(!$calls,'Unauthorized actors cannot contact provider');$GLOBALS['test_support']=true;reset_denied(fn()=>manager_remove_plans($db,4,1,$version,$settings,$transport),403);$GLOBALS['test_support']=false;
reset_denied(fn()=>manager_remove_plans($db,4,1,str_repeat('0',64),$settings,$transport),409);reset_check(!$calls,'Stale identity does not cancel');
$off=$settings;$off['pro']['enabled']=false;reset_denied(fn()=>manager_remove_plans($db,4,1,$version,$off,$transport),503);
$cancelConfirmed=false;reset_denied(fn()=>manager_remove_plans($db,4,1,$version,$settings,$transport),503);reset_check((int)$db->query('SELECT COUNT(*) FROM pro_payments')->fetchColumn()===1,'Unconfirmed cancellation keeps payments');$cancelConfirmed=true;
$fail=true;reset_denied(fn()=>manager_remove_plans($db,4,1,$version,$settings,$transport),503);reset_check(manager_plan_rows($db,1)['pro']!==null&&manager_plan_rows($db,1)['supervision']!==null,'Partial remote failure never clears local plans');reset_check((int)$db->query('SELECT COUNT(*) FROM billing_cancelled_attempts')->fetchColumn()===0,'No partial archive');$fail=false;
reset_check(manager_remove_plans($db,4,1,$version,$settings,$transport),'Manager removes plans');reset_check(!array_filter(manager_plan_rows($db,1)),'Only target is Free');
reset_check((int)$db->query('SELECT COUNT(*) FROM billing_cancelled_attempts')->fetchColumn()===2,'Both histories retained');foreach($db->query('SELECT snapshot FROM billing_cancelled_attempts')->fetchAll(PDO::FETCH_COLUMN) as $json)reset_check(count(json_decode($json,true)['payments'])===1,'Payment kept in archive');
foreach($preserve as $table=>$before)reset_check($before===$db->query("SELECT * FROM $table")->fetchAll(),'Preserved '.$table);
reset_check(manager_plan_rows($db,5)['pro']['reference']==='other-ref','Other account unchanged');$n=count($calls);reset_check(!manager_remove_plans($db,4,1,$version,$settings,$transport)&&count($calls)===$n,'Duplicate has no provider calls');
$audit=$db->query("SELECT actor_id,target_user,action FROM audit_events WHERE action='plans_removed'")->fetchAll();reset_check(count($audit)===1&&(int)$audit[0]['actor_id']===4&&(int)$audit[0]['target_user']===1,'Manager and target audited');
echo "OK manager plan reset: manager-only, support/stale/disabled denied, provider cancellation confirmed, partial failure safe, idempotence, history/audit, team and stock unchanged.\n";

$db->exec("INSERT INTO supervision_subscriptions(reference,user_id,status,amount_cents) VALUES('resolve-ref',1,'creating',20000)");$v=manager_plan_version(1,manager_plan_rows($db,1));reset_denied(fn()=>manager_remove_plans($db,4,1,$v,$settings,$transport),409);$remote['resolve-sub']=['id'=>'resolve-sub','external_reference'=>'resolve-ref','collector_id'=>123,'preapproval_plan_id'=>'supervision-plan','status'=>'cancelled','auto_recurring'=>['frequency'=>1,'frequency_type'=>'months','transaction_amount'=>200,'currency_id'=>'ARS']];reset_check(manager_remove_plans($db,4,1,$v,$settings,$transport),'Own ambiguous attempt recovered before reset');reset_check(!array_filter(manager_plan_rows($db,1)),'Recovered attempt removed safely');echo "OK manager recovery: unidentified attempt blocked, verified own cancelled reference safely archived.\n";
