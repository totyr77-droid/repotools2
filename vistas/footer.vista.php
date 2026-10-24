<script src="js/team-invitation.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/share-links.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php if(!empty($paginaCuenta)&&empty($paginaReportes)&&empty($paginaTops)): include __DIR__.'/updates.vista.php'; endif; ?>
<footer class="footer site-footer"<?= !empty($mostrarCreditos)?' id="contacto"':'' ?>>
  <small class="site-footer__credits"><span>&copy; 2026 Repotools</span><span aria-hidden="true">&middot;</span><span>v<?= escape(app_release()['label']) ?></span></small>
</footer>

<!-- ALERTA GENERAL -->
<div class="alerta">
  <div class="alerta-contenedor">
    <div class="alertabox">
      <div class="alerta-titulo">
        <i class="fa-solid fa-circle-info"></i><p id="alertaTitulo"></p>
      </div>
      <div class="alerta-info">
        <div id="alertaInfo"></div>
      </div>
    </div>
  </div>
  <button class="alerta-cerrar app-close" type="button" aria-label="Cerrar aviso" title="Cerrar aviso"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
</div>

<?php if (!empty($paginaInicio) || !empty($paginaBiblioteca)): ?>
<?php include __DIR__ . '/product-editor.vista.php'; ?>
<?php include __DIR__ . '/product-report.vista.php'; ?>
<?php endif; ?>
<!-- SCRIPTS -->

<script src="js/navigation.js?v=<?= substr(sha1_file(__DIR__.'/../js/navigation.js'),0,12) ?>"></script>
<?php if (!empty($paginaInicio) || !empty($paginaBiblioteca)): ?>
<link rel="stylesheet" href="css/product-crop.css?v=<?= (int)app_release()['version'] ?>">
<script src="js/product-crop.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php endif; ?>
<script src="js/one-hand-mode.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/mobile-navigation.js?v=<?= substr(sha1_file(__DIR__.'/../js/mobile-navigation.js'),0,12) ?>"></script>
<script src="js/account-sharing.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/pwa-install.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/ux-polish.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php if(!empty($paginaDemo)): ?><script src="js/vendor/JsBarcode.ean-upc-3.11.6.min.js"></script><script src="js/guest-demo.js?v=<?= substr(sha1_file(__DIR__.'/../js/guest-demo.js'),0,12) ?>"></script><?php endif; ?>
<script src="js/loading.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php if(current_user()): ?><script>window.repotoolsScannerMetrics=<?= json_encode(['user'=>(int)current_user()['id'],'csrf'=>csrf_token(),'manager'=>($navRole??'')==='manager','version'=>app_release_label()],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script><?php endif; ?>
<script>window.repotoolsOfflineUser=<?= (int)(current_user()['id']??0) ?>;</script><script src="js/stock-session.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/stock-offline.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/connectivity.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/offline-cache.js?v=<?= substr(sha1_file(__DIR__.'/../js/offline-cache.js'),0,12) ?>"></script><script src="js/offline-sync.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php if (!empty($paginaStock) || !empty($paginaBiblioteca)): ?><link rel="stylesheet" href="css/sync-status.css?v=<?= (int)app_release()['version'] ?>"><script src="js/sync-status.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/library-offline.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<script src="js/date-time.js?v=<?= (int)app_release()['version'] ?>"></script>

<script src="js/connection-position.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/device-connection.js?v=<?= substr(sha1_file(__DIR__.'/../js/device-connection.js'),0,12) ?>"></script>
<script src="js/action-notices.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php $referralNotice=$_SESSION['referral_registration_notice']??null;if($referralNotice&&current_user()&&(int)$referralNotice['user_id']===(int)current_user()['id']){unset($_SESSION['referral_registration_notice']);if((int)$referralNotice['at']>time()-900): ?><script>window.RepoToast?.(<?= json_encode($referralNotice['message'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>,{kind:'info'});</script><?php endif;} ?>
<?php if (!empty($paginaBiblioteca)): ?>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="js/vendor/JsBarcode.ean-upc-3.11.6.min.js"></script>
<script src="js/ean-scanner.js?v=<?= substr(sha1_file(__DIR__.'/../js/ean-scanner.js'),0,12) ?>"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/product-info.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/product-editor.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/product-reports.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/biblioteca.js?v=<?= substr(sha1_file(__DIR__.'/../js/biblioteca.js'),0,12) ?>"></script>
<?php elseif (!empty($paginaReportes)): ?>
<script src="js/vendor/JsBarcode.ean-upc-3.11.6.min.js"></script>
<script src="js/product-reports.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="js/ean-scanner.js?v=<?= substr(sha1_file(__DIR__.'/../js/ean-scanner.js'),0,12) ?>"></script>
<script src="js/branch-maps.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/branch-management.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/audit-records.js?v=<?= substr(sha1_file(__DIR__.'/../js/audit-records.js'),0,12) ?>"></script>
<script src="js/reports-page.js?v=<?= substr(sha1_file(__DIR__.'/../js/reports-page.js'),0,12) ?>"></script>
<?php elseif (!empty($paginaSupervision)): ?>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/ean-scanner.js?v=<?= substr(sha1_file(__DIR__.'/../js/ean-scanner.js'),0,12) ?>"></script>
<?php elseif (!empty($paginaGestionEmpresas)): ?>
<!-- Gestión administrativa: sin lector ni formularios de mercadería. -->
<?php elseif (!empty($paginaEquipo)): ?>
<script src="js/product-info.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/vendor/qrcode.min.js"></script>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/ean-scanner.js?v=<?= substr(sha1_file(__DIR__.'/../js/ean-scanner.js'),0,12) ?>"></script>
<?php elseif (!empty($paginaStock)): ?>
<?php include __DIR__.'/stock-scripts.vista.php'; ?>
<?php elseif (empty($paginaCuenta)): ?>
<script src="js/vendor/html5-qrcode-2.3.8.min.js"></script>
<script src="js/vendor/JsBarcode.ean-upc-3.11.6.min.js"></script>
<script src="js/vendor/lz-string-1.4.4.min.js"></script>
<script src="js/product-info.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/product-editor.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/product-reports.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/ean-scanner.js?v=<?= substr(sha1_file(__DIR__.'/../js/ean-scanner.js'),0,12) ?>"></script>
<script src="js/vendor/quagga-1.12.1.min.js"></script>
<script src="js/script.js?v=<?= substr(sha1_file(__DIR__.'/../js/script.js'),0,12) ?>"></script>
<?php if (current_user()): ?><script src="js/scan-library.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php endif; ?>
<script src="js/scan-photo.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/dialog-scroll.js?v=<?= substr(sha1_file(__DIR__.'/../js/dialog-scroll.js'),0,12) ?>"></script>
<script src="js/title-icons.js?v=<?= (int)app_release()['version'] ?>"></script>

<script src="js/botones.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/report-notifications.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/manager-cache.js?v=<?= substr(sha1_file(__DIR__.'/../js/manager-cache.js'),0,12) ?>"></script>
<script src="js/notifications.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/stock-preferences.js?v=<?= substr(sha1_file(__DIR__.'/../js/stock-preferences.js'),0,12) ?>"></script><script src="js/public-name.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/account-edit.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/account-security.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if (!empty($paginaCuenta) && current_user()): ?><script src="js/vendor/qrcode.min.js"></script><script src="js/user-profiles.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if (!empty($paginaInicio) || (!empty($paginaCuenta) && empty($paginaTops) && current_user())): ?>
<?php include __DIR__ . '/onboarding.vista.php'; ?>
<script src="js/onboarding.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php endif; ?>
<?php if(!empty($paginaInicio)&&current_user()): ?><script src="js/home-location.js?v=<?= substr(sha1_file(__DIR__.'/../js/home-location.js'),0,12) ?>"></script><?php endif; ?>
<?php if (!empty($paginaTops)): ?><script src="js/tops.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if (!empty($paginaStock)): ?><script src="js/pallet-activity.js?v=<?= (int)app_release()['version'] ?>"></script><link rel="stylesheet" href="css/stock-export.css?v=<?= (int)app_release()['version'] ?>"><script src="js/stock-export.js?v=<?= (int)app_release()['version'] ?>"></script><link rel="stylesheet" href="css/stock-overview.css?v=<?= (int)app_release()['version'] ?>"><script src="js/stock-overview.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(current_user()): ?><script src="js/scanner-performance.js?v=<?= substr(sha1_file(__DIR__.'/../js/scanner-performance.js'),0,12) ?>"></script><?php endif; ?>
<?php if(!empty($paginaReportes)&&($navRole??'')==='manager'): ?><link rel="stylesheet" href="css/system-workspace.css?v=<?= substr(sha1_file(__DIR__.'/../css/system-workspace.css'),0,12) ?>"><script src="js/system-workspace.js?v=<?= substr(sha1_file(__DIR__.'/../js/system-workspace.js'),0,12) ?>"></script><?php endif; ?>
<script src="js/motion.js?v=<?= substr(sha1_file(__DIR__.'/../js/motion.js'),0,12) ?>"></script>


<?php if(!empty($stockEmbedded)): ?><script>window.repotoolsStock=<?= json_encode(['csrf'=>csrf_token(),'user_id'=>(int)current_user()['id'],'embedded'=>true,'teamContext'=>!empty($paginaEquipo)],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<?php include __DIR__.'/stock-scripts.vista.php'; ?>
<script src="js/pallet-activity.js?v=<?= (int)app_release()['version'] ?>"></script><link rel="stylesheet" href="css/stock-overview.css?v=<?= (int)app_release()['version'] ?>"><script src="js/stock-overview.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(!empty($paginaEquipo)&&empty($stockEmbedded)): ?><script src="js/pallet-activity.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/stock-utils.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/stock-summary.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(!empty($paginaEquipo)&&empty($stockEmbedded)): ?><script src="js/branch-maps.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(current_user()&&(!empty($paginaInicio)||!empty($paginaEquipo))): ?><link rel="stylesheet" href="css/branch-team.css?v=<?= (int)app_release()['version'] ?>"><script src="js/branch-team.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(!empty($paginaEquipo)): ?><script src="js/inventory-goods.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/team-inventory.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(!empty($paginaGestionEmpresas)): ?><script src="js/branch-maps.js?v=<?= (int)app_release()['version'] ?>"></script><link rel="stylesheet" href="css/branch-team.css?v=<?= (int)app_release()['version'] ?>"><link rel="stylesheet" href="css/branch-manager.css?v=<?= (int)app_release()['version'] ?>"><script src="js/branch-manager.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(current_user()): ?><link rel="stylesheet" href="css/user-qr.css?v=<?= (int)app_release()['version'] ?>"><script src="js/user-qr.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(!empty($paginaCuenta)): ?><script src="js/profile-showcase.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(current_user()): ?><script src="js/achievement-events.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php if(!empty($paginaEquipo)): ?><link rel="stylesheet" href="css/supervision.css?v=<?= (int)app_release()['version'] ?>"><?php endif; ?>
<?php if(!empty($paginaSupervision)||!empty($paginaGestionEmpresas)): ?>
<link rel="stylesheet" href="css/supervision.css?v=<?= (int)app_release()['version'] ?>">
<?php if(!empty($paginaSupervision)): ?><link rel="stylesheet" href="css/branch-team.css?v=<?= (int)app_release()['version'] ?>"><script src="js/stock-utils.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/stock-summary.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php foreach(['branch','chevron','trash','settings']as$supervisionIcon): ?><span hidden data-supervision-icon="<?= $supervisionIcon ?>"><?= stock_icon($supervisionIcon) ?></span><?php endforeach;endif; ?><script src="js/supervision.js?v=<?= (int)app_release()['version'] ?>"></script><?php if(!empty($paginaSupervision)): ?><script src="js/supervision-scanner.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<?php endif; ?>
<?php include __DIR__.'/team-shortcut.vista.php'; ?>
<?php if(current_user()&&empty($paginaCuenta)&&!support_active()): include __DIR__.'/push-notice.vista.php'; endif; ?>
<?php if(!support_active()): ?><script src="js/push-settings.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<script src="js/app-updates.js?v=<?= (int)app_release()['version'] ?>"></script></body>
</html>
