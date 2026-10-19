# Rendimiento del escáner — 1.45

Entrega de instrumentación; no reduce las confirmaciones EAN ni modifica los perfiles de reconocimiento.

## Qué mide

- Apertura: pedido de cámara hasta primer fotograma disponible, o finalización de apertura cuando el navegador no informa ese evento.
- Lectura: cámara lista hasta código confirmado, con la página visible. Incluye apuntar y enfocar; no equivale al costo puro del decodificador. En modo rápido cada ciclo empieza cuando el lector se rearma.
- Confirmación EAN: desde el inicio de la ventana de coincidencias del código que finalmente se confirma. Conserva las tres coincidencias, los intervalos y el dígito de control.
- QR directo confirma al decodificar (confirmación adicional cero). Para OCR no se informa un tiempo de confirmación separado que no podamos medir.
- Tiempo total registrado: suma de apertura, si hubo una nueva apertura, y lectura activa. No incluye la consulta del catálogo ni la presentación completa del resultado.
- Cierre sin lectura y error de cámara quedan registrados por separado. Si no llegó a abrirse la cámara, se conserva la duración del intento.

No son tiempos de imágenes que sabemos legibles, ni un benchmark físico controlado. Sirven para observación y comparación de uso real. No incluye entrada manual ni lector externo sin cámara. Los datos son informados por el navegador y no deben usarse como auditoría de seguridad, premios o facturación.

## Captura y entrega

El motor emite un identificador de sesión y un ciclo, sin emitir el código leído a la telemetría. El consumidor registra tipo QR/EAN, pantalla, origen de lectura, modo rápido, versión, navegador/dispositivo en categorías generales y resolución disponible. No almacena EAN, números de palet, imágenes ni agente de usuario completo.

La escritura local y el envío se difieren fuera del callback de confirmación. Cola independiente por cuenta de hasta 100 registros; lotes de hasta 20, idempotentes por UUID. Se vacía al enviar, reintenta fallos de red/servidor y nunca bloquea una carga de mercadería. No es una cola de movimientos de stock: si alcanza el límite conserva los últimos registros.

La página offline usa la identidad y versión de su copia preparada, sin guardar CSRF. Envía cuando la misma cuenta vuelve a una página autenticada con token vigente. El servidor valida que la cuenta remitente coincida con la sesión. Las copias antiguas sin metadatos de medición no inventan una versión.

Las pausas con pestaña oculta se descuentan de lectura y confirmación. Los intentos de más de diez minutos se omiten del registro diagnóstico.

## Sistema para managers

Moderación incorpora Sistema, con filtros por 24 horas / 7 / 30 días, lector, versión, navegador y método. Muestra promedio y número de lecturas por tipo/versión, apertura y confirmación, cierres sin lectura, errores y registros recibidos. Los promedios de lectura usan solo confirmados; SQL ignora valores nulos en los otros promedios. Fecha mostrada: recepción del registro en servidor, en horario de Buenos Aires.

La paginación muestra 30 registros por página, acotada a 201 páginas por filtro. Los agregados abarcan todos los registros del período, no solo la página. Se puede actualizar y se refresca al sincronizar la cola local.

Solo un manager ve la sección. GET comprueba rango actual en base de datos, incluso si cambió después del inicio de sesión. Usuarios autenticados pueden enviar sus propios registros con CSRF, campos acotados, tamaño máximo, validación de consistencia y límite por minuto.

El aviso de tiempo se muestra solo a managers, es pasivo, dura 1,5 segundos y se actualiza sin formar una cola de mensajes durante escaneo rápido. No intercepta controles. No otorga permisos de consulta: el control de acceso sigue en el servidor.

## Esquema y versión

Migración 33 crea scanner_metrics e índice por fecha/tipo/versión. REPOTOOLS_SCHEMA_VERSION se actualiza a 33, incluyendo la aplicación pendiente de la migración 32 en instalaciones existentes. No altera palets ni sus productos. Versión pública 1.45, versión interna/caché 50.

DDL e inserción/agregado se probaron en PostgreSQL en una rama temporal con vencimiento automático; muestra artificial de tres tiempos 100/200/300 ms produjo promedio 200 ms. No son datos de escaneo reales. La API y la actualización desde esquema 31 también se probaron con SQLite temporal.

## Validación

- scanner-performance-ui.cjs: ciclo QR rápido/EAN/cierre/error, tiempos independientes, aviso único pasivo, idempotencia, promedio esperado, datos inválidos, CSRF y aislamiento de cuenta, manager degradado rechazado y UI oculta, filtros y layout 320/390/1366 con ambas manos.
- scanner-performance-offline.cjs: captura offline, pausas ocultas, cola por usuario, ausencia de códigos escaneados y envío con token fresco.
- scanner-metrics-migration.php: esquema 31 a 32/33 preservando alias y segunda apertura sin repetir DDL.
- ean-scanner, scanner-scheduling, shared-camera-ui, main-rapid-scanner: validación/confirmación/coordinar cámara/continuidad rápida.
- global-toast-ui, app-release y service-worker-offline: compatibilidad de avisos, incremento de versión y cachés.

Las cámaras de estas pruebas son simuladas o usan fixtures. Safari/iPhone y Android físicos siguen pendientes: esta entrega permite empezar a recolectar esas mediciones, no acredita una mejora de velocidad.

## Reversión

Desactivar la inclusión de scanner-performance y Sistema, y retirar los eventos adicionales del motor y el aviso con clave. Mantener registros, tablas y migraciones ya aplicadas; no borrar datos ni bajar la versión de esquema. Conservar la migración 32 de enlaces. Publicar una versión posterior al revertir para actualizar los recursos offline.
