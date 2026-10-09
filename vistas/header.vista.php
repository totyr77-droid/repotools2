<?php require_once __DIR__ . "/../includes/auth.php"; ?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
    <?php if (!empty($paginaInicio) || !empty($paginaBiblioteca)): ?><link href="css/product-info.css" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaInicio)): ?><link href="css/scan-result.css" rel="stylesheet" type="text/css"><?php endif; ?>
    <?php if (!empty($paginaBiblioteca) || !empty($paginaInicio)): ?><link href="css/library.css" rel="stylesheet" type="text/css"><link href="css/ean-scanner.css" rel="stylesheet" type="text/css"><?php endif; ?>
  </head>
  <body<?= !empty($paginaInicio) ? ' class="home-page"' : '' ?>>
    <header>
      <div class="header-contenedor">
        <a class="logo" href="./" aria-label="Inicio Repotools"><p>REPOTOOLS</p></a>

        <nav class="nav" aria-label="Navegacion principal">
          <a href="./"><i class="fa-solid fa-house"></i>INICIO</a>
          <a href="stock.php"><i class="fa-solid fa-qrcode"></i>STOCK</a>
          <a href="biblioteca.php"><i class="fa-solid fa-book"></i>BIBLIOTECA</a>
          <a href="cuenta.php"><i class="fa-solid fa-user"></i><?= current_user() ? 'MI CUENTA' : 'ENTRAR' ?></a>
        </nav>

        <nav class="nav-celular" id="nav-celular" aria-label="Navegación móvil">
          <a href="./"><i class="fa-solid fa-house"></i>INICIO</a>
          <a href="stock.php"><i class="fa-solid fa-qrcode"></i>STOCK</a>
          <a href="biblioteca.php"><i class="fa-solid fa-book"></i>BIBLIOTECA</a>
          <a href="cuenta.php"><i class="fa-solid fa-user"></i><?= current_user() ? 'MI CUENTA' : 'ENTRAR' ?></a>
        </nav>

        <button id="btn.nav-celular" class="botonMenu" type="button" aria-label="Abrir menu">
          <i id="btn-nav-celularA" class="fa-solid fa-angle-left"></i>
        </button>
      </div>
    </header>
    <?php if (current_user()): ?>
    <div class="account-bar"><span>Hola, <?= escape(current_user()['name']) ?></span><form action="salir.php" method="post"><input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>"><button type="submit">Cerrar sesión</button></form></div>
    <?php endif; ?>
