<details class="account-public-name">
  <summary><?= account_icon('user') ?>Nombre público<?= stock_icon('chevron') ?></summary>
  <p>Tu nombre en TOPs, palets y enlaces compartidos.</p>
  <form id="publicNameForm">
    <input type="hidden" name="csrf" value="<?= escape(csrf_token()) ?>">
    <input type="hidden" name="action" value="public_name">
    <label for="publicNameInput">Nombre público</label>
    <div class="public-name-controls"><input id="publicNameInput" name="public_name" value="<?= escape(get_public_user_name(current_user())) ?>" minlength="3" maxlength="24" placeholder="Ej.: Gastón R." autocomplete="nickname" required><button type="submit"><?= stock_icon('check') ?>Guardar</button></div>
    <p class="public-name-preview">Así te verán otros usuarios: <strong id="publicNamePreview"><?= escape(get_public_user_name(current_user())) ?></strong></p>
    <p id="publicNameStatus" role="status" hidden></p>
  </form>
</details>
