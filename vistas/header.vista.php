<?php
require_once __DIR__ . "/../includes/auth.php";
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
  <head>
    <meta charset="utf-8">
    <?php include __DIR__ . '/page-loader-head.vista.php'; ?>
    <?php if(isset($publicShareBase)): ?><base href="<?= escape($publicShareBase) ?>"><?php endif; ?>
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
    <meta property="og:image:alt" content="Logo de Repotools blanco sobre fondo negro">
    <meta property="og:site_name" content="Repotools">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= escape($previewTitle) ?>">
    <meta name="twitter:description" content="<?= escape($previewDescription) ?>">
    <meta name="twitter:image" content="<?= escape($metadata['image']) ?>">
    <meta name="twitter:image:alt" content="Logo de Repotools blanco sobre fondo negro">
    <meta property="og:url" content="<?= escape($previewUrl) ?>">
    <meta property="og:type" content="website">

    <meta name="description" content="<?= escape($previewDescription) ?>">
    <meta name="keywords" content="repositores, escaneo de codigos de barras, gestion de stock, inventario, productos, logistica, scanner de codigos, administracion de inventario, manejo de stock">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="img/Repotools_Logo_Pack_Blanco/03_isotipo_fondo_negro/repotools-icon-negro-32x32.png" sizes="32x32" type="image/png">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#27187e">
    <link rel="apple-touch-icon" href="img/Repotools_Logo_Pack_Blanco/05_apple/apple-touch-icon-180x180.png" sizes="180x180">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="preload" href="css/vendor/fontawesome-6.7.2/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="css/vendor/fontawesome-6.7.2/css/fontawesome.min.css">
    <link rel="stylesheet" href="css/vendor/fontawesome-6.7.2/css/solid.min.css">
    <link rel="stylesheet" href="css/vendor/fontawesome-6.7.2/css/brands.min.css">
    <link rel="stylesheet" href="https://use.typekit.net/sxf4kmm.css">
    <link href="css/loading.css?v=20261013-loading1" rel="stylesheet" type="text/css">
    <?php if(!empty($paginaDemo)): ?><link href="css/guest-demo.css?v=20261013-demo3" rel="stylesheet"><?php endif; ?>
    <?php if (!empty($paginaInicio) || (!empty($paginaCuenta) && empty($paginaTops) && current_user())): ?><link href="css/onboarding.css?v=20261013-intro1" rel="stylesheet" type="text/css"><?php endif; ?>
    <link href="css/normalize.css" rel="stylesheet" type="text/css">
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <link href="css/contador.css" rel="stylesheet" type="text/css">
    <link href="css/mobile.css" rel="stylesheet" type="text/css">
    <link href="css/footer.css" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaInicio) || !empty($paginaBiblioteca) || !empty($paginaReportes)): ?><link href="css/reports.css?v=20261011-reportphotofallback10" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaReportes)): ?><link href="css/library.css?v=20261011-reference4" rel="stylesheet" type="text/css"><link href="css/product-info.css?v=20261011-summary1" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio) || !empty($paginaBiblioteca)): ?><link href="css/product-info.css?v=20261011-summary1" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/scan-result.css?v=20261013-settings1" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaBiblioteca) || !empty($paginaInicio)): ?><link href="css/library.css?v=20261011-reference4" rel="stylesheet" type="text/css"><link href="css/ean-scanner.css?v=20261011-lock1" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaStock)): ?><link href="css/library.css?v=20261011-reference4" rel="stylesheet" type="text/css"><link href="css/ean-scanner.css?v=20261011-lock1" rel="stylesheet" type="text/css"><?php endif; ?>

    <link href="css/responsive.css" rel="stylesheet" type="text/css">
    <link href="css/notifications.css?v=20261012-desktop-status" rel="stylesheet" type="text/css">
    <?php if(!empty($paginaCuenta)&&empty($paginaTops)&&current_user()): ?><link href="css/public-name.css?v=20261013-public1" rel="stylesheet"><?php endif; ?>
    <link href="css/account-bar.css?v=20261010" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaStock) || !empty($paginaCuenta)): ?><link href="css/stock-branches.css?v=20261012-exclusions" rel="stylesheet" type="text/css"><link href="css/stock.css?v=20261013-batch1" rel="stylesheet" type="text/css"><?php endif; ?>
    <link href="css/action-notices.css?v=20261013-settings1" rel="stylesheet" type="text/css">
    <link href="css/close-buttons.css?v=20261010-scroll1" rel="stylesheet" type="text/css">
    <link href="css/navigation.css?v=20261013-loading1" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaTops)): ?><link href="css/tops.css?v=20261010" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/home.css?v=20261013-pwa" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/results-viewer.css?v=20261011-shareone3" rel="stylesheet" type="text/css"><?php endif; ?>
  <?php if (!empty($paginaCuenta)): ?><link href="css/user-profiles.css?v=20261011-directory7" rel="stylesheet"><?php endif; ?>
<?php if (!empty($paginaReportes)): ?><link href="css/ean-scanner.css?v=20261011-lock1" rel="stylesheet"><?php endif; ?>
<link href="css/hand-preferences.css?v=20261011-title-alert9" rel="stylesheet">
<link href="css/title-icons.css?v=20261011-icons2" rel="stylesheet">
<link href="css/device-workspace.css?v=20261013-settings1" rel="stylesheet" type="text/css">
<?php if (!empty($paginaCuenta) && current_user()): ?><link href="css/settings.css?v=20261013-settings1" rel="stylesheet"><?php endif; ?>
<?php if (!empty($paginaStockCompartido)): ?><link href="css/stock-shared.css?v=20261012-branch-refresh" rel="stylesheet"><?php endif; ?>
</head>
  <body data-hand="<?= $userHand === 'left' ? 'left' : 'right' ?>" data-scan-sound="<?= $scanSound?'1':'0' ?>" data-scan-vibration="<?= $scanVibration?'1':'0' ?>"<?= !empty($paginaInicio) ? ' class="home-page"' : (!empty($paginaBiblioteca) ? ' class="library-page"' : '') ?>>
    <header>
      <div class="header-contenedor">
        <a class="logo" href="./" aria-label="Inicio Repotools"><img src="img/Repotools_Logo_Pack_Blanco/06_horizontal_blanco_transparente/repotools-horizontal-blanco-480w.png" width="480" height="82" alt="Repotools"></a>

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
        <div class="notification-control">
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
    </header>
    <?php if (current_user()): ?>
    <div class="account-bar"><span class="account-greeting" title="Hola, <?= escape(current_user()['name']) ?>">Hola, <strong><?= escape(current_user()['name']) ?></strong><?php if (isset($roleLabels[$navRole])): $badge = $roleLabels[$navRole]; ?><span class="account-role account-role-<?= escape($navRole) ?>" title="<?= escape($badge[1]) ?>" aria-label="<?= escape($badge[1]) ?>"><i class="fa-solid <?= escape($badge[2]) ?>" aria-hidden="true"></i><?= escape($badge[0]) ?></span><?php endif; ?></span><form action="salir.php" method="post"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 4H4v16h5m5-12 4 4-4 4m-6-4h13"/></svg><span>Salir</span></button></form></div>
    <?php endif; ?>


<?php if(current_user()): ?><aside id="deviceConnection" class="device-connection" data-user="<?= (int)current_user()['id'] ?>" data-kind="<?= escape($_SESSION['device_kind']) ?>" data-csrf="<?= escape(csrf_token()) ?>" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg><span data-relay-message role="status"></span><button type="button" data-relay-accept hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 15l6-6M8 12l-2 2a4 4 0 0 0 6 6l2-2M16 12l2-2a4 4 0 0 0-6-6l-2 2"/></svg>Conectar</button><button type="button" data-relay-scan hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 4v16M6 4v16M10 4v16M14 4v16M17 4v16M21 4v16"/></svg>Escanear para PC</button><button type="button" data-relay-disconnect hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 4H3v16h3m8-12 4 4-4 4M8 12h13"/></svg>Desconectar</button></aside><?php endif; ?>
