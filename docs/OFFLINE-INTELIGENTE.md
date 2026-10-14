# Offline inteligente (beta)

## Alcance
La interfaz offline se prepara con Service Worker después de una carga con conexión. Inicio permite leer y guardar EAN en una lista local; Stock permite consultar copias autorizadas de palets previamente abiertos y agregar productos a esos palets; Biblioteca permite dejar altas pendientes en colecciones previamente consultadas. Las copias muestran fecha de actualización: palets y colecciones caducan a los siete días; el catálogo mínimo local, a los treinta días. La lista EAN de la interfaz offline es local y separada de la lista online; no se promete sincronizar esas listas.

QR y OCR utilizan el mismo motor global. OCR necesita haber descargado sus modelos con conexión: se preparan al consultar un palet. Sin modelos disponibles quedan QR y entrada manual. Reconocer ocho números no autoriza a usar un palet desconocido ni reservado. Cámara, sonido y vibración dependen del navegador y permisos existentes.

Crear/eliminar palets, modificar productos existentes, permisos, moderación, autenticación, mapas y consultas nuevas requieren servidor. Una copia offline no puede conocer una revocación de permisos que todavía no fue comunicada: todas las cargas se revalidan al enviarlas.

## Conectividad y colas
`api/conectividad.php` comprueba sesión y disponibilidad de base de datos, sin devolver datos personales. `js/connectivity.js` diferencia red, servidor y sesión; navigator.onLine es solo una señal. El indicador del logo se oculta en estado normal y muestra sincronización o pendientes cuando corresponde. Consultas tienen límite de tiempo, sin bloquear la interfaz.

`js/offline-sync.js` centraliza Stock/Biblioteca en todas las páginas: inicio, regreso a primer plano, recuperación y comprobaciones periódicas. Reintentos exponenciales entre 2 y120 segundos, sin depender de Background Sync. IndexedDB `repotools-stock-offline`, versión3, conserva las colas existentes `loads` y `libraryLoads`; UUID, fecha, estado y número de intentos. Web Locks o leases IndexedDB serializan pestañas; una cola local evita reentrada en el mismo documento.

Se elimina una carga solo después de confirmación y registro local del resultado. Stock conserva revisión optimista y claves idempotentes. Biblioteca usa el ledger existente `stock_operations`, con prefijo `library:` y huella del contenido; repetir una operación confirmada no vuelve a crear un producto borrado después. Una misma clave con otro contenido da409. Referencias distintas se revisan explícitamente; ningún conflicto se resuelve sobrescribiendo automáticamente.

## Privacidad
Las copias excluyen CSRF, contraseñas, tokens, correo, IP y miembros. Las credenciales de sincronización se obtienen en memoria desde la sesión activa. Al salir o cambiar de cuenta se borran las consultas privadas y la lista offline de esa cuenta. Las cargas pendientes se conservan separadas por usuario para evitar perder trabajo; otra cuenta no las visualiza ni puede enviarlas. Son datos locales del navegador, no un respaldo: borrar almacenamiento o la expulsión por el sistema puede eliminarlos.

## Validación
Pruebas aisladas con SQLite temporal y Chrome: pérdida de respuesta tras guardado, reenvío sin duplicados, huellas, rechazo de operaciones inseguras, referencias en conflicto, sesión vencida, cambio de cuenta, exclusión de pestañas sin Web Locks, shell sin red, QR y OCR real con modelos cacheados sobre video sintético. No se usaron cuentas ni escrituras de producción. Consultar `tests/offline-intelligent.cjs`, `tests/stock-api.cjs`, `tests/stock-offline-ui.cjs` y `tests/offline-start.cjs`.

Regresión: se ejecutaron57 pruebas;54 pasaron en la primera ejecución. Se corrigieron el bloqueo de controles offline, el historial al abrir Stock y esperas de las pruebas; las tres pruebas fallidas se repitieron por separado. Los controles offline se comprobaron a320/390/430/1366px. El bloqueo de scroll de esa interfaz mantiene overflow/touch sin reposicionar el cuerpo; la entrada y los contenidos conservan un fade, y el cierre libera inmediatamente la capa modal. El resto conserva las animaciones globales.

Pendiente físico: Safari/iPhone instalado y pestaña normal, modo avión desde varias páginas, expulsión/retención del almacenamiento, cámara real y red móvil intermitente. No afirmar compatibilidad física por simulación Chrome.

## Reversión
Revertir el commit de esta entrega en desarrollo, sin tocar Main. No hay migraciones SQL. Al revertir conservar `indexedDB.open('repotools-stock-offline',3)` y almacén `leases`: volver a versión2 causaría VersionError en navegadores ya actualizados. No borrar colas del usuario. El ledger aditivo reutiliza `stock_operations`; conservar sus filas para no perder idempotencia. Actualizar versión del shell al revertir recursos, para retirar caché anterior.

Verificación pública de beta (2026-10-14): recursos comparados por SHA-256, plantilla con versiones nuevas y conectividad200/no-store. Navegador Chrome temporal preparó el Service Worker y reabrió Inicio sin red a390px; sin escrituras en datos de producción. Implementación1392b3d y actualización de cachéff6592d subidas exclusivamente a desarrollo. Main permaneció f7c6003e7dcec8a38ec640c068260107538b769d. Esto no sustituye las pruebas físicas pendientes de Safari.
