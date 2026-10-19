# Auditoría de rendimiento, offline y enlace PC–celular

Fecha: 15/10/2026. Base de comparación: Main `ea98363` (1.08/build13).
Trabajo autorizado en Main. No se usó inventario real para las pruebas ni se
ejecutaron correcciones de datos históricos. No se agregaron dependencias.

## A. Hallazgos y evidencia

| Problema | Causa y módulo | Impacto / evidencia |
|---|---|---|
| Espera al navegar sin internet | `sw.js` intentaba la red con un límite de 8 s incluso con desconexión conocida | Transporte bloqueado reproducido: 8.079 ms |
| Recursos preparados podían esperar la red | JS/CSS esenciales tenían estrategia network-first | La caché existía, pero se consultaba después del fallo de red |
| Faltaban palets offline | `stock-offline.js` desalojaba consultas hasta conservar solo 50; se preparaban principalmente al abrir cada palet | Fixture de 70 palets conservaba 50/5.000 productos |
| Instantánea grande descartada | `offline-cache.js` no guardaba el JSON si alcanzaba 2.000.000 caracteres | Instantánea de 2.454.572 caracteres conservaba 0 de sus 70 palets en la vista |
| Reconexión cambiaba la pantalla | `offline-page.js` consultaba y hacía `location.replace` cada 10 s | Riesgo de interrumpir el espacio de trabajo; no era necesario para sincronizar |
| Consultas de lectura tomaban bloqueos de escritura | Inicialización de esquema en cada petición y mantenimiento de caducidades en cada GET de Stock | Esquema instalado: 4 llamadas SQLite de inicialización, aunque no había migraciones |
| Retardo y trabajo repetido en relay | Sondeo PC 2 s / móvil conectado 3 s, sin exclusión explícita entre disparadores ni plazo de cada consulta | Mediana local final de envío: 1.831 ms; riesgo de respuestas antiguas y esperas indefinidas |
| Trabajo auxiliar de cámara en segundo plano | El análisis suplementario no comprobaba `document.hidden` | Se podía seguir preparando/decodificando imágenes sin interfaz visible |
| Volumen de DOM offline | Preparar todos los detalles haría renderizar todos los productos de todos los palets | La fixture contiene 7.000 productos; se evita crearlos todos de entrada |

El incidente histórico del palet comunicado anteriormente **no tiene una causa
histórica confirmada**. Estos hallazgos no demuestran que aquel movimiento se
haya perdido por uno de ellos. No se reinsertaron movimientos.

## B. Correcciones y comportamiento conservado

### Conectividad, navegación y PWA

- Una navegación con desconexión conocida utiliza la interfaz offline preparada
  sin probar primero la red. Los recursos esenciales usan la caché de la versión
  instalada. Las API privadas no se cachean.
- Navegación AJAX aborta una lectura obsoleta al perder conexión; Stock cancela
  sus GET, conserva sus escrituras y puede abrir una copia válida del palet.
- Un resultado tardío del probe no puede anunciar conexión si el dispositivo
  informa desconexión. Un fallo de transporte con `onLine=true` se informa como
  servidor inaccesible: no se puede diagnosticar desde el navegador si falló el
  proveedor, DNS o servidor. 401/403 siguen indicando autenticación.
- Se conservan los límites originales de 8 s de navegación y 6 s del probe cuando
  la conectividad es incierta. No se redujeron arbitrariamente.
- Los probes periódicos se espacian en segundo plano y se reanudan al volver.
- Recuperar internet sincroniza en segundo plano. La vista offline ofrece un
  enlace a la vista actualizada; ya no fuerza una recarga cada 10 s.
- Activación del SW elimina solo versiones anteriores del shell; conserva OCR,
  IndexedDB y operaciones pendientes. No se incorporó recarga automática.

### Datos offline completos y autorizados

- IndexedDB existente pasa de versión 3 a 4, agregando `views` sin borrar stores.
  Identidad/preferencias pequeñas continúan en la infraestructura existente.
  Instantáneas completas y grandes viven en IndexedDB por usuario y sección.
  Las vistas legacy se migran con su fecha original; se preservan las colecciones.
- Se elimina la expulsión arbitraria a 50 palets. Se mantienen las copias durante
  el plazo existente de siete días; no se presenta información vieja como actual.
- Al entrar en Stock se prepara el manifiesto completo de palets propios y
  compartidos aceptados. Se descargan detalles en lotes secuenciales de hasta
  cinco, solo si faltan, cambió su revisión o requieren renovación diaria.
- El backend valida nuevamente cada código y permiso. Este endpoint no permite
  descargar todos los palets por tener rango administrativo.
- Progreso discreto `preparados/total`, estado incompleto si falla un lote o el
  guardado y reanudación conservando lo ya preparado al siguiente intento.
- Revocaciones/eliminaciones observadas en el manifiesto quitan la copia de
  lectura correspondiente, **nunca la cola pendiente**. Una generación vieja no
  puede sobrescribir progreso nuevo ni expulsar copias durante una navegación.
- Listas de más de 20 productos se despliegan bajo demanda. Una consulta puntual
  de palet muestra sus productos directamente. Los productos siguen guardados,
  también disponibles para el reconocimiento/referencias offline.

### Integridad y cargas

Se conserva el contrato 2: operación única, confirmación `committed`, cuenta,
acción, palet interno y código público, producto, revisión y unidades verificadas.
Solo entonces se guarda recibo/copia y se retira la cola en una transacción local.
Las escrituras de Stock conservan su bloqueo y sus validaciones de servidor.

- Una pérdida de respuesta permite reintentar con el mismo identificador sin
  sumar nuevamente. HTTP200 por sí solo no confirma mercadería.
- Rechazos/conflictos conservan una operación revisable; no se sobrescribe otra
  carga silenciosamente. Los leases/bloqueos entre pestañas siguen activos.
- Añadir productos a palets conocidos y cargas compatibles de Biblioteca siguen
  funcionando offline. Descuentos, cambios administrativos, permisos, reservas y
  eliminaciones **requieren internet**. El servidor rechaza descuentos enviados
  como operación offline; no se inventó soporte offline para restar mercadería.
- Desconectar/cambiar de cuenta limpia lecturas privadas y mantiene las colas
  aisladas para su cuenta original. No se guardan credenciales en las vistas.

### Backend y carga visual

- Esquema completo: un SELECT sustituye DDL/transacción/bloqueo de migración por
  petición. Instalaciones nuevas o antiguas conservan la ruta transaccional de
  migraciones. `REPOTOOLS_SCHEMA_VERSION` tiene una prueba que exige actualizarlo
  cuando se incorpora una migración nueva.
- GET de Stock solo realiza mantenimiento bloqueado cuando hay caducidades o
  enlaces liberados que requieren limpieza. POST conserva el bloqueo original.
- GET del relay valida la sesión y sus slots sin bloquear la fila del usuario ni
  hacer limpieza global en cada sondeo. Las mutaciones limpian hasta 20 fotos
  vencidas por vez mediante el índice existente y mantienen su serialización;
  una conexión caducada/inválida no vuelve a aparecer por una segunda lectura.
- Se reutiliza el loader local al consultar un palet. Los loaders existentes
  mantienen demora de aparición, limpieza y watchdog; no se agregó fullscreen.

### Escáner y fotografías

- Se pausa únicamente el análisis suplementario cuando el documento está oculto.
  No se cambiaron resolución, consenso OCR, validación EAN, cámara compartida,
  zoom, sonido, vibración ni reglas de aceptación.
- Se auditó el fallback JPEG/Quagga (hasta 1920 px, calidad .92). **No se bajó su
  calidad ni frecuencia visible sin mediciones de detección con cámara real.**
- Las fotos de reportes ya se reducen a 1280 px/JPEG .8 con `toBlob`; se conserva
  esa calidad y el aislamiento de evidencias EAN. No se recomprime repetidamente.
- Se agregó plazo/cancelación a la descarga de fotos del relay para que una foto
  trabada no deje el sondeo detenido indefinidamente.

## C. Mediciones antes/después

Datos completos: `docs/measurements/performance-2026-10-15.json`.
Fixtures locales aisladas, Chrome de escritorio y PHP/SQLite. No representan
latencia de Render/Neon, redes móviles, tiempo de cámara ni Safari físico.

| Métrica y alcance | Antes | Después |
|---|---:|---:|
| Navegación SW con desconexión conocida y transporte bloqueado, harness | 8.079 ms | 43 ms |
| Palets conservados, fixture de 70 | 50 | 70 |
| Productos conservados, 100 por palet | 5.000 | 7.000 |
| Palets en instantánea de 2.454.572 caracteres | 0 | 70 |
| Inicialización de esquema instalado, llamadas SQLite | 4 | 1 |
| Inicialización instalada, mediana 30 muestras locales | 0,249 ms | 0,212 ms |
| GET detalle de palet, mediana 10 muestras HTTP | 37,59 ms | 36,47 ms |
| GET página Stock, mediana 10 muestras HTTP | 42,50 ms | 25,07 ms |
| GET API resumen Stock, mediana 10 muestras HTTP | 39,94 ms | 30,87 ms |
| Código móvil → campo PC, mediana 5 envíos locales | 1.831 ms | 311 ms |
| Detección física EAN/QR | No medida | No medida; regresión funcional aprobada |
| Foto móvil → PC, latencia | No medida | No medida; flujo funcional aprobado |
| Recuperación de dos operaciones de 84 unidades | Contrato existente | 2 confirmadas; replay sin duplicación |

Las cifras HTTP solo incluyen respuesta, no renderizado. No todas las pantallas
mejoraron en esa muestra: Biblioteca 35,54→38,85 ms, TOPs 34,59→41,13 ms y Ajustes
40,36→44,84 ms. Son muestras pequeñas con variación local; no se afirma una
aceleración general ni una capacidad de concurrencia de producción.

El primer ensayo del relay dio 1.913→316 ms; la repetición final dio 1.831→311 ms.
Ambos comparan el cliente anterior con el nuevo **sobre el mismo backend actual**.
Incluyen solicitudes de control de la fixture, envío, sondeo y actualización del
input; no incluyen reconocimiento de cámara. La mediana final baja aproximadamente
83%. No es una garantía de entrega menor a un segundo en internet.

## D. Tecnología PC–móvil

Se conserva HTTP autenticado y sondeo corto sobre el backend PHP actual. En uso
activo: PC conectado/listo consulta cada 400 ms después de terminar la petición;
móvil listo cada 500 ms. Espera de aceptación/inactividad tiene intervalos mayores;
segundo plano/desconexión 15 s, errores con backoff hasta 30 s. Volver al primer
plano o recuperar conexión despierta el sondeo.

No hay sondeos superpuestos dentro de la instancia. Una época de mutación evita
aplicar una respuesta GET anterior sobre un estado POST más nuevo. Peticiones y
fotos tienen plazo de 12 s y se cancelan al abandonar la página. No se agregaron
reintentos ciegos de POST.

Se mantiene flow/secuencia, consentimiento, misma cuenta, slots de sesión,
caducidad y ACK. El aviso en el móvil significa que el servidor aceptó el envío;
la PC muestra recepción después de ejecutar su callback. No son el mismo evento.

SSE/long polling mantendrían peticiones PHP ocupadas en esta arquitectura; usar
WebSocket exigiría servicio/proceso e infraestructura adicionales. No se cambiaron
sin una prueba de capacidad. El sondeo adaptativo aprovecha lo existente; aumenta
las consultas durante escaneo activo. Se debe medir consumo/concurrencia en Render
y Postgres antes de habilitar frecuencias todavía mayores.

## E. Pruebas y alcance

Ejecutadas con fixtures, nunca sobre mercadería real:

- `stock-preparation`: lotes completos, interrupción/reanudación, revisiones,
  upgrade IndexedDB3→4, cola existente y eliminación de lectura revocada.
- `offline-capacity`: 70/7.000, instantánea grande, migración de Biblioteca,
  render de 70 palets y despliegue de 100 productos de uno de ellos.
- `service-worker-offline`: cero peticiones de red en navegación conocida offline
  y recursos preparados; activación conserva caché OCR.
- `stock-api`, `stock-movement-proof`, `stock-sync-integrity`: altas online y
  offline, dos x84, QR inmediato, reinicio, respuesta perdida, replay, payload
  alterado, producto eliminado, 500/429, confirmación incompleta, permisos,
  invitaciones, reserva/reutilización, vencimientos y descuento offline rechazado.
- `offline-intelligent`: transporte/servidor/autenticación, lease entre pestañas,
  Biblioteca con respuesta perdida, conflictos, sesión vencida, privacidad,
  shell y OCR local preparados, palet desconocido.
- `device-connection-ui`, `device-report-photos`: recepción Inicio/Stock/Biblioteca,
  QR, foto EAN/producto, cancelación, slots, aislamiento, CSRF, fotos obsoletas.
- `ean-scanner`, `scanner-identification`: EAN8/13, checksum, cámara única, OCR WASM
  real local y tres fotogramas, duplicados y liberación de recursos.
- `navigation`, `loading`, `ux-polish`, `one-hand-workspaces`: limpieza AJAX,
  loaders, estados, móvil/PC, ambas manos y despeje de navegación.
- `schema-fastpath`, `stock-overview`, `library-sharing`, `reports-access`,
  `referrals`: esquema nuevo/antiguo, permisos, agregados, compartir, moderación y
  guardia de referidos en producción.

No ejecutadas: Safari/iPhone físico/PWA real, modo avión físico, cámara en depósito,
redes distintas, impresión, concurrencia PostgreSQL/Render y prueba sostenida de
memoria/batería. No se anuncian como aprobadas.

Reproducción: `node tests/offline-capacity.cjs`,
`node tests/stock-preparation.cjs`, `node tests/performance-relay.cjs` y
`C:/xampp/php/php.exe tests/performance-database.php`.
El benchmark relay admite como primer argumento el archivo cliente baseline;
el de páginas admite checkout y etiqueta (`node tests/performance-pages.cjs . after`).
Los JSON son evidencia de estas ejecuciones, no telemetría de usuarios.

## F. Archivos

Aplicación: `sw.js`, `js/connectivity.js`, `js/navigation.js`, `js/offline-cache.js`,
`js/offline-page.js`, `js/offline-sync.js`, `js/stock-offline.js`,
`js/stock-preparation.js`, `js/stock.js`, `js/device-connection.js`,
`js/ean-scanner.js`, `includes/database.php`, `includes/stock.php`, `api/stock.php`,
`api/scan-relay.php`, `vistas/footer.vista.php`, `css/stock.css`, `css/offline.css`.

Pruebas/mediciones: `tests/offline-capacity.cjs`, `tests/stock-preparation.cjs`,
`tests/service-worker-offline.cjs`, `tests/schema-fastpath.php`,
`tests/stock-api.cjs`, `tests/offline-intelligent.cjs`,
`tests/device-connection-ui.cjs`, `tests/performance-relay.cjs`,
`tests/performance-database.php`, `tests/performance-pages.cjs`.
Entrega: este informe, JSON de mediciones, `PENDIENTES.md`, `release.json`.

## G. Riesgos y reversión

- El navegador puede negar persistencia o agotar/expulsar almacenamiento. Se
  solicita persistencia cuando está disponible; no se puede garantizar offline
  antes de completar preparación, sin almacenamiento o tras desinstalar la PWA.
- Las fotos vencidas no se entregan; su eliminación física es oportunista en
  mutaciones, hasta 20 por solicitud. Sin actividad no hay una tarea programada
  independiente que elimine datos justo al segundo de caducidad.
- La vista local conserva el TTL existente de siete días. Una revocación remota
  no puede conocerse mientras el dispositivo está desconectado; el servidor
  siempre revalida al sincronizar.
- El manifiesto es completo y los lotes limitan palets, no productos: un palet
  excepcionalmente enorme aún puede necesitar paginación de sus detalles.
  Listas grandes offline se despliegan bajo demanda, sin virtualizador adicional.
- La biblioteca offline conserva su presentación actual; colecciones enormes
  todavía pueden requerir optimización de DOM. No se promete uso ilimitado.
- El escáner conserva fallback JPEG de calidad alta. Optimizar más requiere
  perfiles y pruebas de detección reales, especialmente Safari y barras lejanas.
- Sin conexión no se puede leer información nunca preparada, aprobar reportes,
  administrar accesos ni descontar stock con validación inmediata.
- Al migrar IndexedDB, una pestaña antigua puede bloquear el upgrade: cerrar
  pestañas antiguas y reabrir, **sin borrar datos del sitio ni cargas pendientes**.
- Revertir el commit publicado restaura la implementación, pero debe conservarse
  compatibilidad con IndexedDB versión4. No volver a abrirla como versión3 ni
  borrar stores/colas. Publicar un build superior para renovar recursos del SW.

## H. Resumen para probar

La copia offline dejaba fuera palets por límites locales y preparación incompleta;
ahora se prepara en segundo plano y muestra cuánto quedó listo. La navegación con
desconexión conocida usa los recursos guardados y reconectar no cambia de pantalla.
El enlace PC–celular consulta más rápido durante el uso activo, con menos trabajo
innecesario en reposo. Sus pruebas locales bajaron de unos 1,8 s a 0,31 s.

Prueba recomendada: entrar a Stock, esperar copia lista, activar modo avión,
consultar varios palets, agregar una carga, reconectar y verificar el recibo y la
cantidad desde otro equipo. Luego probar QR/EAN y fotos entre PC y un iPhone real.
No borrar datos locales para probar: podrían contener operaciones recuperables.
