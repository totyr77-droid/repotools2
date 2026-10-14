# Auditoría crítica de sincronización de mercadería — beta

## Diagnóstico y alcance
La revisión encontró tres riesgos comprobables: `offline-sync.js` borraba la operación y escribía el recibo antes de consultar el palet actualizado; el fallo de esa consulta se ignoraba; la caché admitía reemplazar una revisión nueva por otra anterior. El cliente aceptaba `ok:true` sin verificar operación, cuenta ni destino. El backend ya guardaba movimiento y registro idempotente en una transacción bajo mutex.

Estas evidencias no demuestran la causa histórica del incidente real. No se inspeccionó ni alteró el palet real ni la base compartida. El caso de dos productos de 84 unidades se reprodujo exclusivamente en SQLite temporal.

## Corrección
- API entrega confirmación con UUID, cuenta, acción, ID interno y código, junto con snapshot autorizado dentro de la misma transacción. La respuesta se envía tras commit. El resultado persistido permite reconstruir la confirmación después de perder la respuesta.
- Reintentos verifican el fingerprint existente. Operaciones antiguas sin fingerprint suficiente quedan para revisión, no se vuelven a aplicar. Si el palet fue liberado/reasignado o cambian permisos, se conserva la operación y se avisa; no se recrea inventario histórico eliminado legítimamente.
- Alta offline valida también que la etiqueta apunte al ID interno original.
- Cliente rechaza confirmación incompleta, incorrecta o destinada a otro palet. No usa HTTP200 como prueba de persistencia.
- IndexedDB versión3, mismas tablas: una transacción `loads/sent/palets` actualiza snapshot y recibo y elimina el pendiente. Si falla o se interrumpe, conserva la operación; el siguiente envío reutiliza UUID.
- Caché y vista no retroceden de revisión dentro del mismo ID. Un palet que figura disponible pero tiene cargas locales no ofrece Utilizar antes de reconciliar.
- Estados pendiente/enviando/retryable/conflict/rejected/auth y completado en recibos. Se conserva último error y fecha, intentos y backoff2–120s; revisión/discard con confirmación y reintento manual existentes. No nuevos toasts; éxito Stock «Mercadería sincronizada correctamente» solo tras confirmación y commit local.
- Reconexión/primer plano/reapertura siguen usando conectividad real y el sincronizador existente.

## Funciones offline
Se admite agregar mercadería a palets existentes consultados y autorizados. Crear palets, editar cantidades absolutas, restar y tareas administrativas requieren servidor. No se añadieron estas funciones offline ni se transformaron ediciones en sumas. Datos pendientes no admitidos se conservan para atención. Los nombres locales siguen como referencia del palet, no catálogo compartido.

## Archivos
api/stock.php; js/stock-offline.js; js/offline-sync.js; js/stock.js; js/sync-status.js; sw.js; tests/stock-sync-integrity.cjs; tests/offline-add-auto.cjs; PENDIENTES.md; este documento.

## Validación
Aprobadas: PHP lint y JS syntax; stock-api; stock-offline-ui; stock-sync-integrity; offline-add-auto; pallet-deduct-ui; sync-receipts-ui. Caso aislado: dos lotes84, cierre/recarga, pérdida de respuesta tras commit, replay sin duplicar, lectura inmediata por código, snapshot local, caché de revisión antigua, HTTP200 sin ack,500/429, ack de destino incorrecto y datos no admitidos conservados. Pruebas API existentes validan permisos compartidos, revisión, idempotencia y límites negativos; UI controla ambos lados360–1366.

`offline-intelligent.cjs` no quedó aprobado: un botón Buscar palet en shell offline está cubierto por `main.app-page-enter`, impidiendo interacción. No se cambió navegación ajena a esta corrección ni se ocultó el fallo. Queda pendiente resolver/verificar esa regresión y repetir el recorrido completo de OCR offline, autenticación y privacidad de esa suite. No hay pruebas de hardware Safari ni concurrencia PostgreSQL real en esta entrega.

## Comprobación física iPhone/PWA
1. En beta, abrir un palet de prueba autorizado con conexión y anotar código/stock.
2. Modo avión: agregar dos referencias de84; comprobar filas pendientes, cerrar/reabrir PWA y verificar cola.
3. Reconectar: esperar confirmación, abrir recibo y escanear mismoQR. Verificar84 por cada producto, nunca168 por reintento.
4. Suspender PWA mientras envía, reabrir y revisar que no duplique ni desaparezca la operación.
5. Compartido: otra cuenta agrega20 y primera15 offline; reconectar, comprobar ambas sumas; retirar permisos y verificar que una carga nueva queda para atención.
6. Red débil/servidor inaccesible: ningún éxito antes de confirmación; usar Reintentar. No descartar una carga de resultado desconocido sin revisar inventario.

## Riesgos y reversión
Beta y producción comparten DB: no migraciones ni escrituras de prueba en ella. Compatibilidad segura con frontend antiguo: nuevos campos son adicionales. Cliente nuevo con servidor antiguo conserva pendientes si no puede verificar ack. IndexedDBv3 permanece, sin vaciados. Revertir únicamente commit en desarrollo; no borrar cola ni bajar versiónDB. Caché SWv31 actualiza recursos. Verificar despliegue antes de prueba física; push no garantiza Render activo.
