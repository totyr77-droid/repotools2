<?php
$teamHomeUser=current_user();
if($teamHomeUser){
    require_once __DIR__.'/../includes/branch-team.php';
    require_once __DIR__.'/../includes/stock-icons.php';
    $teamHomeLocations=team_locations(database(),(int)$teamHomeUser['id']);
    $teamHomeAssign=team_can_assign(database(),(int)$teamHomeUser['id']);
}
if($teamHomeUser&&$teamHomeLocations): require_once __DIR__.'/../includes/stock-icons.php'; ?>
<section class="team-home" data-team-home aria-label="Trabajo en sucursal">
  <div class="team-home-row"><label<?= !$teamHomeLocations?' hidden':'' ?>>Sucursal activa<select data-team-location aria-label="Sucursal activa"><option value="0">Uso personal</option></select></label><a href="sucursales.php" class="library-secondary"><?= stock_icon('branch') ?><span data-team-home-label><?= $teamHomeLocations?'Mi equipo':'Asignar empresa' ?></span></a><button type="button" class="library-primary" data-team-scan hidden><?= stock_icon('qr') ?>Escanear palet</button></div>
  <p data-team-home-status role="status" hidden></p>
</section>
<dialog id="teamScanner" class="stock-dialog"><div class="dialog-title"><button type="button" class="library-secondary" data-team-close aria-label="Cerrar"><?= stock_icon('close') ?></button><h2><?= stock_icon('qr') ?>Escanear palet</h2></div><p id="teamScanBranch"></p><div id="teamReader" class="ean-reader"></div><p id="teamScanStatus" role="status"></p><button type="button" class="library-secondary" id="teamManual">Ingresar número</button><form id="teamCodeForm" hidden><label>Código del palet<input name="code" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" placeholder="8 números" required></label><button class="library-primary" type="submit">Abrir palet</button></form></dialog>
<?php endif; ?>
