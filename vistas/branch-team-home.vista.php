<?php
$teamHomeUser=current_user();
if($teamHomeUser){
    require_once __DIR__.'/../includes/branch-team.php';
    require_once __DIR__.'/../includes/supervision.php';
    $teamHomeCompany=supervisor_team(database(),(int)$teamHomeUser['id']);
    require_once __DIR__.'/../includes/stock-icons.php';
    $teamHomeLocations=team_locations(database(),(int)$teamHomeUser['id']);
    $teamHomeInvites=team_incoming(database(),(int)$teamHomeUser['id']);
    $teamHomeAssign=team_can_assign(database(),(int)$teamHomeUser['id']);
}
if($teamHomeUser&&($teamHomeLocations||$teamHomeInvites)): require_once __DIR__.'/../includes/stock-icons.php'; ?>
<section class="team-home" data-team-home aria-label="Trabajo en sucursal">
  <div class="team-home-row"><label class="team-title-location team-branch-badge"<?= !$teamHomeLocations?' hidden':'' ?>><span class="sr-only">Sucursal activa</span><?= stock_icon('branch') ?><select data-team-location aria-label="Sucursal activa"<?= count($teamHomeLocations)===1?' hidden':'' ?>></select><?php if(count($teamHomeLocations)===1): $onlyHomeBranch=$teamHomeLocations[0]; ?><span class="team-location-static"><?= escape($onlyHomeBranch['name']) ?></span><?php endif; ?></label></div>
  <p data-team-home-status role="status" hidden></p>
</section>
<dialog id="teamScanner" class="stock-dialog"><div class="dialog-title"><button type="button" class="library-secondary" data-team-close aria-label="Cerrar"><?= stock_icon('close') ?></button><h2><?= stock_icon('qr') ?>Escanear palet</h2></div><p class="team-scan-branch"><?= stock_icon('branch') ?><span id="teamScanBranch"></span></p><p class="team-scan-notice"><?= stock_icon('info') ?><span>Si el palet no tiene sucursal, se asigna a esta al escanear.</span></p><div id="teamReader" class="ean-reader"></div><p id="teamScanStatus" role="status"></p><button type="button" class="library-secondary" id="teamManual"><?= stock_icon('keyboard') ?>Ingresar número</button><form id="teamCodeForm" hidden><label>Código del palet<input name="code" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" placeholder="8 números" required></label><button class="library-primary" type="submit">Abrir palet</button></form></dialog>
<?php endif; ?>
