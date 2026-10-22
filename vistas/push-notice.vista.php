<link rel="stylesheet" href="css/push-notice.css?v=<?= (int)app_release()['version'] ?>">
<dialog id="pushPreferences" class="push-notice" data-prompt="true" data-csrf="<?= escape(csrf_token()) ?>" aria-labelledby="pushNoticeTitle" aria-describedby="pushNoticeText">
 <h2 id="pushNoticeTitle"><?= stock_icon('bell') ?>Te avisamos por tu mercadería</h2>
 <p id="pushNoticeText">Dejá que te avisemos cuando tu mercadería esté por vencer, incluso con la app cerrada.</p>
 <p class="push-notice-hint">También recibís invitaciones y solicitudes.</p>
 <p id="pushSettingsStatus" role="status" aria-live="polite"></p>
 <div class="push-controls"><button type="button" id="pushNoticeLater" class="library-secondary">Ahora no</button><button type="button" id="enablePush" class="library-primary" disabled><?= stock_icon('bell') ?><span>Activar avisos</span></button><button type="button" id="disablePush" hidden>Desactivar</button><button type="button" id="testPush" hidden>Probar aviso</button></div>
</dialog>
