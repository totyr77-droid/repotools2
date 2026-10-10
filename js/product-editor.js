(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;

  const get = id => document.getElementById(id);
  const dialog = get('productEditDialog');
  if (!dialog) return;
  const fields = { name: 'catalogName', quantity: 'catalogQuantity', brand: 'catalogBrand', category: 'catalogCategory', packaging: 'catalogPackaging' };
  let ean = '', revision = 0, operation = 0, saving = false;
  function close() { operation++; dialog.close(); }
  async function open(code) {
    if (saving) return;
    ean = code;
    const token = ++operation;
    get('productEditForm').hidden = true;
    get('reloadProductEditor').hidden = true;
    get('productEditStatus').textContent = 'Cargando producto…';
    if (!dialog.open) dialog.showModal();
    try {
      const product = await window.RepoProductInfo.get(code, { fresh: true });
      if (token !== operation || !dialog.open) return;
      if (!window.RepoProductInfo.permission(code)?.canEdit) {
        get('productEditStatus').textContent = 'Solo administradores y moderadores pueden editar productos.';
        return;
      }
      revision = product?.revision || 0;
      get('productEditEan').textContent = code;
      for (const [field, id] of Object.entries(fields)) get(id).value = product?.[field] || '';
      get('productEditStatus').textContent = product ? '' : 'Completa la información del producto.';
      get('productEditForm').hidden = false;
      get('saveProductEditor').disabled = false;
      get('catalogName').focus();
    } catch (error) { if (token === operation) get('productEditStatus').textContent = error.message; }
  }
  get('closeProductEditor').addEventListener('click', close);
  dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
  get('reloadProductEditor').addEventListener('click', () => open(ean));
  get('productEditForm').addEventListener('submit', async event => {
    event.preventDefault();
    if (saving) return;
    const token = operation;
    const values = Object.fromEntries(Object.entries(fields).map(([field, id]) => [field, get(id).value.trim()]));
    saving = true; get('saveProductEditor').disabled = true;
    get('productEditStatus').textContent = 'Guardando…';
    try {
      await window.RepoProductInfo.save(ean, values, revision);
      if (token === operation) close();
    } catch (error) {
      if (token === operation) {
        get('productEditStatus').textContent = error.message;
        get('reloadProductEditor').hidden = error.status !== 409;
      }
    } finally { saving = false; get('saveProductEditor').disabled = false; }
  });
  window.RepoProductEditor = { open };
})();
