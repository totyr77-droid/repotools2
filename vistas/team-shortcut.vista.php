<?php
if(current_user()&&empty($paginaInicio)&&empty($paginaEquipo)&&empty($paginaSupervision)){
    require_once __DIR__.'/../includes/branch-team.php';
    require_once __DIR__.'/../includes/stock-icons.php';
    $teamShortcutLocations=array_filter(team_locations(database(),(int)current_user()['id']),static fn($location)=>in_array($location['role'],['admin','manager'],true));
    if($teamShortcutLocations): ?>
<link rel="stylesheet" href="css/team-shortcut.css?v=<?= (int)app_release()['version'] ?>">
<a href="sucursales.php?ambito=sucursales" class="team-edge-shortcut" aria-label="Mi sucursal" title="Mi sucursal"><?= stock_icon('branch') ?></a>
<script src="js/team-shortcut.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php endif; } ?>
