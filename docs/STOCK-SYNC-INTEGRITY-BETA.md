# Integridad de sincronización de Stock — beta

## Diagnóstico
Se rastrearon creación, IndexedDB, sincronización, commit, comprobantes y lectura por QR. No se inspeccionó ni modificó producción. Las consultas de lectura en la base beta no encontraron el código informado, sus operaciones, palets activos sin etiqueta ni palets activos con productos y flag activated=0. Por eso la causa histórica no queda confirmada: cambio de entorno/base, eliminación posterior y cliente antiguo siguen siendo hipótesis.

Riesgos reproducidos: una respuesta de reintento podía confirmar solo el palet aunque el producto ya se hubiera eliminado; un código disponible podía presentarse inicialmente como utilizable aunque existieran cargas locales; un palet con activated=0 podía caducar sin comprobar si tenía productos. Una carga editada durante el envío también necesitaba protección contra sobrescritura local.

## Qué significa el comprobante
Antes, api/stock.php ya hacía commit antes del HTTP 200 y offline-sync exigía ID de operación/usuario/palet/código; no bastaba enviar la petición. RepoStockOffline.confirm borraba loads y escribía sent/palets en la misma transacción IndexedDB. Su oncomplete emitía repotools:offline-sent; library-offline mostraba los comprobantes recuperados recientes al abrir el icono del header. Faltaba detalle verificable del producto/movimiento. El comprobante es histórico: una eliminación/resta posterior puede cambiar el contenido actual.

Ahora stock_save_product registra el producto, EAN, vencimiento, unidades previas/agregadas/resultantes y revisión en stock_operations, dentro de la misma transacción que modifica mercadería y actividad. stock_offline_confirmation verifica asociación y presencia; committed=true se añade solo tras commit. El cliente contrasta estos datos con la petición y el snapshot. Un producto eliminado, asociación incorrecta o confirmación histórica incompleta devuelve conflicto; no se repite el movimiento. Si hubo cambios posteriores permitidos, el snapshot refleja cantidades actuales y el comprobante indica que hubo otros movimientos.

## Conservación y lectura
No hay migración, limpieza de IndexedDB ni eliminación de colas. Errores HTTP, red, timeout, sesión y respuestas ambiguas conservan la carga. La confirmación local compara nuevamente el payload; si cambió durante envío, aborta la transacción y conserva los datos actuales para revisión. Operaciones antiguas sin detalle verificable se conservan en conflicto; no se convierten automáticamente en nuevos movimientos. Comprobantes antiguos permanecen disponibles con advertencia de verificación.

La consulta comprueba sesión, estructura, código e ID; no convierte una respuesta fallida en disponible. Cargas pendientes de un código libre bloquean Usar palet antes del render, se muestran sin confirmar y no reemplazan la copia confirmada. Los movimientos de un ID anterior no se mezclan con otro palet que reutilice el código. Si falla una actualización abierta, la pantalla informa que la vista anterior no se pudo actualizar. La caducidad exige ausencia de productos; un código con un palet activo huérfano necesita revisión, no reutilización.

## Validación
Fixtures locales SQLite, usuarios .invalid y contextos de navegador aislados; ningún dato real usado para pruebas. Suites: stock-movement-proof (dos x84, commit, replay concurrente, payload modificado, producto eliminado, confirmación antigua, caducidad y vínculo huérfano), stock-sync-integrity (respuesta perdida tras commit, QR simulado inmediato, recarga, cache antiguo, respuesta 200 sin prueba/producto, 500/429, edición durante envío y sesión vencida), stock-api, stock-offline-ui, offline-add-auto, sync-receipts-ui, sync-silent-ui, offline-intelligent y stock.php. La prueba amplia detectó bloqueo de controles tras lecturas offline repetidas: se excluyó solo #offlineContent de animaciones de contenido, manteniendo lector y navegación; prueba repetida aprobada.

Pendiente: Safari/iPhone físico, red celular intermitente y concurrencia PostgreSQL real. No se puede reconstruir el incidente histórico sin conocer entorno/fecha y consultar la cola del dispositivo o los registros del entorno correspondiente. No reinsertar automáticamente datos desde capturas o comprobantes: primero comparar operación única e historial del palet para evitar duplicados.

## Publicación y reversión
Solo desarrollo/beta. Main permanece sin modificaciones. Revertir el commit y publicar una versión interna superior para volver atrás; el código anterior pierde estas protecciones. No borrar operaciones, comprobantes ni snapshots para revertir.

## Archivos y puntos de control
- includes/stock.php: stock_save_product, stock_expire y stock_label.
- api/stock.php: stock_offline_confirmation, replay de stock_operations y respuestas después de commit.
- js/offline-sync.js: flush, validación de confirmación y conservación del payload actual.
- js/stock-offline.js: confirm transaccional en IndexedDB.
- js/stock.js: verifiedPallet, openPallet y renderPendingProducts.
- js/library-offline.js: lectura de comprobantes y advertencia de formatos anteriores.
- js/sync-status.js: render de sesión, servidor, pendiente y sincronización.
- js/motion.js: excluye solo el contenido offline para evitar capas de interacción obsoletas.
- Pruebas nuevas/actualizadas: stock-movement-proof.cjs, stock-sync-integrity.cjs, offline-add-auto.cjs y sync-silent-ui.cjs.
