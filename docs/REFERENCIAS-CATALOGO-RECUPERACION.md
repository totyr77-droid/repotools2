# Referencias de catálogo y recuperación offline — 1.36

## Cambios

- La carga manual de Stock puede incorporar el nombre de referencia al catálogo solo si la consulta previa confirmó que no existe en DB ni en la API. No se interpreta un error de consulta como producto inexistente.
- `js/product-info.js` prepara una autorización temporal en memoria: descarta offline, ahorro de datos, 2G/3G y RTT declarado mayor a 800 ms; exige además una comprobación real al servidor de hasta 1000 ms y una preparación de hasta 1200 ms. Safari sin Network Information usa la comprobación real. Es un criterio conservador, no una garantía física de calidad de internet.
- `includes/catalog-reference.php` valida sesión, CSRF en API, EAN, permisos sobre el palet y ausencia confirmada. La autorización es de un solo uso y vence a los 3 segundos. Se vincula a usuario, operación, palet, EAN y nombre. La inserción se realiza dentro de la misma transacción que la mercadería, sin reemplazar productos existentes.
- El registro tiene `products.source=stock_reference`. No crea escaneos, reportes aprobados, recompensas ni una segunda operación para rankings. El movimiento de mercadería conserva su auditoría y reglas anteriores.
- Operaciones creadas offline, diferidas o reintentadas no se promueven al catálogo. La autorización nunca se persiste en IndexedDB ni en comprobantes. La referencia del palet y su mercadería sí conservan la sincronización actual.
- Conectividad y navegación del Service Worker comprueban la red aunque `navigator.onLine` esté desactualizado. El fallback HTML es `no-store`, para no reutilizarlo como respuesta online.
- El shell offline vuelve automáticamente a la sección online tras comprobar conexión, esperando si hay formularios/diálogos, foco editable, guardado o sincronización activos. Límite de dos intentos automáticos en cinco minutos; el enlace de reconectar permite un intento explícito. Errores de servidor o autenticación no se tratan como recuperación.

## Archivos

- Backend: `includes/catalog-reference.php`, `api/productos.php`, `api/stock.php`.
- Frontend: `js/product-info.js`, `js/stock.js`, `js/offline-sync.js`, `js/connectivity.js`, `js/offline-page.js`, `sw.js`.
- Pruebas nuevas: `tests/catalog-reference.php`, `tests/catalog-reference-ui.cjs`, `tests/offline-recovery.cjs`.
- Prueba adaptada: `tests/service-worker-offline.cjs`.
- Continuidad y publicación: este documento, `PENDIENTES.md`, `release.json`.

## Validación

Aprobadas con SQLite temporal y Chrome automatizado: catálogo de usuario común con conexión rápida; ausencia de crédito scan/report; conexión lenta y carga offline conservan mercadería sin catálogo; permisos, CSRF, autorización vencida/de otro usuario/payload modificado, rollback y no sobrescritura; reconexión con formulario abierto, cierre, autenticación fallida, servidor inaccesible y límite de redirección.

Regresiones aprobadas: `stock-sync-integrity.cjs` (respuesta perdida, reintentos sin duplicar, comprobante atómico, error500/429), `unified-ux.cjs` (retiros y conflictos persistentes), `stock-api.cjs`, `stock-offline-ui.cjs`, `catalog.php`, `catalog-api.cjs`, `product-info.cjs`, `service-worker-offline.cjs`.

La prueba nueva inicialmente consultaba DB antes de observar el comprobante específico de la tercera carga; se sustituyó esa espera por un sondeo explícito del comprobante del EAN. La prueba final verifica unidades en DB y su comprobante, no solo cola vacía. No se encontró pérdida de esa operación en la comprobación final.

Pendiente prueba física Safari/iPhone/PWA, Android y redes reales intermitentes. No se modificaron inventarios reales, se borraron colas ni se ejecutaron correos.

## Base de datos y reversión

Sin migraciones. Revertir únicamente el commit de esta entrega si se necesita retirar la incorporación automática; mantener los productos ya confirmados, las operaciones y todos los almacenes IndexedDB. No vaciar cachés privadas ni movimientos. Conservar la corrección de recuperación si se revierte solo catálogo.
