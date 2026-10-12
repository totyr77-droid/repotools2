<?php
require_once __DIR__.'/includes/auth.php'; require_once __DIR__.'/includes/stock-icons.php';
$paginaStock=true; $paginaStockCompartido=true; include __DIR__.'/vistas/header.vista.php';
$token=is_string($_GET['token']??null)?$_GET['token']:'';
?>
<main class="stock-main"><section id="publicStockShare" class="stock-workspace" data-token="<?= escape(preg_match('/^[a-f0-9]{64}$/D',$token)?$token:'') ?>"><h1><?= stock_icon('branch') ?> <span id="publicShareTitle">Stock compartido</span></h1><p id="publicShareAddress" class="stock-muted"></p><p id="publicShareDescription" class="stock-muted"></p><p id="publicShareStatus" class="library-status" role="status">Cargando contenido…</p><div id="publicSharePallets" class="branch-public-list"></div><button id="publicShareMore" class="library-secondary" type="button" hidden><?= stock_icon('chevron') ?> Ver más palets</button></section></main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
