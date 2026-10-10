<?php
require_once __DIR__.'/includes/auth.php'; require_once __DIR__.'/includes/stock-icons.php';
$paginaStock=true; include __DIR__.'/vistas/header.vista.php';
$code=is_string($_GET['codigo']??null)?$_GET['codigo']:'';
?>
<main class="stock-main"><section class="stock-workspace" id="publicPallet" data-code="<?= escape($code) ?>"><h1>Palet <?= escape(preg_match('/^[0-9]{8}$/D',$code)?$code:'') ?></h1><p id="publicPalletDescription" class="stock-muted"></p><p id="publicPalletStatus" class="library-status" role="status">Cargando palet…</p><div id="publicPalletProducts"></div><div id="publicPalletMetadata" class="stock-metadata" hidden></div><a id="publicPalletStock" class="library-primary" href="stock.php?codigo=<?= escape(rawurlencode($code)) ?>">Abrir desde Stock</a></section></main>
<span id="publicOwnerIcon" hidden><?= stock_icon('user') ?></span><span id="publicClockIcon" hidden><?= stock_icon('clock') ?></span>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
