# Referencias inmediatas y actualización del palet abierto

Entrega exclusiva de beta/desarrollo. Producción conserva su código anterior.

## Problemas reproducidos

Una carga con referencia guardada localmente, todavía sin confirmar, no participaba en la siguiente búsqueda del mismo EAN. El formulario esperaba catálogo y servidor juntos para completar el nombre. Además, una consulta de catálogo sin producto quedaba memorizada después de guardar la referencia. La recuperación del servidor contemplaba propiedad e invitaciones personales, pero faltaban los vínculos de sucursal.

La vista de Stock atendía los eventos de sincronización de la página, sin consultar modificaciones de otra cuenta. Una retirada autorizada llegaba a la base, pero el dueño no la veía hasta volver a consultar el palet.

## Comportamiento

El formulario consulta en paralelo la referencia local, la referencia del servidor y el catálogo. Ofrece el nombre útil cuando llega, con preferencia por cargas locales de la misma cuenta y el mismo palet. Incluye cargas pendientes y la copia confirmada del palet. Una respuesta posterior no reemplaza el texto que la persona haya escrito ni el que otro componente haya completado. Cada apertura/EAN mantiene sus controles de versión.

Una confirmación de carga invalida la caché de ese EAN antes de retirar la operación de la cola. Guardados directos también invalidan. La respuesta de una consulta antigua ya invalidada no puede restaurar producto o permisos anteriores. El servidor permite recuperar nombres de palets de una sucursal con vínculo vigente; al revocar, vuelve al alcance personal autorizado. No modifica fichas de catálogo ni crea referencias por el solo hecho de consultar.

Mientras un palet activo esté abierto y visible, se consulta aproximadamente cada cuatro segundos. La respuesta sin cambios trae una firma y no vuelve a descargar productos. La firma depende del usuario, revisión y permisos actuales, incluidas invitaciones, rangos y vínculos de sucursal. Al cambiar se entrega el detalle habitual, con su validación y sanitización existentes. El intervalo se suma al tiempo de red; es consulta periódica, sin garantía de actualización instantánea.

La consulta se pausa durante formularios, menús de configuración, guardados/sincronización, falta de conexión o pestaña oculta; se detiene al cerrar/navegar. Al volver o cerrar un formulario consulta de nuevo. Conserva búsqueda, foco en el buscador y desplazamiento, sin loader ni animación de entrada del contenido actualizado. Aplica la proyección de retiros locales pendientes una sola vez.

Un cambio de selección descarta respuestas anteriores. Si se libera/reutiliza el código, conserva la vista como consulta y pide reabrir antes de trabajar sobre otro palet. La revocación refresca permisos y elimina la copia privada local del palet. Las operaciones de edición siguen comprobando permisos y revisiones en servidor. Los errores transitorios usan espera creciente, hasta treinta segundos, sin vaciar la vista ni repetir movimientos.

## Validación

- Reproducción anterior: referencia pendiente vacía y cantidad de otra cuenta sin actualizar.
- stock-reference-live-ui: referencia pendiente/confirmada sin recargar, catálogo demorado, nombre manual conservado, dos cuentas con vínculo de sucursal, retiros visibles, formulario protegido, pausa al ocultar, búsqueda/foco conservados, desplazamiento estable, respuesta retenida al cambiar de palet, revocación y cierre sin nuevas consultas.
- stock-watch PHP: firma por usuario, respuesta compacta, cantidad, referencia de equipo, revocación sin cambio de revisión, datos públicos saneados y etiqueta liberada.
- product-info-cache: una respuesta antigua sin producto no pisa una ficha o permiso posterior.
- Regresiones catalog-reference PHP/UI, stock-offline-ui, stock-api, stock-expiry-keypad-ui, branch-team, service-worker-offline y sintaxis aprobadas.

Fixtures actualizados: la prueba de catálogo espera el recibo del EAN enviado; la de fecha cierra el borrador online y parte de una fecha offline vacía. Se conservan los controles de fecha/calculadora de la aplicación. Pruebas locales Chrome y SQLite; PostgreSQL completo y Safari físico siguen pendientes.

## Archivos y reversión

API/includes Stock, js/stock.js, stock-live.js, product-info.js, offline-sync.js, motion.js, css/stock.css y footer. El helper está en ASSETS del service worker; ninguna respuesta API privada se almacena en caché.

Para revertir, restaurar selectivamente estos cambios desde 7ffb85f, retirar el include/ASSETS de stock-live y publicar una versión beta superior. Conservar empresas, vínculos, colas, inventario, referencias y la excepción de sesiones móviles de manager. Sin migraciones ni cambios a la base por esta entrega.
