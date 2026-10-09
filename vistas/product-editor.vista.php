<dialog id="productEditDialog" class="library-dialog product-edit-dialog" aria-labelledby="productEditTitle">
  <div class="library-heading"><h2 id="productEditTitle">Editar producto</h2><button id="closeProductEditor" class="library-secondary" type="button">Cerrar</button></div>
  <p id="productEditStatus" class="library-status" role="status"></p>
  <form id="productEditForm" hidden>
    <p id="productEditEan" class="product-edit-ean"></p>
    <label>Nombre<input id="catalogName" name="name" maxlength="120" required></label>
    <label>Presentación<input id="catalogQuantity" name="quantity" maxlength="80" placeholder="Ej.: 500 ml"></label>
    <label>Marca<input id="catalogBrand" name="brand" maxlength="160"></label>
    <label>Categoría<input id="catalogCategory" name="category" maxlength="240"></label>
    <label>Envase<input id="catalogPackaging" name="packaging" maxlength="240"></label>
    <p class="product-edit-note">Los cambios serán visibles para todos.</p>
    <div class="library-dialog-actions"><button id="reloadProductEditor" class="library-secondary" type="button" hidden>Revisar versión actual</button><button id="saveProductEditor" class="library-primary" type="submit">Guardar cambios</button></div>
  </form>
</dialog>
