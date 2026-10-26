<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/pro-subscriptions.php';
function check(bool $condition,string $message): void {if(!$condition)throw new RuntimeException($message);}
function denied(callable $fn,int $status): void {try{$fn();}catch(CatalogException $e){check($e->status===$status,'Unexpected error '.$e->getMessage());return;}throw new RuntimeException('Request should fail');}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
initialize_database($db);initialize_database($db);
$db->exec("INSERT INTO users(name,email,password_hash,role) VALUES('Test','test@example.invalid','fixture','manager'),('Other','other@example.invalid','fixture','usuario')");
check((int)$db->query('SELECT MAX(version) FROM app_schema_migrations')->fetchColumn()===REPOTOOLS_SCHEMA_VERSION,'Migration idempotence');
denied(fn()=>pro_require_tester($db,2),403);pro_require_tester($db,1);
check(pro_config()['enabled']===false,'Billing must default off');
putenv('REPOTOOLS_BILLING_MODE=live');check(pro_config()['enabled']===false,'Live billing must fail closed');putenv('REPOTOOLS_BILLING_MODE=');
$config=['enabled'=>true,'token'=>'fixture','secret'=>'fixture-secret-long','seller'=>'123','buyer'=>'test_user_fixture@testuser.com','amount'=>10000,'url'=>'https://repotools-desarrollo.onrender.com'];
$remote=[];$payment=['id'=>456,'status'=>'approved','live_mode'=>false,'collector_id'=>123,'transaction_amount'=>100,'currency_id'=>'ARS'];$calls=[];$testSeller=true;$timeout=false;$cancelConfirmed=true;
$transport=function($method,$path,$body,$key)use(&$remote,&$payment,&$calls,&$testSeller,&$timeout,&$cancelConfirmed){
    $calls[]=[$method,$path,$key];
    if($path==='/users/me')return ['id'=>123,'tags'=>$testSeller?['test_user']:[]];
    if($method==='POST'&&$path==='/preapproval'){
        $remote=['id'=>'sub_fixture','collector_id'=>123,'status'=>'pending','init_point'=>'https://www.mercadopago.com.ar/subscriptions/checkout?preapproval_id=sub_fixture',...$body];
        if($timeout)throw new CatalogException('Timeout fixture',503);
        return $remote;
    }
    if(str_starts_with($path,'/preapproval/search?'))return ['results'=>[$remote]];
    if($path==='/preapproval/sub_fixture'){
        if($method==='PUT'&&$cancelConfirmed)$remote['status']=$body['status'];
        return $remote;
    }
    if(str_starts_with($path,'/authorized_payments/'))return ['id'=>substr($path,strrpos($path,'/')+1),'preapproval_id'=>'sub_fixture','debit_date'=>'2026-01-31T10:30:00Z','payment'=>['id'=>456]];
    if($path==='/v1/payments/456')return $payment;
    throw new RuntimeException('Unexpected provider call '.$path);
};
$service=new ProSubscriptions($db,$config,$transport);
$testSeller=false;denied(fn()=>$service->checkout(1),503);check($db->query('SELECT COUNT(*) FROM pro_subscriptions')->fetchColumn()===0,'Real seller must not create subscription');$testSeller=true;
$timeout=true;denied(fn()=>$service->checkout(1),503);$timeout=false;
check($db->query('SELECT status FROM pro_subscriptions')->fetchColumn()==='creating','Ambiguous attempt persisted');
$url=$service->checkout(1);check(str_starts_with($url,'https://www.mercadopago.com.ar/'),'Recover checkout');$service->checkout(1);
check(count(array_filter($calls,fn($r)=>$r[0]==='POST'))===1,'Never duplicate create after timeout/repeated click');
$remote['status']='authorized';$service->subscription('sub_fixture');check((int)$db->query('SELECT paid_until FROM pro_subscriptions')->fetchColumn()===0,'Authorization alone grants no Pro');
$db->exec("UPDATE pro_subscriptions SET provider_id=NULL,status='creating'");
$service->invoice('invoice_fixture');$until=(int)$db->query('SELECT paid_until FROM pro_subscriptions')->fetchColumn();
check($db->query('SELECT provider_id FROM pro_subscriptions')->fetchColumn()==='sub_fixture','Early invoice safely recovers subscription reference');
check(gmdate('Y-m-d H:i:s',$until)==='2026-02-28 10:30:00','Month end clamped');
$service->invoice('invoice_fixture');check((int)$db->query('SELECT COUNT(*) FROM pro_payments')->fetchColumn()===1,'Repeated webhook does not duplicate payment');check((int)$db->query('SELECT paid_until FROM pro_subscriptions')->fetchColumn()===$until,'Repeated webhook does not extend period');
$payment['status']='rejected';$service->invoice('invoice_fixture');check((int)$db->query('SELECT paid_until FROM pro_subscriptions')->fetchColumn()===0,'Rejected payment grants nothing');
$payment['status']='approved';$payment['live_mode']=true;denied(fn()=>$service->invoice('invoice_fixture'),409);$payment['live_mode']=false;
$payment['transaction_amount']=1;denied(fn()=>$service->invoice('invoice_fixture'),409);$payment['transaction_amount']=100;
$payment['collector_id']=987;denied(fn()=>$service->invoice('invoice_fixture'),409);$payment['collector_id']=123;
$service->invoice('invoice_fixture');$cancelConfirmed=false;denied(fn()=>$service->cancel(1),503);$cancelConfirmed=true;$service->cancel(1);
check($db->query('SELECT status FROM pro_subscriptions')->fetchColumn()==='cancelled','Provider cancellation confirmed');check((int)$db->query('SELECT paid_until FROM pro_subscriptions')->fetchColumn()===$until,'Cancel preserves paid period');$service->cancel(1);
$payment['status']='refunded';$service->invoice('invoice_fixture');check((int)$db->query('SELECT paid_until FROM pro_subscriptions')->fetchColumn()===0,'Refund revokes unpaid entitlement');
$ts=(string)time();$request='fixture-request';$signature='ts='.$ts.',v1='.hash_hmac('sha256',"id:abc123;request-id:$request;ts:$ts;",$config['secret']);
check(pro_verify_webhook('data.id=ABC123',$signature,$request,$config['secret'],time())==='ABC123','Signature authenticates original query');
denied(fn()=>pro_verify_webhook('data.id=forged',$signature,$request,$config['secret'],time()),401);
denied(fn()=>pro_verify_webhook('data.id=ABC123&data.id=another',$signature,$request,$config['secret'],time()),401);
denied(fn()=>pro_verify_webhook('data.id=ABC123',$signature,$request,$config['secret'],time()+400),401);
check((int)$db->query('SELECT COUNT(*) FROM pallets')->fetchColumn()===0,'No stock mutated');
echo "OK: schema migrations, private tests, default/live disabled, test seller, timeout recovery, duplicate create/payment, authorization vs payment, calendar period, rejection/refund, wrong amount/seller/live payment, cancellation preserves paid period, signed webhook/tamper/replay. No provider network calls.\n";

$trialDb=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($trialDb);$trialDb->exec("INSERT INTO users(name,email,password_hash,role) VALUES('Trial','trial@example.invalid','fixture','manager')");$trialUntil=time()+86400;$trialDb->prepare("INSERT INTO pro_subscriptions(reference,user_id,status,amount_cents,paid_until) VALUES('trial-fixture',1,'beta_trial',1,?)")->execute([$trialUntil]);$trialService=new ProSubscriptions($trialDb,$config,$transport);$trialService->checkout(1);$trial=$trialDb->query('SELECT * FROM pro_subscriptions')->fetch();check((int)$trial['amount_cents']===10000,'Trial checkout uses configured amount');check((int)$trial['paid_until']===$trialUntil,'Opening checkout preserves existing trial');check($trial['status']==='pending','Trial can begin a provider test');

$planDb=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($planDb);$planDb->exec("INSERT INTO users(name,email,password_hash,role) VALUES('Plan','plan@example.invalid','fixture','manager')");$planConfig=$config+['plan'=>'plan_fixture'];$planService=new ProSubscriptions($planDb,$planConfig,$transport);$planService->checkout(1,'fixture_card_token_123456');check(($remote['card_token_id']??'')==='fixture_card_token_123456','Token sent to provider only');check(($remote['status']??'')==='authorized','Plan authorization uses card token');check(($remote['preapproval_plan_id']??'')==='plan_fixture','Create uses associated plan');$remote['preapproval_plan_id']='wrong';denied(fn()=>$planService->subscription('sub_fixture'),409);

foreach([[400,['message'=>'card_token_id is invalid'],'card_token'],[400,['message'=>'Both payer and collector must be test users'],'payer_seller'],[401,['message'=>'private credential value'],'credentials'],[400,['message'=>'preapproval_plan invalid'],'plan'],[0,null,'network'],[500,['message'=>'test_user_private@example.invalid SECRET_TOKEN_123'],'provider']] as [$status,$payload,$kind]){$failure=pro_provider_failure($status,$payload);check($failure['category']===$kind,'Failure category');check(!str_contains($failure['message'],'SECRET')&&!str_contains($failure['message'],'@'),'No private provider body in notice');}

$identityDb=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($identityDb);$identityDb->exec("INSERT INTO users(name,email,password_hash,role) VALUES('Identity','identity@example.invalid','fixture','manager')");$identityCalls=0;$identityService=new ProSubscriptions($identityDb,$config,function($method,$path)use($config,&$identityCalls){$identityCalls++;check($method==='GET'&&$path==='/users/me','Same-account guard must never create subscription');return ['id'=>123,'tags'=>['test_user'],'email'=>strtoupper($config['buyer'])];});denied(fn()=>$identityService->checkout(1),422);check($identityCalls===1,'Only identity lookup');check((int)$identityDb->query('SELECT COUNT(*) FROM pro_subscriptions')->fetchColumn()===0,'Invalid buyer creates no local attempt');
