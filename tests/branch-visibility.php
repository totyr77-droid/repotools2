<?php
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/stock-branches.php';
require_once __DIR__.'/../includes/notification-context.php';
function verify($ok,$why){if(!$ok)throw new RuntimeException($why);}
function denied(callable $fn){try{$fn();}catch(CatalogException $e){verify($e->status===404,'Generic not found');return;}throw new RuntimeException('Expected denial');}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
foreach(['manager','administrador','usuario'] as $i=>$role)stock_query($db,'INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',['Persona','person'.$i.'@example.invalid','unused',$role]);
foreach([1=>'Visible',12=>'Test'] as $id=>$name)stock_query($db,'INSERT INTO stock_branches(id,owner_id,name,address,locality,province) VALUES(?,1,?,?,?,?)',[$id,$name,'Calle','Ciudad','Provincia']);
foreach([null,2,3] as $user){verify(!branch_visible($db,12,$user),'Managers only');verify(branch_visible($db,1,$user),'Normal branches unaffected');verify(count(stock_query($db,'SELECT id FROM stock_branches b WHERE '.branch_visibility_sql($db,$user,'b.id'))->fetchAll())===1,'Directory filtered');}
verify(branch_owned($db,12,1)['name']==='Test','Manager reads test');denied(fn()=>branch_owned($db,12,2));denied(fn()=>branch_owned($db,12,3));
verify(count(branch_management($db,2,[])['items'])===1,'Catalog admin cannot list test');verify(count(branch_management($db,1,[])['items'])===2,'Manager lists test');
$w=(int)stock_query($db,"INSERT INTO branch_workspaces(owner_id,name) VALUES(1,'Pruebas') RETURNING id")->fetchColumn();team_attach($db,1,$w,12);verify(team_role($db,1,12)==='admin','Manager can manage team');
stock_query($db,"INSERT INTO branch_team_members(branch_id,user_id,role,active) VALUES(12,3,'manager',1)");verify(team_role($db,3,12)==='','Business manager is not platform manager');verify(team_locations($db,3)===[],'Membership cannot reveal test');denied(fn()=>team_invite($db,1,12,'operator','person2@example.invalid',true));
$token=team_invite($db,1,12,'operator');denied(fn()=>team_accept($db,3,$token));verify(stock_query($db,'SELECT used_by FROM branch_team_invites WHERE token_hash=?',[hash('sha256',$token)])->fetchColumn()===null,'Rejected user does not consume token');
stock_query($db,"INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES('12345678',1,?)",[time()+3600]);$p=stock_claim($db,'12345678',1);stock_query($db,'UPDATE pallets SET branch_id=12,activated=1 WHERE id=?',[$p['id']]);
verify(stock_detail($db,'12345678',1)['branch']['name']==='Test','Manager sees own location');foreach([null,2,3] as $user){$detail=stock_detail($db,'12345678',$user);verify(empty($detail['branch'])&&empty($detail['branch_id']),'Hidden public branch metadata');}
$share=branch_share_create($db,1,['branch_id'=>12]);denied(fn()=>branch_public($db,$share['share']['token']));denied(fn()=>branch_public($db,$share['share']['token'],0,2));verify(branch_public($db,$share['share']['token'],0,1)['branch']['name']==='Test','Manager public link authorized');
$invite=stock_query($db,'SELECT token_hash FROM branch_team_invites LIMIT 1')->fetchColumn();$items=[['type'=>'team_invitation','event_key'=>'team-invite:'.$invite,'body'=>'Legacy']];verify(notification_branch_context($db,$items,3)===[],'Hidden context not leaked');verify(notification_branch_context($db,$items,1)[0]['branch_name']==='Test','Structured branch context');
$request=(int)stock_query($db,'INSERT INTO team_pallet_requests(pallet_id,branch_id,owner_id,requested_by,base_revision) VALUES(?,12,1,1,1) RETURNING id',[$p['id']])->fetchColumn();
$context=notification_branch_context($db,[['type'=>'team_pallet_request','event_key'=>'team-pallet-request:'.$request,'body'=>'Legacy']],1);verify(str_contains($context[0]['body'],'12345678'),'Request keeps pallet code');
stock_query($db,"INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(3,?,'team_pallet_request','Solicitud','Test')",['team-pallet-request:'.$request]);stock_query($db,"INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(3,'other','achievement','Logro','Meta')");
verify((int)stock_query($db,'SELECT COUNT(*) FROM notifications n WHERE n.user_id=3 AND '.notification_visibility_sql($db,3))->fetchColumn()===1,'Hidden notifications excluded before pagination and unread count');
verify((int)stock_query($db,'SELECT COUNT(*) FROM notifications n WHERE n.user_id=3 AND '.notification_visibility_sql($db,1))->fetchColumn()===2,'Manager visibility query retains context');
stock_query($db,"INSERT INTO pallet_labels(code,reserved_by,reserved_until) VALUES('87654321',3,?)",[time()+3600]);$other=stock_claim($db,'87654321',3);denied(fn()=>branch_assign($db,3,['pallet_id'=>$other['id'],'branch_id'=>12,'revision'=>1]));
echo "OK: test branch manager-only directory, direct ID, catalog admin, business role, invite/token, public metadata/share and notification context.\n";

