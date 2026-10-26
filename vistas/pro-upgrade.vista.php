<dialog id="proUpgradeDialog" class="library-dialog pro-upgrade-dialog" aria-labelledby="proUpgradeTitle">
  <div class="pro-upgrade-hero"><div class="library-heading"><span class="pro-upgrade-brand"><?= stock_icon('crown') ?>RepoTools <strong>PRO</strong></span><button class="app-close" type="button" data-pro-close aria-label="Cerrar"><?= stock_icon('close') ?></button></div><h2 id="proUpgradeTitle">Tu stock, en equipo.</h2><p>Compartí más. Coordiná mejor.</p></div>
  <div class="pro-upgrade-content">
  <p id="proUpgradeReason">Compartí y coordiná tu stock con otras personas.</p>
  <table class="pro-comparison"><caption class="sr-only">Comparación de Free y Pro</caption><thead><tr><th>Función</th><th>Free</th><th>Pro</th></tr></thead><tbody>
    <tr><th>Gestionar tu stock</th><td>Incluido</td><td class="pro-included"><?= stock_icon('check') ?><span class="pro-included-label">Incluido</span></td></tr>
    <tr><th>Compartir palets y stock</th><td>—</td><td class="pro-included"><?= stock_icon('check') ?><span class="pro-included-label">Incluido</span></td></tr>
    <tr><th>Transferir a palet o usuario</th><td>—</td><td class="pro-included"><?= stock_icon('check') ?><span class="pro-included-label">Incluido</span></td></tr>
    <tr><th>Invitar a otras cuentas</th><td>—</td><td class="pro-included"><?= stock_icon('check') ?><span class="pro-included-label">Incluido</span></td></tr>
  </tbody></table>
  <p class="stock-muted">Tus invitados pueden participar con Free, según el acceso que les des.</p>
  <p id="proUpgradeAvailability" class="stock-muted">La contratación está en preparación. Tu stock sigue disponible.</p>
  <div class="library-dialog-actions"><button id="proUpgradeReview" class="library-secondary" type="button" hidden>Revisar accesos actuales</button><button id="proUpgradeJoin" class="library-primary" type="button" disabled>Unirme a Pro</button></div>
  </div>
</dialog>
