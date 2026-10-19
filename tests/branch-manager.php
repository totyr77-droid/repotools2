<?php
require_once __DIR__.'/../includes/database.php';
require_once __DIR__.'/../includes/branch-manager.php';
function management_check(bool $ok,string $why): void {if(!$ok)throw new RuntimeException($why);}
function management_denied(callable $fn,int $status): void {try{$fn();}catch(CatalogException $e){management_check($e->status===$status,$e->getMessage());return;}throw new RuntimeException('Expected denial');}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);initialize_database($db);
foreach(['manager','administrador','usuario'] as $i=>$role)stock_query($db,'INSERT INTO users(name,public_name,email,password_hash,role) VALUES(?,?,?,?,?)',['Test '.$i,'Test '.$i,'person'.$i.'@example.invalid','unused',$role]);
for($i=0;$i<24;$i++)stock_query($db,'INSERT INTO branch_workspaces(owner_id,name) VALUES(3,?)',['Empresa '.$i]);
$workspace=24;$branch=branch_save($db,1,['name'=>'Sucursal Norte','address'=>'Calle 1','locality'=>'Mendoza','province'=>'Mendoza','description'=>'Entrada lateral'])['branch'];team_attach($db,1,$workspace,(int)$branch['id']);
foreach([2,3] as $uid){management_denied(fn()=>team_manager_companies($db,$uid),403);management_denied(fn()=>team_manager_company($db,$uid,$workspace),403);management_denied(fn()=>team_manager_rename($db,$uid,$workspace,'Empresa 23','Cambio'),403);management_denied(fn()=>team_manager_save_branch($db,$uid,$workspace,[]),403);}
$list=team_manager_companies($db,1);management_check(count($list['companies'])===20&&$list['next_cursor']!==null,'company pagination');
$next=team_manager_companies($db,1,'',$list['next_cursor']);management_check(count($next['companies'])===4&&!$next['next_cursor'],'second page');
management_check(count(array_unique(array_column([...$list['companies'],...$next['companies']],'id')))===24,'no duplicate pages');
foreach(['Sucursal Norte','Mendoza','person2@example.invalid','Test 2'] as $q)management_check(count(team_manager_companies($db,1,$q)['companies'])>0,'search '.$q);
management_check(!team_manager_companies($db,1,'%')['companies'],'literal wildcard');management_check(!team_manager_companies($db,1,"' OR 1=1 --")['companies'],'literal SQL');
management_denied(fn()=>team_manager_company($db,1,999),404);
$detail=team_manager_company($db,1,$workspace);management_check(count($detail['branches'])===1&&$detail['company']['owner_email']==='person2@example.invalid','detail');
management_check(!array_key_exists('products',$detail)&&!array_key_exists('members',$detail),'no inventory or member directory');
management_denied(fn()=>team_manager_rename($db,1,$workspace,'Old','New'),409);management_denied(fn()=>team_manager_rename($db,1,$workspace,'Empresa 23',''),422);
$db->beginTransaction();team_manager_rename($db,1,$workspace,'Empresa 23','Empresa corregida');$db->commit();management_check(team_manager_company($db,1,$workspace)['company']['name']==='Empresa corregida','rename');management_check((int)$db->query("SELECT COUNT(*) FROM branch_events WHERE action='workspace_updated'")->fetchColumn()===1,'rename audit');
$input=['branch_id'=>$branch['id'],'revision'=>1,'name'=>'Sucursal corregida','address'=>'Calle 2','locality'=>'Mendoza','province'=>'Mendoza','description'=>'Entrada lateral'];
management_denied(fn()=>team_manager_save_branch($db,1,23,$input),409);$db->beginTransaction();team_manager_save_branch($db,1,$workspace,$input);$db->commit();management_denied(fn()=>team_manager_save_branch($db,1,$workspace,$input),409);
$saved=team_manager_company($db,1,$workspace)['branches'][0];management_check($saved['name']==='Sucursal corregida'&&(int)$saved['revision']===2,'branch saved with revision');
management_check(team_role($db,3,(int)$branch['id'])==='admin','responsible unchanged');management_check(team_role($db,1,(int)$branch['id'])==='','platform manager does not acquire business access');
for($i=0;$i<31;$i++){$id=(int)stock_query($db,'INSERT INTO stock_branches(owner_id,name,address,locality,province) VALUES(1,?,?,?,?) RETURNING id',['Page '.$i,'Address '.$i,'City','Province'])->fetchColumn();stock_query($db,'INSERT INTO branch_workspace_locations(branch_id,workspace_id) VALUES(?,22)',[$id]);}
$page=team_manager_company($db,1,22);management_check(count($page['branches'])===30&&$page['next_cursor']!==null,'branch pagination');$tail=team_manager_company($db,1,22,$page['next_cursor']);management_check(count($tail['branches'])===1&&!$tail['next_cursor'],'branch pagination end');
$db->exec("UPDATE users SET role='usuario' WHERE id=1");management_denied(fn()=>team_manager_companies($db,1),403);
echo "OK: manager-only reads/writes, current role, pagination, search, literals, scoped branch edit, conflict, audit and business permissions retained.\n";
