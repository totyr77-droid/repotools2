(() => {
  'use strict';
  const labels = window.RepoPalletLabel;
  if (!labels) return;
  const encoder = new TextEncoder();
  function crc32(bytes) {
    let crc = 0xffffffff;
    for (const byte of bytes) { crc ^= byte; for (let bit = 0; bit < 8; bit++) crc = crc >>> 1 ^ (crc & 1 ? 0xedb88320 : 0); }
    return (crc ^ 0xffffffff) >>> 0;
  }
  const header = (size, signature) => { const bytes = new Uint8Array(size), view = new DataView(bytes.buffer); view.setUint32(0, signature, true); return { bytes, view }; };
  // Stored ZIP entries preserve already-compressed PNGs without another dependency.
  labels.zip = async files => {
    const parts = [], directory = []; let offset = 0, directorySize = 0;
    for (const file of files) {
      const name = encoder.encode(file.name), data = new Uint8Array(await file.arrayBuffer()), crc = crc32(data);
      const local = header(30, 0x04034b50), central = header(46, 0x02014b50);
      local.view.setUint16(12,33,true);central.view.setUint16(14,33,true);local.view.setUint16(4, 20, true); local.view.setUint16(6, 0x800, true);
      local.view.setUint32(14, crc, true); local.view.setUint32(18, data.length, true); local.view.setUint32(22, data.length, true); local.view.setUint16(26, name.length, true);
      central.view.setUint16(4, 20, true); central.view.setUint16(6, 20, true); central.view.setUint16(8, 0x800, true);
      central.view.setUint32(16, crc, true); central.view.setUint32(20, data.length, true); central.view.setUint32(24, data.length, true); central.view.setUint16(28, name.length, true); central.view.setUint32(42, offset, true);
      parts.push(local.bytes, name, data); directory.push(central.bytes, name);
      offset += 30 + name.length + data.length; directorySize += 46 + name.length;
    }
    const end = header(22, 0x06054b50);
    end.view.setUint16(8, files.length, true); end.view.setUint16(10, files.length, true); end.view.setUint32(12, directorySize, true); end.view.setUint32(16, offset, true);
    return new Blob([...parts, ...directory, end.bytes], { type: 'application/zip' });
  };
  labels.files = async items => {
    const files = [];
    for (const item of items) { const image = await labels.image(item); files.push(new File([image.blob], `Repotools-palet-${item.code}.png`, { type: 'image/png' })); }
    return files;
  };
  labels.shareFiles = async files => {
    if (!files.length) throw Error('No hay etiquetas para compartir.');
    let supported=false;try{supported=!!navigator.canShare?.({files});}catch{}
    if (supported) {
      try { await navigator.share({ files, title: 'Etiquetas de palets · Repotools' }); return 'Etiquetas compartidas.'; }
      catch (error) { if (error.name === 'AbortError') throw error; }
    }
    const blob = files.length === 1 ? files[0] : await labels.zip(files), link = document.createElement('a'), url = URL.createObjectURL(blob);
    link.href = url; link.download = files.length === 1 ? files[0].name : 'Repotools-etiquetas.zip'; link.click(); setTimeout(() => URL.revokeObjectURL(url), 60000);
    return files.length === 1 ? 'Etiqueta descargada.' : 'Etiquetas descargadas en ZIP.';
  };
})();
