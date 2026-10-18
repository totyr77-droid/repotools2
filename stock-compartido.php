<?php
require_once __DIR__.'/includes/auth.php'; require_once __DIR__.'/includes/stock-icons.php';
require_once __DIR__.'/includes/share-links.php';
$publicShareBase=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/stock-compartido.php')),'/').'/';
$token=is_string($_GET['token']??null)?$_GET['token']:'';
if(stock_share_token_valid($token)&&($_GET['ingresar']??'')==='filtros'){if(!current_user())$_SESSION['pending_stock_report_token']=$token;header('Location: '.$publicShareBase.(current_user()?'stock-compartido.php?token='.rawurlencode($token):'cuenta.php?next=stock-compartido'));exit;}
$publicPageTitle='Stock compartido · Repotools';$publicPageDescription='Consulta los productos, las cantidades y los vencimientos del stock compartido. Acceso de solo lectura.';
$paginaStock=true; $paginaStockCompartido=true; include __DIR__.'/vistas/header.vista.php';

?>
<main class="stock-main"><section id="publicStockShare" class="stock-workspace" data-token="<?= escape(stock_share_token_valid($token)?$token:'') ?>"><div class="public-share-heading"><div class="public-share-title-row"><h1><?= stock_icon('branch') ?> <span id="publicShareTitle">Stock compartido</span></h1><p id="publicShareStatus" class="public-report-status" role="status">Cargando…</p></div><button id="refreshPublicShare" class="library-secondary stock-icon" type="button" aria-label="Actualizar informe" title="Actualizar informe"><?= stock_icon('refresh') ?></button></div><div class="public-report-meta"><p id="publicShareOwner" class="public-share-owner" hidden></p><p id="publicShareAddress" class="stock-muted" hidden></p></div><p id="publicShareDescription" class="stock-muted"></p><?php if(!current_user()&&stock_share_token_valid($token)): ?><p id="publicShareFilterHint" class="public-pallet-filter-hint" hidden><a href="<?= escape($publicShareBase.'stock-compartido.php?token='.rawurlencode($token).'&ingresar=filtros') ?>">Ingresá para usar los filtros</a></p><?php endif; ?><?php include __DIR__.'/vistas/public-stock-filters.vista.php'; ?><div id="publicSharePallets" class="branch-public-list"></div><button id="publicShareMore" class="library-secondary" type="button" hidden><?= stock_icon('chevron') ?> Ver más palets</button><section id="publicShareSummary" class="public-stock-summary" hidden></section></section></main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
