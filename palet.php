<?php
require_once __DIR__.'/includes/auth.php'; require_once __DIR__.'/includes/stock-icons.php';
$publicShareBase=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/palet.php')),'/').'/';
$code=is_string($_GET['codigo']??null)?$_GET['codigo']:'';
if(preg_match('/^[0-9]{8}$/D',$code)){if(current_user()){header('Location: '.$publicShareBase.'stock.php?codigo='.rawurlencode($code));exit;}$_SESSION['pending_stock_code']=$code;}
$publicPageTitle='Palet · Repotools';
if(is_string($_GET['codigo']??null)&&preg_match('/^[0-9]{8}$/D',$_GET['codigo']))$publicPageTitle='Palet '.$_GET['codigo'].' · Repotools';
$publicPageDescription='Consulta los productos, las cantidades y los vencimientos de este palet en Repotools.';
$paginaStock=true; $paginaStockCompartido=true; include __DIR__.'/vistas/header.vista.php';
$code=is_string($_GET['codigo']??null)?$_GET['codigo']:'';
?>
<main class="stock-main"><section class="stock-workspace" id="publicPallet" data-code="<?= escape($code) ?>"><h1><?= stock_icon('pallet') ?> Palet <?= escape(preg_match('/^[0-9]{8}$/D',$code)?$code:'') ?></h1><p id="publicPalletBranch" class="public-pallet-branch" hidden></p><div id="publicPalletMetadata" class="stock-metadata" hidden></div><p id="publicPalletDescription" class="stock-muted"></p><p id="publicPalletStatus" class="library-status" role="status">Cargando palet…</p><div id="publicPalletProducts" class="public-share-products"></div><section id="publicPalletSummary" class="public-stock-summary" hidden></section><a id="publicPalletStock" class="library-primary" href="<?= escape($publicShareBase) ?>cuenta.php?next=stock"><?= stock_icon('user') ?> Ingresar a RepoTools</a></section></main>
<span id="publicBarcodeIcon" hidden><?= stock_icon('barcode') ?></span><span id="publicBoxIcon" hidden><?= stock_icon('box') ?></span><span id="publicOwnerIcon" hidden><?= stock_icon('user') ?></span><span id="publicClockIcon" hidden><?= stock_icon('clock') ?></span>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
