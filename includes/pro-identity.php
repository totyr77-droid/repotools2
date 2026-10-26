<?php
require_once __DIR__.'/pro-access.php';
require_once __DIR__.'/stock-icons.php';
function pro_identity_badge(PDO $db,int $user): void {
    if(pro_has_access($db,$user)){ ?><span class="account-pro" aria-label="Cuenta Pro" title="Cuenta Pro"><?= stock_icon('crown') ?>PRO</span><?php }
}
