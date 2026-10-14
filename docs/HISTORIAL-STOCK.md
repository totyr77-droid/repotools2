# Historial general de Stock (beta)

El resumen de Stock aparece encima de Escanear palet. «Últimos movimientos» abre una ventana con el historial de todos los palets propios y las colaboraciones actualmente aceptadas, incluyendo palets eliminados. Cada registro muestra autor público, acción, palet, momento y acceso a una ficha identificada por su número de movimiento.

La ficha vuelve a consultar el servidor y muestra datos registrados: producto/EAN cuando existen, cantidades, colaboradores y comparación Antes/Después para referencias, descripciones, cantidades o vencimientos. La fecha del detalle es Argentina en24horas. Es una ficha de actividad de consulta, no crea reportes de moderación.

Se reutilizan `pallet_activity`, sus nombres públicos, iconos y formato de fechas. API GET `api/pallet-activity.php?scope=stock` pagina25movimientos con cursor porID; `event_id` consulta una ficha dentro del mismo alcance autorizado. Sin consultas por registro, sin cambios de esquema. La actividad individual existente y el resumen conservan sus contratos. No se inventa actividad anterior a la implementación del registro; solo aparecen movimientos que fueron guardados.

Permisos comprobados cada vez en el servidor. Una cuenta ajena no recibe registros; una colaboración revocada pierde acceso a sus fichas. El historial general de Stock conserva su alcance personal incluso para administradores; no se convierte en un listado indiscriminado de otros usuarios. Este historial necesita conexión.

Archivos: stock.php, vistas/stock-dialogs.vista.php, js/stock-overview.js, js/pallet-activity.js, css/stock-overview.css, includes/pallet-activity.php, api/pallet-activity.php y versiones de recursos en header/footer.

Validación: pallet-activity-ui cubre dos palets propios, otro ajeno, paginación sin repetición, detalles y comparación13→18, permisos/revocación, retención tras eliminación, movimiento arriba de los controles y360/390/430/1366px con ambas manos. stock-export-ui comprueba métricas, exportaciones, fallos y UI. Inspección visual Chrome móvil/escritorio; Safari físico pendiente. Pruebas aisladas con SQLite temporal, sin tocar datos de producción.

Reversión: revertir el commit de esta entrega solo en desarrollo. No hay migración, datos nuevos de negocio ni modificaciones de identificadores o movimientos existentes; los registros se conservan. Volver a subir versiones de recursos al revertir para evitar caché antigua.
