<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/catalog.php";
require_once __DIR__ . "/../includes/reports.php";
$userHand = '';
$navModerator = false; $navPending = 0; $navRole = '';
if (current_user()) {
    try { $navDb = database(); $navModerator = can_edit_catalog($navDb, current_user()); if ($navModerator) { $navCount = $navDb->prepare("SELECT COUNT(*) FROM product_reports WHERE status = 'pending' AND (user_id IS NULL OR user_id <> ?)"); $navCount->execute([report_is_manager($navDb,current_user())?0:current_user()['id']]); $navPending = (int) $navCount->fetchColumn(); } }
    catch (Throwable $error) { error_log('Repotools: no se pudo consultar el menú de moderación.'); }
}
if (isset($navDb) && current_user()) {
    try { $roleQuery = $navDb->prepare('SELECT role,handedness FROM users WHERE id = ?'); $roleQuery->execute([current_user()['id']]); $navProfile = $roleQuery->fetch(); $navRole = (string) ($navProfile['role'] ?? ''); $userHand = (string) ($navProfile['handedness'] ?? ''); } catch (Throwable $ignored) {}
}
$roleLabels = ['manager' => ['MNG', 'Manager', 'fa-crown'], 'administrador' => ['ADM', 'Administrador', 'fa-user-gear'], 'moderador' => ['MOD', 'Moderador', 'fa-shield-halved'], 'supervisor' => ['SUP', 'Supervisor', 'fa-user-tie'], 'empresa' => ['EMP', 'Empresa', 'fa-building']];
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <title>Repotools - Escaner para Repositores | Gestion de Stock e Inventario</title>
    <meta property="og:title" content="Repotools - Escaner para Repositores">
    <meta property="og:description" content="Escanea productos y gestiona stock facilmente. Herramienta eficiente para repositores.">
    <meta property="og:image" content="https://repotools.online/img/logo.png">
    <meta property="og:url" content="https://repotools.online/">
    <meta property="og:type" content="website">

    <meta name="description" content="Optimiza tu trabajo como repositor con Repotools: escanea codigos de barras y gestiona tu stock sin cargar productos. Facil, rapido y eficiente.">
    <meta name="keywords" content="repositores, escaneo de codigos de barras, gestion de stock, inventario, productos, logistica, scanner de codigos, administracion de inventario, manejo de stock">
    <meta name="robots" content="index, follow">
    <link rel="shortcut icon" href="img/logo.ico" type="image/x-icon">
    <script src="https://kit.fontawesome.com/d9b377fc7f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://use.typekit.net/sxf4kmm.css">
    <link href="css/normalize.css" rel="stylesheet" type="text/css">
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <link href="css/contador.css" rel="stylesheet" type="text/css">
    <link href="css/mobile.css" rel="stylesheet" type="text/css">
    <link href="css/footer.css" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaInicio) || !empty($paginaBiblioteca) || !empty($paginaReportes)): ?><link href="css/reports.css?v=20261011-reportphotofallback10" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaReportes)): ?><link href="css/library.css?v=20261011-reference4" rel="stylesheet" type="text/css"><link href="css/product-info.css?v=20261011-summary1" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio) || !empty($paginaBiblioteca)): ?><link href="css/product-info.css?v=20261011-summary1" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/scan-result.css?v=20261011-result3" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaBiblioteca) || !empty($paginaInicio)): ?><link href="css/library.css?v=20261011-reference4" rel="stylesheet" type="text/css"><link href="css/ean-scanner.css?v=20261011-lock1" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaStock)): ?><link href="css/library.css?v=20261011-reference4" rel="stylesheet" type="text/css"><link href="css/ean-scanner.css?v=20261011-lock1" rel="stylesheet" type="text/css"><?php endif; ?>
    <link href="css/responsive.css" rel="stylesheet" type="text/css">
    <link href="css/notifications.css?v=20261011-compactpalets17" rel="stylesheet" type="text/css">
    <link href="css/account-bar.css?v=20261010" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaStock) || !empty($paginaCuenta)): ?><link href="css/stock.css?v=20261011-security1" rel="stylesheet" type="text/css"><?php endif; ?>
    <link href="css/action-notices.css?v=20261010-notices1" rel="stylesheet" type="text/css">
    <link href="css/close-buttons.css?v=20261010-scroll1" rel="stylesheet" type="text/css">
    <link href="css/navigation.css?v=20261011-lock1" rel="stylesheet" type="text/css">
    <?php if (!empty($paginaTops)): ?><link href="css/tops.css?v=20261010" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/home.css?v=20261011-scanmeta3" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/results-viewer.css?v=20261011-shareone3" rel="stylesheet" type="text/css"><?php endif; ?>
  <?php if (!empty($paginaCuenta)): ?><link href="css/user-profiles.css?v=20261011-directory5" rel="stylesheet"><?php endif; ?>
<?php if (!empty($paginaReportes)): ?><link href="css/ean-scanner.css?v=20261011-lock1" rel="stylesheet"><?php endif; ?>
<link href="css/hand-preferences.css?v=20261011-title-alert9" rel="stylesheet">
<link href="css/title-icons.css?v=20261011-icons1" rel="stylesheet">
</head>
  <body data-hand="<?= $userHand === 'left' ? 'left' : 'right' ?>"<?= !empty($paginaInicio) ? ' class="home-page"' : (!empty($paginaBiblioteca) ? ' class="library-page"' : '') ?>>
    <header>
      <div class="header-contenedor">
        <a class="logo" href="./" aria-label="Inicio Repotools"><p>REPOTOOLS</p></a>

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

