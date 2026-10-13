# Impresión de etiquetas pendientes

## Número al ancho disponible — desarrollo

Nueva petición13/10: número del palet ocupa2040px del lienzo2480px mediante textLength y lengthAdjust, manteniendo220px a cada lado; fuente360px y línea base3150, título2800. Aprovecha todo el ancho útil sin salir de los márgenes ni superponerse al pie3240. SVG y PNG comparten ajuste; QR y paginación intactos. La prueba pallet-label-layout comprueba caja geométrica del número, márgenes y separación vertical además de PDF1/2/5 páginas y lectura QR. Reversión mediante git revert del commit de esta ampliación; solo rama desarrollo hasta autorización de fusionar.

## Corrección de A4 — 13/10/2026

La hoja anterior fijaba210×297mm completos sin margen de impresión. Ahora @page conserva A4 con8mm de margen; cada etiqueta usa280mm de alto,1mm por debajo del área imprimible, sin desbordamiento y con salto únicamente antes de la siguiente. Controles no se imprimen. Esto evita que el ajuste y redondeo de papel arrastren contenido a una página adicional.

js/pallet-label.js integra el logo original06_horizontal_blanco_transparente de2048px como PNG embebido y filtro SVG negro que conserva su transparencia sobre fondo blanco; no depende de una descarga externa dentro de la imagen. Logo2040px, QR vectorial1920px con espacio blanco de lectura, código200px y texto48–64px dentro del lienzo A4 de2480×3508. Exportación PNG reutiliza exactamente el SVG; no hay dos diseños distintos. La URL y el código del QR se conservan. La fecha reservada usa horario argentino sin segundos.

Archivos: css/pallet-label.css, js/pallet-label.js, versiones en etiqueta.php y vistas/footer.vista.php, tests/pallet-label-layout.cjs y continuidad. No modifica reservas, permisos, productos ni datos.

Pruebas: node tests/pallet-label-layout.cjs genera PDF reales de Chrome con1/2/5 etiquetas y confirma exactamente1/2/5 páginas A4, sin página residual; logo original monocromo sobre blanco, PNG2480×3508 y QR decodificado con el lector local al enlace correcto. Vista PNG revisada. node tests/pending-label-print.cjs conserva selección, impresión, autorización403 y móvil/PC. Lint PHP y sintaxis JS pasan. Impresora física y Safari pendientes; imprimir en A4, escala100% y sin encabezados/pies añadidos por el navegador.

Revertir solo esta corrección: git revert del commit de esta entrega, o restaurar los cuatro archivos de aplicación a0430763 y retirar su prueba/documentación. Sin migraciones ni cambios de datos.

Stock → Pendientes → Seleccionar aparece cuando existen dos o más etiquetas reservadas para la cuenta. En modo selección, tocar un bloque marca/desmarca ese código en lugar de abrir el palet; aria-pressed expresa la selección y funciona también con teclado. Todos/Ninguno permite seleccionar hasta100, límite existente de etiqueta.php. Cancelar limpia la selección y recupera el comportamiento normal.

Imprimir (cantidad) abre etiqueta.php?codigos=… en otra pestaña y conserva Stock. Una hoja A4 por etiqueta, QR, logo y código legible existentes; el usuario confirma la impresión con el botón de impresora de esa página. Sin selección, el enlace no tiene href y no es interactivo. Si queda una sola etiqueta, desaparece el control múltiple y conserva su reimpresión individual. Recargar la lista descarta códigos que dejaron de estar pendientes.

No hay nuevas APIs, migraciones, reservas ni cambios de QR. etiqueta.php valida autorización y caducidad para cada código en el servidor; editar el enlace no concede acceso. Un código que caducó/cambió de dueño puede requerir volver a Stock y actualizar la selección.

Validación: node tests/pending-label-print.cjs pasa con SQLite temporal y Chrome simulado. Verifica selección sin abrir palet, cancelar, todos/ninguno, dos etiquetas A4, decode y llamada a print, rechazo403 de etiqueta ajena, desaparición del control con una etiqueta y ausencia de desbordamiento320/390/1366. Captura320px zurdo revisada. Sintaxis JS y lint PHP pasan. Impresora física, diálogo Safari y despliegue Render pendientes.

Reversión: restaurar stock.php, js/stock.js, css/stock.css y versiones header/footer a15afbc9; retirar la prueba nueva y esta nota. No eliminar datos ni cambiar permisos existentes.

## Actualización13/10/2026

La entrega horizontal reemplaza el formato vertical anterior descrito en esta nota. Ver docs/ETIQUETAS-HORIZONTAL.md para composición, PDF iPhone, pruebas1/2/6 y reversión. Safari/impresora físicos pendientes.
