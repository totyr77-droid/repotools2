# Resumen de Stock (desarrollo)

Cuatro cifras compactas: palets activos, lotes de productos, por vencer (incluye hoy) y vencidos. Productos cuenta filas EAN+vencimiento, no unidades; coincide con el contador dentro de cada palet. Sin stock devuelve ceros y conserva la acción existente para crear el primer palet. No muestra datos demo a cuentas autenticadas.

Los últimos cinco movimientos están plegados: nombre público actual, acción, palet y fecha argentina. Reutilizan `pallet_activity_present`, el mismo saneamiento y consulta agrupada de colaboradores del historial. Nunca email/nombre privado. No se incorpora otra tabla de actividad.

`api/stock-overview.php` hace una petición por resumen. Backend: preferencia existente, agregación única y últimos cinco movimientos; opcionalmente una búsqueda agrupada de colaboradores, no una por evento. Solo propios/colaboración aceptada, activos y no liberados. Sin nuevos índices o migraciones. Usa los límites de vencimiento existentes (30d por defecto, configuración menor respetada).

Carga local independiente: un error muestra Reintentar y permite usar Stock. Actualiza después de cambios reales, agrupando eventos cercanos. No agrega filtros duplicados: el botón Vencimientos actual continúa disponible.

Archivos: includes/stock-overview.php, api/stock-overview.php, includes/pallet-activity.php, js/stock-overview.js, css/stock-overview.css, stock.php, vistas/footer.vista.php, tests/stock-overview.php y ampliación tests/stock-export-ui.cjs.

Pruebas: ceros,300+ palets, preparados/ajenos/pending excluidos, compartidos aceptados, lotes y fechas frontera, días0/10, últimos5, nombre público dinámico. Agregado local SQLite ~0,21ms con300 palets (no representa Render/Neon). UI360/390/430/1366, error/reintento sin bloquear scanner; regresión de historial pasa. Safari físico y rendimiento remoto pendientes de auditoría.

Reversión: revertir este commit. Ninguna modificación de datos ni migración.
