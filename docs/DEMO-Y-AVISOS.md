# Vista previa y avisos superiores — 13/10/2026

## Vista previa sin cuenta

Stock y Biblioteca muestran ejemplos en lugar de redirigir inmediatamente al login. Se interpreta Lista como las listas guardadas de Biblioteca; Inicio y su lista real del dispositivo siguen intactos. Las cuentas con sesión siguen pasando por el código real y conservan sus estados vacíos, sin demo.

Datos centralizados en includes/demo-data.php: tres palets ficticios 001/002/003, Supermercado Centro/Sucursal Norte/Sucursal Sur,42/63/43 productos; resumen3 palets,148 productos,6 por vencer,2 vencidos. Cada palet permite ver una muestra ficticia de productos/cantidades/vencimientos, con etiquetas distintas para por vencer/vencido. Biblioteca muestra cuatro productos y EAN incompletos identificados como ejemplos; no consulta catálogos ni usuarios.

Las acciones Crear/Editar/Compartir/Agregar producto abren un diálogo de registro sin llamar APIs. Crear cuenta y Entrar usan next=stock/biblioteca, el registro existente y las reservas/enlaces pendientes conservados. Escanear producto lleva a Inicio, cuyo escáner anónimo sigue operativo. Los datos no se guardan en localStorage ni se mezclan con lista. No hay analítica nueva ni cuentas temporales.

Render: guest-demo.vista.php reutiliza workspace/library/dialog/controles e iconos SVG existentes; guest-demo.css adapta ejemplos y columnas a móvil/PC. Antes del pie, se desactivan flags de página real para no incluir sus formularios/scripts de operación. guest-demo.js solo interactúa con datos embebidos ficticios. Los servicios normales de sesión/autenticación y preparación offline siguen funcionando: «no escribe DB» se refiere a datos de demo/negocio; la infraestructura de sesión preexistente conserva su persistencia.

Prueba tests/guest-demo.cjs: exploración, diálogos y CTA, sin requests API Stock/Biblioteca/Reportes durante la demo, tablas de negocio sin cambios, lista real local conservada,320/375/390/430/1366, paso al Stock/Biblioteca auténticos y sin ejemplos. Capturas Stock móvil y Biblioteca PC revisadas. APIs conservan sus controles; las modificaciones de reportes de esta entrega corresponden al pedido separado de seguridad.

## Avisos celular–PC

Actualización visual13/10: la demo lleva etiqueta DEMO, aviso amarillo «Estás viendo una demo», explicación explícita de cantidades/vencimientos ficticios y acceso a registro arriba. «Tu lista» pasa a «Lista de ejemplo»; tarjetas de palets se titulan «Palet de ejemplo» y usan borde discontinuo/fondo suave. No cambia el conjunto de datos ni lógica de cuentas. guest-demo pasa320/375/390/430/1366, exploración y aislamiento. Captura móvil revisada. Revertir solo vista/CSS y versión CSS del header a d9f7ec7 para deshacer este ajuste sin quitar controles de acceso.

El z-index no atraviesa la capa superior de dialog.showModal. RepoToast usa un popover manual, sin interacción ni cambios de foco, para aparecer sobre ventanas y backdrops. Mantiene SVG, verde,2,4 segundos, safe area y limpieza al navegar. En navegadores sin Popover API usa el diálogo activo/último abierto como respaldo. La PC confirma EAN/QR después de procesar el código, sin repetir avisos por cada sondeo. Las fotos mantienen su confirmación propia.

tests/toast-top-layer.cjs pasa con dos modales abiertos, aviso en :popover-open dentro de body y eliminación temporizada. tests/device-connection-ui.cjs y device-report-photos.cjs pasan conexión, códigos, evidencia/fotos y cancelaciones. Captura PC revisada con el aviso sobre backdrop. Cámara física/Safari real pendientes; no se modificó detector, flash, zoom ni APIs del enlace entre dispositivos.

Archivos nuevos: includes/demo-data.php, vistas/guest-demo.vista.php, js/guest-demo.js, css/guest-demo.css y pruebas guest-demo/toast-top-layer. Modificados: stock.php,biblioteca.php, header/footer (estilos/scripts y versiones), js/action-notices.js, js/device-connection.js, css/device-workspace.css y documentación. Sin migraciones ni cambios de service worker/offline.

Reversión de demo/avisos: restaurar esos archivos desde 7ba2ba6 y retirar archivos nuevos. Preservar los guards de reportes del pedido de seguridad. No se crearon datos que borrar ni se modificaron datos de usuarios.
