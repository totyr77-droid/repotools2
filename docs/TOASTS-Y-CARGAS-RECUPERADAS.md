# Avisos flotantes y cargas recuperadas — beta, 2026-10-14

## Cambios
Se reutiliza RepoToast, sin sistema paralelo: cola de un aviso, deduplicación5s, prioridad de errores, cierre X, success3s/info4s/warning5s/error6s, descripción opcional, ARIA status/alert, SVG existentes y movimiento reducido. Popover manual mantiene el aviso en la capa superior sin bloquear; fallback dentro del diálogo activo. El gestor permanece durante navegación AJAX y limpia temporizadores al salir de la página.

Los resultados transitorios de los elementos de estado existentes pasan a toast en Inicio, Stock, Biblioteca, formularios, Moderación y Ajustes. No se convierten prompts estáticos de diálogos cerrados, progreso del escáner, validaciones de campos ni información necesaria para operar. Mensajes locales pendientes se muestran como información, sin afirmar guardado en servidor. Los avisos de scanner y fotos ya emitidos con RepoToast usan el mismo gestor.

Cargas enviadas: se retira el bloque fijo Ver cargas enviadas. El icono de subida junto a la campana aparece en Stock/Biblioteca solo para confirmaciones de operaciones diferidas por falta de conexión/servidor inaccesible o pérdida de respuesta. No aparece por una operación online normal ni por comprobaciones de conectividad. Detalle combinado Stock/Biblioteca, identificación de producto/EAN/destino/cantidad. Caduca sin abrirse a los10min; después de cerrar el detalle, se oculta a los3s. El slot del header se reserva desde el HTML inicial para que mostrar/ocultar el icono no desplace la campana ni cambie alturas. Los registros confirmados siguen en el almacén sent existente; estado leído usa sessionStorage por cuenta, sin credenciales.

Se oculta Exportar en Stock (API existente conservada para retomarla). Métricas de Stock en una fila y Últimos movimientos con estilo de botón discreto. Buscar palet se oculta por redundancia con QR/teclado generales; no se altera acceso por rango.

Historial individual incluye todos los actores con acceso permitido, no solo el dueño o quien consulta. Eliminaciones ya se registraban; se añaden invitaciones pendientes/cancelación/rechazo y cambios de sucursal que faltaban. Nombres públicos o alias Usuario #id, sin revelar nombre legal/correo. No se inventan eventos anteriores inexistentes. Se mantienen reglas de permisos y no hay migración SQL.

## Validación
- global-toast-ui: timers, cola, duplicados, errores prioritarios, cierre, cero movimiento de contenido, validación inline, ARIA, capa superior y móvil.
- sync-receipts-ui: solo recuperadas, sin bloque fijo/desplazamiento header, detalle, cierre3s y caducidad10min.
- sync-silent-ui: carga/navegación saludable silenciosas, envío real, conflicto, Stock/Biblioteca.
- toast-top-layer, settings-ui, responsive-workspaces (7 pantallas/10 tamaños/ambas manos).
- pallet-history-all-actors.php: dueño/colaborador ven acciones de ambos, borrado y detalle, invitación y sucursal; tercero403.
- pallet-activity-ui, stock-api, pallet-activity-grouping.php, branch-assignment-choice.php.
- offline-intelligent: reconexión, autenticación, IDB, idempotencia, privacidad y OCR cacheado.

Pendiente físico: Safari/PWA iPhone y Android, notch real, rotación y reconexión de red móvil. Chrome emulado no sustituye estas pruebas.

## Revertir
Revertir el commit de esta entrega exclusivamente en desarrollo. Al revertir conservar/incrementar la versión Service Worker (shell-v21) para refrescar los clientes; no bajar IndexedDB ni borrar colas. Las marcas opcionales deferred/offline_wait/recovered son compatibles con código anterior. Registros de auditoría añadidos se conservan; no requieren migración. Main/producción intactos.

Archivos: js/action-notices.js y css/action-notices.css; js/library-offline.js, js/offline-sync.js y js/stock-offline.js; stock.php, js/stock.js y css/stock-overview.css; includes/pallet-activity.php, includes/stock-branches.php y js/pallet-activity.js; vistas/header.vista.php y vistas/footer.vista.php; sw.js y offline.html; pruebas global-toast-ui, sync-receipts-ui, pallet-history-all-actors, sync-silent-ui, toast-top-layer, offline-start y stock-export-ui; PENDIENTES.md y este documento. Export UI y reapertura offline también comprobadas en regresión.

Regresión offline: se desactiva la transición inicial del main en el shell offline para evitar una capa que intercepta clics; las demás animaciones se conservan. Dos ejecuciones consecutivas offline-start aprobadas tras el ajuste. Archivos adicionales: css/offline.css y tests/stock-branches-ui.cjs (Enlace desactivado ahora se verifica como toast).
