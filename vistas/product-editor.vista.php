<dialog id="productEditDialog" class="library-dialog product-edit-dialog" aria-labelledby="productEditTitle">
  <div class="library-heading"><h2 id="productEditTitle">Editar producto</h2><button id="closeProductEditor" class="library-secondary app-close" type="button" aria-label="Cerrar" title="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
  <p id="productEditStatus" class="library-status" role="status"></p>
  <form id="productEditForm" hidden>
    <p id="productEditEan" class="product-edit-ean"></p>
    <label>Marca<input id="catalogBrand" name="brand" maxlength="160" placeholder="Ej.: Red Bull"></label>
    <label>Producto<input id="catalogName" name="name" maxlength="120" placeholder="Ej.: Energizante Lata" required></label>
    <label>Contenido neto<input id="catalogQuantity" name="quantity" maxlength="80" placeholder="Ej.: 250 ml, 500 g o 1 kg"></label>
    <label>Categoría<input id="catalogCategory" name="category" maxlength="240" placeholder="Ej.: Bebidas, limpieza o alimentos"></label>
    <label>Envase<input id="catalogPackaging" name="packaging" maxlength="240" placeholder="Ej.: Lata, botella o bolsa"></label>
    <fieldset id="catalogPhotoControls" class="report-photo"><legend>Foto del producto</legend><img id="catalogPhotoPreview" class="report-photo-preview" alt="Foto del producto" data-crop-upload="1" hidden><input id="catalogPhotoFile" type="file" name="photo_product" accept="image/jpeg,image/png,image/webp" hidden><input id="catalogPhotoChanged" type="text" hidden name="photo_changed" value="0"><div class="report-photo-actions"><button id="catalogChoosePhoto" type="button" class="library-secondary">Agregar foto</button><button id="catalogAdjustPhoto" type="button" class="library-secondary product-crop-adjust" hidden>Ajustar encuadre</button><button id="catalogUndoPhoto" type="button" class="library-secondary" hidden>Deshacer foto</button></div><p id="catalogPhotoNote" class="product-edit-note" role="status"></p></fieldset>
    <p class="product-edit-note">Los cambios serán visibles para todos.</p>
    <div class="library-dialog-actions"><button id="reloadProductEditor" class="library-secondary" type="button" hidden>Revisar versión actual</button><button id="saveProductEditor" class="library-primary" type="submit">Guardar cambios</button></div>
  </form>
</dialog>
