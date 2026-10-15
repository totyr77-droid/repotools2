<?php
$paginaInicio = true;
include 'vistas/header.vista.php';
?>
<main>
  <?php if (current_user()): ?>
  <script>window.repotoolsStats = <?= json_encode(['userId' => (int) current_user()['id'], 'csrf' => csrf_token()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
  <?php endif; ?>
  <div class="contenedor-main">
<?php if (current_user() && $userHand === ''): ?><aside class="profile-pending" data-profile-pending role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 3h.01"/></svg><span>Tienes información por completar en tu perfil.</span><a href="cuenta.php#handPreferences">Completar <span aria-hidden="true">→</span></a></aside><?php endif; ?>
    <div class="app-resumen">
      <div class="home-intro"><h1>Herramientas para repositores</h1><p>Escaneá productos, controlá stock y organizá tu trabajo.</p></div>
      <a class="home-tops" href="tops.php">TOPs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h10v6a5 5 0 0 1-10 0ZM7 5H3v3a4 4 0 0 0 4 4m10-7h4v3a4 4 0 0 1-4 4m-5 2v6m-4 1h8"/></svg></a>
      <?php if (current_user()): ?><p id="scanStatsStatus" class="scan-stats-status" role="status"></p><?php endif; ?>
    </div>

    <!--BOTON SCANER-->
    <button id="boton" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 5v14M6 5v14M10 5v14M13 5v14M18 5v14M21 5v14"/></svg>Escanear producto</button>

    <!--BOTON VER-->
    <aside id="pwaInstallCard" class="pwa-install-card" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 3H5v18h14V3h-3M12 3v11m-4-4 4 4 4-4"/></svg><div><strong>RepoTools a mano</strong><p>Instalala para abrirla desde tu pantalla de inicio.</p><button id="installApp" type="button" class="library-secondary">Instalar app</button><p id="installAppHelp" hidden></p></div></aside>
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
include 'vistas/footer.vista.php';
?>
