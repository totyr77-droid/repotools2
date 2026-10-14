# Etiquetas A4 horizontal — 13/10/2026

Entrega exclusiva desarrollo. No se cambian IDs, enlaces QR, permisos ni reglas de reserva/vencimiento.

## Problema y solución

El formato anterior usaba SVG vertical2480×3508 y una altura de impresión fija280mm. Pasaba en Chrome pero dependía de cómo el motor imprimía/encajaba la imagen y aplicaba orientación/márgenes. No se pudo reproducir físicamente la fragmentación de Safari reportada por el usuario, por lo que no se atribuye a un bug exacto del navegador.

Nueva composición3508×2480 horizontal: QR grande a la izquierda, logo06_horizontal original filtrado a negro, número de8 dígitos en negrita a la derecha y aviso de reserva/disponibilidad completo. Quiet zone de al menos4 módulos comprobada con los enlaces actuales; blanco/negro, texto secundario aproximadamente10,6pt. QR aproximadamente131mm en el PDF; la lectura a6m depende de cámara, luz, inclinación e impresión, pendiente prueba real.

CSS `@page A4 landscape`, margen8mm; hoja280×193mm dejando1mm de tolerancia. Saltos únicamente antes de etiquetas siguientes, evitar división interna, sin salto final. En impresión se oculta todo fuera de las hojas; imágenes eager y decodificadas antes de habilitar imprimir.

## PDF fiable para iPhone

Nuevo `js/pallet-label-pdf.js`: genera localmente PDF1.4 con MediaBox297×210mm, una Page por etiqueta, QR/textos vectoriales y logo negro incrustado una sola vez. Comparte matriz QR y composición con SVG/PNG. Sin proveedor remoto, nueva librería frontend ni datos enviados. En iPhone/iPad el botón imprimir abre directamente este PDF; botón PDF disponible siempre. Se abre la pestaña durante el tap para evitar bloqueo tras await; si se bloquea, descarga el PDF. Desde el visor del sistema se imprime/comparte.

`js/label-page.js`, `etiqueta.php`, versiones footer y CSS actualizados. Imagen compartida también horizontal y del mismo diseño.

Referencia de orientación CSS: https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/At-rules/%40page/size . La disponibilidad de CSS no acredita una prueba física de AirPrint.

## Validación

- `pallet-label-layout.cjs`: PDF HTML1/2/6 páginas, dimensiones horizontales, logo negro, límites de número/aviso y PNG3508×2480 con QR correcto.
- `pallet-label-pdf.cjs`: PDF directo1/2/6, IDs correctos, estados activos y reservados, quiet zone.
- PDF directo analizado/renderizado por PDF.js independiente, todas las páginas1/2/6; revisión visual de página reservada y activa; decodificados los6 QR renderizados contra cada ID. Herramientas de validación solo en tmp, no dependencias de la app.
- `pending-label-print.cjs`: selección múltiple, permisos por etiqueta403, ruta iPhone simulada a PDF, fallback descarga si popup bloqueado y ruta desktop a impresión HTML; tamaños320/390/1366.
- PHP lint etiqueta/footer y JS syntax checks pasan.

Safari físico/AirPrint/impresora doméstica y distancia de6m pendientes. No se declara prueba física por usar user-agent de iPhone en Chrome.

## Reversión

Revertir el commit de esta entrega (SVG/PNG, CSS, página/visorPDF y versiones). Sin migraciones ni cambios de datos. Mantener BETA exclusivo desarrollo.

## Refinamiento beta solicitado — 13/10/2026

Se reutiliza la composición horizontal/PDF902b5f7. El QR mantiene texto, matriz y nivelM; solo su tamaño y posición se ajustan para reservar4 módulos blancos en los cuatro lados según la cantidad real de módulos. Sigue dentro del área imprimible, también para URL de beta y subcarpetas. Número con Arial/Helvetica Bold280, proporciones naturales (se eliminan textLength/lengthAdjust del SVG y la compresión del PDF); contraste negro/blanco y alineación del texto derecho mejorada. Aviso y fecha siguen completos en la misma hoja. CSS de impresión fuerza ausencia de sombras/filtros exteriores/esquinas y conserva el logo negro interno.

Archivos modificados:
- css/pallet-label.css
- js/pallet-label.js
- js/pallet-label-pdf.js
- etiqueta.php
- vistas/footer.vista.php
- tests/pallet-label-layout.cjs
- tests/pallet-label-pdf.cjs
- docs/ETIQUETAS-HORIZONTAL.md
- CHANGELOG.md
- PENDIENTES.md

Validación: HTML y PDF directo1/2/6 páginas A4 horizontal; PDF.js independiente leyó/renderizó todas las páginas, revisión visual del reservado y6 QR decodificados coincidentes con cada ID. Pruebas adicionales de QR de beta/subcarpeta, ceros iniciales y reserva futura, margen mínimo4 módulos y números sin deformación. pending-label-print comprueba preview/selección320/390/1366, ruta iPhone simulada a PDF, descarga al bloquear popup, impresión desktop y permisos403. PHP/JS syntax pasan.

No se probó Safari/AirPrint en un iPhone físico ni OCR a distancia: requiere prueba real con cámara/impresora. No se registran pruebas simuladas como físicas. El PDF define297×210mm y una página por etiqueta sin depender de @page del navegador. Revertir este refinamiento devuelve los tamaños anteriores, manteniendo el PDF horizontal y sin afectar datos/QR/permisos.
