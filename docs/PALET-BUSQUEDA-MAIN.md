# Main v3.11 — limpiar búsqueda desde la lupa

Buscador reutilizable de detalle de palet: lupa al estar vacío; pasa a X con cualquier texto, incluido valor recibido por escáner y evento input. La X limpia solo búsqueda, mantiene el orden seleccionado y devuelve foco al campo. Se elimina Cancelar de la fila de resultados; no se duplican controles. Área táctil44px, etiqueta accesible Limpiar búsqueda y foco visible. Cámara conserva su botón y preparación independiente mediante selector explícito.

Validación: pallet-keyboard-layout-ui Chromium/WebKit verifica inicial vacío, EAN programático como escáner, texto escrito, limpieza, ausencia de botón separado,44px,320/390/1366 ambas manos y teclado. pallet-product-space-ui conserva espacio/scroll/acciones en ambos motores. Sintaxis JS/diff. Componentes compartidos aplican también al detalle público que ya usa product-view-tools; no permisos ni datos ni cambios en beta.

Reversión: restaurar js/product-view-tools.js y retirar bloque CSS final del icono de búsqueda; publicar caché superior. Conservar documentación local previa fuera del commit.
