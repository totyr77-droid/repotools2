<?php
$paginaInicio = true;
require_once __DIR__.'/includes/auth.php';require_once __DIR__.'/includes/branch-team.php';
$stockEmbedded=current_user()&&(bool)team_locations(database(),(int)current_user()['id']);
include 'vistas/header.vista.php';
?>
<main>
<script>window.repotoolsListCSRF = <?= json_encode(csrf_token(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;<?php if(isset($sharedScanCodes)): ?>window.repotoolsSharedCodes=<?= json_encode($sharedScanCodes,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;<?php endif; ?></script>
  <?php if (current_user()): ?>
  <script>window.repotoolsStats = <?= json_encode(['userId' => (int) current_user()['id'], 'csrf' => csrf_token()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
  <?php endif; ?>
  <div class="contenedor-main">
<?php if (current_user() && $userHand === ''): ?><aside class="profile-pending" data-profile-pending role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 3h.01"/></svg><span>Tienes información por completar en tu perfil.</span><a href="cuenta.php#handPreferences">Completar <span aria-hidden="true">→</span></a></aside><?php endif; ?>
    <div class="app-resumen">
      <div class="home-intro"><h1>Herramientas para repositores</h1><p>Escaneá productos, controlá stock y organizá tu trabajo.</p></div>
      <div class="home-shortcuts">
      <?php include __DIR__.'/vistas/branch-team-home.vista.php'; ?>
      <nav class="home-shortcut-links" aria-label="Accesos de inicio">
      <a class="home-tops" href="tops.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h10v6a5 5 0 0 1-10 0ZM7 5H3v3a4 4 0 0 0 4 4m10-7h4v3a4 4 0 0 1-4 4m-5 2v6m-4 1h8"/></svg><span>TOPs</span></a>
      <?php if($teamHomeUser&&($teamHomeLocations||$teamHomeInvites)): ?><a class="home-tops" href="sucursales.php?ambito=sucursales" data-team-home-link title="Equipo de sucursal"><?= stock_icon('branch') ?><span>Mi equipo</span></a><?php endif; ?>
      <?php if(current_user()): require_once __DIR__.'/includes/supervision.php';$homeSupervisor=supervisor_team(database(),(int)current_user()['id']);$homeSupervisorLinks=supervisor_links(database(),(int)current_user()['id']);if($homeSupervisor||$homeSupervisorLinks): ?><a class="home-tops" href="supervision.php"><?= stock_icon('users') ?><span><?= $homeSupervisor?'Mi empresa':'Supervisores' ?></span></a><?php endif;endif; ?>
      <?php if($teamHomeUser&&$teamHomeAssign): ?><a class="home-tops" href="gestion-empresas.php"><?= stock_icon('branch') ?><span data-team-home-label>Asignar empresa</span></a><?php endif; ?>
      </nav>
      </div>
      <?php if (current_user()): ?><p id="scanStatsStatus" class="scan-stats-status" role="status"></p><?php endif; ?>
    </div>

    <?php include __DIR__.'/vistas/home-location.vista.php'; ?>
    <!--BOTON SCANER-->
    <aside id="pwaInstallCard" class="pwa-install-card" hidden><?= stock_icon('install') ?><div><strong>RepoTools a mano</strong><p>Instalala para abrirla desde tu pantalla de inicio.</p><button id="installApp" type="button" class="library-secondary">Instalar app</button></div></aside>
    <dialog id="installAppHelpDialog" class="library-dialog" aria-labelledby="installAppHelpTitle"><div class="library-heading"><h2 id="installAppHelpTitle"><?= stock_icon('install') ?> Instalar RepoTools</h2><button type="button" class="app-close" id="installAppHelpClose" aria-label="Cerrar ayuda de instalación"><?= stock_icon('close') ?></button></div><p id="installAppHelp"></p></dialog>
    <div class="home-scan-actions"><button id="boton" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 5v14M6 5v14M10 5v14M13 5v14M18 5v14M21 5v14"/></svg>Escanear producto</button><?php if($teamHomeUser&&$teamHomeLocations): ?><button type="button" class="library-secondary home-pallet-scan" data-team-scan hidden aria-label="Escanear palet" title="Escanear palet"><?= stock_icon('qr') ?><span>Palet</span></button><?php endif; ?></div>

    <!--BOTON VER-->

    <section class="home-list"><div class="home-list-heading"><div><h2>Tu lista</h2><p>Guardada en este dispositivo.</p></div><div class="home-list-actions">
    <div class="box-tools" id="resultados">
      <button class="resultados-box" id="ver" type="button" aria-label="Ver codigos escaneados">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><span id="cantidad"></span>
      </button>
      <button class="compartir" id="compartir" type="button" aria-label="Compartir codigos">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M5 13v7h14v-7"/></svg>
      </button>
    </div>

    <!--BOTON BORRAR-->
    <button class="borrar-box" id="borrarD" type="button" aria-label="Borrar todos los códigos" title="Borrar todos los códigos"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg></button>
    </div></div></section>
  </div>

  <!-- BOX INFO -->
  <div class="info" hidden>
    <div class="info-contenedor">
      <div class="infotitulo">
        <p><i class="fa-solid fa-circle-info"></i>INFORMACION DE ESCANEOS</p>
      </div>
      <div class="infobox">
        <div class="infocontador">
          <p><span id="contadorGlobal">0</span> TOTALES</p>
        </div>
        <div class="infocontador">
          <p><span id="sM">0</span> TUYOS</p>
        </div>
      </div>
    </div>
  </div>
</main>
<?php
include 'vistas/escaner.vista.php';
if($stockEmbedded){include __DIR__.'/vistas/stock-workspace.vista.php';include __DIR__.'/vistas/stock-dialogs.vista.php';}

include 'vistas/footer.vista.php';
?>
