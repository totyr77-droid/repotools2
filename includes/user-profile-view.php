<?php if ($moderationView && $auditView): ?>
<section id="userWorkspace" class="user-workspace" hidden>
  <form id="userSearch" class="user-search"><label class="user-search-field"><span>Correo de la cuenta</span><input name="email" type="email" autocomplete="off" placeholder="nombre@correo.com" required maxlength="254"></label><button class="library-primary" type="submit" aria-label="Buscar usuario"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button><button class="library-secondary" id="userScan" type="button" aria-label="Escanear QR de perfil" title="Escanear QR"><i class="fa-solid fa-qrcode" aria-hidden="true"></i></button></form>
  <p id="userStatus" class="library-status" role="status"></p><div id="userProfile"></div>
</section>
<dialog id="userScannerDialog" class="library-dialog library-scanner-dialog"><div class="library-heading"><h2>QR del perfil</h2><button id="userScannerClose" class="library-secondary app-close" type="button" aria-label="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div><div id="userReader" class="ean-reader"></div><p id="userScannerStatus" role="status"></p></dialog>
<?php endif; ?>
