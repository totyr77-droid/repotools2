(() => {
  'use strict';
  const page = window.RepoNavigation?.page;
  const document = page?.document || globalThis.document;

  const get = id => document.getElementById(id);
  const dialog = get('productEditDialog');
  if (!dialog) return;
  const fields = { name: 'catalogName', quantity: 'catalogQuantity', uxb: 'catalogUxb', brand: 'catalogBrand', category: 'catalogCategory', packaging: 'catalogPackaging' };
  let ean = '', revision = 0, operation = 0, saving = false;
  let cropSession=null,photo=null,previewUrl=null,currentImage='',photoVersion=0;
  function resetPhoto(){++photoVersion;cropSession?.dispose();cropSession=null;photo=null;if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;get('catalogPhotoFile').value='';get('catalogPhotoChanged').value='0';get('catalogPhotoPreview').hidden=!currentImage;if(currentImage)get('catalogPhotoPreview').src=currentImage;else get('catalogPhotoPreview').removeAttribute('src');get('catalogAdjustPhoto').hidden=!currentImage;get('catalogUndoPhoto').hidden=true;get('catalogChoosePhoto').textContent=currentImage?'Cambiar foto':'Agregar foto';}
  function close() { operation++; dialog.close(); }
  dialog.addEventListener('close',()=>{operation++;currentImage='';resetPhoto();});
  async function setPhoto(blob,automatic=true){const version=++photoVersion,token=operation;const bitmap=await createImageBitmap(blob);const pixels=bitmap.width*bitmap.height;bitmap.close();if(pixels>16000000)throw Error();if(version!==photoVersion||token!==operation)return;cropSession?.dispose();const session=window.RepoProductCrop.create(blob,result=>{if(version!==photoVersion||token!==operation)return;photo=result;if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=URL.createObjectURL(result);get('catalogPhotoPreview').src=previewUrl;get('catalogPhotoPreview').hidden=false;get('catalogPhotoChanged').value=String(Number(get('catalogPhotoChanged').value)+1);get('catalogAdjustPhoto').hidden=false;get('catalogUndoPhoto').hidden=false;},{automatic});cropSession=session;await session.ready;}
  get('catalogChoosePhoto').addEventListener('click',()=>get('catalogPhotoFile').click());
  get('catalogPhotoFile').addEventListener('change',async()=>{const file=get('catalogPhotoFile').files[0];if(!file)return;if(file.size>6*1024*1024||!['image/jpeg','image/png','image/webp'].includes(file.type)){get('catalogPhotoNote').textContent='Usá JPG, PNG o WebP de hasta 6 MB.';get('catalogPhotoFile').value='';return;}get('catalogPhotoNote').textContent='Preparando foto…';get('saveProductEditor').disabled=true;try{await setPhoto(file);get('catalogPhotoNote').textContent='Revisá el encuadre antes de guardar.';}catch{get('catalogPhotoNote').textContent='No pudimos leer la foto. Probá con otra.';}finally{get('saveProductEditor').disabled=saving;}});
  get('catalogAdjustPhoto').addEventListener('click',async()=>{const token=operation;get('saveProductEditor').disabled=true;try{if(!cropSession&&currentImage){const response=await (page?.fetch||globalThis.fetch.bind(globalThis))(currentImage,{credentials:'same-origin',cache:'no-store'});if(!response.ok)throw Error();const blob=await response.blob();if(token!==operation||!dialog.open)return;await setPhoto(blob,false);}await cropSession?.edit();}catch{get('catalogPhotoNote').textContent='No pudimos abrir la foto. Podés reemplazarla.';}finally{get('saveProductEditor').disabled=saving;}});
  get('catalogUndoPhoto').addEventListener('click',()=>{resetPhoto();get('catalogPhotoNote').textContent='';});
  page?.onLeave(()=>{currentImage='';resetPhoto();});
  async function open(code) {
    if (saving) return;
    ean = code;
    currentImage='';resetPhoto();
    const token = ++operation;
    get('productEditForm').hidden = true;
    get('reloadProductEditor').hidden = true;
    get('productEditStatus').textContent = 'Cargando producto…';
    if (!dialog.open) dialog.showModal();
    try {
      const product = await window.RepoProductInfo.get(code, { fresh: true, image:true });
      if (token !== operation || !dialog.open) return;
      if (!window.RepoProductInfo.permission(code)?.canEdit) {
        get('productEditStatus').textContent = 'Solo administradores y moderadores pueden editar productos.';
        return;
      }
      revision = product?.revision || 0;
      currentImage=product?.image||'';resetPhoto();get('catalogPhotoControls').disabled=!window.RepoProductInfo.permission(code)?.photosEnabled;get('catalogPhotoNote').textContent=get('catalogPhotoControls').disabled?'Las fotos no están disponibles en este momento.':'';
      get('productEditEan').textContent = code;
      for (const [field, id] of Object.entries(fields)) get(id).value = product?.[field] || '';
      get('productEditStatus').textContent = product ? '' : 'Completa la información del producto.';
      get('productEditForm').hidden = false;
      get('saveProductEditor').disabled = false;
      dialog.dispatchEvent(new CustomEvent('repotools:form-saved',{bubbles:true}));get('catalogName').focus();
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
    saving = true; get('saveProductEditor').disabled = true;get('catalogPhotoControls').disabled=true;get('closeProductEditor').disabled=true;
    get('productEditStatus').textContent = 'Guardando…';
    try {
      await cropSession?.wait();await window.RepoProductInfo.save(ean, values, revision, photo);
      dialog.dispatchEvent(new CustomEvent('repotools:form-saved',{bubbles:true}));window.RepoToast?.('Producto actualizado.');
      if (token === operation) close();
    } catch (error) {
      if (token === operation) {
        get('productEditStatus').textContent = error.message;
        get('reloadProductEditor').hidden = error.status !== 409;
      }
    } finally { saving = false; get('saveProductEditor').disabled = false;get('catalogPhotoControls').disabled=!window.RepoProductInfo.permission(ean)?.photosEnabled;get('closeProductEditor').disabled=false; }
  });
  window.RepoProductEditor = { open };
})();
