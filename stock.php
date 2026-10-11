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
<section id="stockInvitations" hidden><h3>Invitaciones</h3><div id="invitationList"></div></section>
<section class="stock-palets-block"><h2>Palets</h2><div class="stock-tabs" role="tablist" aria-label="Palets"><button id="ownedTab" type="button" role="tab" aria-controls="ownedPanel" aria-selected="true" data-stock-tab="ownedPanel">Mis palets<span id="ownedExpiryAlert" class="stock-alert-dot" aria-label="Productos por vencer o vencidos" hidden></span></button><button id="pendingTab" type="button" role="tab" aria-controls="pendingPanel" aria-selected="false" tabindex="-1" data-stock-tab="pendingPanel" hidden>Pendientes</button><button id="sharedTab" type="button" role="tab" aria-controls="sharedSection" aria-selected="false" tabindex="-1" data-stock-tab="sharedSection" hidden>Compartidos conmigo<span id="sharedExpiryAlert" class="stock-alert-dot" aria-label="Productos por vencer o vencidos" hidden></span></button><button id="adminTab" type="button" role="tab" aria-controls="adminPanel" aria-selected="false" tabindex="-1" data-stock-tab="adminPanel" hidden>Buscar palet</button></div>
<div id="adminPanel" role="tabpanel" aria-labelledby="adminTab" hidden><p class="stock-muted">Abre un palet por su QR o código.</p><div class="stock-toolbar"><button id="adminScanPallet" class="library-primary" type="button"><?= stock_icon('qr') ?> Escanear</button><button id="adminManualPallet" class="library-secondary stock-icon" type="button" aria-label="Ingresar código de palet"><?= stock_icon('keyboard') ?></button></div><div id="adminPallets" hidden></div></div><div id="ownedPanel" role="tabpanel" aria-labelledby="ownedTab"><div id="ownedPallets" class="stock-grid"></div></div>
<div id="pendingPanel" role="tabpanel" aria-labelledby="pendingTab" hidden><div id="pendingPallets" class="stock-grid"></div><section id="stockLabels" hidden><h3>Etiquetas listas para usar</h3><p class="stock-muted">Reservadas durante 3 días desde su generación.</p><div id="labelList" class="stock-grid"></div></section><p id="pendingEmpty" class="stock-muted">No tienes etiquetas ni invitaciones pendientes.</p></div>
<div id="sharedSection" role="tabpanel" aria-labelledby="sharedTab" hidden><div id="sharedPallets" class="stock-grid"></div></div></section>
<section id="legacyStock" hidden><h2>Palets de este dispositivo</h2><p class="stock-muted">Puedes conservar tus palets anteriores importándolos a la cuenta con un QR nuevo.</p><div id="legacyList"></div></section>
</section></main>
<script>window.repotoolsStock=<?= json_encode(['csrf'=>csrf_token(),'user_id'=>(int)current_user()['id']],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<?php include __DIR__.'/vistas/stock-dialogs.vista.php'; include __DIR__.'/vistas/footer.vista.php'; ?>
