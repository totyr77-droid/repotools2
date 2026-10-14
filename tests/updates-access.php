<?php
function current_user(){return $GLOBALS['testUser'];}
$roleLabels=array_fill_keys(['supervisor','empresa','moderador','administrador','manager'],true);
foreach(['guest','usuario','supervisor','empresa','moderador','administrador','manager'] as $role){$navRole=$role;$testUser=$role==='guest'?null:['id'=>1];ob_start();include __DIR__.'/../vistas/updates.vista.php';$html=ob_get_clean();if((strlen($html)>0)!==isset($roleLabels[$role]))throw new RuntimeException('Access failed: '.$role);}
echo "OK: update log hidden for guests/users, visible for authorized ranks.\n";
