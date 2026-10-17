<?php
require_once __DIR__.'/includes/auth.php'; require_once __DIR__.'/includes/stock-icons.php';
require_once __DIR__.'/includes/share-links.php';
$publicShareBase=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/stock-compartido.php')),'/').'/';
$publicPageTitle='Stock compartido · Repotools';$publicPageDescription='Consulta los productos, las cantidades y los vencimientos del stock compartido. Acceso de solo lectura.';
$paginaStock=true; $paginaStockCompartido=true; include __DIR__.'/vistas/header.vista.php';
$token=is_string($_GET['token']??null)?$_GET['token']:'';
?>
<main class="stock-main"><section id="publicStockShare" class="stock-workspace" data-token="<?= escape(stock_share_token_valid($token)?$token:'') ?>"><div class="public-share-heading"><h1><?= stock_icon('branch') ?> <span id="publicShareTitle">Stock compartido</span></h1><button id="refreshPublicShare" class="library-secondary stock-icon" type="button" aria-label="Actualizar informe" title="Actualizar informe"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6 6a8 8 0 0 1 14 6M18 18a8 8 0 0 1-14-6"/></svg></button></div><p id="publicShareOwner" class="public-share-owner" hidden></p><p id="publicShareAddress" class="stock-muted"></p><p id="publicShareDescription" class="stock-muted"></p><p id="publicShareStatus" class="library-status" role="status">Cargando contenido…</p><?php include __DIR__.'/vistas/public-stock-filters.vista.php'; ?><div id="publicSharePallets" class="branch-public-list"></div><button id="publicShareMore" class="library-secondary" type="button" hidden><?= stock_icon('chevron') ?> Ver más palets</button><section id="publicShareSummary" class="public-stock-summary" hidden></section></section></main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
