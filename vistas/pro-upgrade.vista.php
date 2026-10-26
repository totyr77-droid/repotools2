<dialog id="proUpgradeDialog" class="library-dialog pro-upgrade-dialog" aria-labelledby="proUpgradeTitle">
  <div class="library-heading"><h2 id="proUpgradeTitle"><?= stock_icon('user') ?>Sumá Pro a tu cuenta</h2><button class="app-close" type="button" data-pro-close aria-label="Cerrar"><?= stock_icon('close') ?></button></div>
  <p id="proUpgradeReason">Compartí y coordiná tu stock con otras personas.</p>
  <table class="pro-comparison"><caption class="sr-only">Comparación de Free y Pro</caption><thead><tr><th>Función</th><th>Free</th><th>Pro</th></tr></thead><tbody>
    <tr><th>Gestionar tu stock</th><td>Incluido</td><td>Incluido</td></tr>
    <tr><th>Compartir palets y stock</th><td>—</td><td>Incluido</td></tr>
    <tr><th>Transferir propiedad</th><td>—</td><td>Incluido</td></tr>
    <tr><th>Invitar a otras cuentas</th><td>—</td><td>Incluido</td></tr>
  </tbody></table>
  <p class="stock-muted">Tus invitados pueden participar con Free, según el acceso que les des.</p>
  <p id="proUpgradeAvailability" class="stock-muted">La contratación está en preparación. Tu stock sigue disponible.</p>
  <div class="library-dialog-actions"><button id="proUpgradeReview" class="library-secondary" type="button" hidden>Revisar accesos actuales</button><button id="proUpgradeJoin" class="library-primary" type="button" disabled>Unirme a Pro</button></div>
</dialog>
