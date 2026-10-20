<?php
require __DIR__.'/team-inventory.php';
stock_query($db,"UPDATE users SET role='manager' WHERE id=4");
inv_check(team_role($db,4,1)==='admin','platform manager administrative access');
inv_check(team_role($db,4,99999)==='','unknown branch denied');
inv_check(team_locations($db,4)===[],'unselected unrelated branches do not fill home');
team_select($db,4,1);inv_check(team_active($db,4)===1,'manager selects linked branch');
$locations=team_locations($db,4);inv_check(count($locations)===1&&$locations[0]['role']==='admin','selected branch admin visible');
inv_check(count(team_inventory_page($db,4,1,'palets',[])['items'])===30,'manager reads inventory');
$token=team_invite($db,4,1,'manager');inv_check(strlen($token)===64,'manager may grant encargado');
stock_query($db,"UPDATE users SET role='usuario' WHERE id=4");
inv_check(team_role($db,4,1)===''&&team_active($db,4)===0&&team_locations($db,4)===[],'demotion revokes implicit access immediately');
inv_denied(fn()=>team_inventory_page($db,4,1,'palets',[]),403);
stock_query($db,"UPDATE users SET role='administrador' WHERE id=4");inv_check(team_role($db,4,1)==='','platform admin does not inherit manager access');
echo "OK: manager admin access, selected scope, invitations, unknown branch and demotion boundaries.\n";
