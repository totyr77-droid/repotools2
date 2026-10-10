<?php
require_once __DIR__.'/includes/auth.php';
if (!current_user() && is_string($_GET['codigo']??null) && preg_match('/^[0-9]{8}$/D',$_GET['codigo'])) $_SESSION['pending_stock_code']=$_GET['codigo'];
require_account();
require_once __DIR__.'/includes/stock-icons.php';
$paginaStock=true; include __DIR__.'/vistas/header.vista.php';
?>
<main class="stock-main"><section id="stockApp" class="stock-workspace">
<div class="library-heading"><div><h1>Stock</h1><p>Organiza y comparte tus palets.</p></div><button id="generateLabels" class="library-primary stock-icon" type="button" aria-label="Generar etiquetas QR" title="Generar etiquetas QR"><?= stock_icon('plus') ?></button></div>
<div class="stock-toolbar"><button id="scanPallet" class="library-primary" type="button"><?= stock_icon('qr') ?> Escanear palet</button><button id="manualPallet" class="library-secondary stock-icon" type="button" aria-label="Ingresar código" title="Ingresar código"><?= stock_icon('keyboard') ?></button></div>
<p id="stockStatus" class="library-status" role="status"></p>
<section id="stockInvitations" hidden><h2>Invitaciones</h2><div id="invitationList"></div></section>
<section id="stockLabels" hidden><h2>Etiquetas pendientes</h2><p class="stock-muted">Reservadas durante 3 días desde su generación.</p><div id="labelList" class="stock-grid"></div></section>
<section><h2>Mis palets</h2><div id="ownedPallets" class="stock-grid"></div></section>
<section id="sharedSection" hidden><h2>Compartidos conmigo</h2><div id="sharedPallets" class="stock-grid"></div></section>
<section id="legacyStock" hidden><h2>Palets de este dispositivo</h2><p class="stock-muted">Puedes conservar tus palets anteriores importándolos a la cuenta con un QR nuevo.</p><div id="legacyList"></div></section>
</section></main>
<script>window.repotoolsStock=<?= json_encode(['csrf'=>csrf_token(),'user_id'=>(int)current_user()['id']],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<?php include __DIR__.'/vistas/stock-dialogs.vista.php'; include __DIR__.'/vistas/footer.vista.php'; ?>
