<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . '/../includes/stock-icons.php';
require_once __DIR__ . "/../includes/catalog.php";
require_once __DIR__ . "/../includes/reports.php";
require_once __DIR__ . '/../includes/page-metadata.php';
$metadata=repotools_page_metadata($publicPageTitle??null,$publicPageDescription??null);
$previewTitle=$metadata['title'];$previewDescription=$metadata['description'];$previewUrl=$metadata['url'];
$userHand = '';
$scanSound = true; $scanVibration = true;
$navModerator = false; $navPending = 0; $navRole = '';
if (current_user()) {
    try { $navDb = database(); $navModerator = can_edit_catalog($navDb, current_user()); if ($navModerator) { $navCount = $navDb->prepare("SELECT COUNT(*) FROM product_reports WHERE status = 'pending' AND (user_id IS NULL OR user_id <> ?)"); $navCount->execute([report_is_manager($navDb,current_user())?0:current_user()['id']]); $navPending = (int) $navCount->fetchColumn(); } }
    catch (Throwable $error) { error_log('Repotools: no se pudo consultar el menú de moderación.'); }
}
if (isset($navDb) && current_user()) {
    try { $roleQuery = $navDb->prepare('SELECT role,handedness,sound_enabled,vibration_enabled FROM users WHERE id = ?'); $roleQuery->execute([current_user()['id']]); $navProfile = $roleQuery->fetch(); $navRole = (string) ($navProfile['role'] ?? ''); $userHand = (string) ($navProfile['handedness'] ?? ''); $scanSound=(int)($navProfile['sound_enabled']??1)!==0; $scanVibration=(int)($navProfile['vibration_enabled']??1)!==0; } catch (Throwable $ignored) {}
}
$roleLabels = ['manager' => ['MNG', 'Manager', 'fa-crown'], 'administrador' => ['ADM', 'Administrador', 'fa-user-gear'], 'moderador' => ['MOD', 'Moderador', 'fa-shield-halved'], 'supervisor' => ['SUP', 'Supervisor', 'fa-user-tie'], 'empresa' => ['EMP', 'Empresa', 'fa-building']];
?>
<!DOCTYPE html>
<html lang="es" class="is-loading">
  <head><?php require_once __DIR__.'/../includes/app-release.php'; ?>
    <meta charset="utf-8">
    <?php include __DIR__ . '/page-loader-head.vista.php'; ?>
    <?php if(isset($publicShareBase)): ?><base href="<?= escape($publicShareBase) ?>"><?php endif; ?>
    <?php if(support_active()): ?><script>window.RepoSupport=<?= json_encode(['context'=>support_context()],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script><script src="js/support-session.js?v=<?= substr(sha1_file(__DIR__.'/../js/support-session.js'),0,12) ?>"></script><?php endif; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <title><?= escape($previewTitle) ?></title>
    <link rel="canonical" href="<?= escape($previewUrl) ?>">
    <meta property="og:title" content="<?= escape($previewTitle) ?>">
    <meta property="og:description" content="<?= escape($previewDescription) ?>">
    <meta property="og:image" content="<?= escape($metadata['image']) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Logo de Repotools blanco sobre fondo violeta">
    <meta property="og:site_name" content="Repotools">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= escape($previewTitle) ?>">
    <meta name="twitter:description" content="<?= escape($previewDescription) ?>">
    <meta name="twitter:image" content="<?= escape($metadata['image']) ?>">
    <meta name="twitter:image:alt" content="Logo de Repotools blanco sobre fondo violeta">
    <meta property="og:url" content="<?= escape($previewUrl) ?>">
    <meta property="og:type" content="website">

    <meta name="description" content="<?= escape($previewDescription) ?>">
    <meta name="keywords" content="repositores, escaneo de codigos de barras, gestion de stock, inventario, productos, logistica, scanner de codigos, administracion de inventario, manejo de stock">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="img/brand/repotools-icon-violeta-32x32.png" sizes="32x32" type="image/png">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#27197F">
    <link rel="apple-touch-icon" href="img/brand/apple-touch-icon-180x180.png" sizes="180x180">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="preload" href="css/vendor/fontawesome-6.7.2/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="css/vendor/fontawesome-6.7.2/css/fontawesome.min.css">
    <link rel="stylesheet" href="css/vendor/fontawesome-6.7.2/css/solid.min.css">
    <link rel="stylesheet" href="css/vendor/fontawesome-6.7.2/css/brands.min.css">
    <link rel="stylesheet" href="https://use.typekit.net/sxf4kmm.css">
    <link href="css/loading.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaInicio) || (!empty($paginaCuenta) && empty($paginaTops) && current_user())): ?><link href="css/onboarding.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if(!empty($paginaInicio)&&current_user()): ?><link rel="stylesheet" href="css/home-location.css?v=<?= substr(sha1_file(__DIR__.'/../css/home-location.css'),0,12) ?>"><?php endif; ?>
    <link href="css/normalize.css" rel="stylesheet" type="text/css">
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <link href="css/contador.css" rel="stylesheet" type="text/css">
    <link href="css/mobile.css" rel="stylesheet" type="text/css">
    <link href="css/footer.css" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaInicio) || !empty($paginaBiblioteca) || !empty($paginaReportes)): ?><link href="css/reports.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaReportes)): ?><link href="css/library.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><link href="css/product-info.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio) || !empty($paginaBiblioteca) || !empty($stockEmbedded)): ?><link href="css/product-info.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/scan-result.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if(!empty($paginaGestionEmpresas)||!empty($paginaSupervision)): ?><?php if(!empty($paginaGestionEmpresas)): ?><link href="css/stock-branches.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?><link href="css/library.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if(!empty($paginaSupervision)): ?><link href="css/ean-scanner.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaBiblioteca) || !empty($paginaInicio) || !empty($paginaEquipo)): ?><link href="css/library.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><link href="css/ean-scanner.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaStock)): ?><link href="css/library.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><link href="css/ean-scanner.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>


    <link href="css/notifications.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
    <?php if(!empty($paginaCuenta)&&empty($paginaTops)&&current_user()): ?><link href="css/public-name.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
    <link href="css/account-bar.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaStock) || !empty($stockEmbedded) || !empty($paginaCuenta) || !empty($paginaEquipo) || !empty($paginaSupervision)): ?><link href="css/stock-branches.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><link href="css/stock.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <link href="css/action-notices.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
    <link href="css/close-buttons.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
    <link href="css/navigation.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaReferidos)): ?><link href="css/referrals.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
    <?php if (!empty($paginaTops)): ?><link href="css/tops.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/home.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/results-viewer.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css"><?php endif; ?>
  <?php if (!empty($paginaCuenta)): ?><link href="css/user-profiles.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
<?php if (!empty($paginaReportes)): ?><link href="css/ean-scanner.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
<link href="css/hand-preferences.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet">
<link href="css/title-icons.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet">
<link href="css/device-workspace.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
<?php if (!empty($paginaCuenta) && current_user()): ?><link href="css/settings.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
  <?php if (!empty($paginaCuenta)): ?><link href="css/profile-showcase.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
<?php if (!empty($paginaStockCompartido)): ?><link href="css/stock-shared.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
<?php if (!empty($paginaStock) || !empty($stockEmbedded)): ?><link href="css/pallet-activity.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><?php endif; ?>
<link href="css/connectivity.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet"><link href="css/responsive.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet" type="text/css">
<link href="css/motion.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet">
<link href="css/mobile-navigation.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet">
<link href="css/one-hand-mode.css?v=<?= (int)app_release()['version'] ?>" rel="stylesheet">
<link href="css/ux-polish.css?v=<?= substr(sha1_file(__DIR__.'/../css/ux-polish.css'),0,12) ?>" rel="stylesheet"><link rel="preload" href="js/one-hand-mode.js?v=<?= (int)app_release()['version'] ?>" as="script"><link rel="preload" href="js/ux-polish.js?v=<?= (int)app_release()['version'] ?>" as="script">    <?php if(!empty($paginaDemo)): ?><link href="css/guest-demo.css?v=<?= substr(sha1_file(__DIR__.'/../css/guest-demo.css'),0,12) ?>" rel="stylesheet"><?php endif; ?>
</head>
  <?php require_once __DIR__.'/../includes/app-release.php'; ?><body data-app-updates="<?= current_user() && isset($roleLabels[$navRole]) ? '1' : '0' ?>" data-app-version="<?= (int)app_release()['version'] ?>" data-ui-user="<?= (int)(current_user()['id']??0) ?>" data-hand="<?= $userHand === 'left' ? 'left' : 'right' ?>" data-hand-preference="<?= in_array($userHand,['left','right'],true)?$userHand:'' ?>" data-scan-sound="<?= $scanSound?'1':'0' ?>" data-scan-vibration="<?= $scanVibration?'1':'0' ?>"<?= !empty($paginaInicio) ? ' class="home-page"' : ((!empty($paginaBiblioteca)||!empty($paginaEquipo)||!empty($paginaGestionEmpresas)||!empty($paginaSupervision)) ? ' class="library-page"' : '') ?>>
    <?php include __DIR__.'/../includes/support-banner.php'; ?>
    <script>window.repotoolsOfflineUser=<?= (int)(current_user()['id']??0) ?>;try{document.body.dataset.oneHand=localStorage.getItem('repotools_one_hand_v1_user_<?= (int)(current_user()['id']??0) ?>')==='1'?'1':'0';}catch{document.body.dataset.oneHand='0';}</script>
    <header>
      <div class="header-contenedor">
        <a class="logo" href="./" aria-label="Inicio Repotools"><span class="logo-mark"><img src="img/brand/repotools-horizontal-transparente.svg" width="1561" height="268" alt="Repotools"><?php if(current_user()&&$navRole==='manager'): ?><span class="app-logo-version" aria-label="Versión <?= app_release_label() ?>">v<?= app_release_label() ?></span><?php endif; ?></span></a>

        <?php if(current_user() && (!empty($paginaStock)||!empty($paginaBiblioteca)||!empty($paginaReportes))): ?><a class="workspace-account-icon" href="cuenta.php" aria-label="Mi cuenta y ajustes" title="Mi cuenta y ajustes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg></a><?php endif; ?>
        <nav class="nav" aria-label="Navegacion principal">
          <a href="./"><i class="fa-solid fa-house"></i>INICIO</a>
          <a href="stock.php" class="stock-nav-link"><i class="fa-solid fa-qrcode"></i>STOCK<span class="report-pending-badge stock-expiry-badge" data-stock-expiry aria-label="Productos por vencer o vencidos" hidden></span></a>
          <a href="biblioteca.php"><i class="fa-solid fa-book"></i>BIBLIOTECA</a>
          <?php if ($navModerator): ?><a href="reportes.php?queue=1" class="report-nav-link"><i class="fa-solid fa-shield-halved"></i>MODERACIÓN <span class="report-pending-badge" data-report-pending<?= $navPending === 0 ? ' hidden' : '' ?> aria-label="<?= $navPending ?> reportes pendientes"><?= $navPending > 99 ? '99+' : $navPending ?></span></a><?php endif; ?>
          <a href="cuenta.php"><i class="fa-solid <?= current_user() ? 'fa-gear' : 'fa-user' ?>"></i><?= current_user() ? 'AJUSTES' : 'INGRESAR' ?><?php if (current_user()): ?><span class="profile-pending-dot" data-profile-pending aria-label="Perfil por completar"<?= $userHand !== '' ? ' hidden' : '' ?>></span><?php endif; ?></a>
        </nav>

        <nav class="nav-celular" id="nav-celular" aria-label="Navegación móvil">
          <a href="./"><i class="fa-solid fa-house"></i>INICIO</a>
          <a href="stock.php" class="stock-nav-link"><i class="fa-solid fa-qrcode"></i>STOCK<span class="report-pending-badge stock-expiry-badge" data-stock-expiry aria-label="Productos por vencer o vencidos" hidden></span></a>
          <a href="biblioteca.php"><i class="fa-solid fa-book"></i>BIBLIOTECA</a>
          <?php if ($navModerator): ?><a href="reportes.php?queue=1" class="report-nav-link"><i class="fa-solid fa-shield-halved"></i>MODERACIÓN <span class="report-pending-badge" data-report-pending<?= $navPending === 0 ? ' hidden' : '' ?> aria-label="<?= $navPending ?> reportes pendientes"><?= $navPending > 99 ? '99+' : $navPending ?></span></a><?php endif; ?>
          <a href="cuenta.php"><i class="fa-solid <?= current_user() ? 'fa-gear' : 'fa-user' ?>"></i><?= current_user() ? 'AJUSTES' : 'INGRESAR' ?><?php if (current_user()): ?><span class="profile-pending-dot" data-profile-pending aria-label="Perfil por completar"<?= $userHand !== '' ? ' hidden' : '' ?>></span><?php endif; ?></a>
        </nav>

        <?php if (current_user()): ?>
        <div class="notification-control"><button id="openOfflineSent" class="sync-receipt-button" type="button" data-available="false" tabindex="-1" aria-hidden="true" aria-label="Cargas enviadas al reconectar" title="Cargas enviadas al reconectar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M4 15v6h16v-6"/></svg></button>
          <?php if($navRole==='manager'): ?><button id="refreshAppCache" type="button" aria-label="Actualizar caché" title="Actualizar caché"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6 7a7 7 0 0 1 12-1l2 3M4 15l2 3a7 7 0 0 0 12-1"/></svg></button><?php endif; ?>
          <button id="openNotifications" type="button" aria-label="Notificaciones" aria-expanded="false" aria-controls="notificationPanel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM9 21h6"/></svg><span id="notificationBadge" hidden></span></button>
          <section id="notificationPanel" class="notification-panel" aria-label="Notificaciones" hidden>
            <div class="notification-heading"><h2>Notificaciones</h2><button id="closeNotifications" class="app-close" type="button" aria-label="Cerrar notificaciones" title="Cerrar notificaciones"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
            <button id="readAllNotifications" type="button" hidden>Marcar todas como leídas</button>
            <p id="notificationStatus" role="status"></p><div id="notificationList"></div>
          </section>
        </div>
        <script>window.repotoolsNotifications = <?= json_encode(['csrf' => csrf_token()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
        <?php endif; ?>
        <button id="btn.nav-celular" class="botonMenu" type="button" aria-label="Abrir menu">
          <i id="btn-nav-celularA" class="fa-solid fa-angle-left"></i>
          <?php if ($navModerator): ?><span class="report-pending-badge report-menu-badge" data-report-pending<?= $navPending === 0 ? ' hidden' : '' ?> aria-label="<?= $navPending ?> reportes pendientes"><?= $navPending > 99 ? '99+' : $navPending ?></span><?php endif; ?>
        </button>
      </div>
    <?php if(current_user()): ?><details id="quickUserQR" class="quick-user-qr"><summary aria-label="Mostrar mi QR de usuario" title="Mi QR"><?= stock_icon('chevron') ?></summary><div class="quick-user-qr-panel"><strong>Mi QR de usuario</strong><div id="quickUserQRImage" aria-label="QR de mi cuenta"></div><p id="quickUserQRStatus" role="status"></p><small>Abre tu perfil y comparte tu invitación. En la app también permite seleccionarte.</small></div></details><?php endif; ?>
    </header>
    <?php if (current_user() && empty($paginaStock) && empty($paginaBiblioteca) && empty($paginaReportes) && empty($paginaEquipo) && empty($paginaGestionEmpresas) && empty($paginaSupervision)): ?>
    <div class="account-bar"><span class="account-greeting" title="Hola, <?= escape(current_user()['name']) ?>">Hola, <strong><?= escape(current_user()['name']) ?></strong><?php if (isset($roleLabels[$navRole])): $badge = $roleLabels[$navRole]; ?><span class="account-role account-role-<?= escape($navRole) ?>" title="<?= escape($badge[1]) ?>" aria-label="<?= escape($badge[1]) ?>"><i class="fa-solid <?= escape($badge[2]) ?>" aria-hidden="true"></i><?= escape($badge[0]) ?></span><?php endif; ?></span><form action="salir.php" method="post"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 4H4v16h5m5-12 4 4-4 4m-6-4h13"/></svg><span>Salir</span></button></form></div>
    <?php endif; ?>


<?php if(current_user()): ?><aside id="deviceConnection" class="device-connection" data-user="<?= (int)current_user()['id'] ?>" data-kind="<?= escape($_SESSION['device_kind']) ?>" data-csrf="<?= escape(csrf_token()) ?>" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg><span data-relay-message role="status"></span><button type="button" data-relay-accept hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 15l6-6M8 12l-2 2a4 4 0 0 0 6 6l2-2M16 12l2-2a4 4 0 0 0-6-6l-2 2"/></svg>Conectar</button><button type="button" data-relay-scan hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 4v16M6 4v16M10 4v16M14 4v16M17 4v16M21 4v16"/></svg>Escanear para PC</button><button type="button" data-relay-disconnect hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 4H3v16h3m8-12 4 4-4 4M8 12h13"/></svg>Desconectar</button></aside><?php endif; ?>
