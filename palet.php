<?php
require_once __DIR__.'/includes/auth.php'; require_once __DIR__.'/includes/stock-icons.php';
$publicShareBase=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/palet.php')),'/').'/';
$code=is_string($_GET['codigo']??null)?$_GET['codigo']:'';

if(preg_match('/^[0-9]{8}$/D',$code)&&($_GET['ingresar']??'')==='1'){if(!current_user())$_SESSION['pending_stock_code']=$code;header('Location: '.$publicShareBase.(current_user()?'stock.php?codigo='.rawurlencode($code):'cuenta.php?next=stock'));exit;}
$publicPageTitle='Palet · Repotools';
if(is_string($_GET['codigo']??null)&&preg_match('/^[0-9]{8}$/D',$_GET['codigo']))$publicPageTitle='Palet '.$_GET['codigo'].' · Repotools';
$publicPageDescription='Consulta los productos, las cantidades y los vencimientos de este palet en Repotools.';
$paginaStock=true; $paginaStockCompartido=true; include __DIR__.'/vistas/header.vista.php';
$code=is_string($_GET['codigo']??null)?$_GET['codigo']:'';
?>
<main class="stock-main"><section class="stock-workspace" id="publicPallet" data-code="<?= escape($code) ?>" data-signed-in="<?= current_user()?'1':'0' ?>"><div class="public-pallet-heading"><h1><?= stock_icon('pallet') ?> Palet <?= escape(preg_match('/^[0-9]{8}$/D',$code)?$code:'') ?></h1><a id="publicPalletStock" class="library-secondary" href="<?= escape($publicShareBase.(current_user()?'stock.php?codigo=':'palet.php?codigo=').rawurlencode($code).(current_user()?'':'&ingresar=1')) ?>"><?= stock_icon(current_user()?'pallet':'user') ?><span><?= current_user()?'Abrir en Stock':'Ingresar' ?></span></a></div><p id="publicPalletOwner" class="public-share-owner" hidden></p><p id="publicPalletBranch" class="public-pallet-branch" hidden></p><div id="publicPalletMetadata" class="stock-metadata" hidden></div><p id="publicPalletDescription" class="stock-muted"></p><p id="publicPalletStatus" class="library-status" role="status">Cargando palet…</p><div id="publicPalletProducts" class="public-share-products"></div><section id="publicPalletSummary" class="public-stock-summary" hidden></section></section></main>
<span id="publicBranchIcon" hidden><?= stock_icon('branch') ?></span><span id="publicBarcodeIcon" hidden><?= stock_icon('barcode') ?></span><span id="publicBoxIcon" hidden><?= stock_icon('box') ?></span><span id="publicOwnerIcon" hidden><?= stock_icon('user') ?></span><span id="publicClockIcon" hidden><?= stock_icon('clock') ?></span>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
