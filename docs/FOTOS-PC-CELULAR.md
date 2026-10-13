# Fotos de reportes desde el celular — 13/10/2026

La PC recibe la captura de EAN del escaneo remoto y la conserva en memoria hasta cinco minutos, vinculada a ese código. Al abrir su reporte, se consume una vez. Desde el reporte en PC se puede solicitar otra foto de EAN o del producto: el teléfono muestra la solicitud, abre la cámara de reportes al tocar Tomar foto y conserva guía de EAN/flash según hardware. La PC no abre su cámara. Cancelar conserva la foto anterior; cerrar el reporte, cambiar de petición o desconectar invalida la captura remota.

El celular debe estar conectado con la misma cuenta y en Inicio, Stock, Biblioteca o Moderación, donde está disponible el formulario/cámara de reportes. Desde otras secciones se indica abrir Inicio. Los permisos para reportar y la configuración del almacenamiento final se conservan.

Migración 23 añade scan_relay_photos y un índice de vencimiento. Solo guarda una foto JPEG temporal por cuenta, como máximo 1 MB/1280 px, ligada a flujo/secuencia. Se borra al confirmar recepción, cancelar, reemplazar petición o desconectar; caduca a los cinco minutos y los registros caducados se purgan en las próximas consultas al relay. No se envían imágenes en el polling: se descargan por un endpoint privado, con sesión vigente y vinculación de ambos dispositivos. El almacenamiento final usa el envío de reportes existente; fotos descartadas no entran al catálogo.

Se ajustó únicamente la entrega asíncrona de la foto del escáner para que pueda consumirse incluso antes de finalizar toBlob. No cambió la detección, confirmación, orientación, zoom ni validación EAN/QR.

Validación: device-report-photos, device-connection-ui, ean-scan-photo, ean-scanner y scan-photo-orientation pasan; PHP lint y JS syntax correctos. Pruebas con SQLite temporal/Chrome y cámara simulada: reutilización, fotos de ambos tipos, guía/flash, aislamiento entre cuentas/dispositivos, archivos inválidos, solicitudes obsoletas, limpieza al confirmar, cancelar/cerrar. DDL/índice de migración 23 probado en rama Neon solo-esquema, con cero usuarios; expira automáticamente el 14/10/2026. No se modificó producción. Falta prueba de cámara real en iPhone/Android y recorrido HTTP completo contra PostgreSQL.

La suite antigua reports-api falla al volver a iniciar sesión administrativa sin resolver el cupo de dispositivo; ocurrió después de sus comprobaciones de tickets/CSRF/privacidad y no se alteró la autenticación ni esa suite para ocultarlo.

Reversión: restaurar API scan-relay, database.php, device-connection, product-reports, ean-scanner y cache versions de footer desde ef6dffc; retirar los nuevos endpoints/helpers. Mantener la migración 23 aplicada y su tabla: no borrar versiones ni datos de reportes. La versión anterior ignora la tabla temporal.
