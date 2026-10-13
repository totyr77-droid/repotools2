# Estado de sincronización (desarrollo)

Stock y Biblioteca muestran un indicador compacto con conexión, pendientes, envío y error. Los números se leen directamente de `loads` y `libraryLoads` en el IndexedDB existente, filtrados por cuenta. No hay otra cola, contador persistido ni service worker.

Los controladores actuales anuncian inicio/fin del envío; escribir o quitar operaciones dispara una actualización. Reintentar reutiliza esos controladores y conserva sus claves idempotentes y revisión de palet. Las cargas con conflicto siguen necesitando Revisar: un reintento no autoriza sobrescribir cambios ajenos. La fila reserva espacio; respeta mano móvil y movimiento reducido.

Archivos: js/sync-status.js, css/sync-status.css, js/stock-offline.js, js/library-offline.js, js/stock.js, vistas/footer.vista.php, sw.js y tests/stock-offline-ui.cjs.

Validación: stock-offline-ui pasa online, 1/2 cargas offline, reconexión, Biblioteca, persistencia tras recarga, aislamiento por cuenta, conflicto, revisión y respuesta perdida/reintento sin duplicar cantidad, anchos 320/390/1366. pallet-activity-ui verifica historial sin duplicados, permisos y paginación. Pruebas Chrome simuladas; cierre completo de Safari/PWA y dispositivo físico pendientes.

Revertir el commit de esta entrega. No borrar IndexedDB ni cargas locales. El esquema de datos no cambió.
