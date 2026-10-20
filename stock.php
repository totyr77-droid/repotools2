<?php
require_once __DIR__.'/includes/auth.php';
if (!current_user() && is_string($_GET['codigo']??null) && preg_match('/^[0-9]{8}$/D',$_GET['codigo'])) $_SESSION['pending_stock_code']=$_GET['codigo'];
if(!current_user()){
    require_once __DIR__.'/includes/stock-icons.php';
    $demoSection='stock';$paginaDemo=true;$paginaStock=true;
    include __DIR__.'/vistas/header.vista.php';include __DIR__.'/vistas/guest-demo.vista.php';
    $paginaStock=false;include __DIR__.'/vistas/footer.vista.php';exit;
}
require_account();
require_once __DIR__.'/includes/stock-icons.php';
$paginaStock=true; include __DIR__.'/vistas/header.vista.php';
?>
<?php include __DIR__.'/vistas/stock-workspace.vista.php'; ?>
<script>window.repotoolsStock=<?= json_encode(['csrf'=>csrf_token(),'user_id'=>(int)current_user()['id']],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<?php include __DIR__.'/vistas/stock-dialogs.vista.php'; include __DIR__.'/vistas/footer.vista.php'; ?>
