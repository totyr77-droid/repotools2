# Etiquetas A4 vertical — 13/10/2026

Entrega exclusiva desarrollo. Reemplaza la composición horizontal por pedido del usuario, conservando una hoja por palet, identificadores, URL del QR y avisos de disponibilidad/reserva.

Composición compartida SVG/PNG/PDF de2480×3508: logo06 original en negro, arriba y casi a todo el ancho (2320px); QR central con zona blanca mínima4 módulos; número de8 dígitos natural en Arial/HelveticaBold de500px, ancho2224px. Aviso legible al pie dentro de la hoja. QR con módulos de píxeles enteros para nitidez PNG, sin deformar número ni estirar logo.

CSS @page A4 portrait/margen8mm; hoja193×280mm, un milímetro de tolerancia. Solo se imprime la etiqueta, salto antes de cada siguiente hoja y sin salto final. PDF nativo210×297mm, una Page por etiqueta; mantiene apertura directa PDF en iPhone y descarga si el visor no se puede abrir. Compartir imagen y PDF usan la misma orientación y composición.

Archivos: js/pallet-label.js, js/pallet-label-pdf.js, js/label-page.js, css/pallet-label.css, etiqueta.php, vistas/footer.vista.php (portrait1); tests/pallet-label-layout.cjs, tests/pallet-label-pdf.cjs y tests/pending-label-print.cjs. No cambios de datos, permisos ni escáner.

Validación: HTML/PDF nativo1/2/6 etiquetas coinciden con1/2/6 páginas A4 vertical. PDF.js renderiza todas las páginas; seis QR de esos renders decodificados coinciden con sus números. PNG completo y QR de beta/subcarpeta con ceros iniciales comprobados; estos últimos se encuadran con su zona blanca y rotaciones/tamaño de prueba para evitar el reescalado del A4 completo por scanFile. Se conservan reserva/fecha y el aviso de tres días. Revisión visual de PDF rasterizado: logo, QR, número y cuatro líneas finales no se recortan. Prueba de impresión múltiple/autorización/visor iPhone simulado/download/Desktop y PHP lint pasan.

No se verificó físicamente AirPrint/Safari en iPhone ni distancia de lectura; comprobar con impresora/cámara reales. Revertir el commit de esta entrega devuelve la composición horizontal sin modificar IDs/QR/avisos. Main permanece intacto.
