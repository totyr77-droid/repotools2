# Impresión de etiquetas pendientes

Stock → Pendientes → Seleccionar aparece cuando existen dos o más etiquetas reservadas para la cuenta. En modo selección, tocar un bloque marca/desmarca ese código en lugar de abrir el palet; aria-pressed expresa la selección y funciona también con teclado. Todos/Ninguno permite seleccionar hasta100, límite existente de etiqueta.php. Cancelar limpia la selección y recupera el comportamiento normal.

Imprimir (cantidad) abre etiqueta.php?codigos=… en otra pestaña y conserva Stock. Una hoja A4 por etiqueta, QR, logo y código legible existentes; el usuario confirma la impresión con el botón de impresora de esa página. Sin selección, el enlace no tiene href y no es interactivo. Si queda una sola etiqueta, desaparece el control múltiple y conserva su reimpresión individual. Recargar la lista descarta códigos que dejaron de estar pendientes.

No hay nuevas APIs, migraciones, reservas ni cambios de QR. etiqueta.php valida autorización y caducidad para cada código en el servidor; editar el enlace no concede acceso. Un código que caducó/cambió de dueño puede requerir volver a Stock y actualizar la selección.

Validación: node tests/pending-label-print.cjs pasa con SQLite temporal y Chrome simulado. Verifica selección sin abrir palet, cancelar, todos/ninguno, dos etiquetas A4, decode y llamada a print, rechazo403 de etiqueta ajena, desaparición del control con una etiqueta y ausencia de desbordamiento320/390/1366. Captura320px zurdo revisada. Sintaxis JS y lint PHP pasan. Impresora física, diálogo Safari y despliegue Render pendientes.

Reversión: restaurar stock.php, js/stock.js, css/stock.css y versiones header/footer a15afbc9; retirar la prueba nueva y esta nota. No eliminar datos ni cambiar permisos existentes.
