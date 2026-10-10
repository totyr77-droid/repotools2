(() => {
  const get = id => document.getElementById(id);
  const button = get('saveScanLibrary');
  if (!button || !window.repotoolsStats) return;
  const dialog = get('scanLibraryDialog'), select = get('scanLibrarySelect');
  let libraries = [], ean = '', busy = false, generation = 0;
  function current(code, token) {
    return token === generation && get('contenedormax').open && get('resultado').textContent === code && get('cartelScaner').style.display !== 'none';
  }
  async function request(fields) {
    const response = await fetch('api/biblioteca.php', fields ? {
      method: 'POST', credentials: 'same-origin',
      body: new URLSearchParams({ ...fields, csrf: window.repotoolsStats.csrf })
    } : { credentials: 'same-origin', cache: 'no-store' });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'No pudimos guardar el producto. Intenta de nuevo.');
    libraries = data.libraries;
    return libraries;
  }
  function selection() {
    const creating = select.value === 'new';
    get('scanLibraryNew').hidden = !creating;
    get('scanLibraryName').required = creating;
    get('scanLibraryError').textContent = '';
    get('confirmScanLibrary').textContent = creating ? 'Crear y guardar' : 'Guardar';
  }
  function options(selected = 'new') {
    select.replaceChildren();
    for (const library of libraries) {
      const option = document.createElement('option'); option.value = library.id; option.textContent = library.name; select.append(option);
    }
    const option = document.createElement('option'); option.value = 'new'; option.textContent = 'Crear nueva biblioteca'; select.append(option);
    select.value = String(selected); get('scanLibraryChoice').hidden = libraries.length === 0; selection();
  }
  async function save(library, code, token) {
    const exists = library.products.some(product => product.ean === code);
    if (!exists) {
      const product = await window.RepoProductInfo.get(code).catch(() => null);
      if (!current(code, token)) return;
      const name = Array.from(window.RepoProductInfo.referenceName(product)).slice(0, 120).join('');
      await request({ action: 'add_product', library_id: library.id, ean: code, name });
    }
    if (!current(code, token)) return;
    get('scanLibraryStatus').textContent = exists ? `Ya está en «${library.name}».` : `Guardado en «${library.name}».`;
    if (dialog.open) dialog.close();
  }
  function loading(value) {
    busy = value; button.disabled = value; get('confirmScanLibrary').disabled = value;
    select.disabled = value; get('scanLibraryName').disabled = value; get('cancelScanLibrary').disabled = value;
  }
  button.addEventListener('click', async () => {
    if (busy) return;
    const code = get('resultado').textContent;
    if (!window.RepoEANScanner.validEAN(code)) return;
    const token = ++generation; ean = code; loading(true);
    get('scanLibraryStatus').textContent = 'Abriendo Biblioteca…';
    try {
      await request();
      if (!current(code, token)) return;
      get('scanLibraryStatus').textContent = '';
      if (libraries.length === 1) await save(libraries[0], code, token);
      else {
        get('scanLibraryForm').reset(); options(libraries[0]?.id || 'new');
        get('scanLibraryEan').textContent = `EAN ${code}`;
        dialog.showModal();
      }
    } catch (error) { if (current(code, token)) get('scanLibraryStatus').textContent = error.message; }
    finally { loading(false); }
  });
  select.addEventListener('change', selection);
  get('cancelScanLibrary').addEventListener('click', () => dialog.close());
  dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
  get('scanLibraryForm').addEventListener('submit', async event => {
    event.preventDefault();
    if (busy || !current(ean, generation)) return;
    const code = ean, token = generation;
    loading(true); get('scanLibraryError').textContent = '';
    try {
      let library;
      if (select.value === 'new') {
        const name = get('scanLibraryName').value.trim();
        if (!name) throw new Error('Escribe un nombre para la biblioteca.');
        await request({ action: 'create', name });
        library = libraries.find(item => item.name === name);
        options(library.id);
      } else library = libraries.find(item => String(item.id) === select.value);
      if (!library) throw new Error('Elige una biblioteca.');
      await save(library, code, token);
    } catch (error) { get('scanLibraryError').textContent = error.message; }
    finally { loading(false); }
  });
  for (const id of ['rescanear', 'conterdormaxc', 'rescancelar']) get(id).addEventListener('click', () => {
    generation++; get('scanLibraryStatus').textContent = ''; if (dialog.open) dialog.close();
  });
  get('contenedormax').addEventListener('close', () => { generation++; get('scanLibraryStatus').textContent = ''; });
})();
