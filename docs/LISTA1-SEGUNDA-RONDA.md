# Lista 1 — Segunda ronda

Entrega sobre Main, versión 1.10. Se ajustan los puntos 5, 7, 10, 11, 12,
13, 14, 17, 18 y 19. Sin cambios de permisos, catálogo, cantidades, cola offline
ni reglas de generación/reserva de códigos.

## Cambios por punto

| Punto | Corrección |
|---|---|
| 5 | Inicio: lista y escaneo existentes se sitúan juntos al fondo del contenido en modo de una mano. Orden normal conservado. En la fixture móvil quedan a 39 px del nav, sin superposición, para ambas manos. |
| 7 | Stock tradicional: barra sin salto de fila, controles compactos y desplazamiento horizontal interno solo si hace falta. Vencimientos conserva el panel inline. Modo de una mano conserva su distribución. |
| 10 | Componente compartido: zoom vertical fino con +, rango, valor y −. Arrastre, botones .1 o paso real mayor, pulsación sostenida y pellizco. Actualización serial/coalescida existente, límites reales y posición según mano. Selección y menú contextual bloqueados únicamente en ese control. |
| 11 | Normal y QR reproducían el tono después de liberar la cámara. Ahora usan el mismo `beep` antes de liberarla y dejan terminar el tono. Rápido conserva su ruta, volumen, frecuencia y duración. Se mantiene un tono por confirmación, preferencias y cancelación. |
| 12 | Mensajes existentes movidos dentro del visor, arriba, blancos sobre banda oscura semitransparente de altura limitada. Se preservan al limpiar/reabrir la cámara y también al fallar el permiso. |
| 13 | Resultado con una etiqueta visible, contador, flechas y swipe. Solo Compartir etiquetas e Imprimir etiquetas. Compartir prepara un único PDF vectorial con todas las páginas antes del toque; compartir nativo o descarga PDF, sin ZIP ni múltiples archivos. |
| 14 | Encabezado más compacto y QR proporcionalmente mayor; número colocado tras la zona blanca mínima de seguridad. Se conservan cuatro módulos blancos, número grande, logo negro, aviso y A4 vertical de una etiqueta por hoja. El margen blanco necesario para escanear no se elimina. |
| 17 | Normal oculta todo el hueco de cámara tras confirmar; resultado y acciones aprovechan el modal. Se conserva el único botón existente Escanear otro, sin duplicarlo. Rápido sigue abierto, con estado/contador y Finalizar. Layout adaptado también a horizontal corto. |
| 18 | Calculadora táctil flotante hasta 380 px de alto, botones cómodos y Listo visible. Cantidades seleccionan el cero al enfocar, admiten vacío durante edición y normalizan ceros iniciales en entrada directa. Utilidad compartida online/offline; EAN, códigos de palet y fechas quedan excluidos. |
| 19 | Selector PC compacto, checkbox horizontal, botones alineados, foco y Enter existentes. Producto en columnas con vencimiento/nombre y presentación/cantidad relacionados; Guardar conserva su flujo. Calculadora oculta con puntero fino y hover; permanece disponible si hay puntero táctil. |

## Archivos

- `js/ean-scanner.js`: controles, gesto, ubicación de mensajes y momento del tono.
- `js/stock.js`: calculadora y resultado de etiquetas reutilizado como carrusel/PDF.
- `js/stock-utils.js`: edición de cantidades compartida, sin tocar cálculo/validación.
- `js/pallet-label.js`: composición común SVG/PNG/PDF.
- `js/label-files.js`: compartir o descargar un único PDF.
- `vistas/stock-dialogs.vista.php`: navegación del carrusel y dos acciones.
- `vistas/footer.vista.php`: carga del escritor PDF existente en Stock.
- `css/ean-scanner.css`, `css/scan-result.css`, `css/stock.css`,
  `css/device-workspace.css`, `css/ux-polish.css`: presentación responsive.
- `sw.js`, `release.json`: recurso PDF preparado y versión de publicación.
- Pruebas: `tests/list2-ui.cjs`, `tests/ean-scanner.cjs`,
  `tests/scanner-zoom.cjs`, `tests/main-rapid-scanner.cjs`,
  `tests/label-files.cjs`, `tests/pallet-label-layout.cjs`,
  `tests/pallet-label-pdf.cjs`, `tests/pallet-deduct-ui.cjs`.

## Validación

Pruebas ejecutadas con cuentas y bases temporales locales:

- `list2-ui`: ambas manos, Inicio cerca del nav, fila Stock, cero seleccionado,
  cantidades vacías/normalizadas, calculadora compacta, generación 1/6/15,
  carrusel/swipe, PDF con todas las páginas, impresión con todos los códigos,
  anchos 360/430/768/1366 y formulario PC con Enter.
- `ean-scanner`: EAN8/13, confirmaciones/cancelaciones, tono antes de parar la
  cámara, QR y preferencias de sonido/vibración con AudioContext simulado.
- `scanner-zoom`: rangos reales simulados .1/.5, arrastre, botones/hold,
  pellizco, una solicitud de cámara a la vez, reversión de error, cierre y
  ausencia de controles cuando no hay capacidades. Selección/contextmenu locales.
- `scanner-frame`: 360/430/768/1043/1366 y horizontal844x390, mensajes largos
  sin desbordar ni cambiar la geometría entre modos mientras se escanea.
- `main-rapid-scanner`: veinte lecturas, duplicados, un stream, Normal sin
  hueco negro, mensaje dentro del visor, Escanear otro y reapertura.
- `pallet-label-pdf`: PDF A4 vertical con 1/2/6/15 páginas, códigos correctos,
  etiquetas activas/reservadas, ceros iniciales, URLs de distintos entornos.
- `pallet-label-layout`: impresión HTML 1/2/6 sin páginas extra, logo negro,
  texto sin recortes, zona blanca real y QR de la imagen decodificado.
- `label-files`: compartir un PDF nativo, descarga alternativa y rechazo de
  archivos múltiples. `pending-label-print`: selección e impresión existentes.
- `pallet-deduct-ui`: cantidades/cajas/calculadora, idempotencia, permisos,
  revisiones y rechazo offline. `offline-intelligent`: sincronización y privacidad.
- `settings-ui`, `ux-polish`, `one-hand-workspaces`, `responsive-workspaces`,
  `tablet-scan-methods`, `shared-camera-ui` y `stock-utils`: regresión de ajustes,
  iconos/controles, modos/manos, tablet/PC, cámara compartida y cálculo.

Se revisaron capturas locales del carrusel móvil, producto PC y resultado Normal.
Las expectativas antiguas de calculadora de pantalla completa y compartir PNG/ZIP
se actualizaron porque contradicen esta solicitud.

## Limitaciones reales y pendientes manuales

No se dispone de iPhone físico, impresora ni lector de pistola en estas pruebas.
Quedan por comprobar Safari/PWA real, volumen audible en Normal/Rápido/QR,
interacción con música/modo silencio, pellizco/linterna en la cámara concreta,
AirPrint, menú nativo de compartir y lectura de etiquetas a distancia real.

El zoom solo aparece si el track publica un rango ajustable y acepta sus
constraints. Las restricciones de audio/Silent de cada versión de iOS siguen
dependiendo del navegador. No se aumenta el volumen del sistema ni se garantiza
una conducta que no se pudo comprobar físicamente. Los lectores que introducen
texto y Enter conservan la ruta probada mediante teclado; no se probó hardware.

Todos los puntos tienen implementación y pruebas locales; las comprobaciones
físicas anteriores no se declaran completadas. No se rediseñaron Ajustes, Seguridad,
iconos de mercadería, navegación, conexión arrastrable, versión ni reglas de tablet
correspondientes a los puntos ya aprobados.

## Reversión

Revertir el commit de esta entrega y publicar un build superior para renovar
el Service Worker. No requiere rollback de base de datos ni limpieza de cachés
privadas/colas. No borrar datos del sitio: podrían contener cargas pendientes.
